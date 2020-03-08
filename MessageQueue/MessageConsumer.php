<?php
/**
 * Created by PhpStorm.
 * User: czm
 * Date: 2019/2/22
 * Time: 11:21
 */

namespace App\MessageQueue;
use Illuminate\Support\ServiceProvider;
use RocketMQ\PushConsumer;
use RocketMQ\MessageListenerType;
use RocketMQ\ConsumeFromWhere;
use RocketMQ\MessageModel;
use App\Model\hlyun_message_erroraccept;

class MessageConsumer  extends ServiceProvider
{
    public function register()
    {
         //加载配置
         $this->loadConfig();
         //是否允许加载消费端
         if(extension_loaded('rocketmq')&&extension_loaded('phptars')&&config('messageQueue.consumerStatus')){
            $pid=@file_get_contents(base_path().'/process.pid');
            if($pid&&\swoole_process::kill($pid,0)){
                \swoole_process::kill($pid,SIGTERM);
            }
            //启用swoole 进程管理 
            //@缺陷 2019-6-12 无法托管到tars主进程
            $consumerProcess = new \swoole_process(function (\swoole_process $process) {
            
                $consumer = new PushConsumer(config('messageQueue.pushConsumer'));  //消费者
                $consumer->setInstanceName(config('messageQueue.pushConsumer'));    //消费者实例名
                $consumer->setNamesrvAddr(config('messageQueue.mqNameServerAddr'));//服务地址 
                $consumer->setThreadCount(1);     //消费线程数量
                $consumer->setListenerType(MessageListenerType::LISTENER_ORDERLY); //监听对象
                $consumer->setConsumeFromWhere(ConsumeFromWhere::CONSUME_FROM_FIRST_OFFSET); //
                $consumer->setMessageModel(MessageModel::CLUSTERING); //BROADCASTING设置消息类型 广播 CLUSTERING = 1;
                $count = 0;
                // if thread > 1 & use echo method will core dump.
                $consumer->setCallback(function ($msgExt) use (&$count){
                    try{
    
                        $msg = $msgExt->getMessage();  
                        if($msgExt->getReconsumeTimes() > 3){
                            throw new \Exception("msgID: {$msgExt->getMsgId()}, topic: {$msg->getTopic()}, tags: {$msg->getTags()} body: {$msg->getBody()} Reconsume 3 times");
                        }
    
                        $body=json_decode($msg->getBody(),true);
                            // $result=app('App\Http\Controllers\MessageController')->test($body);
                        if(!empty($body)){
                            $result=app('App\Http\Controllers\SendController')->sendMessage($body);

                            if( isset($result['code'])&&$result['code']==0 ){
                                return \RocketMQ\ConsumeStatus::CONSUME_SUCCESS;
                            }else{
                                return \RocketMQ\ConsumeStatus::RECONSUME_LATER;
                            }
                        }else{
                            throw new \Exception("msgID: {$msgExt->getMsgId()}, topic: {$msg->getTopic()}, tags: {$msg->getTags()} body: 数据解密为空");
                        }
                        
                    }catch(\Throwable $t){
                        //错误日志记录
                        report($t);
                        $content = $t->getMessage();
                        hlyun_message_erroraccept::create(['Content'=>$content]);
                        //消费成功上报
                        return \RocketMQ\ConsumeStatus::CONSUME_SUCCESS;
                    }
                    
                    file_put_contents(__DIR__.'/m.txt',json_encode($msg->getBody()),FILE_APPEND);
                });
    
                $configures=\DB::table('hlyun_message_configures')->select('Tag','Topic')->where('Status',1)->get()->toArray();
                // file_put_contents(__DIR__.'/ms.txt',json_encode($configures));
                // $consumer->subscribe('SEA','*');
                foreach ($configures as $key => $value) {
    
                    $consumer->subscribe($value->Topic,$value->Tag);
                }
                $consumer->start();
    
    
            }, false ,false);
            $consumerProcess->name("MessageConsumer");
            $pid=$consumerProcess->start();
            file_put_contents(base_path().'/process.pid',$pid);
        }
    }

    protected function loadConfig()
    {
        $config=require __DIR__ .'/Config/messageQueue.php';
        foreach ($config as $key => $value) {
           config(['messageQueue.'.$key=>$value]);
        }
    }
}   