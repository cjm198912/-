<?php
namespace app\core;
use app\publicfun;
use  app\core\Db;
class query{
	  public static function run(){
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
            $sq2='select name_us from cmf_area where id='.$res1['tid'];
            $res2=$db->getOne( $sql2);
            var_dump($res2);exit;

        
	  }
}