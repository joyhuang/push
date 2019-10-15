<?php

namespace joyhuang\Push\Services;

use joyhuang\Push\MzPushSDK\MzPush;
use joyhuang\Push\MzPushSDK\VarnishedMessage;


class MeizuPush
{
    private $_mzPush;
    private $_appId;
    private $_appSecret;

    /**
     * 构造函数。
     *
     * @param array $config
     * @throws \Exception
     */
    public function __construct($config = null)
    {
        if (!empty($config['meizu_app_id'])) {
            $this->_appId = $config['meizu_app_id'];
        } else {
            throw new \Exception('Cannot found configuration: meizu_app_id!');
        }
        if (!empty($config['meizu_app_secret'])) {
            $this->_appSecret = $config['meizu_app_secret'];
        } else {
            throw new \Exception('Cannot found configuration: meizu_app_secret!');
        }
        $this->_mzPush = new MzPush($this->_appId, $this->_appSecret);
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
        $varnishedMessage = new VarnishedMessage();
        $varnishedMessage->setTitle($title)
            ->setContent($message)
            ->setClickType(3)
            ->setCustomAttribute('intent://com.tiantong.push/notify_push?key=1#Intent;scheme=notifypush;launchFlags=0x10000000;component=com.yuyin.live.voice/com.yuyin.live.activity.HnNotifyPushActivity;end')
            ->setUrl(null)
            ->setNoticeExpandType(1)
            ->setNoticeExpandContent($message)
            ->setOffLine(1)
            ->setParameters($aData);
        
        return $this->_mzPush->varnishedPush($extraData,$varnishedMessage);

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
        $varnishedMessage = new VarnishedMessage();
        $varnishedMessage->setTitle($title)
            ->setContent($message)
            ->setClickType(3)
            ->setCustomAttribute('intent://com.tiantong.push/notify_push?key=1#Intent;scheme=notifypush;launchFlags=0x10000000;component=com.yuyin.live.voice/com.yuyin.live.activity.HnNotifyPushActivity;end')
            ->setUrl(null)
            ->setNoticeExpandType(1)
            ->setNoticeExpandContent($message)
            ->setOffLine(1)
            ->setParameters($aData);

        return $this->_mzPush->varnishedPush(implode(',',$extraData),$varnishedMessage);
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
        $varnishedMessage = new VarnishedMessage();
        $varnishedMessage->setTitle($title)
            ->setContent($message)
            ->setClickType(3)
            ->setCustomAttribute('intent://com.tiantong.push/notify_push?key=1#Intent;scheme=notifypush;launchFlags=0x10000000;component=com.yuyin.live.voice/com.yuyin.live.activity.HnNotifyPushActivity;end')
            ->setUrl(null)
            ->setNoticeExpandType(1)
            ->setNoticeExpandContent($message)
            ->setOffLine(1)
            ->setParameters($aData);

        return $this->_mzPush->pushToApp(0,$varnishedMessage);
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
        $varnishedMessage = new VarnishedMessage();
        $varnishedMessage->setTitle($title)
            ->setContent($message)
            ->setClickType(3)
            ->setCustomAttribute('intent://com.tiantong.push/notify_push?key=1#Intent;scheme=notifypush;launchFlags=0x10000000;component=com.yuyin.live.voice/com.yuyin.live.activity.HnNotifyPushActivity;end')
            ->setUrl(null)
            ->setNoticeExpandType(1)
            ->setNoticeExpandContent($message)
            ->setOffLine(1)
            ->setParameters($aData);

        return $this->_mzPush->pushToTags(0,$extraData,$varnishedMessage);
    }

    /**
     * @param $param
     * @return string
     * 获取签名
     */
    private function _getSign($param) {
        //将appId打包的参数中
        //对key进行排序
        ksort($param);
        $sign = '';
        foreach ($param as $key => $value) {
            $sign .= "$key=$value";
        }
        $sign .= $this->_appSecret;
        return $sign;
    }



}