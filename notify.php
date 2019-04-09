<?php

require_once 'vendor/autoload.php';
require_once 'app/config/config.php';

Logger::configure(__DIR__.'/log4php.xml');
//获取日志类
$logger = Logger::getLogger('task1');
//写入日志
$logger->info(json_encode($_POST));
 app\base::websiteNotiy();

