 <?php
namespace app;


class changPay implements iPay{

    /**
    http://47.101.178.117:2666
    账号：cp001
    密码：123456
    商户信息，接口文档后台获取
    需要绑定谷歌验证码
    绑定步骤： 打开微信搜索“二级验证码”第一个，直接使用，再商户后台点击进行绑定操作，如果要绑定多个验证请同时扫码一起绑定
     */
    private    $ORGID='0820193706210377'; //机构代码
    private    $MERID='082019370621036629'; //商户号
    private    $DES_KEY='TBjdpptEpTYKzczB4EiXwbXPb4XZ4jR5';
    private    $KEY="Kz7kPYKmzccwWGYB3skBEGiwYzPkE4D6";
    private    $payGateway='http://api.ys666999.net/open-gateway/trade/invoke';
    private    $notifyKey     ='0c3a436a0b867f854693cd7f36c85d97'; //回调秘钥
    private    $merchantId = 'pzhctagomk';
    private    $merchantKey = '0c3a436a0b867f854693cd7f36c85d98';
    private    $host = 'https://b-sdk.zumire.com/';
    private    $_notify="http://zhifu.com/basePay/notify.php?callback=chang"; //本站回调地址
    private    $_appNotify="http://zhifu.com/basePay/notify.php?callback=chang"; //本站回调地址


//'out_trade_no',pay_type 'total_amount','subject','body','notify_url','return_url','timeout_express','version','timestamp','sign_type',

  
    public function pay($arg,$payType){
        $res['code']='0';
        $res=$this->websitpay($arg,$payType);
        if($res['code']=='0'){
              publicfun::retAppPayJson('0000','success',[
                  'pay_status'=>'ok',
                  'url'=>$res['data']['url'],
            ]);
        }else{
            publicfun::logWrite('用户 '.$arg['userId'].' 支付失败 原因是'.'tt');
            publicfun::retAppPayJson('0007','支付失败');
        }
    }

