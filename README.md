# reliable-queue
reliable queue for php

### 简述
- PHP版本 >=7.0.0
- 注意module的命名，避免不同业务使用相同的module导致意外的错误
- 暂时只支持rabbitMq
- 实现可靠队列，简单的配置即可实现自动重试，无法再次重试时进入失败队列，使用方法可参考examples中reliable-queue的内容
- 实现延迟队列，推送消息到延迟队列，到达延迟时间后消息进入常规队列，使用方法可参考examples中delay-queue的内容
- 应用场景
  - 发送通知时，接收方返回失败，根据设置的不同重试时间间隔，到达时间后自动重试
  - 某种业务逻辑处理失败概率较高但重试成功概率较高时

### 使用方法

#### 引用包
```
composer require wantp/reliable-queue
```

##### 使用

>重试的说明
```
$retryInterval = [1, 20, 60, 120]; //表示在上一次失败后的1、20、60、120秒后重试
$retryInterval = []; //表示不重试
```

>失败的说明
```
当不能再重试时，失败消息会进入失败队列，等待失败处理程序或人工介入处理
```

>怎么重试
```
//当处理失败时,调用方法
ReliableQueue::publishRetryMsg($msg);
//程序会根据是否可以重试推送到重试或者失败队列里,此方法会返回FLAG,帮助用户可以根据不同阶段做不同处理
```

##### 可靠队列示例

###### 推送消息到常规队列
>publish.php

```
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
```

###### 消费常规队列
>consume.php

```
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
```

