<?php
namespace app;


class yongshunPay implements iPay{

    /** 永顺大豪棋牌
    http://47.101.178.117:2666
    账号：cp001
    密码：123456
    商户信息，接口文档后台获取
    需要绑定谷歌验证码
    绑定步骤： 打开微信搜索“二级验证码”第一个，直接使用，再商户后台点击进行绑定操作，如果要绑定多个验证请同时扫码一起绑定
     */
    private    $ORGID='0020193113180323'; //机构代码
    private    $MERID=''; //商户号
    private    $DES_KEY=''; //数据加密秘钥
    private    $KEY="BcmMQtwaRrRTip33KB24H2zGe6xGre5b";     //验签秘钥
    private    $payGateway=''; //支付网关
    private    $notifyKey     =''; //本站跟app之间的回调秘钥
    private    $_notify=""; //本站回调地址
    private    $_appNotify=""; //app回调地址 
    private    $_api_uri  = "";//接口地址

   //限额 支付宝100 微信 200
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
        if($res['code']=='0'){
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
        $pay_type_arr = array(
            'wxpayscan' =>'0101',
            'aipayscan' =>'0201',
            'aipayh5' =>'0203',
            'wxpayh5' =>'0103',
        );
        $goods_info='jb';
       
       if(!isset($pay_type_arr[$pay_type])){
             publicfun::retJson('0016','支付方式不存在');
       }
        
      $pay_type=$pay_type_arr[$pay_type];
    
      

        $data=array(
                        'requestId'     =>  $order_no,
                        'orgId'         =>  $this->ORGID,
                        'productId'     =>  '0100',
                        'dataSignType'  => 1,
                        'timestamp'     =>  date('YmdHis',time())
                        ); //验证数据结构
        $busMap=array(
                        'merno'         => $this->MERID,
                        'bus_no'        => $pay_type,  //业务编号 0101微信扫码 0201支付宝扫码 0501 QQ钱包扫码 0601京东钱包扫码 0701银联二维码
                        'amount'        =>  $amount, //交易金额-单位分
                        'goods_info'    =>  $goods_info,
                        'order_id'      =>  $order_no,
                        // 'return_url'    =>  $returnUrl,
                        'notify_url'    =>  $this->_notify
                      );
                      // var_dump($busMap);exit;
        $businessData=json_encode($busMap);

        
        $businessData =(new lib\DesUtils())->encrypt($businessData,$this->DES_KEY);//加密
        $businessData = urlencode($businessData); //加密结果 UrlEncode
        $data['businessData']=$businessData;
        
        $data['signData'] =  $this->sign($data,$this->KEY);

        $posturl=$this->payGateway;
          
		try{
			 $pageContents = (new lib\HttpClient())->quickPost($posturl, $data); 
		}catch(\Exception $e){
			    return array('code'=>'1');
		}
       
       // var_dump($pageContents);exit;
        $result=json_decode($pageContents,true);
        $errCode = isset($result["key"])?$result["key"]:'9999';

        if($errCode=="00" || $errCode=="05"){
               $qodeUrl=json_decode($result['result'],true);
                $retHtml = $qodeUrl['url'];
               return array('code'=>'0','data'=>['url'=> $retHtml]);
         
        }else{
              publicfun::logWrite('用户 '.$uid.' 订单'.$order_no.'支付失败 原因是'.json_encode($result),'','error_pay');

                return array('code'=>'1');
        }
       
        header("Content-Type: text/html; charset=utf-8");
        $errMsg = $result["msg"];
        
       
    }

   public  function sign($data,$key){
            ksort($data);
            $KEY=$key;
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
                $b.=$KEY;
                $signData=md5($b);
                $signData=strtoupper($signData);
                
                return $signData;
}



