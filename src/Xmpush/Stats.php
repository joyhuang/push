<?php
/**
 * 获取发送的消息统计数据.
 * @author wangkuiwei
 * @name Stats
 * @desc 获取发送的消息统计数据。
 *
 */
namespace joyhuang\Push\Xmpush;

class Stats extends HttpBase {
    private $package;   //android用
    private $bundle;    //ios用

    public function __construct() {
        parent::__construct();
        $this->package = Constants::$packageName;
        $this->bundle = Constants::$bundle_id;
    }

    public function getStats($startDate, $endDate, $type = 'android', $retries = 1) {
        if ($type == 'ios') {
            $fields = array(
                'start_date' => $startDate,
                'end_date' => $endDate,
                'restricted_package_name' => $this->bundle
            );
        } else {
            $fields = array(
                'start_date' => $startDate,
                'end_date' => $endDate,
                'restricted_package_name' => $this->package
            );
        }
        $result = $this->getResult(PushRequestPath::V1_GET_MESSAGE_COUNTERS(), $fields, $retries);
        return $result;
    }

}

?>
