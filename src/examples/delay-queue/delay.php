<?php

require_once 'vendor/autoload.php';


$rabbitMqConfig = [
    'host' => '192.168.56.10',
    'port' => 35672,
    'username' => 'test',
    'password' => 'test',
    'vhost' => '/test'
];

$module = 'wantp-delay';

$reliableQueue = new \Wantp\ReliableQueue\ReliableQueue($rabbitMqConfig, $module);


$data = ['index' => 1];
$reliableQueue->publishDelayMsg($data, 20);

$data = ['index' => 2];
$reliableQueue->publishDelayMsg($data, 60);
