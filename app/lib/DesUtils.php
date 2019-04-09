<?php
namespace app\lib;
class DesUtils {
   function encrypt($str,$key){
     $size = mcrypt_get_block_size(MCRYPT_DES,MCRYPT_MODE_ECB);
     $str = $this->PaddingPKCS7($str);
     $key = str_pad($key,8,'0');
     $td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
     @mcrypt_generic_init($td, $key, '');
     $data = mcrypt_generic($td, $str);
     mcrypt_generic_deinit($td);
     mcrypt_module_close($td);
     $data = base64_encode($data);
     return $data;
   }
    function decrypt($encrypted,$key){
     $encrypted = base64_decode($encrypted);
     $key = str_pad($key,8,'0');
     $td = mcrypt_module_open(MCRYPT_DES,'',MCRYPT_MODE_ECB,'');
     $ks = mcrypt_enc_get_key_size($td);
     @mcrypt_generic_init($td, $key, '');
     $decrypted = mdecrypt_generic($td, $encrypted);
     mcrypt_generic_deinit($td);
     mcrypt_module_close($td);
     $y=$this->pkcs5_unpad($decrypted);
     return $y;
   }
   function PaddingPKCS7($data) {
     $block_size = mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_ECB);
     $padding_char = $block_size - (strlen($data) % $block_size);
     $data .= str_repeat(chr($padding_char),$padding_char);
     return $data;
   }
}
$businessData='1234';
$_des_key='BhjFCwGyZHak4QndmGdACQ7QWKeKWT26';
$des = new DesUtils();//（秘钥向量，混淆向量）
//echo $ret = $des->encrypt($businessData,$_des_key);//加密字符串
//echo DesUtils::encrypt($businessData,$_des_key);//加密
?>
