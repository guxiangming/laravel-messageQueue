<?php
/**
 * Created by PhpStorm.
 * User: czm
 * Date: 2019/2/22
 * Time: 11:21
 */

namespace App\MessageQueue;
use Illuminate\Support\ServiceProvider;


class MessageServiceProvider  extends ServiceProvider
{
    //使用rocketmq 引擎 或者 curl
    public function register()
    {
       
        $this->app->singleton('MessageProducer',function(){
            //初始化所有配置
            $this->mergeConfig();
            $producer = new MessageProducer();   
            return $producer;    
        });

        //案例调用使用门面
        // \MessageQueue::send([
        //    'TemplateCallName'=>'system.account.passwordchange',//匹配消息模板 必填
        //    'LogData'=>$logsData,//日志表需要的字段数据---写日志的时候需要带此键
        //    'LogMessage'=>true,//需要发送消息---写日志的时候需要带此键
        //    'Content'=>[],//消息模板解析变量 必填
        //    'SenderInfo'=>[//选填 不填默认标记为系统自产
        //        'SenderId'=>0,
        //        'CellPhone'=>'',
        //        'AccreditId'=>'',
        //    ],
        //    'ReceiverInfo'=>[//选填 可根据模板二次匹配定向发送对象
        //        'SsoToken'=>"",
        //        'Email'=>[],
        //        'CellPhone'=>[],
        //        'UserID'=>[],//用户ID
        //        'OpenID'=>[],
        //    ],
        //    'Navigate'=>[//选填 进入下一个节点的导航
        //        'title'=>'查看详情',
        //        'url'=>"http://网址/路由/参数",
        //    ]
        // );
    }

    /**
     * Merge configurations.
     *
     * @return void
     */
    protected function mergeConfig()
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/messageQueue.php', 'messageQueue');
    }
}