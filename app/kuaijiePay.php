<?php
namespace app;


class kuaijiePay implements iPay{

    /**快捷支付
     */
    private    $_tongDao='kuaijie';
    private    $APPID='dahaokeji'; //appid
    private    $KEY="";
    private    $payGateway='';
    private    $notifyKey     ='0c3a436a0b867f854693cd7f36c85d97'; //本站回调秘钥
    private    $host = 'https://b-sdk.zumire.com/';
    private    $_notify=""; //本站回调地址

    private    $_appNotifyUrl=""; //app回调地址
    private    $_api_uri  = "";//接口地址
    private    $_thirdQueryUrl  = "";//第三方查询接口地址


    public function config(){
        $ret= array(
            'pay_type' => 'ali_h5',//支持的支付方式
            'md5_key'=>'4f115d9b51f5c3e7bea3cd80fdb82f42', //支付验签秘钥
            );

        return $ret;
    }
  
    public function pay($arg,$payType){
        // $res='0';
    
        $res=$this->websitpay($arg,$payType);
      //var_dump($res,$res['data']['url']);
        if($res['code']=='0'){
      //echo 'h';exit;
              publicfun::retAppPayJson('0000','success',[
                  'pay_status'=>'ok',
                    'url'=>$res['data']['url'],
            ]);
        }else{
            publicfun::logWrite('用户 '.$arg['userId'].' 订单 '.$arg['order_id']. ' 支付失败 原因是');
            publicfun::retAppPayJson('0007','支付失败');
        }
    }

     public function websitpay($arg,$payType){

        $uid = $arg['userId'];
        $pay_type = $payType;//[Ali,AliWeb,WeChat]
        $amount = $arg['pay_amount'];
        $order_no = $arg['order_id'];
    
        publicfun::logWrite('用户 '.$uid.' 订单 '.$order_no.' 发起支付');
        $par = array();
            /* 
               ALP     支付宝
               QQP    QQ支付
               WXP   微信支付
               JDB     借贷宝
               UPOP  银联活码
               BANKB2C 网银B2C
               ex:kuaijie-uppayh5-pay
            */
        $subPay=substr($payType,0,5);//alpay
        $payChannel  = array(
            'uppay' =>'UPOP', //网银快捷支付
            'alpay' =>'ALP',
            'wxpay' =>'WXP',
        );

        $payChannel=$payChannel[$subPay];
       
        $payTy=substr($payType, -2); 
        $tradeTypeArr=array(
              'qr'=>'QRCODE', //扫码
              'h5'=>'H5',    //H5
         );
        $tradeType=$tradeTypeArr[$payTy];
        $goods_info='jb';
// var_dump($subPay,$tradeType);exit;
       if(!isset($tradeTypeArr[$payTy])){
             publicfun::retJson('0016','支付方式不存在');
       }
      
    
      $ip=$_SERVER['REMOTE_ADDR'];
        $dataa=array(   
                       'appId'           =>$this->APPID,
                       'version'         =>'1.0',
                       'nonceStr'        =>publicfun::str_rand(),
                        'orderId'        =>$order_no,
                        'amount'         =>   $amount ,
                        'payChannel'     =>  $payChannel,
                        'goodsName'      => 'jb',
                        'goodsDesc'      => 'jb',
                        'clientIp'       =>$ip,
                        'tradeType'       => $tradeType,
                        'asyncNotifyUrl'       => $this->_notify

                        ); //验证数据结构

         $dataa['sign']=$this->sign($dataa,$this->KEY);
         // var_dump($this->payGateway,$dataa);exit;
       $pageContents=  publicfun::requestPost($this->payGateway,$dataa);
        $result=json_decode($pageContents,true);
        //var_dump($result);exit;
        if(!isset($result["data"])){
              publicfun::logWrite('用户 '.$uid.' 订单'.$order_no.'支付失败 原因是'.json_encode($result));
              return false;
              // publicfun::retAppPayJson('0009','请求出错');
        }
  

        if(isset($result['data'])){
             
                $retHtml =$result['data'];
               return array('code'=>'0','data'=>['url'=> $retHtml]);
         
        }else{
                return array('code'=>'1');
        }
       
    }

