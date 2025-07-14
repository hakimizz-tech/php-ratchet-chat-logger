hakeem@HP:~/programs/chatlogger$ php app/bin/server.php
PHP Warning:  PHP Startup: Unable to load dynamic library 'pdo_mysql' (tried: /usr/lib/php/20230831/pdo_mysql (/usr/lib/php/20230831/pdo_mysql: cannot open shared object file: No such file or directory), /usr/lib/php/20230831/pdo_mysql.so (/usr/lib/php/20230831/pdo_mysql.so: undefined symbol: pdo_parse_params)) in Unknown on line 0
PHP Fatal error:  Uncaught Dotenv\Exception\InvalidPathException: Unable to read any of the environment file(s) at [/home/hakeem/programs/chatlogger/app/.env]. in /home/hakeem/programs/chatlogger/app/vendor/vlucas/phpdotenv/src/Store/FileStore.php:68
Stack trace:
#0 /home/hakeem/programs/chatlogger/app/vendor/vlucas/phpdotenv/src/Dotenv.php(222): Dotenv\Store\FileStore->read()
#1 /home/hakeem/programs/chatlogger/app/config.php(5): Dotenv\Dotenv->load()
#2 /home/hakeem/programs/chatlogger/app/bin/server.php(11): require_once('...')
#3 {main}
  thrown in /home/hakeem/programs/chatlogger/app/vendor/vlucas/phpdotenv/src/Store/FileStore.php on line 68
