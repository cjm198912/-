<?php

namespace app;




class publicfun {

    public static function sign($params,$key){
         unset($params['token']);
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

    public static function get_all_header()
    {
        // 忽略获取的header数据。这个函数后面会用到。主要是起过滤作用
        $ignore = array('host','accept','content-length','content-type');

        $headers = array();
        //这里大家有兴趣的话，可以打印一下。会出来很多的header头信息。咱们想要的部分，都是‘http_'开头的。所以下面会进行过滤输出。
        /*    var_dump($_SERVER);
            exit;*/

        foreach($_SERVER as $key=>$value){
            if(substr($key, 0, 5)==='HTTP_'){
                //这里取到的都是'http_'开头的数据。
                //前去开头的前5位
                $key = substr($key, 5);
                //把$key中的'_'下划线都替换为空字符串
                $key = str_replace('_', ' ', $key);
                //再把$key中的空字符串替换成‘-’
                $key = str_replace(' ', '-', $key);
                //把$key中的所有字符转换为小写
                $key = strtolower($key);

                //这里主要是过滤上面写的$ignore数组中的数据
                if(!in_array($key, $ignore)){
                    $headers[$key] = $value;
                }
            }
        }
//输出获取到的header
        return $headers;

    }


    public static function getHead($name){

        $heard = publicfun::get_all_header();


        return $heard[$name];
    }

    public static function requestGet($url, $timeout=8) {

        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_HEADER, true);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1');
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, false);

        $result = curl_exec($curlHandle);
        $info = curl_getinfo($curlHandle);
        $return = trim(substr($result, $info['header_size']));

        curl_close($curlHandle);

        return $return;
    }


    public static function requestPost($url, $params, $timeout=8) {

        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_HEADER, true);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($curlHandle, CURLOPT_POST, true);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1');
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, false);

        $result = curl_exec($curlHandle);
        $info = curl_getinfo($curlHandle);
        $return = trim(substr($result, $info['header_size']));

        curl_close($curlHandle);

        return $return;
    }

    public static function retJson($code,$msg,$data = array()){

        $data = array(
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        );

        foreach ($data as $key => $value) {
              if(empty($data[$key])){
                     unset($data[$key]);
              }
        }

        echo json_encode($data);
        exit;
    }

        public static function retAppPayJson($code,$msg,$data = array()){
            $data = array(
                'code' => $code,
                'msg' => $msg,
                'data' => $data,
                );
            echo json_encode($data);
            exit;
    }

    public static function retAppQueryJson($code,$msg,$data = array()){
            $data = array(
                'code' => $code,
                'msg' => $msg,
                'data' => $data,
                );
            echo json_encode($data);
            exit;
    }



//query 文件
 //  public static  function logWrite($info,$method='playlog'){
 //       $logFile = fopen("logs/".$method.date('Ymd').".log11", "a+");
 //       fwrite($logFile, date('Y-m-d H:i:s').$info."\r\n");
  //      fclose($logFile);
  //  }

  
  public static  function logWrite($info,$method='pay',$func_name='',$tongdao=''){
       ini_set('date.timezone','Asia/Shanghai'); // 'Asia/Shanghai' 为上海时区 
       if(APP_LOG==true){
             if(!empty($func_name)){
            $starMes=date('Y-m-d H:i:s').PHP_EOL."---------------------------- \n";
            $logdir=!empty($tongdao)?'logs/'.date('Y-m-d').'/'.$tongdao.'/':$logdir='logs/'.date('Y-m-d').'/';
            if(!is_dir($logdir)){
                  mkdir($logdir,777);
            }
            $fileName=$func_name.date('Ymd').".log";
            file_put_contents($logdir.$fileName, $starMes.$info.PHP_EOL.'---------------------------- '.PHP_EOL,FILE_APPEND);
          
        }else{
              $logFile = fopen("logs/paylog".date('Ymd').".log11", "a+");
             fwrite($logFile, date('Y-m-d H:i:s').$info."\r\n");
             fclose($logFile);
        }
       } 
    }
  
    //获取13位的时间戳
public static  function getMillisecond() {
    list($t1, $t2) = explode(' ', microtime());
    return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
}
//获取随机字符串
   public static function str_rand($length = 13, $char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
      if(!is_int($length) || $length < 0) {
         return false;
    }

    $string = '';
     for($i = $length; $i > 0; $i--) {
         $string .= $char[mt_rand(0, strlen($char) - 1)];
     }

     return $string;
 }

}