   public static function sign($params,$key){
     
        ksort($params);
        $string='';

        foreach ($params as $k=>$v){
            if($v!=''){
                if($v!=''&&$v!=null){
                $string=$string.$k.'='.$v.'&';
                }
            }
        }

        $string=$string.'key='. $key;
      // print_r($string);exit;
//        print_r('-------------------------');
        $sign=strtoupper(md5($string));
        // var_dump($sign);exit;
        return $sign;
    }



    //本站回调方法 
    public function  websiteNotiy($arg){
        $sign=$arg['sign'];
        unset($arg['sign']);
        $orderId=$arg['orderId'];
        $qm=$this->sign($arg,$this->KEY);
        publicfun::logWrite(' 订单'. $orderId.' 来自第三方回调信息为 '.json_encode($arg),'','callback_from_third_info',$this->_tongDao);
       // var_dump($qm,$sign);exit;
        if( $qm==$sign){
            $result = true;
        } else{
            $result = false;
        }

        if (!$result){
             publicfun::logWrite(' 订单'.$orderId.'来自第三方签名失败 原因是'.json_encode($arg),'','third_sign_error',$this->_tongDao);
            echo 'error';
        }else{
            publicfun::logWrite(' 订单'.$orderId.'来自第三方回调信息签名成功','','callback_from_third_info',$this->_tongDao);

           $pay_status=1;
           $arr=array('order_id'=>$orderId,'plat_id'=>'','pay_amount'=>$arg['amount']);
           //回调通知app
           $ret= $this->notify($arr,$pay_status);
            if ($ret == true) {
              echo 'success';
                exit;
            } else {
                 publicfun::logWrite(' 订单 '.$orderId.'通知游戏端回调失败 原因是请看callback_form_app文件','','notify_app_error',$this->_tongDao);
               echo 'notice app error';
            }
    }
}
   //本站回调通知App方法
   public function  notify($arg,$pay_status){    
     $sign= publicfun::sign($arg,$this->notifyKey);
      //  $sign=$this->sign($arg,$this->notifyKey);
        $data = array(
            'code' =>'0000',
            'msg' =>'success',
            'data' => [
                'order_id'=>$arg['order_id'],//用户订单
                'plat_id'=>$arg['plat_id'],//平台订单
                'pay_amount'=>$arg['pay_amount'],//实际付款金额
                'sign'=>$sign,//实际付款金额
            ],
        );
       //取订单对应app的回调地址 进行通知
         $app_notify_url=pay::getOrderAppNotifyUrl($arg['order_id']);
        if(empty($notify_url)){
             publicfun::logWrite(' 订单回调 '. $arg['order_id'].' 开始通知app端 失败 找不到订单对应的回调地址','','callback_to_app');
             exit;

        }
        $dataString=json_encode($data);
        $configArr=$this->config();
        // var_dump($configArr);
        $postAppNotiyUrl= $this->_appNotifyUrl;
        // var_dump($postAppNotiyUrl,$dataString);exit;
    
        //开始通知app
         publicfun::logWrite(' 订单回调 '. $arg['order_id'].' 开始通知app端 信息为 '.$dataString,'','notify_to_app',$this->_tongDao);

        $ret = publicfun::requestPost($postAppNotiyUrl,$dataString);
    // var_dump($ret);exit;
        $res=json_decode($ret,true);
   

       //接收app返回数据 
       publicfun::logWrite(' 订单'. $arg['order_id'].' app端返回信息 '.$ret,'','callback_from_app',$this->_tongDao);
// echo 'h';exit;

        if(isset($res['error']) && $res['error']=='0000'){
            return true;
        }else{
            return false;
        }
   }


 public   function replaySign($data){
            ksort($data);
            $KEY=$this->KEY;
            $b='';
                //dump($mykeyarr);
                $lastvalue=end($data);
                foreach($data as $key=>$value){
                    if($value==$lastvalue){
                        $b.=$key.'='.$value;
                    }else{
                        $b.=$key.'='.$value.'&';
                    }
                }
                //echo end($data).'<br/>';
                $b.='key='.$KEY;
                $signData=md5($b);
                $signData=strtoupper($signData);
                
                return $signData;
    }


