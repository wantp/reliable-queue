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

$reliableQueue->consume('_handler','_failHandler');

function _handler(\PhpAmqpLib\Message\AMQPMessage $msg)
{
    //消费队列时会调用此方法,处理成功必须返回true
    $data = json_decode($msg->getBody(), true);
    if (((int)$data['index']) % 2 == 0) {
        return true;
    }
    return false;
}

function _failHandler(\PhpAmqpLib\Message\AMQPMessage $msg)
{
    //进入失败队列后会调用此方法
    logFail();
}
```

##### examples中提供了一些使用示例，以供参考

