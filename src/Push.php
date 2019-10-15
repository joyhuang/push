<?php

namespace joyhuang\Push;

use joyhuang\Push\Exceptions\PushException;

class Push
{
    /** @var string 推送平台-华为 */
    const PUSH_PLATFORM_HUAWEI = 'huawei';
    /** @var string 推送平台-小米 */
    const PUSH_PLATFORM_XIAOMI = 'xiaomi';
    /** @var string 推送平台-VIVO */
    const PUSH_PLATFORM_VIVO = 'vivo';
    /** @var string 推送平台-OPPO */
    const PUSH_PLATFORM_OPPO = 'oppo';
    /** @var string 推送平台-魅族 */
    const PUSH_PLATFORM_MEIZU = 'meizu';
    /** @var string 推送平台-极光 */
    const PUSH_PLATFORM_JPUSH = 'jpush';
    private static $_redis    = NULL;
    private static $_platform = NULL;
    private static $_app_env;

    public function __construct( $config )
    {
        static::$_platform = $config['platform'];
        static::$_redis    = $config['redis'];
        static::$_app_env  = $config['app_env'];
    }

    /**
     * @return array
     * 获取必须有注册编号的推送平台类型
     */
    public static function getRequiredTokenPushPlatformArr()
    {
        return [
            self::PUSH_PLATFORM_HUAWEI,
            self::PUSH_PLATFORM_OPPO,
            self::PUSH_PLATFORM_VIVO,
            self::PUSH_PLATFORM_MEIZU,
        ];
    }


    /**
     * @return array
     * 获取所有可设置的推送平台
     */
    public static function getAllPushPlatform()
    {
        return [
            self::PUSH_PLATFORM_MEIZU,
            self::PUSH_PLATFORM_VIVO,
            self::PUSH_PLATFORM_XIAOMI,
            self::PUSH_PLATFORM_HUAWEI,
            self::PUSH_PLATFORM_OPPO,
            self::PUSH_PLATFORM_JPUSH
        ];
    }

    private static function getService( $platform )
    {
        switch ( $platform ) {
            case self::PUSH_PLATFORM_XIAOMI:
                $service = "MiPush";
                break;
            case self::PUSH_PLATFORM_HUAWEI:
                $service = "HmsPush";
                break;
            case self::PUSH_PLATFORM_VIVO:
                $service = "VivoPush";
                break;
            case self::PUSH_PLATFORM_MEIZU:
                $service = "MeizuPush";
                break;
            case self::PUSH_PLATFORM_OPPO:
                $service = "OppoPush";
                break;
            case self::PUSH_PLATFORM_JPUSH:
            default:
                $service = "JPush";
        }

        return "joyhuang\\Push\\Services\\" . $service;
    }

