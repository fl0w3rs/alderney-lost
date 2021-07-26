<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger('log');
$log->pushHandler(new StreamHandler(config['base_dir'] . '/logs/log.txt', Logger::WARNING));
