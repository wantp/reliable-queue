<?php

require_once 'vendor/autoload.php';


$rabbitMqConfig = [
    'host' => '192.168.56.10',
    'port' => 35672,
    'username' => 'test',
    'password' => 'test',
    'vhost' => '/test'
];

$module = 'wantp-reliable';

$retryInterval = [10, 20, 60, 120];

$reliableQueue = new \Wantp\ReliableQueue\ReliableQueue($rabbitMqConfig, $module, $retryInterval);

for ($i = 0; $i < 10; $i++) {
    $data = ['index' => $i];
    $reliableQueue->publishNormalMsg($data);
}