    /**
     * @param $APP_ENV string 开发环境
     * @param $title string 标题
     * @param $message string 内容
     * @param string $pushType 推送类型 all(全局) alias(对象) tags（分组）
     * @param array $extraData 推送的用户数据（用户ID，用户标识，用户平台） 或者 tag 数据
     * @param array $aData 传递额外参数
     * @throws PushException
     */
    public function send( $APP_ENV, $title, $message, $pushType = 'all', $extraData = [], $aData = [] )
    {
        switch ( $pushType ) {
            case 'alias':
                if ( !$extraData ) {
                    throw new PushException("没有alias参数", 405);
                }
                $aliasGroupArr = [];
                foreach ( $extraData as $item ) {
                    $platform = $item['platform'];
                    if ( !in_array($platform, self::getAllPushPlatform()) ) {
                        $platform = self::PUSH_PLATFORM_JPUSH;
                    }
                    if ( in_array($platform, self::getRequiredTokenPushPlatformArr()) ) {
                        // 类型必须有标识，但是没有传入 则默认极光推送
                        if ( !isset($item['push_token']) || $item['push_token'] == '' ) {
                            $platform                     = self::PUSH_PLATFORM_JPUSH;
                            $aliasGroupArr[ $platform ][] = $APP_ENV . $item['user_id'];
                        } else {
                            $aliasGroupArr[ $platform ][] = $item['push_token'];
                        }
                    } else {
                        $aliasGroupArr[ $platform ][] = $APP_ENV . $item['user_id'];
                    }
                }
                foreach ( $aliasGroupArr as $itemPlatform => $aliasArr ) {
                    $service = self::getService($itemPlatform);
                    if ( !$service ) {
                        continue;
                    }
                    $config          = static::$_platform[ $itemPlatform ];
                    $config['redis'] = static::$_redis;
                    $push            = new $service($config);
                    if ( count($aliasArr) > 1 ) {
                        if ( method_exists($push, 'sendBatchMessage') ) {
                            $itemResult = $push->sendBatchMessage($title, $message, $aliasArr, $aData);
                            print $itemPlatform . ':' . json_encode($itemResult, JSON_UNESCAPED_UNICODE) . "\n";
                        }
                    } else {
                        if ( method_exists($push, 'sendMessage') ) {
                            $itemResult = $push->sendMessage($title, $message, $aliasArr[0], $aData);
                            print $itemPlatform . ':' . json_encode($itemResult, JSON_UNESCAPED_UNICODE) . "\n";
                        }
                    }
                }

                break;
            case 'tags':
                if ( !$extraData ) {
                    throw new PushException("没有tags参数", 405);
                }
                $tagsGroupArr = [];
                foreach ( $extraData as $item ) {
                    $platform = $item['platform'];
                    if ( !in_array($platform, self::getAllPushPlatform()) ) {
                        $platform = self::PUSH_PLATFORM_JPUSH;
                    }
                    $tagsGroupArr[ $platform ][] = $APP_ENV . $item['tag'];
                }
                foreach ( $tagsGroupArr as $itemPlatform => $tagsArr ) {
                    $service = self::getService($itemPlatform);
                    if ( !$service ) {
                        continue;
                    }
                    $config          = static::$_platform[ $itemPlatform ];
                    $config['redis'] = static::$_redis;
                    $push            = new $service($config);
                    if ( method_exists($push, 'sendTagsMessage') ) {
                        $itemResult = $push->sendTagsMessage($title, $message, $tagsArr, $aData);
                        print json_encode($itemResult, JSON_UNESCAPED_UNICODE) . "\n";
                    }
                }

                break;
            case 'all':
                foreach ( self::getAllPushPlatform() as $itemPlatform ) {
                    $service = self::getService($itemPlatform);
                    if ( !$service ) {
                        continue;
                    }
                    $config = static::$_platform[ $itemPlatform ] ?? [];
                    if ( !$config ) {
                        continue;
                    }
                    $config['redis'] = static::$_redis;
                    $push            = new $service($config);
                    if ( method_exists($push, 'sendAllMessage') ) {
                        $itemResult = $push->sendAllMessage($title, $message, $extraData, $aData);
                        if ( is_array($itemResult) ) {
                            $itemResult = json_encode($itemResult, JSON_UNESCAPED_UNICODE);
                        }
                        print " $itemPlatform : $itemResult\n";

                    }
                }
                break;
            default:
                throw new PushException("推送类型参数错误", 405);
        }

    }


    /**
     * 统一推送接口。
     *
     * @param $deviceToken
     * @param $title
     * @param $message
     * @param $platform
     * @return mixed
     */
    public function send1( $deviceToken, $title, $message, $platform, $type, $id )
    {
        $service = self::getService($platform);

        $push = new $service(static::$_platform[ $platform ]);
        if ( method_exists($push, 'sendMessage') ) {
            return $push->sendMessage($deviceToken, $title, $message, $type, $id);
        }

        return FALSE;
    }

    /**
     * 根据用户ID设置用户token
     */
    public static function setToken( $platform, $app_id, $user_id, $deviceToken )
    {
        if ( !$app_id || !$user_id || !$deviceToken || !$platform ) {
            return FALSE;
        }
        static::$_redis->set($app_id . ":" . $user_id . ":regid:", $platform . ":" . $deviceToken);
        return TRUE;
    }


    /**
     * 根据用户ID获取用户token
     */
    public static function getToken( $app_id, $user_id )
    {
        return static::$_redis->get($app_id . ":" . $user_id . ":regid:");
    }

    /**
     * 根据用户ID设置用户token
     */
    public static function setDeviceToken( $app_id, $list_name, $platform, $deviceToken )
    {
        return static::$_redis->lpush($app_id . $list_name, $platform . ':' . $deviceToken);
    }

    /**
     * 根据用户ID设置用户token
     */
    public static function getDeviceToken( $app_id, $list_name, $page = 1, $pageSize = 100 )
    {
        return static::$_redis->lrange($app_id . $list_name, ($page - 1) * $pageSize, $pageSize);
    }

    //返回列表长度
    public static function getListLen( $app_id, $list_name )
    {
        return static::$_redis->llen($app_id . $list_name);
    }


    public static function success()
    {
        throw new PushException("success", 200);
    }

    public static function error()
    {
        throw new PushException("参数错误", 405);
    }
}