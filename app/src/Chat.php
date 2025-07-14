<?php

namespace ChatApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use ChatApp\Auth\AuthService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $pdo;
    protected $authService;
    protected $onlineUsers; // Maps resourceId to user data

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->onlineUsers = [];

        if (!extension_loaded('pdo_mysql')) {
            echo "Error: The pdo_mysql extension is not enabled in your php.ini file.\n";
            return;
        }

        require_once dirname(__DIR__) . '/database.php';
        $this->pdo = get_db_connection();
        $this->authService = new AuthService($this->pdo);
        echo "Chat server started...\n";
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        $type = $data['type'] ?? '';

        switch ($type) {
            case 'signup':
                $this->handleSignup($from, $data);
                break;
            case 'request_login':
                $this->handleRequestLogin($from, $data);
                break;
            case 'verify_login':
                $this->handleVerifyLogin($from, $data);
                break;
            case 'auth':
                $this->handleAuth($from, $data);
                break;
            case 'message':
                $this->handleMessage($from, $data);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        if (isset($this->onlineUsers[$conn->resourceId])) {
            unset($this->onlineUsers[$conn->resourceId]);
            $this->broadcastUserList();
        }
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred on connection {$conn->resourceId}: {$e->getMessage()}\n";
        if (isset($this->onlineUsers[$conn->resourceId])) {
            unset($this->onlineUsers[$conn->resourceId]);
            $this->broadcastUserList();
        }
        $conn->close();
    }

    private function handleSignup(ConnectionInterface $from, array $data)
    {
        $this->authService->signup($data['firstname'] ?? '', $data['lastname'] ?? '', $data['email'] ?? '');
        $from->send(json_encode(['type' => 'login_request_sent']));
    }

    private function handleRequestLogin(ConnectionInterface $from, array $data)
    {
        $this->authService->requestLogin($data['email'] ?? '');
        $from->send(json_encode(['type' => 'login_request_sent']));
    }

    private function handleVerifyLogin(ConnectionInterface $from, array $data)
    {
        $jwt = $this->authService->verifyLoginToken($data['email'] ?? '', $data['otp'] ?? '');
        if ($jwt) {
            $from->send(json_encode(['type' => 'login_success', 'token' => $jwt]));
        } else {
            $from->send(json_encode(['type' => 'login_failed']));
        }
    }

    private function handleAuth(ConnectionInterface $from, array $data)
    {
        $token = $data['token'] ?? null;
        if (!$token) return;

        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            $userId = $decoded->data->userId;
            $user = $this->getUserById($userId);

            if ($user) {
                $this->onlineUsers[$from->resourceId] = [
                    'id' => $user['id'],
                    'name' => $user['firstname'] . ' ' . $user['lastname']
                ];
                $this->broadcastUserList();
                $this->sendPastMessages($from, $userId);
            }
        } catch (\Exception $e) {
            // Auth failed, close connection
            $from->close();
        }
    }

    private function handleMessage(ConnectionInterface $from, array $data)
    {
        if (!isset($this->onlineUsers[$from->resourceId])) return;

        $sender = $this->onlineUsers[$from->resourceId];
        $messageText = htmlspecialchars($data['message'] ?? '');

        if (empty($messageText)) return;

        $this->addMessageToDb($sender['id'], $messageText);

        $response = [
            'type' => 'message',
            'sender_name' => $sender['name'],
            'message' => $messageText,
            'timestamp' => date('H:i')
        ];

        $this->broadcast(json_encode($response));
    }

    private function broadcastUserList()
    {
        $userList = array_values($this->onlineUsers);
        $response = ['type' => 'user_list_update', 'users' => $userList];
        $this->broadcast(json_encode($response));
    }

    private function sendPastMessages(ConnectionInterface $conn, int $userId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.firstname, u.lastname, m.message, m.created_at 
             FROM messages m 
             JOIN users u ON m.user_id = u.id 
             ORDER BY m.created_at ASC LIMIT 50"
        );
        $stmt->execute();
        $messages = $stmt->fetchAll();

        foreach ($messages as $msg) {
            $response = [
                'type' => 'message',
                'sender_name' => $msg['firstname'] . ' ' . $msg['lastname'],
                'message' => $msg['message'],
                'timestamp' => date('H:i', strtotime($msg['created_at']))
            ];
            $conn->send(json_encode($response));
        }
    }

    private function broadcast($message)
    {
        foreach ($this->clients as $client) {
            if ($client->resourceId && isset($this->onlineUsers[$client->resourceId])) {
                try {
                    $client->send($message);
                } catch (\Exception $e) {
                    // Could be a broken pipe, ignore and let onClose handle it
                }
            }
        }
    }

    private function addMessageToDb(int $userId, string $message)
    {
        $stmt = $this->pdo->prepare("INSERT INTO messages (user_id, message) VALUES (?, ?)");
        $stmt->execute([$userId, $message]);
    }

    private function getUserById(int $userId)
    {
        $stmt = $this->pdo->prepare("SELECT id, firstname, lastname FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
}