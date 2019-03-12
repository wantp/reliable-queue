<?php

namespace Wantp\ReliableQueue\Interfaces;


use PhpAmqpLib\Message\AMQPMessage;


interface ReliableQueueInterface
{
    const NORMAL_ROUTING_KEY = 'normal-routing-key';
    const FAIL_ROUTING_KEY = 'fail-routing-key';
    const PUBLISH_NORMAL_MSG_FLAG = 1;
    const PUBLISH_RETRY_MSG_FLAG = 2;
    const PUBLISH_FAIL_MSG_FLAG = 3;

    /**
     * Notes:publishNormalMsg
     * @author  zhangrongwang
     * @date 2019-03-12 10:24:34
     * @param array $data
     */
    public function publishNormalMsg(array $data);

    /**
     * Notes:publishRetryMsg
     * @author  zhangrongwang
     * @date 2019-03-12 10:24:49
     * @param AMQPMessage $msg
     * @return int
     */
    public function publishRetryMsg(AMQPMessage $msg): int;

    /**
     * Notes:publishFailMsg
     * @author  zhangrongwang
     * @date 2019-03-12 10:25:16
     * @param AMQPMessage $msg
     */
    public function publishFailMsg(AMQPMessage $msg);

    /**
     * Notes:publishDelayMsg
     * @author  zhangrongwang
     * @date 2019-03-12 11:32:26
     * @param array $data
     * @param int $ttl
     */
    public function publishDelayMsg(array $data, int $ttl);

    /**
     * Notes:getRetryTimes
     * @author  zhangrongwang
     * @date 2019-03-12 10:25:31
     * @param AMQPMessage $msg
     * @return int
     */
    public function getRetryTimes(AMQPMessage $msg): int;

    /**
     * Notes:getNormalQueue
     * @author  zhangrongwang
     * @date 2019-03-12 10:28:56
     */
    public function getNormalQueue(): string;

    /**
     * Notes:getFailQueue
     * @author  zhangrongwang
     * @date 2019-03-11 19:51:21
     */
    public function getFailQueue(): string;

    /**
     * Notes:getMaxRetryTimes
     * @author  zhangrongwang
     * @date 2019-03-12 10:29:09
     */
    public function getMaxRetryTimes(): int;

}