    public function query($arg,$payType=''){
        $orderId=$arg['order_id'];
    
    
        $res=$this->orderQuery($orderId);
    //var_dump('999',$res);exit;
        if($res['code']=='000'){
              publicfun::retAppPayJson('0000','success',[
                  'status'=>'ok',
                  'pay_code'=> $res['status'],
                  'plat_id'=>$res['plat_id'],
                  'pay_amount'=>$res['amount'],   
            ]);
        }else{
             publicfun::retAppPayJson('0015','查询失败');
        }
        
    }
  

  
  
    /**
     * 订单查询 
    */
    public function orderQuery($order_id){
        $data=array(
                        'orderId'     => $order_id ,
                        'appId'         =>  $this->APPID,
                        'version'     =>'1.0',
                         'nonceStr'      =>publicfun::str_rand(),
                        ); //验证数据结构


        $data['sign']=$this->sign($data,$this->KEY);
 
        $pageContents = $this->requestGet($this->_thirdQueryUrl.'?'.http_build_query($data));
       // $pageContents=publicfun::requestPost($this->_thirdQueryUrl,$data);
    
        $result=json_decode($pageContents,true);
         //var_dump($result);exit;
        if(!isset($result["orderId"])){
              publicfun::logWrite(' 订单 '.$order_id.' 查询失败 原因是'.json_encode($result),'','query',$this->_tongDao);
               $returnArr=array('code'=>'1000','msg'=>'查询失败');
               return $returnArr;
              // publicfun::retAppPayJson('0009','请求出错');
        }
         //var_dump($result);exit;
        $errCode = $result["orderId"];

        header("Content-Type: text/html; charset=utf-8");
        if(isset($result["orderId"]) ){
        
              if($result['pay']==true){
                  $pay_code=1;
              }else{
                $pay_code=-1;
              }
             
                $returnArr=array('code'=>'000','查询成功','status'=>$pay_code,'amount'=>'','plat_id'=>'');
                   //接收app返回数据 
        
               publicfun::logWrite(' 订单查询'. $order_id.' 发送给app端信息'.json_encode($returnArr),'','callback_to_app',$this->_tongDao);
               // var_dump($returnArr);exit;  
          return $returnArr;
         
        }else{
           $returnArr=array('code'=>'1000','msg'=>'查询失败');
         return $returnArr;
        }

      
    }

    function requestGet($url, $timeout=8) {
    //初始化
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    //设置抓取的url
    curl_setopt($curl, CURLOPT_URL, $url);
    //设置头文件的信息作为数据流输出
    curl_setopt($curl, CURLOPT_HEADER, 0);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

    //执行命令
    $data = curl_exec($curl);
    //关闭URL请求
    curl_close($curl);
    //显示获得的数据

    return $data;

}
  
    //返回给app查询接口的支付状态码 -1-待支付 1-已支付 2-已取消 3-支付失败 4-下单失败 5-处理中
    public function get_pay_code($code){
      $array=['0'=>-1,'1'=>1,'2'=>2,'3'=>3,'4'=>4,'5'=>5];
      return $array[$code];
    }






    public function checkParamsExists($params)
    {   
  
        if (empty($params)) {
             publicfun::retJson('0013','请求参数不能为空!');
        }
        $params = is_array($params) ? $params : [$params];
        $configParams=$this->config();
        $mod=$configParams[$this->payMethod.'_'.'pargrams']; //对应请求方法必要参数
        $fields=$configParams[$this->payMethod.'_'.'fields_pargrams']; //扩展参数
        if ($fields) {
            $fields = array_flip($fields);
            $params = array_merge($params, $fields);
        } 
     
        foreach ($mod as $mod_key => $mod_value) {
            if (!array_key_exists($mod_value, $params)) {
                //var_dump('canshu '.$mod_value.' queshi');
                       publicfun::retJson('0013','参数缺失!');
            }
        }
     
        return true;

    }
        public function checkAppSign($arg){
                       
    }

}