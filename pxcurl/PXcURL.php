<?php

/**
 * cURL请求类
 * @author peak xin<xinyflove@gmail.com>
 * @create 2020-01-16
 */


class PXcURL
{
    private $__timeOut = 30;// 默认请求30秒未响应超时
    private $__useCert = false;// 默认不使用证书
    private $__certPath = null;// *cert.pem文件路径
    private $__keyPath = null;// *key.pem文件路径

    private function __init($url)
    {
        $ch  = curl_init();// 初始化一个 cURL 对象

        curl_setopt($ch, CURLOPT_URL, $url);// 设置你需要抓取的URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);// 设置 cURL 参数，要求结果保存到字符串中还是输出到屏幕上
        curl_setopt($ch, CURLOPT_HEADER, FALSE);// 头文件的信息不输出

        if ( stripos($url,"https://") !== false )
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);// 规避ssl的证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);// host验证
            //curl_setopt($ch, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1,不建议设置此项
        }
        
        if ( $this->__useCert )
        {
            // 设置证书,使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, $this->__certPath);
            curl_setopt($ch,CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, $this->__keyPath);
        }

        return $ch;
    }

    /**
     * url get请求
     * @param $url [请求地址]
     * @param int $httpCode [返回状态码]
     * @return mixed
     * @throws \Exception
     * @author peak xin
     */
    public function get($url, &$httpCode = 0)
    {
        return $this->request('GET', $url, $httpCode);
    }

    /**
     * url post请求
     * @param $url [请求地址]
     * @param int $httpCode [返回状态码]
     * @param array $data [请求参数]
     * @param array $header [请求头部]
     * @return mixed
     * @throws \Exception
     * @author peak xin
     */
    public function post($url, &$httpCode = 0, $data = array(), $header = array())
    {
        return $this->request('POST', $url, $httpCode, $data, $header);
    }

    /**
     * url put请求
     * @param $url [请求地址]
     * @param int $httpCode [返回状态码]
     * @param array $data [请求参数]
     * @param array $header [请求头部]
     * @return mixed
     * @throws \Exception
     * @author peak xin
     */
    public function put($url, &$httpCode = 0, $data = array(), $header = array())
    {
        return $this->request('PUT', $url, $httpCode, $data, $header);
    }

    /**
     * url 请求
     * @param $method [请求方式:GET,POST,PUT]
     * @param $url [请求地址]
     * @param int $httpCode [返回状态码]
     * @param array $data [请求参数]
     * @param array $header [请求头部]
     * @return mixed
     * @throws \Exception
     * @author peak xin
     */
    public function request($method, $url, &$httpCode = 0, $data = array(), $header = array())
    {
        $method = strtoupper($method);
        $ch = $this->__init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->__timeOut);// 响应超时时间

        if ($method == 'POST')
        {
            curl_setopt($ch, CURLOPT_POST, TRUE);
        }

        if ($method == 'PUT')
        {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }

        if (in_array($method, array('POST', 'PUT')))
        {
            $json = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

            if (empty($header))
            {
                $header = array('Content-Type: application/json');
            }
        }

        if (!empty($header))
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        $result = curl_exec($ch);

        if (!$result)
        {
            $errNo = curl_errno($ch);
            throw new \Exception('request url failed, curl error code: '. $errNo);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return $result;
    }

    /**
     * 设置响应超时时间
     * @param int $second [单位秒]如果为0则使用默认超时时间
     * @return $this
     * @author peak xin
     */
    public function timeOut($second = 0)
    {
        if ( $second > 0 )
        {
            $this->__timeOut = $second;
        }
        
        return $this;
    }

    /**
     * 设置使用证书参数
     * @param $certPath [*cert.pem路径]
     * @param $keyPath [*key.pem路径]
     * @return $this
     * @throws \Exception
     * @author peak xin
     */
    public function useCert($certPath, $keyPath)
    {
        if ( !file_exists($certPath) )
        {
            throw new \Exception('not found *cert.pem file.');
        }
        if ( !file_exists($keyPath) )
        {
            throw new \Exception('not found *key.pem file.');
        }

        $this->__useCert = true;
        $this->__certPath = $certPath;
        $this->__keyPath = $keyPath;
        
        return $this;
    }
}