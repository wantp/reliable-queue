<?php

require_once 'vendor/autoload.php';

class consumeClass
{

    public function consume()
    {
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

        $reliableQueue->consume(array($this, '_handler'), array($this, '_failHandler'));
    }

    /**
     * Notes:_handler 队列消费处理函数 必须是public
     * @author  zhangrongwang
     * @date 2019-03-23 17:52:05
     * @param \PhpAmqpLib\Message\AMQPMessage $msg
     * @return bool
     */
    public function _handler(\PhpAmqpLib\Message\AMQPMessage $msg): bool
    {
        $data = json_decode($msg->getBody(), true);
        if (((int)$data['index']) % 2 == 0) {
            return true;
        }
        return false;
    }

    /**
     * Notes:_failHandler 失败处理函数  必须是public
     * @author  zhangrongwang
     * @date 2019-03-23 17:51:47
     * @param \PhpAmqpLib\Message\AMQPMessage $msg
     */
    public function _failHandler(\PhpAmqpLib\Message\AMQPMessage $msg)
    {
        $data = json_decode($msg->getBody(), true);
        logFail($data);
    }
}

$obj = new consumeClass();
$obj->consume();
