# laravel messageQueue

- extend: swoole rocketMQ laravel-event laravel-observer laravel-provider

## MessageQueue服务提供者
 - 消息生产者

    MessageProducer消息推送客户端(mqGroupName实例名称、topic主题、tag标签)
    
 - 消息消费者


    MessageConsumer消息消费者
    $consumer = new PushConsumer(config('messageQueue.pushConsumer'));  //消费者
    $consumer->setInstanceName(config('messageQueue.pushConsumer'));    //消费者实例名
    $consumer->setNamesrvAddr(config('messageQueue.mqNameServerAddr'));//服务地址 
    $consumer->setThreadCount(1);     //消费线程数量
    $consumer->setListenerType(MessageListenerType::LISTENER_ORDERLY); //监听对象
    $consumer->setConsumeFromWhere(ConsumeFromWhere::CONSUME_FROM_FIRST_OFFSET); //
    $consumer->setMessageModel(MessageModel::CLUSTERING); //BROADCASTING设置消息类型 广播 CLUSTERING = 1;

 ## 消息模板
- 消息分类
- 调用名称
- 消息类型(日志、通知)
- 消息发送方式(短信、站内信、微信、邮箱)
- 消息标题
- 消息体
- 消息事务

 ## 消息转发
- 事件注册
- 事件监听
- 事件观察者

 ## 消息数据库解析

 - 字典管理
 - 事件分类
 - 事件索引
 - 索引连表配置