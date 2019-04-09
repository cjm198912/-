<?php
namespace app;
use  app\core\pay;

class commPay {

    private $singFreeMethod=['websiteNotiy']; //免签方法 yongshun-alipay-h5-websitnotiy

/*
        $channel 渠道 yongshun
        $tid 通道id  
        $payType pay   query
        $payMentod alipayh5
*/ 
    function __construct($channel,$data,$payMethod,$payType='',$tid){

        $ref = new \ReflectionClass('app\\'.$channel.'Pay');
        $this->ipay = $ref->newInstanceArgs();
        $this->payMethod=$payMethod;
        //判断执行方法是否为免签方法 
        if(in_array($payMethod, $this->singFreeMethod)){
               $this->ipay->$payMethod($data);
               exit;
        }

        $this->checkParamsExists($data);//检测函数必传参数是否存在

        $configArray=$this->ipay->config();
        $mySign=$this->checkSign($data,$configArray['md5_key']);
         //var_dump($mySign);exit;
        if(trim($data['token'])!=$mySign){
            publicfun::retJson('0004','签名不合法');
        }

      if($payMethod=='pay'){
        error_reporting(E_ERROR | E_WARNING | E_PARSE);
        // echo 'h';exit;
              //记录订单支付订单
             
              try{
                 $res=pay::recordOrder($data,$tid);
                 if($res){
                     $this->ipay->$payMethod($data,$payType,$tid);
                 }
              }catch(\Exception $e){
                      publicfun::retJson('0035','记录订单失败');
                      publicfun::logWrite('用户 '.$arg['userId'].' 订单 '.$arg['order_id']. ' 记录失败','','recordOrder');
               }
         
      }
      // var_dump($payType);exit;
      //     echo 'eh';exit;
      //查询
     $this->ipay->$payMethod($data,$payType,$tid);
 
    }



/**
 * @brief 检测函数必传参数是否存在
 * @param $params array 关联数组 要检查的参数
 * @param array $fields array 索引数组 额外要检查参数的字段
 * @return bool


 * @throws Exception
 */
public function checkParamsExists($params)
{   
  
    if (empty($params)) {
         publicfun::retJson('0005','请求参数不能为空!');
    }
    $params = is_array($params) ? $params : [$params];
    $configParams=$this->config();
    // var_dump($this->payMethod);exit;
    $mod=$configParams[$this->payMethod.'_'.'pargrams']; //对应请求方法必要参数
    $fields=$configParams[$this->payMethod.'_'.'fields_pargrams']; //扩展参数
    if ($fields) {
        $fields = array_flip($fields);
        $params = array_merge($params, $fields);
    } 
 
    foreach ($mod as $mod_key => $mod_value) {
        if (!array_key_exists($mod_value, $params)) {
            //var_dump('canshu '.$mod_value.' queshi');
                   publicfun::retJson('0006','参数缺失!');
        }
    }
 
    return true;

}


    public function checkSign($data,$key){
       return  publicfun::sign($data,$key);
    }

     public function checkAppSign($arg){
                       
    }

  



    public function query($arg = array()){
     
    }



    public function config(){
        $configs = [
             'pay_pargrams'=>['pay_amount','userId','order_id','notify_url','token','app_id','timestamp'],//支付必备参数
             'pay_fields_pargrams'=>['ip'],//支付扩展参数

             'query_pargrams'=>['order_id','token','pay_type'],//查询必备参数
             'query_fields_pargrams'=>['ip'],//查询扩展参数
        ];
        return $configs;
    }


}