<?php

namespace joyhuang\Push\Services;


use joyhuang\Http\Http;
use joyhuang\Http\Request;
use joyhuang\Push\Xmpush\Builder;
use joyhuang\Push\Xmpush\Constants;
use joyhuang\Push\Xmpush\Sender;

class MiPush
{
    private $_appPackageName;
    private $_appSecret;
    private $_request;


    /**
     * MiPush constructor.
     *
     * @param null $config
     * @throws \Exception
     */
    public function __construct( $config )
    {
        if ( !empty($config['xiaomi_app_package_name']) ) {
            $this->_appPackageName = $config['xiaomi_app_package_name'];
        } else {
            throw new \Exception('Cannot found configuration: xiaomi_app_package_name!');
        }

        if ( !empty($config['xiaomi_app_secret']) ) {
            $this->_appSecret = $config['xiaomi_app_secret'];
        } else {
            throw new \Exception('Cannot found configuration: xiaomi_app_secret!');
        }
        $this->_request = new Request();
        $this->_request->setHttpVersion(Http::HTTP_VERSION_1_1);
    }

    private function _messageInit( $title, $message, $extraData, $aData )
    {
        $payload = $aData;
        Constants::setPackage($this->_appPackageName);
        Constants::setSecret($this->_appSecret);
        $sender         = new Sender();
        $BuilderMessage = new Builder();
        $BuilderMessage->title($title);  // 通知栏的title
        $BuilderMessage->description($message); // 通知栏的description
        $BuilderMessage->passThrough(0);  // 这是一条通知栏消息，如果需要透传，把这个参数设置成1,同时去掉title和descption两个参数
        $BuilderMessage->payload($payload); // 携带的数据，点击后将会通过客户端的receiver中的onReceiveMessage方法传入。
        $BuilderMessage->extra(Builder::notifyForeground, 0); // 应用在前台是否展示通知，如果不希望应用在前台时候弹出通知，则设置这个参数为0
        $BuilderMessage->notifyId(0); // 通知类型。最多支持0-4 5个取值范围，同样的类型的通知会互相覆盖，不同类型可以在通知栏并存
        $BuilderMessage->build();
        return [
            'sender'        => $sender,
            'build_message' => $BuilderMessage
        ];
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
        $messageInit = $this->_messageInit($title, $message, $extraData, $aData);
        /** @var \joyhuang\Push\Xmpush\Sender $sender */
        $sender = $messageInit['sender'];
        /** @var \joyhuang\Push\Xmpush\Builder $BuilderMessage */
        $BuilderMessage = $messageInit['build_message'];
        return $sender->sendToAlias($BuilderMessage, $extraData)->getRaw();
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
        $messageInit = $this->_messageInit($title, $message, $extraData, $aData);
        /** @var \joyhuang\Push\Xmpush\Sender $sender */
        $sender = $messageInit['sender'];
        /** @var \joyhuang\xmpush\Builder $BuilderMessage */
        $BuilderMessage = $messageInit['build_message'];
        return $sender->sendToAliases($BuilderMessage, $extraData)->getRaw();
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
        $messageInit = $this->_messageInit($title, $message, $extraData, $aData);
        /** @var \joyhuang\Push\Xmpush\Sender $sender */
        $sender = $messageInit['sender'];
        /** @var \joyhuang\Push\Xmpush\Builder $BuilderMessage */
        $BuilderMessage = $messageInit['build_message'];
        return $sender->broadcastAll($BuilderMessage)->getRaw();
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
        $messageInit = $this->_messageInit($title, $message, $extraData, $aData);
        /** @var \joyhuang\Push\Xmpush\Sender $sender */
        $sender = $messageInit['sender'];
        /** @var \joyhuang\Push\Xmpush\Builder $BuilderMessage */
        $BuilderMessage = $messageInit['build_message'];
        return $sender->multiTopicBroadcast($BuilderMessage, $extraData, \XiaoMiPush\Constants::UNION)->getRaw();
    }

}