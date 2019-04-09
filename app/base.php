<?php

namespace app;
use app\publicfun;
use  app\core\Db;

class base {
   

    public static function run(){
        header('Content-type:text/json;charset=utf-8');
        $data=$_REQUEST;
        $channelArr='';
        if($data['pay_type']=='pay'){
                $channelArr=core\pay::run();
        }elseif($data['pay_type']=='query'){
                $channelArr=core\pay::queryrun();

        }else{
                publicfun::retJson('1001','无权访问');
        }

        $callFun=$channelArr['method'];
        $channel=$channelArr['channel'];
        $payType=$channelArr['pay_type'];
        $tid=$channelArr['tid'];
  
        try{
        
             $commPay = new commPay($channel,$data, $callFun,$payType,$tid); 
             $commPay->$callFun();
        }catch(\Exception $e){
             echo $e->getMessage();exit;
              publicfun::retJson('0003','通道不存在');
        }
     
   }






  //网站回调方法
   public static function  websiteNotiy(){
      $data=$_REQUEST;
  //  var_dump($data);exit;
      $func='';
      if(isset($data['callback']) && !empty($data['callback'])){
          $payClass=$data['callback'];
          unset($data['callback']); //回调参数
          $callBackPaGrams=$data;
      }else{
          return ;
      }
    
      try{
        
            $commPay = new commPay($payClass,$callBackPaGrams,'websiteNotiy');
        }catch(\Exception $e){
              publicfun::retJson('0003','通道不存在');
        }
      
   }
 





  //---------------------------------
       //快捷支付科技回调
   public static function  kjwebsiteNotiy(){
      $data=$_REQUEST;
      $data['callback']='kuaijie';
   // var_dump($data);exit;
      $func='';
      if(isset($data['callback']) && !empty($data['callback'])){
          $payClass=$data['callback'];
          unset($data['callback']); //回调参数
          $callBackPaGrams=$data;
      }else{
          return ;
      }
    
      try{
        
            $commPay = new commPay($payClass,$callBackPaGrams,'websiteNotiy');
        }catch(\Exception $e){
              publicfun::retJson('0003','通道不存在');
        }
      
   }
}

