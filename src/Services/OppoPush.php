<?php

namespace joyhuang\Push\Services;

use joyhuang\Http\Http;
use joyhuang\Http\Request;

class OppoPush
{
    private $_accessToken;
    private $_masterSecret;
    private $_appKey;
    private $_time;
    private $_request;
    private $_redis;
    private $_url = "https://api.push.oppomobile.com/";

    /**
     * 构造函数。
     *
     * @param array $config
     * @throws \Exception
     */
    public function __construct( $config )
    {

        if ( !empty($config['oppo_app_key']) ) {
            $this->_appKey = $config['oppo_app_key'];
        } else {
            throw new \Exception('Cannot found configuration: oppo_app_key!');
        }
        if ( !empty($config['oppo_master_secret']) ) {
            $this->_masterSecret = $config['oppo_master_secret'];
        } else {
            throw new \Exception('Cannot found configuration: oppo_master_secret!');
        }
        $this->_redis   = new \joyhuang\Push\Redis($config['redis']);
        $this->_request = new Request();
        $this->_request->setHttpVersion(Http::HTTP_VERSION_1_1);

    }

    /**
     * 请求新的 Access Token。
     */
    private function _getAccessToken()
    {
        $this->_accessToken = $this->_redis->get("oppo:authToken:");
        if ( !$this->_accessToken ) {
            $this->_getTime();
            $sign = hash('sha256', $this->_appKey . $this->_time . $this->_masterSecret);
//        $sign = md5($this->_appKey . $this->_time . $this->_masterSecret);

            $data['app_key']   = $this->_appKey;
            $data['timestamp'] = $this->_time;
            $data['sign']      = $sign;
//

            $response = $this->_request->post(sprintf('%s%s', $this->_url, 'server/v1/auth'), [
                'headers' => [
                    'content-type' => 'application/x-www-form-urlencoded'
                ],
                'data'    => $data
            ]);
            $res      = $response->getResponseArray();

            if ( $res['code'] != '0' ) {
                throw new \Exception($res['desc']);
            }
            $this->_accessToken = $res['data']['auth_token'];
            $this->_redis->set('oppo:authToken:', $this->_accessToken);
            $this->_redis->expire('oppo:authToken:', 3600);
        }
    }

    private function _getTime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $this->_time = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }


    /**
     * 发送OPPO推送消息。
     * @param $deviceToken
     * @param $title
     * @param $message
     * @return Response
     * @throws
     */
    public function sendMessage1( $title, $message, $alias, $aData )
    {
        $this->_getAccessToken();

        $notification['title']             = $title;
        $notification['content']           = $message;
        $notification['action_parameters'] = json_encode($aData);

        $notification['click_action_type']     = 5;
        $notification['click_action_activity'] = 'intent://com.tiantong.push/notify_push?key=1#Intent;scheme=notifypush;launchFlags=0x10000000;component=com.yuyin.live.voice/com.yuyin.live.activity.HnNotifyPushActivity;end';

        $pushMessage['target_type']  = 2;
        $pushMessage['target_value'] = $alias;
        $pushMessage['notification'] = $notification;

        $data['auth_token'] = $this->_accessToken;
        $data['message']    = json_encode($pushMessage);

        $url = $this->_url . 'server/v1/message/notification/unicast';

        $response = $this->_request->post($url, [
            'headers' => [
                'authToken'    => $this->_accessToken,
                'content-type' => 'application/x-www-form-urlencoded'
            ],
            'data'    => $data
        ]);
        $res      = $response->getResponseArray();
        return $res;

    }


    /**
     * 批量推送
     * @param $title
     * @param $message
     * @param $extraData
     * @param $aData
     */
    public function sendBatchMessage( $title, $message, $extraData, $aData )
    {

    }

    /**
     * 全量推送
     * @param $title
     * @param $message
     * @param $extraData
     * @param $aData
     */
    public function sendAllMessage( $title, $message, $extraData, $aData )
    {
        $messageId           = $this->_getMessageId($title, $message, $extraData, $aData);
        $data['auth_token']  = $this->_accessToken;
        $data['message_id']  = $messageId;
        $data['target_type'] = 1;
        $url                 = $this->_url . 'server/v1/message/notification/broadcast';

        $response = $this->_request->post($url, [
            'headers' => [
                'authToken'    => $this->_accessToken,
                'content-type' => 'application/x-www-form-urlencoded'
            ],
            'data'    => $data
        ]);
        $res      = $response->getResponseArray();
        return $res;
    }

    /**
     * 根据tag推送
     * @param $title
     * @param $message
     * @param $extraData
     * @param $aData
     */
    public function sendTagsMessage( $title, $message, $extraData, $aData )
    {
        return '暂时没有这个功能';
    }


    private function _getMessageId( $title, $message, $extraData, $aData )
    {
        $this->_getAccessToken();

        $data['title']             = $title;
        $data['content']           = $message;
        $data['action_parameters'] = json_encode($aData);

        $data['click_action_type']     = 5;
        $data['click_action_activity'] = 'intent://com.tiantong.push/notify_push?key=1#Intent;scheme=notifypush;launchFlags=0x10000000;component=com.yuyin.live.voice/com.yuyin.live.activity.HnNotifyPushActivity;end';

        $data['auth_token'] = $this->_accessToken;

        $url = $this->_url . 'server/v1/message/notification/save_message_content';

        $response = $this->_request->post($url, [
            'headers' => [
                'authToken'    => $this->_accessToken,
                'content-type' => 'application/x-www-form-urlencoded'
            ],
            'data'    => $data
        ]);
        $res      = $response->getResponseArray();
        if ( !isset($res['data']['message_id']) ) {
            throw new \Exception('message_id 生成失败');
        }
        return $res['data']['message_id'];
    }
}