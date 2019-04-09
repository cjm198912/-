<?php
namespace app;


interface iPay {

    public function pay($arg,$payType);              //app 请求支付方法
    public function websitpay($arg,$payType);         //本站发起支付

    //public function notify($arg);               //app 回调方法
 
    public function query($arg);			//app 请求支付订单状态
    public function config();                //方法配置参数
   
    public function  websiteNotiy($arg);      //第三方回调本站方法


}