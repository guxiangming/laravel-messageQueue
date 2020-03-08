<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidatorException;
use App\Http\ModuleClass\HelperClass;
use App\Libraries\Base\MessageHelper;
use App\Libraries\Base\SearchHelper;
use App\Model\hlyun_message_message_receivers as MessageReceivers;
use App\Model\hlyun_message_receiver_groups as ReceiverGroups;
use App\Model\hlyun_message_sms_logs;
use App\Model\hlyun_message_template_categories as TemplateCategories;
use App\Model\hlyun_message_templates as Templates;
use App\Model\hlyun_message_messages as Messages;
use App\Model\hlyun_message_configures as configures;
use App\Model\hlyun_message_bases as Bases;
use App\Model\hlyun_message_index;

use Illuminate\Http\Request;
use App\Http\Requests\MessageRequest;
use App\Jobs\MessageJobs;
use App\Model\hlyun_message_message_receivers;
use Illuminate\Swoole\WebSocket\Message as WSMessage;
use iscms\Alisms\SendsmsPusher;

use App\Model\hlyun_message_receiverfields as Fields;
use Carbon\Carbon;
use Doctrine\DBAL\Schema\Index;
use Tars\App as Tapp;
use Swoole\Coroutine as co;

class MessageController extends Controller
{
    protected $messageHelper;
    protected $searchHelper;
    protected $template; //消息模板
    public $chan;
    public function __construct(MessageHelper $messageHelper, SearchHelper $searchHelper)
    {
        $this->messageHelper = $messageHelper;
        $this->searchHelper = $searchHelper;
        if(extension_loaded('phptars')&&extension_loaded('swoole')&&env('tarsQueue',true)){
            $this->chan=new co\Channel(1);
        }
    }


    public function http(Request $request)
    {
        try {
            return $this->preSend($request->templateName, $request->input('content'));
        } catch (\Exception $e) {
            report($e);
            return ['code' => 600, '消息发送失败'];
        }
    }



    public function mq_preSend($body)
    {
        try {
            dump($body);
            return $this->preSend($body['templateName'], $body['content']);
        } catch (\Exception $e) {
            dump($e);
            report($e);
        }
    }

    //czm 
    public function messageQueue(MessageRequest $request)
    {   
        if(isset($request['LogData'])&&!empty($request['LogData'])){
            //日志处理
            return app('App\Http\Controllers\SendController')->Log($request['LogData']);
        }

        if(!isset($request['callName'])&&!$request['callName']&&!isset($request['index'])&&!$request['index']){
            
            return ['code'=>600,'data'=>['Index与callName不能为空']];
        }

         if(extension_loaded('phptars')&&extension_loaded('swoole')&&env('tarsQueue',true)){
        
            $data=$request->toArray();
            $chan=$this->chan;
            co::create(function () use ($chan,$data) {
                    $chan->push($data);

            });
            co::create(function () use ($chan) {
                while(1) {
                    $data = $chan->pop();
                    \Swoole\Timer::after(5000, function() use ($data) {
                        $intance=new \App\Jobs\MessageJobs($data);
                        $intance->handle();
                    });
                }
            });

             return  ['code' => 0, 'data' => '消息已推送至队列'];
            // \swoole_event::wait();
            // $tasks=Tapp::getSwooleInstance();
            // $tasks->task(new \App\Tars\task\messageQueueTask(json_encode($request->toArray())));
           
        }
      
        if(isset($request['dispatchNow'])&&$request['dispatchNow']==true){
            //立即推送不需要延迟同步操作
            MessageJobs::dispatchNow($request->toArray());
            // $intance=new \App\Jobs\MessageJobs($request->toArray());
            // $intance->handle();
        }else {
            //延迟队列针对事务场景
            MessageJobs::dispatch($request->toArray())->delay(Carbon::now()->addSeconds(5));
        }
        return  ['code' => 0, 'data' => '消息已推送至队列'];
    }

    

    
}
