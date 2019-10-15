# push
- 聚合推送
  - 小米、华为、vivo、oppo、魅族、极光
```$xslt
单个推送
	取推送的类型 只用推送单个服务
	
批量推送
	将数据按照平台进行分组推送
	
tag单个推送
	取推送的类型 只用tag推送单个服务
	
tag批量推送
	将数据按照平台进行分组tag推送
	
全量推送
	所有平台都推送一遍
```

 - demo
```$xslt
<?php

try {
    $config    = [
        'app_env'  => APP_ENV,
        'redis'    => [
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'auth'     => 'xxxxxxxxxx',
            'db'       => 10,
            'pconnect' => FALSE,
        ],
        'platform' => [
            'vivo'   => [
                'vivo_app_id'     => '1xxxx',
                'vivo_app_key'    => '94b827ce-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
                'vivo_app_secret' => '8c56a0b3-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
            ],
            'oppo'   => [
                'oppo_app_key'       => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
                'oppo_master_secret' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
            ],
            'xiaomi' => [
                'xiaomi_app_package_name' => 'xxxx',
                'xiaomi_app_secret'       => 'xxxxxxxxxxxx'
            ],
            'huawei' => [
                'huawei_client_id'     => '10xxxxxxx',
                'huawei_client_secret' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            ],
            'meizu'  => [
                'meizu_app_id'     => '12xxxx',
                'meizu_app_secret' => 'xxxxxxxxxxxxxxxxxxxx'
            ],
            'jpush'  => [
                'jpush_app_key'       => 'xxxxxxxxxxxxxxxxxxxxxxxx',
                'jpush_master_secret' => 'xxxxxxxxxxxxxxxxxxxxxxxx'
            ]
        ]
    ];
    $push      = new \joyhuang\Push\Push($config);
    // 标题
    $title     = '标题';
    // 详情
    $message   = '恭喜发财，红包拿来';
    // 额外参数（部分待补充）
    $aData     = [
        'user_id' => 63,
        'test'    => '123213213'
    ];
    // 推送类型 （暂时支持，alias，tags， all）
    $pushType  = 'alias';
    $extraData = [
        [
            // 用户ID 客户端用开发环境标识加此参数注册极光，小米推送。  ps： dev63   product63； 可根据自身要求修改
            'user_id'    => '63',
            // 推送平台  暂时支持  xiaomi,jpush,vivo,oppo,huawei,meizu
            'platform'   => 'xiaomi',
            // 推送平台注册的信息 vivo,oppo,huawei,meizu 必须传
            'push_token' => '',
        ]
    ];
//            $pushType  = 'tags';
//            $extraData = [
//                [
//                    'platform' => 'meizu',
//                    // 分组 OPPO 暂不支持；华为需要服务端订阅，客户端无法操作。其他平台客户端注册时操作
//                    'tag'      => ''
//                ]
//            ];
//             // 全量推送时，由于华为没有查到全量推送，以及vivo全量推送1天只能推送一次。则需要传入开发环境标识， 当做分组来代替全量推送。
//            $pushType  = 'all';
//            $extraData = APP_ENV;
    $push->send(APP_ENV, $title, $message, $pushType, $extraData, $aData);
    die;
} catch ( \Exception $e ) {
    return $e->getMessage();
}
```