    //本站回调方法 
    public function  websiteNotiy($arg){
        $sign=$arg['sign_data'];
        unset($arg['sign_data']);
        $qm=$this->replaySign($arg);
        publicfun::logWrite(' 订单'. $arg['order_id'].' 来自第三方回调信息为'.json_encode($arg),'','callback_from_third');
       // var_dump($qm,$sign);exit;
        if( $qm==$sign){
            $result = true;
        } else{
            $result = false;
        }

        if (!$result){
             publicfun::logWrite(' 订单'. $arg['order_id'].'来自第三方签名失败 原因是'.json_encode($arg),'','callback_from_third');
            echo 'error';
        }else{
            publicfun::logWrite(' 订单'. $arg['order_id'].'来自第三方回调信息签名成功','','callback_from_third');

           $pay_status=$arg['trade_status'];
           $arr=array('order_id'=>$arg['order_id'],'plat_id'=>$arg['plat_order_id'],'pay_amount'=>$arg['amount']);
           //回调通知app
           $ret= $this->notify($arr,$pay_status);
            if ($ret == true) {
                $replay=array("responseCode"=>"0000");
                echo json_encode($replay);
                exit;
            } else {
                 publicfun::logWrite(' 订单'.$arg['order_id'].'通知游戏端回调失败 原因是'.json_encode($result),'','notify');
                echo 'Notify app that callback failed ';
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
		//var_dump($data);exit;
        $dataString=json_encode($data);
        $postAppNotiyUrl=  $app_notify_url;
        //var_dump($postAppNotiyUrl,$dataString);exit;
		
        //开始通知app
         publicfun::logWrite(' 订单回调 '. $arg['order_id'].' 开始通知app端 信息为 '.$dataString,'','callback_to_app');

        $ret = publicfun::requestPost($postAppNotiyUrl,$dataString);
		//var_dump($ret);exit;
        $res=json_decode($ret,true);
   

       //接收app返回数据 
       publicfun::logWrite(' 订单'. $arg['order_id'].'app端返回信息 '.$ret,'','callback_from_app');


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
                $b.=$KEY;
                $signData=md5($b);
                //$signData=strtoupper($signData);
                
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
                        'requestId'     =>  $order_id,
                        'orgId'         =>  $this->ORGID,
                        'productId'     =>  '9701',
                        'dataSignType'  => 1,
                        'timestamp'     =>  date('YmdHis',time())
                        ); //验证数据结构
        //业务参数
        $busMap=array('order_id' => $order_id);
        $businessData=json_encode($busMap);
        $desKey=$this->DES_KEY;
        // var_dump($this->DES_KEY);exit;
        $businessData =  (new lib\DesUtils)->encrypt($businessData,$desKey);//加密
        $businessData = urlencode($businessData); //加密结果 UrlEncode
        $data['businessData']=$businessData;
        ksort($data);
        //dump($mykeyarr);
        $lastvalue=end($data);
        $b='';
        foreach($data as $key=>$value){
            if($value==$lastvalue){
                $b.=$key.'='.$value;
            }else{
                $b.=$key.'='.$value.'&';
            }
        }
        //echo end($data).'<br/>';
        $b.=$this->KEY;
        //echo $b.'<br/>';
        $signData=md5($b);
        $signData=strtoupper($signData);
        $data['signData']=$signData;
        $posturl=$this->_api_uri . '/query/invoke';
        
        $pageContents =(new lib\HttpClient())->quickPost($posturl, $data); 

        $result=json_decode($pageContents,true);
      
        if(!isset($result["key"])){
              publicfun::logWrite(' 订单'.$order_id.'查询失败 原因是'.json_encode($result),'','query');
               $returnArr=array('code'=>'1000','msg'=>'查询失败');
               return $returnArr;
              // publicfun::retAppPayJson('0009','请求出错');
        }
         //var_dump($result);exit;
        $errCode = $result["key"];

        header("Content-Type: text/html; charset=utf-8");
        $errMsg = $result["msg"];
        if($errCode=="00" || $errCode=="05"){
				
                $qodeUrl=json_decode($result['result'],true);
			   	$pay_code=$this->get_pay_code($qodeUrl['payment_status']);
                //$retHtml = $qodeUrl['url'];
                $returnArr=array('code'=>'000','查询成功','status'=>$pay_code,'amount'=>$qodeUrl['amount'],'plat_id'=>$qodeUrl['plat_order_sn']);
                   //接收app返回数据 
				
               publicfun::logWrite(' 订单查询'. $order_id.'发送给app端信息'.json_encode($returnArr),'','callback_to_app');
               //	var_dump($returnArr);exit;  
			    return $returnArr;
         
        }else{
           $returnArr=array('code'=>'1000','msg'=>'查询失败');
		     return $returnArr;
        }

      
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