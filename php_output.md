 php bin/server.php
PHP Warning:  PHP Startup: Unable to load dynamic library 'pdo_mysql' (tried: /usr/lib/php/20230831/pdo_mysql (/usr/lib/php/20230831/pdo_mysql: cannot open shared object file: No such file or directory), /usr/lib/php/20230831/pdo_mysql.so (/usr/lib/php/20230831/pdo_mysql.so: undefined symbol: pdo_parse_params)) in Unknown on line 0
// bin/server.php

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use ChatApp\Chat;

require dirname(__DIR__) . '/vendor/autoload.php';

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8081
);

$server->run();