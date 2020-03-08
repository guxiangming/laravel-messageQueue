<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidatorException;
use App\Http\ModuleClass\HelperClass;
use App\Model\hlyun_message_message_receivers as MessageReceivers;
use App\Model\hlyun_message_template_categories as TemplateCategories;
use App\Model\hlyun_message_templates as Templates;
use App\Model\hlyun_message_messages as Messages;
use App\Model\hlyun_message_template_user_sendway;



class ResolvingController extends Controller
{

    protected $template; //消息模板

    public function __construct()
    {

    }

    /**
     * @Descripttion: 消息解析器
     * @param 
     * @Date: 2019-12-26 10:59:27
     * @Author: czm
     * @return: mixed
     */
    public function resolving($request)
    {
       
        //获取模板
        $this->setTemplate($request['callName']);
        //解析模板内容
        $template = $this->resolvingTemplate();
        //查询api解析数据
        $data = $this->resolvingApi($request['index'], $template);
        //绑定值合并
        $info = $this->bindData($data) + $data;
        //记录数据表
        $category = TemplateCategories::select('ID', 'Pid', 'Name')->get()->keyBy('ID')->toArray(); //获取分类
        $categoryId = $this->template->CategoryId;
        $categoryName = function ($category, $categoryId) {
            $tree = [];
            array_push($tree, $category[$categoryId]['Name']);
            $Pid = $category[$categoryId]['Pid'];
            while ($Pid) {
                array_push($tree, $category[$Pid]['Name']);
                $Pid = $category[$Pid]['Pid'];
            }
            return implode("->", array_reverse($tree));
        };
        $categoryName = $categoryName($category, $categoryId);
        $message = Messages::create([
            'TemplateId' => $this->template->ID,
            'TemplateCallName' => $request['callName'],
            'NavigateTitle' => $request['navigateTitle'] ?? '',
            'NavigaeUrl' => $request['navigateUrl'] ?? '',
            'Category' => $categoryName, 
            'CategoryId' => $this->template->CategoryId, 
            'SenderId' => $this->getSender(), 
            'Title' => $info['title'],
             'Content' => $info['content'],
        ]);
        //推送站内信
        $this->pushMsg($message, $info, $template['sendWays']);
        return ['code' => 0, 'data' => '消息流程已推送结束'];
    }

    public function setTemplate($callName)
    {
        $this->template = Templates::where('CallName', $callName)->first();
        if (empty($this->template)) {
            throw new ValidatorException("[$callName]模板为空 请检测数据合法性");
        }
    }

    public function resolvingTemplate()
    {
        $indexEventId = TemplateCategories::where('ID', $this->template['CategoryId'])->value('EventId');
        if(empty($indexEventId)) throw new ValidatorException("解析分类绑定事件索引为空 请该模板分类[EventId]字段");
        $sendWays = $this->template->activeSendWay;
        $title = $this->template->activeTitle;
        $receiver = $this->template->activeReceivers;
        $content = $this->template->activeContent;
        return compact('indexEventId', 'sendWays', 'title', 'receiver', 'content');
    }

    public function resolvingApi($index, $info)
    {
      
        $params['index'] = $index;
        $params['indexEventId'] = $info['indexEventId'];
        $params['dictionaryId'] = [
            'receiver' => $info['receiver'],
            // 'receiver'=>[3],
            'title' => $info['title'],
            'content' => $info['content'],
        ];
        $curlRequest = ['method' => 'POST', 'params' => $params, 'route' => '/api/template/content'];
        $result = HelperClass::curl($curlRequest);
        $result = json_decode($result, true);
        if (isset($result['code']) && $result['code'] == 0) {
            if (empty($result['receiver'])) {
                throw new ValidatorException($this->template->CallName.'模板[receiver]API解析消息接收者为空');
            }
            $receiver = $result['receiver'] ?? '';
            $content = $result['content'] ?? '';
            $title = $result['title'] ?? '';
            return compact('receiver', 'content', 'title');
        } else {
            throw new ValidatorException($result['data'] ?? $this->template->CallName.'模板解析错误');
        }
    }

    public function bindData($data)
    {
        //
        if (!empty($data['title'])) {
            foreach ($data['title'] as $key => $val) {
                if (is_array($val)) $val = implode(',', $val);
                $title = str_replace("@@{$key}@@", $val, $this->template->Title);
            }
        } else {
            $title = $this->template->Title;
        }
       

        if (!empty($data['content'])) {
            $content= $this->template->Content;
            foreach ($data['content'] as $key => $val) {
                if (is_array($val)) $val = implode(',', $val);
                $content = str_replace("@@{$key}@@", $val,$content);
            }
        } else {
            $content = $this->template->Content;
        }
        return compact('title', 'content');
    }

    /**
     * @Descripttion: 推送消息 czm
     * @param {type} 
     * @Date: 2019-12-27 17:25:22
     * @Author: czm
     * @return: 
     */
    public function pushMsg($message, $info, $sendWays)
    {

        foreach ($info['receiver'] as $k => $val) {
            //获取对应模板用户自定义发送方式
            $userSendWay = hlyun_message_template_user_sendway::where('UserId', $k)
                ->where('TemplateId', $this->template->ID)
                ->first();
            if (!empty($userSendWay)) {
                $sendWays['SendMail'] = $userSendWay['SendMail'];
                $sendWays['SendWechat'] = $userSendWay['SendWechat'];
                $sendWays['SendSMS'] = $userSendWay['SendSMS'];
            }

            //已监听model 
            MessageReceivers::create([
                'MessageId' => $message->ID,
                'ReceiverId' => $val['ID'],
                'PromptStatus' => $sendWays['SendPrompt'] ? 1 : 0,
                'MailStatus' => $sendWays['SendMail'] ? 1 : 0,
                'WechatStatus' => $sendWays['SendWechat'] ? 1 : 0,
                'SMSStatus' => $sendWays['SendSMS'] ? 1 : 0,
            ]);
        }
        return true;
    }


    //发送者解析 czm
    public function getSender()
    {
        $request = \Request::all();
        if (!isset($request['sender'])) {
            return 0;
        }
        return $request['sender'];
    }


    
}
