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

//消费回调函数
$callback = function ($msg) use ($reliableQueue) {
    if (!_handler($msg)) {
        //处理失败推送到重试队列
        $reliableQueue->publishRetryMsg($msg);
    }
    //ACK
    $channel = $reliableQueue->getMq()->getChannel();
    $channel->basic_ack($msg->delivery_info['delivery_tag']);
};

$channel = $reliableQueue->getMq()->getChannel();

//消费消息
$channel->basic_consume($reliableQueue->getNormalQueue(), '', false, false, false, false, $callback);
//柱塞消费
while (count($channel->callbacks)) {
    $channel->wait();
}
//关闭channel
$channel->close();

function _handler(\PhpAmqpLib\Message\AMQPMessage $msg)
{
    $data = json_decode($msg->getBody(), true);
    if (((int)$data['index']) % 2 == 0) {
        return true;
    }
    return false;
}