     public function websitpay($arg,$payType){

        // var_dump($arg,$payType);exit;
        $uid = $arg['userId'];
        $pay_type = $payType;//[Ali,AliWeb,WeChat]
        $amount = $arg['pay_amount'];
        $order_no = $arg['order_id'];
        

        publicfun::logWrite('用户 '.$uid.' 订单 '.$order_no.' 发起支付');
        $par = array();


        $pay_type_arr = array(
            'wxpayscan' =>'0101',
            'aipayscan' =>'0201',
        );
        $goods_info='jb';
        $pay_type = $pay_type_arr[$pay_type];//ali

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
                        'amount'        =>  $amount*100, //交易金额-单位分
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
            // var_dump($posturl,$data);exit;
        $pageContents = (new lib\HttpClient())->quickPost($posturl, $data); 
        // var_dump($pageContents);exit;
        $result=json_decode($pageContents,true);
       
        if(!isset($result["key"])){
              publicfun::logWrite('用户 '.$uid.' 订单'.$order_no.'支付失败 原因是'.json_encode($result));
              return false;
              // publicfun::retAppPayJson('0009','请求出错');
        }
        $errCode = $result["key"];

        header("Content-Type: text/html; charset=utf-8");
        $errMsg = $result["msg"];
        if($errCode=="00" || $errCode=="05"){
               $qodeUrl=json_decode($result['result'],true);
                $retHtml = $qodeUrl['url'];
               return array('code'=>'0','data'=>['url'=> $retHtml]);
         
        }else{
           return false;
        }
       
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

    public function notify(){
//'out_trade_no','total_amount','subject','body','notify_url','return_url','timeout_express','version','timestamp','sign_type',

        $ret = array();
        $arg = $_POST;
        $sign = $arg['sign'];
        unset($arg['sign']);
        $signC = $this->sign($arg);
        if($sign != $signC){
            ob_clean();
            echo 'fail';
            exit;
        }

        $ret['trade_status'] = 'SUCESS';//交易成功或者失败
        $ret['trade_no'] = $arg['mch_order'];
        $ret['out_trade_no'] = $arg['mch_order'];
        $ret['total_amount'] = $arg['amt']/10;
        $ret['channel_code'] = 'chang';//渠道编号

        return $ret;

    }

    public function checkAppSign($arg){
                       
    }
    //本站回调方法 
    public function  websiteNotiy($arg){
        // $sign=$data['sign_data'];
        // unset($data['sign_data']);
        // $qm=replaySign($data);
        
        // logWrite('qm--'.$qm);
        // if( $qm==$sign){
        //     $result = true;
        // } else{
        //     $result = false;
        // }

        // if (!$result){
        //     logWrite('sign error error 1');
        //     echo ('error 1');
        // }else{
        //     logWrite('签名校验成功'); 
           
           $arg=array('order_id'=>1111,'plat_id'=>22222,'pay_amount'=>9,);
           $ret= $this->NotiyRePlyToApp($arg);
 
            if ($ret == true) {
                $replay=array("responseCode"=>"0000");
                echo json_encode($replay);
                exit;
            } else {
                echo 'Notify app that callback failed ';
            }
    }
   //本站回调通知App方法
   public function  NotiyRePlyToApp($arg){    
        $sign=$this->notifyToAppSign($arg);
        $data = array(
            'code' =>'0000',
            'msg' =>'success',
            'data' => [
                'status'=>'ok',//支付的状态 ok 成功 error 失败
                'order_id'=>$arg['order_id'],//用户订单
                'plat_id'=>$arg['plat_id'],//平台订单
                'pay_amount'=>$arg['pay_amount'],//实际付款金额
                'sign'=>$sign,//实际付款金额
            ],
        );
        $dataString=json_encode($data);
        $configArr=$this->config();
        // var_dump($configArr);
        $postAppNotiyUrl=$configArr['app_notify'];
        // var_dump($postAppNotiyUrl,$data);exit;
        $ret = publicfun::requestPost($postAppNotiyUrl,$dataString);
        // var_dump($ret);exit;
        //json_encode(array('error'=>0000,'msg'=>'ok'));
        $res=json_decode($ret,true);
               // var_dump($res);exit;
        if(isset($res['error']) && $res['error']=='0000'){
            return true;
        }else{
            return false;
        }
   }




    public function query($arg){

        $post = $_POST;
        $arg = array();
        $arg['mch_id'] = $this->merchantId;
        $arg['mch_order'] = $post['trade_no'];
        $arg['created_at'] = $post['timestamp'];
        $arg['sign_type'] = 'md5';

        $arg['mch_key'] = $this->merchantKey;
        $arg['sign'] = $this->sign($arg);
        unset($arg['mch_key']);

        $retPost = publicfun::requestPost($this->host.'api/fetch_order.api',http_build_query($arg));

        $retPost = json_decode($retPost,true);
        if(isset($retPost['code']) && $retPost['code'] == '1' && $retPost['data']['status'] == 2) {
            $ret['trade_status'] = 'SUCESS';//交易成功或者失败
            $ret['trade_no'] = $ret['mch_order'];
            $ret['out_trade_no'] =  $ret['mch_order'];
            $ret['total_amount'] = $ret['amt'] / 10;
            $ret['channel_code'] = 'chang';//渠道编号
        }else{
            $ret['trade_status'] = 'FAIL';//交易成功或者失败
            if($retPost['data']['status'] == 1){
                $ret['msg'] = '未支付';
            }else {
                $ret['msg'] = $ret['msg'];
            }
        }


        return $ret;
    }


   //通知app回调接口
    public function notifyToAppSign($params){

        ksort($params);
        $string='';

        foreach ($params as $k=>$v){
            if($v!=''){
                if($v!=''&&$v!=null){
                    $string=$string.$k.'='.$v.'&';
                }
            }
        }
        $string.=$this->notifyKey;
        $sign=(md5($string));
        return $sign;
    }

    /*
     * 获取支持的支付方式以及金额
     * ali_h5
     * ali_qr
     * wx_h5
     * wx_qr
     */

    public function config(){
        $ret= array(
            'pay_pargrams'=>['pay_amount','userId','order_id','notify_url','token','channel'],//支付必备参数
            'pay_fields_pargrams'=>['ip'],//支付扩展参数

             'query_pargrams'=>['order_id','userId','token'],//查询必备参数
             'query_fields_pargrams'=>['ip'],//查询扩展参数

            'pay_type' => 'ali_h5',//支持的支付方式
            'md5_key'=>'4f115d9b51f5c3e7bea3cd80fdb82f42', //支付验签秘钥
          
            'app_notify'=>'', //app回调地址
            );

        return $ret;
    }

    public function checkParamsExists($params)
    {   
  
        if (empty($params)) {
             publicfun::retJson('0013','请求参数不为空!');
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

}