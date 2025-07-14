<?php

// src/Chat.php

namespace ChatApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $pdo;

    public function __construct() {
        $this->clients = new \SplObjectStorage;

        if (!extension_loaded('pdo_mysql')) {
            echo "Error: The pdo_mysql extension is not enabled in your php.ini file. The server cannot connect to the database and will not start.\n";
            echo "Please enable it and try again.\n";
            return;
        }

        // Establish DB connection when the chat server starts
        require_once dirname(__DIR__) . '/database.php';
        $this->pdo = get_db_connection();
        echo "Chat server started...\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        $username = $data['username'] ?? 'Anonymous';
        $messageText = $data['message'] ?? '';

        // Add user to DB if they don't exist
        $this->addUserToDb($username);
        // Add message to DB
        $this->addMessageToDb($username, $messageText);

        $response = [
            'type' => 'message',
            'username' => $username,
            'message' => $messageText,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        // Broadcast the message to all connected clients
        foreach ($this->clients as $client) {
            $client->send(json_encode($response));
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    // --- Database Helper Functions ---

    private function addUserToDb($username) {
        $stmt = $this->pdo->prepare("INSERT INTO users (username, is_connected) VALUES (?, 1) ON DUPLICATE KEY UPDATE is_connected = 1");
        $stmt->execute([$username]);
    }

    private function addMessageToDb($username, $message) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $userId = $stmt->fetchColumn();

        if ($userId) {
            $stmt = $this->pdo->prepare("INSERT INTO messages (user_id, message) VALUES (?, ?)");
            $stmt->execute([$userId, $message]);
        }
    }
}