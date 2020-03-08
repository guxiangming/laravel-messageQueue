<?php
namespace App\MessageQueue;
use RocketMQ\Producer;

class MessageProducer{

    public $producer;
    /**
     * @Description: 选择消息通讯方式 分curl 和 rockmq两种驱动
     * @Param: 
     * @Author: czm
     * @return: 
     * @Date: 2019-06-11 11:50:12
     */
    public function __construct()
    {   
        if(extension_loaded('rocketmq')&&config('messageQueue.rockmqStatus')){
            $producer = new Producer(config('messageQueue.mqGroupName'));
            $producer->setNamesrvAddr(config('messageQueue.mqNameServerAddr'));
            $producer->start();
            $this->producer=$producer;
        }else{
            $this->producer=new CurlProducer();
        }  
    }


    /**
     * @Description: 上报消息
     * @Param:array $data 采集的消息 string $topic 消息队列主题,string $tag,
     * @Author: czm
     * @return: array
     * @Date: 2019-06-06 17:00:54
     */
    public function send(array $data,string $topic=null,string $tag=null){

        if(!$topic){
            $topic=config('messageQueue.topic');
        }

        if(!$tag){
            $tag=config('messageQueue.tag');
        }
        $data['topic']=$topic;
        $data=json_encode($data,JSON_UNESCAPED_UNICODE);
        if(extension_loaded('rocketmq')&&config('messageQueue.rockmqStatus')){
            
            $message=new \RocketMQ\Message($topic,$tag,$data);
            $sendResult=$this->producer->send($message);
            // mq状态  SEND_OK = 0;SEND_FLUSH_DISK_TIMEOUT = 1;SEND_FLUSH_SLAVE_TIMEOUT = 2;SEND_SLAVE_NOT_AVAILABLE = 3;          
            return ['type'=>'rocketmq','sendResult'=>$sendResult->getSendStatus() === \RocketMQ\SendStatus::SEND_OK ];
        }else{
            $sendResult=json_decode($this->producer->send($data), true);
//            dd($sendResult);
            if(isset($sendResult['code'])&&$sendResult['code']==0){
                $sendResult=true;
            }else{
                $sendResult=false;
            }
            return ['type'=>'curl','sendResult'=>$sendResult];
        }
    }
}
