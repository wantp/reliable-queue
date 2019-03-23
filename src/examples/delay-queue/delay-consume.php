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

$reliableQueue->consume('_handler');

function _handler(\PhpAmqpLib\Message\AMQPMessage $msg)
{
    $data = json_decode($msg->getBody(), true);
    if (((int)$data['index']) % 2 == 0) {
        return true;
    }
    return false;
}