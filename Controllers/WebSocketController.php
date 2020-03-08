<?php

namespace App\Http\Controllers;


use App\Libraries\Base\MessageHelper;
use App\Model\hlyun_message_message_receivers;
use App\Model\hlyun_message_messages;
use Illuminate\Support\Facades\Redis;
use Illuminate\Swoole\Facades\WebSocket;
use Illuminate\Swoole\WebSocket\Message;


class WebSocketController extends Controller
{

    //初始化查询条件
    protected $selectMap = [];

    protected $messageHelper;


    public function __construct(MessageHelper $messageHelper)
    {
        $this->messageHelper = $messageHelper;
    }

    /**
     * socket连接监听
     * czm
     * 2018/8/20
     * websocket push userOnline-note
     * @param Message $message
     */
    public function connected(Message $message)
    {
        $socketId = $message->getSocketId();
        // $userId   = $message->getData('UserId');
        // $accreditId = $message->getData('AccreditId');

        $ssoToken = $message->getData('sso_token');
        $ssoTokenDecrypted = decrypt($ssoToken);

        // $room     = WebSocket::getClientRoom($socketId);
        /*$uInfo    = $this->dealRediskey(
            $userId,
            $accreditId
        );*/
        $cuid = $this->messageHelper->getRawIdByPhone($ssoTokenDecrypted['Cellphone'],$ssoTokenDecrypted['AccreditId']);

        //记录websocket fd onlineuser
        Redis::hset('onlineFdUser', $socketId, $cuid);
        //用户反转记录 onlineFd
        if ( $fds = Redis::hget('onlineUserFd', $cuid) ) {
            //处理多开浏览器
            $fds = json_decode($fds, true);

            $fds[] = $socketId;

            $fds = array_unique($fds);
            $fds = json_encode($fds);

            Redis::hset('onlineUserFd', $cuid, $fds);
        } else {
            Redis::hset('onlineUserFd', $cuid, json_encode([$socketId]));
        }

        // 第一次返回五条数据
        // $request['AccreditId'] = $accreditId;
        // $request['UserId']     = $userId;

        $res = $this->initialList($cuid);

        WebSocket::emit($socketId, Message::make('connected', $res));
    }

    /*public function dealRediskey($userId, $AccreditId)
    {
        return json_encode([
            'UserId' => (int)$userId,
            'AccreditId' => (int)$AccreditId
        ], true);
    }*/


    /**
     * @param int $socketId
     * @return void
     */
    public static function destroy()
    {
        Redis::del('onlineUserFd');
        Redis::del('onlineFdUser');
    }


    public function initialList($cuid)
    {
        $sql = hlyun_message_messages::whereHas('receivers',function($query) use($cuid){
            return $query->own($cuid);
        });

        $messages = $sql->select('Title','Content','Link')->take('30')->get();
        $unreadCount = hlyun_message_message_receivers::own($cuid)->unread()->count();

        return compact('messages','unreadCount');
    }

}
