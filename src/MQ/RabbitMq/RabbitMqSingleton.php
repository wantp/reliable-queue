<?php

namespace Wantp\ReliableQueue\MQ\RabbitMq;


use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Wantp\ReliableQueue\Exceptions\ConnectException;
use Wantp\ReliableQueue\Exceptions\InstanceException;

class RabbitMqSingleton
{
    /**
     * @var RabbitMqSingleton
     */
    private static $instance = null;

    /**
     * @var AMQPStreamConnection
     */
    private $connection = null;

    /**
     * @var AMQPChannel
     */
    private $channel = null;

    /**
     * RabbitMqSingleton constructor.
     * @param $host
     * @param $port
     * @param $username
     * @param $password
     * @param $vhost
     * @throws \Exception
     */
    private function __construct($host, $port, $username, $password, $vhost = '/')
    {
        $this->connect($host, $port, $username, $password, $vhost);
    }

    /**
     * Notes:__clone
     * @author  zhangrongwang
     * @date 2019-03-08 12:00:23
     */
    private function __clone()
    {

    }

    /**
     * Notes:__sleep
     * @author  zhangrongwang
     * @date 2019-03-08 12:00:08
     * @throws InstanceException
     */
    public function __sleep()
    {
        throw new InstanceException('实例化失败');
    }

    /**
     * Notes:__wakeup
     * @author  zhangrongwang
     * @date 2019-03-08 12:00:12
     * @throws InstanceException
     */
    public function __wakeup()
    {
        throw new InstanceException('实例化失败');
    }

    /**
     * Notes:connect
     * @author  zhangrongwang
     * @date 2019-03-08 12:00:32
     * @param $host
     * @param $port
     * @param $username
     * @param $password
     * @param $vhost
     * @throws \Exception
     */
    private function connect($host, $port, $username, $password, $vhost)
    {
        $this->connection = new AMQPStreamConnection($host, $port, $username, $password, $vhost);
        if (is_null($this->connection) || !$this->connection) {
            throw new ConnectException('RabbitMQ 连接失败');
        }
        $this->channel = $this->connection->channel();
    }

    /**
     * Notes:getInstance
     * @author  zhangrongwang
     * @date 2019-03-08 12:52:41
     * @param string $host
     * @param int $port
     * @param string $username
     * @param string $password
     * @param string $vhost
     * @throws \Exception
     * @return self
     */
    public static function getInstance(string $host, int $port, string $username, string $password, string $vhost = '/')
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self($host, $port, $username, $password, $vhost);
        }
        return self::$instance;
    }

    /**
     * Notes:getConnection
     * @author  zhangrongwang
     * @date 2019-03-08 12:01:09
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Notes:getChannel
     * @author  zhangrongwang
     * @date 2019-03-08 12:01:14
     */
    public function getChannel()
    {
        return $this->channel;
    }
}