<?php

namespace joyhuang\Push\Services;


use joyhuang\Http\Http;
use joyhuang\Http\Request;
use joyhuang\Push\Redis;

class HmsPush
{
    private $_accessToken;
    private $_clientId;
    private $_clientSecret;
    private $_request;
    private $_redis;
    /** @var string 认证请求地址 */
    private $_authUrl = "https://login.cloud.huawei.com/oauth2/v2/token";
    /** @var string 推送请求地址 */
    private $_pushUrl = "https://push-api.cloud.huawei.com/v1/%s/messages:send";
    /** @var string 订阅topic请求地址 */
    private $_subscribeUrl = "https://push-api.cloud.huawei.com/v1/%s/topic:subscribe";
    private $_headers      = array( 'Content-Type: application/x-www-form-urlencoded' );

    /**
     * 构造函数。
     *
     * @param array $config
     * @throws \Exception
     */
    public function __construct( $config = NULL )
    {
        if ( !empty($config['huawei_client_id']) ) {
            $this->_clientId     = $config['huawei_client_id'];
            $this->_pushUrl      = sprintf($this->_pushUrl, $this->_clientId);
            $this->_subscribeUrl = sprintf($this->_subscribeUrl, $this->_clientId);
        } else {
            throw new \Exception('Cannot found configuration: huawei_client_id');
        }
        if ( !empty($config['huawei_client_secret']) ) {
            $this->_clientSecret = $config['huawei_client_secret'];
        } else {
            throw new \Exception('Cannot found configuration: hms.client_secret!');
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
        $this->_accessToken = $this->_redis->get("huawei:authToekn:");
        if ( !$this->_accessToken ) {
            $data = [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->_clientId,
                'client_secret' => $this->_clientSecret
            ];


            $response = $this->_request->post($this->_authUrl, [
                'headers' => $this->_headers,
                'data'    => $data
            ]);
            $res      = $response->getResponseArray();
            if ( !isset($res['access_token']) ) {
                throw new \Exception($res['error_description']);
            }
            $this->_accessToken = $res['access_token'];
            $this->_redis->setex("huawei:authToekn:", $res['expires_in'],
                $this->_accessToken);
        }
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
        date_default_timezone_set('PRC'); //设置中国时区
        // 发送消息通知
        $this->_getAccessToken();

        $data = [
            'validate_only' => FALSE,
            'message'       => [
                'notification' => [
                    'title' => $title,
                    'body'  => $message
                ],
                'android'      => [
                    'notification' => [
                        'title'        => $title,
                        'body'         => $message,
                        'click_action' => [
                            'type'   => 1,
                            'intent' => "intent://com.tiantong.push/notify_push?key=1#Intent;scheme=notifypush;launchFlags=0x10000000;component=com.yuyin.live.voice/com.yuyin.live.activity.HnNotifyPushActivity;end",
                        ]
                    ]
                ],
                'token'        => [ $extraData ]
            ]
        ];

        $response = $this->_request->post($this->_pushUrl, [
            'headers' => [
                'authorization' => 'Bearer ' . $this->_accessToken,
                'content-type'  => 'application/json;charset=utf-8'
            ],
            'data'    => json_encode($data)
        ]);
        return $response->getResponseArray();
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
        date_default_timezone_set('PRC'); //设置中国时区
        // 发送消息通知
        $this->_getAccessToken();

        $data = [
            'validate_only' => FALSE,
            'message'       => [
                'notification' => [
                    'title' => $title,
                    'body'  => $message
                ],
                'android'      => [
                    'notification' => [
                        'title'        => $title,
                        'body'         => $message,
                        'click_action' => [
                            'type'   => 1,
                            'intent' => "intent://com.tiantong.push/notify_push?key=1#Intent;scheme=notifypush;launchFlags=0x10000000;component=com.yuyin.live.voice/com.yuyin.live.activity.HnNotifyPushActivity;end",
                        ]
                    ]
                ],
                'token'        => $extraData
            ]
        ];

        $response = $this->_request->post($this->_pushUrl, [
            'headers' => [
                'authorization' => 'Bearer ' . $this->_accessToken,
                'content-type'  => 'application/json;charset=utf-8'
            ],
            'data'    => json_encode($data)
        ]);
        return $response->getResponseArray();
    }

    /**
     * 全量推送
     * 暂无全量推送方法， 以tags推送代替 暂时可将所有用户订阅到同一个tags
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
     * 根据tag/topic推送
     * @param $title
     * @param $message
     * @param $extraData
     * @param $aData
     */
    public function sendTagsMessage( $title, $message, $extraData, $aData )
    {
        date_default_timezone_set('PRC'); //设置中国时区
        // 发送消息通知
        $this->_getAccessToken();

        $data = [
            'validate_only' => FALSE,
            'message'       => [
                'notification' => [
                    'title' => $title,
                    'body'  => $message
                ],
                'android'      => [
                    'notification' => [
                        'title'        => $title,
                        'body'         => $message,
                        'click_action' => [
                            'type'   => 1,
                            'intent' => "intent://com.tiantong.push/notify_push?key=1#Intent;scheme=notifypush;launchFlags=0x10000000;component=com.yuyin.live.voice/com.yuyin.live.activity.HnNotifyPushActivity;end",
                        ]
                    ]
                ],
                'topic'        => $extraData
            ]
        ];

        $response = $this->_request->post($this->_pushUrl, [
            'headers' => [
                'authorization' => 'Bearer ' . $this->_accessToken,
                'content-type'  => 'application/json;charset=utf-8'
            ],
            'data'    => json_encode($data)
        ]);
        return $response->getResponseArray();
    }

    /**
     * 订阅topic 为统一方法名 设为tags
     * @param $tags
     * @param $extraData
     * @return mixed
     * @throws \Exception
     */
    public function subscribeTags( $tags, $extraData )
    {
        date_default_timezone_set('PRC'); //设置中国时区
        // 发送消息通知
        $this->_getAccessToken();

        $data = [
            'topic'      => $tags,
            'tokenArray' => $extraData,
        ];

        $response = $this->_request->post($this->_subscribeUrl, [
            'headers' => [
                'authorization' => 'Bearer ' . $this->_accessToken,
                'content-type'  => 'application/json;charset=utf-8'
            ],
            'data'    => json_encode($data)
        ]);
        return $response->getResponseArray();
    }

}