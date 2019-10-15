<?php

namespace joyhuang\Push\Services;

use Cassandra\Varint;
use joyhuang\Http\Http;
use joyhuang\Http\Request;
use joyhuang\Push\Exceptions\PushException;
use joyhuang\Push\Redis;

class VivoPush
{
    private $_accessToken;
    private $_appId;
    private $_appKey;
    private $_appSecret;
    private $_redis;
    private $_time;
    private $_request;
    private $_url     = "https://api-push.vivo.com.cn";
    private $_headers = [ 'content-type' => "application/json" ];

    /**
     * 构造函数。
     *
     * @param array $config
     */
    public function __construct( $config )
    {
        if ( !empty($config['vivo_app_id']) ) {
            $this->_appId = $config['vivo_app_id'];
        } else {
            throw new \Exception('Cannot found configuration:vivo_app_id!');
        }
        if ( !empty($config['vivo_app_key']) ) {
            $this->_appKey = $config['vivo_app_key'];
        } else {
            throw new \Exception('Cannot found configuration: vivo_app_key!');
        }
        if ( !empty($config['vivo_app_secret']) ) {
            $this->_appSecret = $config['vivo_app_secret'];
        } else {
            throw new \Exception('Cannot found configuration: vivo_app_secret');
        }
        $this->_redis   = new Redis($config['redis']);
        $this->_request = new Request();
        $this->_request->setHttpVersion(Http::HTTP_VERSION_1_1);

    }


    /**
     * 请求新的 Access Token。
     */
    private function _getAccessToken()
    {
        $this->_accessToken = $this->_redis->get("vivo:authToken:");
        if ( !$this->_accessToken ) {
            $this->_getTime();
            $sign = md5($this->_appId . $this->_appKey . $this->_time . $this->_appSecret);

            $data = [
                "appId"     => $this->_appId,
                "appKey"    => $this->_appKey,
                "timestamp" => $this->_time,
                "sign"      => $sign,
            ];

            $response = $this->_request->post($this->_url . '/message/auth', [
                'headers' => $this->_headers,
                'data'    => json_encode($data)
            ]);
            $res      = $response->getResponseArray();

            if ( $res['result'] != '0' ) {
                throw new \Exception($res['desc']);
            }
            $this->_accessToken = $res['authToken'];

            $this->_redis->setex("vivo:authToken:", 3600, $this->_accessToken);
        }
    }

    private function _getTime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $this->_time = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }

    /**
     * curlPost
     */
    private function curlPost( $url, $data, $headers )
    {

        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $url);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);//运行curl
        curl_close($ch);

        return $result;
    }

    /**
     * 发送vivo推送消息。单推
     * @param $title
     * @param $message
     * @param $extraData
     * @param $aData
     * @return Response
     * @throws \Exception
     */
    public function sendMessage( $title, $message, $extraData, $aData )
    {
        $this->_getAccessToken();
        $data = [
            //用户ID
            'regId'           => $extraData,
            "notifyType"      => '4',
            "title"           => $title,
            "content"         => $message,
            "skipType"        => "4",
            "skipContent"     => "intent://com.tiantong.push/notify_push?key=1#Intent;scheme=notifypush;launchFlags=0x10000000;component=com.yuyin.live.voice/com.yuyin.live.activity.HnNotifyPushActivity;end",
            "requestId"       => $this->_accessToken,
            //自定义参数
            "clientCustomMap" => $aData,
        ];

        $this->_headers['authToken'] = $this->_accessToken;

        $response = $this->_request->post($this->_url . '/message/send', [
            'headers' => $this->_headers,
            'data'    => json_encode($data)
        ]);
        return $response->getResponseArray();
    }


    /**
     * 多用户推送
     * @param $title
     * @param $message
     * @param $extraData
     * @param $aData
     * @return mixed
     * @throws PushException
     */
    public function sendBatchMessage( $title, $message, $extraData, $aData )
    {
        $listPayLoad = $this->_saveListPayload($title, $message, $aData);
        if ( !isset($listPayLoad) || $listPayLoad['taskId'] == '' ) {
            throw new PushException("未获取到taskId", 405);
        }
        $this->_getAccessToken();
        $data = [
            //用户ID
            "regIds"    => $extraData,
            "taskId"    => $listPayLoad['taskId'],
            "requestId" => $this->_accessToken,
        ];

        $this->_headers['authToken'] = $this->_accessToken;

        $response = $this->_request->post($this->_url . '/message/send', [
            'headers' => $this->_headers,
            'data'    => json_encode($data)
        ]);
        return $response->getResponseArray();


    }


    /**
     * 全量推送
     * 1天默认只有一次  如果超过限制 需要循环推送
     * 由于使用限制 用推送tag代替推送全局
     * @param $title
     * @param $message
     * @param $extraData
     * @param $aData
     * @return mixed
     * @throws \Exception
     */
    public function sendAllMessage( $title, $message, $extraData, $aData )
    {
        return $this->sendTagsMessage($title, $message, [$extraData], $aData);
//        $this->_getAccessToken();
//        $data = [
//            //用户ID
//            "notifyType"      => '4',
//            "title"           => $title,
//            "content"         => $message,
//            "skipType"        => "4",
//            "skipContent"     => "xxxx",
//            "requestId"       => $this->_accessToken,
//            //自定义参数
//            "clientCustomMap" => $aData,
//        ];
//
//        $this->_headers[] = "authToken:" . $this->_accessToken;
//
//        $res = $this->curlPost($this->_url . '/message/all', json_encode($data), $this->_headers);
//        return json_decode($res, 1);
    }

    /**
     * @param $title
     * @param $message
     * @param $extraData
     * @param $aData
     * @return mixed
     * @throws \Exception 发送tags消息
     */
    public function sendTagsMessage( $title, $message, $extraData, $aData )
    {
        $this->_getAccessToken();
        $data = [
            //用户ID
            "tagExpression"   => [
                'notTags' => ['tag1'],
                "andTags" => $extraData,
                'orTags'  => []
            ],
            "notifyType"      => 4,
            "title"           => $title,
            "content"         => $message,
            "timeToLive"         => 86400,
            "skipType"        => 4,
            "skipContent"     => "intent://com.tiantong.push/notify_push?key=1#Intent;scheme=notifypush;launchFlags=0x10000000;component=com.yuyin.live.voice/com.yuyin.live.activity.HnNotifyPushActivity;end",
            "requestId"       => $this->_accessToken,
            //自定义参数
            "clientCustomMap" => $aData,
        ];

        $this->_headers['authToken'] = $this->_accessToken;

        $response = $this->_request->post($this->_url . '/message/tagPush', [
            'headers' => $this->_headers,
            'data'    => json_encode($data)
        ]);
        return $response->getResponseArray();
    }


    /**
     * 保存群推消息公共体接口
     * @param $title
     * @param $message
     * @param $aData
     * @return mixed
     * @throws \Exception
     */
    private function _saveListPayload( $title, $message, $aData )
    {
        $this->_getAccessToken();
        $data = [
            //用户ID
            "title"           => $title,
            "content"         => $message,
            "notifyType"      => '4',
            "skipType"        => "4",
            "skipContent"     => "xxxx",
            "requestId"       => $this->_accessToken,
            //自定义参数
            "clientCustomMap" => $aData,
        ];

        $this->_headers['authToken'] = $this->_accessToken;

        $response = $this->_request->post($this->_url . '/message/saveListPayload', [
            'headers' => $this->_headers,
            'data'    => json_encode($data)
        ]);
        return $response->getResponseArray();
    }
}