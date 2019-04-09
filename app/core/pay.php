<?php
namespace app\core;
use app\publicfun;
use  app\core\Db;
class pay{
	  public static function run(){
		  	header('Content-type:text/json;charset=utf-8');
		  	$data=$_REQUEST;
		  	if(empty($data)){
	              publicfun::retJson('1001','无权访问');
	         }
            if(!isset($data['app_id']) || empty($data['app_id'])){
            	 publicfun::retJson('0020','appId不能为空');
            }
            //取对应appid 判断是否禁用 或 是否配置通道
              $db=new Db();
              $sql='select * from cmf_app_manager where apid='.$data['app_id'];

                 //取通道
              $res=$db->getOne( $sql);
            if($res){
                 if($res['status']==0){
                 	publicfun::retJson('0021','该app未启用');
                 }

                 if(empty($res['tdid'])){
                 	publicfun::retJson('0022','该app未配置通道');
                 }

                    $sql='select * from cmf_area where id='. $res['tdid'];
                 //取通道
                  $tdinfo=$db->getOne( $sql);
     
                   if($tdinfo){
                   			 if($tdinfo['status']==0){
			                 	       publicfun::retJson('0025','该通道未启用');
			                     }

                        //取出app信息 存储常量
                                // var_dump($tdinfo);exit;
                        return  array('channel'=>$tdinfo['name_us'],'method'=>'pay','pay_type'=>$tdinfo['name_short'],'tid'=>$tdinfo['id']);

			             
                    }else{
                    	      publicfun::retJson('0003','通道不存在');
                    }  

              
            }else{
            	    publicfun::retJson('0023','app 不合法');
            }
	  }

   //定义商户号 回调秘钥 秘钥 本站回调地址 为常量
    // public function defineConst($const){
    //       define('SITE_NOTIFY_URL',$const['notify_url'],true);     //本站回调地址
    //       define('MERCH_NUM',$const['merch_no'],true);             //商户号
    //       define('THIRD_SIGN_KEY',$const['third_sing_key'],true);  //第三方验签秘钥
    //       define('THIRD_CALLBAK_KEY',$const['call_back_key'],true);//第三方回调秘钥
    //       define('ORG_ID',$const['org_id'],true);     //机构代码
    // }
       //发起查询判断
	   public static function queryrun(){
		  	header('Content-type:text/json;charset=utf-8');
		  	$data=$_REQUEST;
		  	if(empty($data)){
	              publicfun::retJson('1001','无权访问');
	         }
       
            //取对应订单 对应的渠道id 
            $db=new Db();
  
            $sql1='select tid from cmf_channel_order where oid='.$data['order_id'];

             //取通道
            $res1=$db->getOne( $sql1);
            $sql2='select * from cmf_area where id='.$res1['tid'];
            $res2=$db->getOne( $sql2);
          if(!empty($res2['name_us'])){
              return  array('channel'=>$res2['name_us'],'method'=>'query','pay_type'=>$res2['name_short'],'tid'=>$res2['id']);
          }
            publicfun::retJson('0003','通道不存在');
        
	  }


      //发起支付 记录订单 $pay_type 请求方法 pay query
	  public static  function recordOrder($data,$tid=''){
	  	         date_default_timezone_set('PRC'); //设置中国时区 
                 $res['oid']=$data['order_id'];
                 $res['notify_url']=$data['notify_url'];
                 $res['pay_amount']=$data['pay_amount'];
                 $res['return_url']=!empty($data['return_url'])?$data['return_url']:'';
                 $res['app_id'] =$data['app_id'];
                 $res['tid']=$tid;
                  $res['create_time']=$data['timestamp'];
                 $db=new Db();
                 $res=$db->name('channel_order')->insert($res);
                 if($res){
                        return true;
                 }
                 return false;
              
	  }


	  public static function  getOrderAppNotifyUrl($oid=''){
               $db=new Db();
               $sql='select notify_url from cmf_channel_order where oid='.$oid;
               $res=$db->getOne( $sql);
               if(!empty($res['notify_url'])){
                      return $res['notify_url'];
               }
               return '';

	  }




}