<?php

namespace imxiangli\wxpay;

use yii\base\Component;


class WxPay extends Component
{
    public $app_id;
    public $mch_id;
    public $key;
    public $app_secret;
    public $ssl_cert_path;
    public $ssl_key_path;
    public $notify_url;
    public $curl_proxy_host = '0.0.0.0';
    public $curl_proxy_port = 0;
    public $report_level = 1;

    public function init()
    {
        require_once \Yii::getAlias('@vendor/imxiangli/yii2-wxpay/lib/WxPay.Api.php');
        require_once \Yii::getAlias('@vendor/imxiangli/yii2-wxpay/lib/WxPay.Exception.php');
        require_once \Yii::getAlias('@vendor/imxiangli/yii2-wxpay/lib/WxPay.Config.php');
        require_once \Yii::getAlias('@vendor/imxiangli/yii2-wxpay/lib/WxPay.Data.php');
        require_once \Yii::getAlias('@vendor/imxiangli/yii2-wxpay/lib/WxPay.Notify.php');
        \WxPayConfig::$wxpay = $this;
    }

    /**
     * @param \WxPayUnifiedOrder $inputObj
     * @param int $timeOut
     * @return mixed
     */
    public function unifiedOrder($inputObj, $timeOut = 6)
    {
        return \WxPayApi::unifiedOrder($inputObj, $timeOut);
    }

    public function orderQuery($inputObj, $timeOut = 6)
    {
        return \WxPayApi::orderQuery($inputObj, $timeOut);
    }

    public function closeOrder($inputObj, $timeOut = 6)
    {
        return \WxPayApi::orderQuery($inputObj, $timeOut);
    }

    public function refund($inputObj, $timeOut = 6)
    {
        return \WxPayApi::refund($inputObj, $timeOut);
    }

    public function refundQuery($inputObj, $timeOut = 6)
    {
        return \WxPayApi::refundQuery($inputObj, $timeOut);
    }

    public function downloadBill($inputObj, $timeOut = 6)
    {
        return \WxPayApi::downloadBill($inputObj, $timeOut);
    }

    public function micropay($inputObj, $timeOut = 10)
    {
        return \WxPayApi::micropay($inputObj, $timeOut);
    }

    public function reverse($inputObj, $timeOut = 6)
    {
        return \WxPayApi::reverse($inputObj, $timeOut);
    }

    public function report($inputObj, $timeOut = 1)
    {
        return \WxPayApi::report($inputObj, $timeOut);
    }

    public function bizpayurl($inputObj, $timeOut = 6)
    {
        return \WxPayApi::bizpayurl($inputObj, $timeOut);
    }

    public function shorturl($inputObj, $timeOut = 6)
    {
        return \WxPayApi::shorturl($inputObj, $timeOut);
    }

    public static function notify($callback, &$msg)
    {
        return \WxPayApi::notify($callback, $msg);
    }

    public function getNonceStr($length = 32)
    {
        return \WxPayApi::getNonceStr($length);
    }
}
