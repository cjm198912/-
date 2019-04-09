<?php
/**
 * 密钥对生成https://rsatool.org/
 * 密钥格式:PKCS#8 密钥长度：1024
 */
class Handle_RSA
{
    
    /**
     * ras签名
     * @param $data
     * @param $code
     */
     function get_sign($data, $private_key,$code = 'base64'){
        $ret = false;
		$str = chunk_split($private_key, 64, "\n");
        $key = "-----BEGIN RSA PRIVATE KEY-----\n$str-----END RSA PRIVATE KEY-----\n";
		
        if (openssl_sign($data, $ret, $key,OPENSSL_ALGO_SHA1)){
            $ret = $this->_encode($ret, $code);
        }
        return $ret;
    }
    /**
     * 编码格式
     * @param $data
     * @param $code
     */
    function _encode($data, $code){
        switch (strtolower($code)){
            case 'base64':
                $data = base64_encode(''.$data);
                break;
            case 'hex':
                $data = bin2hex($data);
                break;
            case 'bin':
            default:
        }
        return $data;
    }

    /* 
    验证签名： 
    data：原文 
    signature：签名 
    publicKeyPath：公钥 
    返回：签名结果，true为验签成功，false为验签失败 
    */  
    function verity($data, $signature, $publicKey)  
    {  
	    $ret = false;
		$str = chunk_split($publicKey, 64, "\n");
        $key = "-----BEGIN PUBLIC KEY-----\n$str-----END PUBLIC KEY-----\n";
        $pubKey = $key;  
        $res = openssl_get_publickey($pubKey);  
        $result = (bool)openssl_verify($data, base64_decode($signature), $res,OPENSSL_ALGO_SHA1);  
        openssl_free_key($res);  
      
        return $result;  
    }  
    
}