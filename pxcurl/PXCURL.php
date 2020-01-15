<?php

class PXCURL
{
    private $__timeOut = 30;// 默认请求30秒未响应超时

    public function get($url)
    {
        $ch  = curl_init();// 初始化一个 cURL 对象

        if ( stripos($url,"https://") != false )
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);// 设置你需要抓取的URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);// 设置 cURL 参数，要求结果保存到字符串中还是输出到屏幕上
        
        $result = curl_exec($ch);
        $aStatus = curl_getinfo($ch);
        curl_close($ch);
        if(intval($aStatus["http_code"])==200){
            return $sContent;
        }else{
            return false;
        }
    }

    private function __return($status, $data, $code, $msg)
    {
        $arr = array(
            'status' => $status,
            'data'   => $data,
            'code'   => $code,
            'msg'    => $msg
        );
        return $arr;
    }

    private function __success($data, $code, $msg)
    {
        return __return(true, $data, $code, $msg);
    }

    private function __error($data, $code, $msg)
    {
        return __return(false, $data, $code, $msg);
    }
}