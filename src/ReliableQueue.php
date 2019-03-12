<?php

namespace Wantp\ReliableQueue;


use PhpAmqpLib\Message\AMQPMessage;
use Wantp\ReliableQueue\Exceptions\InstanceException;
use Wantp\ReliableQueue\Exceptions\ValidateException;
use Wantp\ReliableQueue\Interfaces\ReliableQueueInterface;
use Wantp\ReliableQueue\MQ\RabbitMq\RabbitMqReliableQueue;

class ReliableQueue implements ReliableQueueInterface
{

    /**
     * @var array
     */
    private $config;

    /**
     * @var string
     */
    private $module;

    /**
     * @var int
     */
    private $retryInterval;

    /**
     * @var RabbitMqReliableQueue
     */
    private $mq;


    /**
     * DelayQueue constructor.
     * @param array $config ['server'=>'rabbit','host'=>'127.0.0.1','port'=>25536,'username'=>'test','password'=>'test'=>'/test']
     * @param string $module
     * @param array $retryInterval 重试时间间隔 [60,120] 表示:60秒后重试，重试失败后120秒后再重试。 [] 表示不会重试
     * @throws \Exception
     */
    public function __construct(array $config, string $module, array $retryInterval = [])
    {
        list($module, $retryInterval) = $this->paramsValidate($module, $retryInterval);
        $this->config = $config;
        $this->module = $module;
        $this->retryInterval = $retryInterval;
        $this->mq = $this->getMqIns();
    }

    /**
     * Notes:getMqIns
     * @author  zhangrongwang
     * @date 2019-03-12 10:36:46
     * @throws InstanceException|\Exception
     * @return RabbitMqReliableQueue
     */
    private function getMqIns()
    {
        $mqServer = $this->config['server'] ?? 'rabbit';
        $mq = null;
        if (is_string($mqServer)) {
            switch ($mqServer) {
                case 'rabbit':
                    $mq = new RabbitMqReliableQueue($this->config, $this->module, $this->retryInterval);
                    break;
                default:
                    break;
            }
        }
        if ($mq instanceof ReliableQueueInterface) {
            return $mq;
        }
        throw new InstanceException('A reliableQueue instance must instanceof ReliableQueueInterface');
    }

    /**
     * Notes:paramsValidate
     * @author  zhangrongwang
     * @date 2019-3-12 10:41:26
     * @param $module
     * @param $retryInterval
     * @throws ValidateException
     * @return array
     */
    private function paramsValidate($module, $retryInterval)
    {
        $module = trim($module);
        if (empty($module)) {
            throw new ValidateException('模块名称不能为空');
        }

        foreach ($retryInterval as $index => $interval) {
            if (!is_int($interval)) {
                throw new ValidateException('时间间隔必须是整数');
            }
        }

        return [$module, $retryInterval];
    }

    /**
     * Notes:getMq
     * @author  zhangrongwang
     * @date 2019-03-12 10:52:25
     */
    public function getMq()
    {
        return $this->mq;
    }

    /**
     * Notes:publishNormalMsg
     * @author  zhangrongwang
     * @date 2019-03-12 13:29:14
     * @param array $data
     */
    public function publishNormalMsg(array $data)
    {
        $this->mq->publishNormalMsg($data);
    }

    /**
     * Notes:publishRetryMsg
     * @author  zhangrongwang
     * @date 2019-03-12 13:32:43
     * @param AMQPMessage $msg
     * @return int
     */
    public function publishRetryMsg(AMQPMessage $msg): int
    {
        return $this->mq->publishRetryMsg($msg);
    }

    /**
     * Notes:publishFailMsg
     * @author  zhangrongwang
     * @date 2019-03-12 13:32:39
     * @param AMQPMessage $msg
     */
    public function publishFailMsg(AMQPMessage $msg)
    {
        $this->mq->publishFailMsg($msg);
    }

    /**
     * Notes:publishDelayMsg
     * @author  zhangrongwang
     * @date 2019-03-12 13:32:34
     * @param array $data
     * @param int $ttl
     */
    public function publishDelayMsg(array $data, int $ttl)
    {
        $this->mq->publishDelayMsg($data, $ttl);
    }

    /**
     * Notes:getRetryTimes
     * @author  zhangrongwang
     * @date 2019-03-12 13:32:24
     * @param AMQPMessage $msg
     * @return int
     */
    public function getRetryTimes(AMQPMessage $msg): int
    {
        return $this->mq->getRetryTimes($msg);
    }

    /**
     * Notes:getNormalQueue
     * @author  zhangrongwang
     * @date 2019-03-12 13:32:19
     */
    public function getNormalQueue(): string
    {
        return $this->mq->getNormalQueue();
    }

    /**
     * Notes:getFailQueue
     * @author  zhangrongwang
     * @date 2019-03-12 13:32:16
     */
    public function getFailQueue(): string
    {
        return $this->mq->getFailQueue();
    }

    /**
     * Notes:getMaxRetryTimes
     * @author  zhangrongwang
     * @date 2019-03-12 13:32:12
     */
    public function getMaxRetryTimes(): int
    {
        return $this->mq->getMaxRetryTimes();
    }

}