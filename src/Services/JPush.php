<?php

namespace joyhuang\Push\Services;


use joyhuang\Http\Http;
use joyhuang\Http\Request;

class JPush
{
    private $_appKey;
    private $_masterSecret;
    private $_request;
    private $_client;
    private $_app_env;


    /**
     * MiPush constructor.
     *
     * @param null $config
     * @throws \Exception
     */
    public function __construct( $config )
    {
        if ( !empty($config['jpush_app_key']) ) {
            $this->_appKey = $config['jpush_app_key'];
        } else {
            throw new \Exception('Cannot found configuration: jiguang_app_key!');
        }

        if ( !empty($config['jpush_master_secret']) ) {
            $this->_masterSecret = $config['jpush_master_secret'];
        } else {
            throw new \Exception('Cannot found configuration: jiguang_master_secret!');
        }
        $this->_request = new Request();
        $this->_request->setHttpVersion(Http::HTTP_VERSION_1_1);
        $this->_client = new \JPush\Client($this->_appKey, $this->_masterSecret);
        $this->_app_env = $config['app_env'] ?? 'dev';
    }

    /**
     * @param $title
     * @param $message
     * @param $extraData
     * @param $aData
     * @return \JPush\PushPayload
     * 获取推送信息
     */
    private function _getPush($title, $message, $extraData, $aData){

        $push = $this->_client->push();

        $push->setNotificationAlert($title)
            ->iosNotification($message, [
                'sound'    => 'sound.caf',
                // 'badge' => '+1',
                // 'content-available' => true,
                // 'mutable-content' => true,
                'category' => 'jiguang',
                'extras'   => $aData,
            ])
            ->androidNotification($message, [
                'title'  => $title,
                'extras' => $aData,
            ])
            ->message($message, [
                'title'  => $title,
                // 'content_type' => 'text',
                'extras' => $aData,
            ])
            ->options([
                // sendno: 表示推送序号，纯粹用来作为 API 调用标识，
                // API 返回时被原样返回，以方便 API 调用方匹配请求与返回
                // 这里设置为 100 仅作为示例

                // 'sendno' => 100,

                // time_to_live: 表示离线消息保留时长(秒)，
                // 推送当前用户不在线时，为该用户保留多长时间的离线消息，以便其上线时再次推送。
                // 默认 86400 （1 天），最长 10 天。设置为 0 表示不保留离线消息，只有推送当前在线的用户可以收到
                // 这里设置为 1 仅作为示例

                // 'time_to_live' => 1,

                // apns_production: 表示APNs是否生产环境，
                // True 表示推送生产环境，False 表示要推送开发环境；如果不指定则默认为推送生产环境

                'apns_production' => $this->_app_env == 'dev' ? FALSE : TRUE,

                // big_push_duration: 表示定速推送时长(分钟)，又名缓慢推送，把原本尽可能快的推送速度，降低下来，
                // 给定的 n 分钟内，均匀地向这次推送的目标用户推送。最大值为1400.未设置则不是定速推送
                // 这里设置为 1 仅作为示例

                // 'big_push_duration' => 1
            ]);
        return $push;
    }

    /**
     * 推送单条消息
     * @param $title
     * @param $message
     * @param $extraData
     * @param $aData
     * @throws \Exception
     */
    public function sendMessage( $title, $message, $extraData, $aData )
    {
        $push = $this->_getPush( $title, $message, $extraData, $aData);
        $push->setPlatform([
            'ios',
            'android'
        ])->addAlias($extraData);

        $response = $push->send();
        return $response;
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
        $push = $this->_getPush( $title, $message, $extraData, $aData);
        $push->setPlatform([
            'ios',
            'android'
        ])->addAlias($extraData);

        $response = $push->send();
        return $response;
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
       return $this->sendTagsMessage($title, $message, $extraData, $aData);
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
        $push = $this->_getPush( $title, $message, $extraData, $aData);

        $push->setPlatform([
            'ios',
            'android'
        ])->addTag($extraData);

        $response = $push->send();
        return $response;
    }

}