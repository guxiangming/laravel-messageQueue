<?php
namespace App\MessageQueue;
use RocketMQ\Producer;

class CurlProducer{

    public function __construct()
    {
        
    }
    /**
     * @Description: 
     * @Param: json $data 
     * @Author: czm
     * @return: json
     * @Date: 2019-06-06 17:00:54
     */
    public function send($data){

        $ch = curl_init();
        curl_setopt_array($ch,[
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=UTF-8'],
            CURLOPT_TIMEOUT=>15,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_URL => config('messageQueue.messageUrlApi'),
        ]);

        $result   = curl_exec($ch);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return $result;
    }

}
