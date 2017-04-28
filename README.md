Yii2微信支付扩展
==========
适用于Yii2的微信支付扩展

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist imxiangli/yii2-wxpay "*"
```

or add

```
"imxiangli/yii2-wxpay": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :


### 第一步，添加配置（注意看注释说明）

```php
// common/config/main-local.php
<?php
return [
  'components' => [
  
      // ...
      
      'wxpay' => [
          'class' => \imxiangli\wxpay\WxPayApi::className(),
          'app_id' => '', // 绑定支付的APPID（必须配置，开户邮件中可查看）
          'mch_id' => '', // 商户号（必须配置，开户邮件中可查看）
          'key' => '', // 商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）设置地址：https://pay.weixin.qq.com/index.php/account/api_cert
          'app_secret' => '', // 公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置），获取地址：https://mp.weixin.qq.com/advanced/advanced?action=dev&t=advanced/dev&token=2005451881&lang=zh_CN
          // 下面两行是证书路径,注意应该填写绝对路径（仅退款、撤销订单时需要，可登录商户平台下载，API证书下载地址：https://pay.weixin.qq.com/index.php/account/api_cert，下载之前需要安装商户操作证书）
          'ssl_cert_path' => Yii::getAlias('@common/secret/apiclient_cert.pem'), // apiclient_cert.pem
          'ssl_key_path' => Yii::getAlias('@common/secret/apiclient_key.pem'), // apiclient_key.pem
          'notify_url' => '', // 支付异步通知地址绝对url（必须填写，用于支付成功后微信支付通知的接收），例如：http://www.hufenbao.com/wx/pay-notify.html 该地址的伪静态URL风格请自行参考Yii2文档
        ],
        
        // ...
        
    ]
];
```

### 第二步，添加控制器（用于接收微信支付异步通知及【模式1】回调）

注：什么是【模式1】请参考微信支付文档

```php
// frontend/controllers/WxController.php
<?php
namespace frontend\controllers;

use imxiangli\wxpay\WxPay;
use Yii;
use yii\web\Controller;

class WxController extends Controller
{
    public $enableCsrfValidation = false;

    // 用于微信支付异步通知接收
    public function actionPayNotify()
    {
        Yii::$app->get('wxpay'); 
        $notify = new PayNotifyCallBack();
        $notify->Handle(false);
    }
    
    // 微信支付【模式1】回调
    public function actionNative()
    {
        Yii::$app->get('wxpay'); 
        $notify = new NativeNotifyCallBack();
        $notify->Handle(true);
    }
}
```

### 第三步，添加业务逻辑处理代码
```php
//【模式1】的回调逻辑，注：什么是【模式1】请参考微信支付文档
// frontend/wxpay/NativeNotifyCallBack.php
<?php
namespace frontend\wxpay;

use imxiangli\wxpay\WxPay;
use Yii;

class NativeNotifyCallBack extends \WxPayNotify
{
    /**
     * @return WxPay
     */
    private function getWxPay()
    {
        /** @var WxPay $wxpay */
        $wxpay = Yii::$app->get('wxpay');
        return $wxpay;
    }

    /**
     * @param $openId
     * @param PayRecord $payRecord
     * @return mixed
     */
    public function unifiedorder($openId, $order_no)
    {
        //统一下单
        $input = new \WxPayUnifiedOrder();
        
        // todo 以下根据实际业务需要传值，参考类 WxPayUnifiedOrder 的方法调用说明
        // 这些信息基本都是通过 $order_no 从您自己系统中订单信息中获取
        $input->SetBody('');
        $input->SetAttach('');
        $input->SetOut_trade_no($order_no);
        $input->SetTotal_fee('100'); // 可根据 $order_no 获得金额（因为这是您自己系统的订单号）
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag('');
        $input->SetProduct_id('');
        // todo 以上根据实际业务需要传值，参考类 WxPayUnifiedOrder 的方法调用说明
        
        $input->SetTrade_type("NATIVE");
        $input->SetOpenid($openId);
        $result = $this->getWxPay()->unifiedOrder($input);
        return $result;
    }

    public function NotifyProcess($data, &$msg)
    {
        if(!array_key_exists("openid", $data) ||
            !array_key_exists("product_id", $data))
        {
            $msg = "回调数据异常";
            return false;
        }

        $openid = $data["openid"];
        $order_no = $data["product_id"]; // 您的订单号

        //统一下单
        $result = $this->unifiedorder($openid, $order_no);
        if(!array_key_exists("appid", $result) ||
            !array_key_exists("mch_id", $result) ||
            !array_key_exists("prepay_id", $result))
        {
            $msg = "统一下单失败";
            return false;
        }

        $this->SetData("appid", $result["appid"]);
        $this->SetData("mch_id", $result["mch_id"]);
        $this->SetData("nonce_str", $this->getWxPay()->getNonceStr());
        $this->SetData("prepay_id", $result["prepay_id"]);
        $this->SetData("result_code", "SUCCESS");
        $this->SetData("err_code_des", "OK");
        return true;
    }
}
```

### 第四步，支付成功处理业务逻辑

```php
//支付成功处理业务逻辑
// frontend/wxpay/PayNotifyCallBack.php
<?php
namespace frontend\wxpay;

use imxiangli\wxpay\WxPay;
use Yii;

class PayNotifyCallBack extends \WxPayNotify
{
    //查询订单
    public function Queryorder($transaction_id)
    {
        /** @var WxPay $wxpay */
        $wxpay = Yii::$app->get('wxpay');
        $input = new \WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = $wxpay->orderQuery($input);
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            return true;
        }
        return false;
    }

    //重写回调处理函数
    public function NotifyProcess($data, &$msg)
    {
        $notfiyOutput = array();

        if(!array_key_exists("transaction_id", $data) ||
            !array_key_exists("out_trade_no", $data)){
            $msg = "输入参数不正确";
            return false;
        }
        //查询订单，判断订单真实性
        if(!$this->Queryorder($data["transaction_id"])){
            $msg = "订单查询失败";
            return false;
        }
        // todo 支付成功，这里编写您自己的业务逻辑代码
        return true;
    }
}
```

### 第五步，退款处理业务

```php
// 以下代码仅说明了一次退款操作所需执行过程，具体如何使用因情况而异。
/** @var WxPay $wxpay */
$wxpay = \Yii::$app->get('wxpay');
$input = new \WxPayRefund();

// todo 以下参考类 WxPayRefund 的方法调用说明
$input->SetOut_trade_no('');
$input->SetTotal_fee('100'); 
$input->SetRefund_fee('100'); 
$input->SetOut_refund_no(''); 
// todo 以上参考类 WxPayRefund 的方法调用说明

$input->SetOp_user_id(\WxPayConfig::MCHID());
$data = $wxpay->refund($input);
if($data && $data['result_code'] == 'SUCCESS')
{
    // todo 退款成功后的处理
}
else
{
    // todo 退款失败
}
```

