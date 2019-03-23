<?php

namespace Wantp\ReliableQueue\MQ\RabbitMq;


use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Wantp\ReliableQueue\Exceptions\ValidateException;
use Wantp\ReliableQueue\Interfaces\ReliableQueueInterface;

class RabbitMqReliableQueue implements ReliableQueueInterface
{
    /**
     * @var RabbitMqSingleton
     */
    private $rabbitMq;

    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var string
     */
    private $module;

    /**
     * @var string
     */
    private $commonExchange;

    /**
     * @var string
     */
    private $normalQueue;

    /**
     * @var string
     */
    private $failQueue;

    /**
     * @var int
     */
    private $retryInterval;

    /**
     * @var int
     */
    private $maxRetryTimes;

    /**
     * DelayQueue constructor.
     * @param array $config ['host'=>'127.0.0.1','port'=>25536,'username'=>'test','password'=>'test'=>'/test']
     * @param string $module
     * @param array $retryInterval 重试时间间隔 [60,120] 表示:60秒后重试，重试失败后120秒后再重试。 [] 表示不会重试
     * @throws \Exception
     */
    public function __construct(array $config, string $module, array $retryInterval = [])
    {
        $config = $this->paramsValidate($config);
        $this->connect($config['host'], $config['port'], $config['username'], $config['password'], $config['vhost']);
        $this->module = $module;
        $this->exchangeDeclare();
        $this->bindNormalQueue();
        $this->bindFailQueue();
        foreach ($retryInterval as $index => $interval) {
            $this->bindDelayQueue($this->getDelayQueueName($interval), $interval);
        }
        $this->retryInterval = $retryInterval;
        $this->maxRetryTimes = count($this->retryInterval);
    }

    /**
     * Notes:paramsValidate
     * @author  zhangrongwang
     * @date 2019-03-12 10:56:46
     * @param array $config
     * @throws ValidateException
     * @return array
     */
    private function paramsValidate(array $config)
    {
        if (empty($config)) {
            throw new ValidateException('rabbit配置不能为空');
        }
        $requiredConfig = ['host', 'port', 'username', 'password'];
        foreach ($requiredConfig as $item) {
            if (!isset($config[$item])) {
                throw new ValidateException('rabbitMq 参数' . $item . '必填');
            }
        }
        $config['port'] = (int)$config['port'];
        $config['vhost'] = $config['vhost'] ?? '/';

        return $config;
    }

    /**
     * Notes:connect
     * @author  zhangrongwang
     * @date 2019-03-12 10:56:40
     * @param string $host
     * @param int $port
     * @param string $username
     * @param string $password
     * @param string $vhost
     * @throws \Exception
     */
    private function connect(string $host, int $port, string $username, string $password, string $vhost = '/')
    {
        $this->rabbitMq = RabbitMqSingleton::getInstance($host, $port, $username, $password, $vhost);
        $this->connection = $this->rabbitMq->getConnection();
        $this->channel = $this->rabbitMq->getChannel();
    }

    /**
     * Notes:exchangeDeclare
     * @author  zhangrongwang
     * @date 2019-03-12 10:56:34
     */
    private function exchangeDeclare()
    {
        $this->commonExchange = $this->module . '_exchange';
        $this->channel->exchange_declare($this->commonExchange, 'direct', false, true, false);
    }

    /**
     * Notes:bindNormalQueue
     * @author  zhangrongwang
     * @date 2019-03-12 10:56:30
     */
    private function bindNormalQueue()
    {
        $this->normalQueue = $this->module . '_normal_queue';
        $this->channel->queue_declare($this->normalQueue, false, true, false, false, false);
        $this->channel->queue_bind($this->normalQueue, $this->commonExchange, self::NORMAL_ROUTING_KEY);
    }

    /**
     * Notes:bindFailQueue
     * @author  zhangrongwang
     * @date 2019-03-12 10:56:18
     */
    private function bindFailQueue()
    {
        $this->failQueue = $this->module . '_fail_queue';
        $this->channel->queue_declare($this->failQueue, false, true, false, false, false);
        $this->channel->queue_bind($this->failQueue, $this->commonExchange, self::FAIL_ROUTING_KEY);
    }

    /**
     * Notes:bindDelayQueue
     * @author  zhangrongwang
     * @date 2019-03-12 10:56:11
     * @param string $delayQueue
     * @param int $interval
     */
    private function bindDelayQueue(string $delayQueue, int $interval)
    {
        $this->channel->queue_declare($delayQueue, false, true, false, false, false, new AMQPTable([
            'x-dead-letter-exchange' => $this->commonExchange,
            'x-dead-letter-routing-key' => self::NORMAL_ROUTING_KEY,
            'x-message-ttl' => $interval * 1000,
        ]));
        $this->channel->queue_bind($delayQueue, $this->commonExchange, $this->getDelayRoutingKeyName($interval));
    }

    /**
     * Notes:getDelayQueueName
     * @author  zhangrongwang
     * @date 2019-03-12 10:56:00
     * @param $interval
     * @return string
     */
    private function getDelayQueueName($interval)
    {
        return $this->module . '_delay_' . $interval;
    }

