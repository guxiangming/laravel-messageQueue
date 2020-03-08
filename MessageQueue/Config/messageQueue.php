<?php
    return [
        'rockmqStatus'=>env('ROCKMQ_STATUS',false),//是否开启MQ通讯

        'consumerStatus'=>env('CONSUMER_STATUS',true),//是否开启MQ消费端

        'mqGroupName'=>env('MQ_GROUP_NAME','hly'),//实例名称

        'mqNameServerAddr'=>env('MQ_NAMESERVER_ADDR','127.0.0.1:9876'),//mq集群名称

        'messageUrlApi'=>env('MESSAGEURLAPI','http://localhost:7060/api/message/messageCurl'),//curl模式的Api

        'topic'=>env('MQ_PRODUCER_TOPIC',''),//主题
        
        'tag'=>env('MQ_PRODUCER_TAG','*'),//标签

        'pushConsumer'=>env('PUSH_CONSUMER','hly_pushConsumer'),//消费者实例


       
    ];