    /**
     * Notes:getDelayRoutingKeyName
     * @author  zhangrongwang
     * @date 2019-03-12 10:55:50
     * @param $interval
     * @return string
     */
    private function getDelayRoutingKeyName($interval)
    {
        return 'delay-routing-key-' . $interval;
    }

    /**
     * Notes:publishNormalMsg
     * @author  zhangrongwang
     * @date 2019-03-12 10:55:46
     * @param array $data
     */
    public function publishNormalMsg(array $data)
    {
        $msg = new AMQPMessage(json_encode($data), [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'content_encoding' => 'UTF-8',
                'content_type' => 'text/plain'
            ]
        );
        $this->channel->basic_publish($msg, $this->commonExchange, self::NORMAL_ROUTING_KEY);
    }

    /**
     * Notes:publishRetryMsg
     * @author  zhangrongwang
     * @date 2019-03-12 10:55:36
     * @param AMQPMessage $msg
     * @return int
     */
    public function publishRetryMsg(AMQPMessage $msg): int
    {
        if ($this->maxRetryTimes < 1) {
            //没有设置重试则推送到失败队列
            $this->publishFailMsg($msg);
            return self::PUBLISH_FAIL_MSG_FLAG;
        }
        $retryTimes = $this->getRetryTimes($msg);
        if ($retryTimes >= $this->maxRetryTimes) {
            //超过最大重试次数推送到失败队列
            $this->publishFailMsg($msg);
            return self::PUBLISH_FAIL_MSG_FLAG;
        }
        if ($this->maxRetryTimes > 0) {
            //设置了重试推送到重试队列
            $routing_key = $this->getDelayRoutingKeyName($this->retryInterval[$retryTimes]);
            $this->channel->basic_publish($msg, $this->commonExchange, $routing_key);
            return self::PUBLISH_RETRY_MSG_FLAG;
        }
    }

    /**
     * Notes:publishFailMsg
     * @author  zhangrongwang
     * @date 2019-03-12 10:55:28
     * @param AMQPMessage $msg
     */
    public function publishFailMsg(AMQPMessage $msg)
    {
        $this->channel->basic_publish($msg, $this->commonExchange, self::FAIL_ROUTING_KEY);
    }

    /**
     * Notes:publishDelayMsg
     * @author  zhangrongwang
     * @date 2019-03-12 11:26:20
     * @param array $data
     * @param int $ttl
     */
    public function publishDelayMsg(array $data, int $ttl)
    {
        $delayQueue = $this->getDelayQueueName($ttl);
        $this->bindDelayQueue($delayQueue, $ttl);
        $msg = new AMQPMessage(json_encode($data), [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'content_encoding' => 'UTF-8',
                'content_type' => 'text/plain'
            ]
        );
        $routing_key = $this->getDelayRoutingKeyName($ttl);
        $this->channel->basic_publish($msg, $this->commonExchange, $routing_key);
    }

    /**
     * Notes:getRetryTimes 获取重试次数
     * @author  zhangrongwang
     * @date 2019-03-12 10:55:09
     * @param AMQPMessage $msg
     * @return int
     */
    public function getRetryTimes(AMQPMessage $msg): int
    {
        $retry = 0;
        if ($msg->has('application_headers')) {
            $headers = $msg->get('application_headers')->getNativeData();
            if (isset($headers['x-death']) && is_array($headers['x-death'])) {
                foreach ($headers['x-death'] as $x_death) {
                    $retry += (int)$x_death['count'];
                }
            }
        }
        return (int)$retry;
    }

    /**
     * Notes:getNormalQueue
     * @author  zhangrongwang
     * @date 2019-03-12 10:55:03
     */
    public function getNormalQueue(): string
    {
        return $this->normalQueue;
    }

    /**
     * Notes:getFailQueue
     * @author  zhangrongwang
     * @date 2019-03-12 10:54:49
     */
    public function getFailQueue(): string
    {
        return $this->failQueue;
    }

    /**
     * Notes:getMaxRetryTimes
     * @author  zhangrongwang
     * @date 2019-03-12 10:54:53
     */
    public function getMaxRetryTimes(): int
    {
        return $this->maxRetryTimes;
    }

    /**
     * Notes:getChannel
     * @author  zhangrongwang
     * @date 2019-03-12 10:54:57
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Notes:consume
     * @author  zhangrongwang
     * @date 2019-03-23 16:56:11
     * @param array|callable $handler
     * @param array|callable|null $failHandler
     * @throws \ErrorException
     */
    public function consume($handler, $failHandler = null)
    {
        $callback = function ($msg) use ($handler, $failHandler) {
            if (is_callable($handler)) {
                $res = $handler($msg);
            } else {
                $res = call_user_func($handler, $msg);
            }
            if (!$res) {
                if (self::PUBLISH_FAIL_MSG_FLAG == $this->publishRetryMsg($msg) && !is_null($failHandler)) {
                    $failHandler($msg);
                }
            }
            //ACK
            $this->channel->basic_ack($msg->delivery_info['delivery_tag']);
        };

        $this->channel->basic_qos(null, 1, null);

        $this->channel->basic_consume($this->getNormalQueue(), '', false, false, false, false, $callback);
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
        $this->channel->close();
    }
}