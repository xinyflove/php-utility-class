<?php

namespace org\payments;

/*银联商务小程序支付*/
use app\common\model\BillPayments;
use app\common\model\UserWx;

class weixinappupacp implements Payment {

    private $debug = false;

    private $config = [
        'mid' => '898340149000005',// 商户号
        'tid' => '88880001',// 终端号
        'instMid' => 'MINIDEFAULT',// 机构商户号
        'msgSrc' => 'WWW.TEST.COM',// 消息来源
        'msgSrcId' => '3194',// 来源编号
        'secret_key' => 'fcAmtnx7MwismjWNhNKdHC44mNXtnEQeJkRrhKJwyrW2ysRR',// MD5钥
        'url' => 'https://qr-test2.chinaums.com/netpay-route-server/api/',// 接口链接
        'gateway_rate' => 0// 网关费率
    ];

    function __construct($config)
    {
        if(!$this->debug)
        {
            $c_keys = array_keys($this->config);
            foreach($c_keys as $v)
            {
                $config[$v] && $this->config[$v] = $config[$v];
            }
        }
    }

    /**
     * 支付
     * @param $paymentInfo
     * @return array|mixed
     */
    function pay($paymentInfo)
    {
        $result = ['status' => false, 'data' => [], 'msg' => ''];

        if(isset($paymentInfo['params']) && $paymentInfo['params'] != "")
        {
            $params = json_decode($paymentInfo['params'],true);
            if(!isset($params['trade_type'])){
                $params['trade_type'] = "MINI";
            }

            $trade_type_re = $this->__getTradeType($params);
            if(!$trade_type_re['status']){
                return $trade_type_re;
            }
            $trade_type = $trade_type_re['data'];

        }
        else
        {
            $result['msg'] = '支付参数不能为空';
            return $result;
        }

        $data['tradeType'] = $trade_type;// 交易类型

        if($trade_type == 'MINI')
        {
            // 取open_id
            $openid_re = $this->__getOpenId($paymentInfo['user_id'],$params['trade_type']);
            if(!$openid_re['status']){
                return $openid_re;
            }
            $data['subOpenId'] = $openid_re['data'];
        }
        
        $data['msgType'] = 'wx.unifiedOrder';// 消息类型[微信:wx.unifiedOrder支付宝:trade.create]
        $data['requestTimestamp'] = date('Y-m-d H:i:s', time());// 报文请求时间，格式yyyy-MM-dd HH:mm:ss
        $data['msgSrc'] = $this->config['msgSrc'];// 消息来源
        $data['mid'] = $this->config['mid'];// 商户号
        $data['tid'] = $this->config['tid'];// 终端号
        $data['instMid'] = $this->config['instMid'];// 机构商户号
        //$data['platformAmount'] = 0;// 平台商户分账金额，若分账标记传，则分账金额必传
        $data['merOrderId'] = $this->config['msgSrcId'] . $paymentInfo['payment_id'];// 商户订单号，商户自行生成，注意长度不要超过32位
        $data['totalAmount'] = $paymentInfo['money'] * 100;// 支付总金额，单位分
        $data['notifyUrl'] = url('b2c/Callback/pay',['code'=>'weixinappupacp'], 'html', true);
        $data['sign'] = $this->__makeSign($data);// 签名

        pay_log('weixinappupacp支付请求参数：', $data);
        pay_log('weixinappupacp支付请求链接：', $this->config['url']);
        $json = json_encode($data);
        $response = $this->__postJsonCurl($json, $this->config['url'], false, 10);
        $re = json_decode($response, true);
        pay_log('weixinappupacp支付响应参数：', $re);
        if(empty($re)){
            $result['msg'] = $response;// 把错误信息都返回到前台吧，方便调试。
            return $result;
        }

        if($re['errCode'] != 'SUCCESS')
        {
            // 平台下单接口请求错误
            $result['msg'] = $re['errMsg'];
            $result['data'] = $re['errCode'];
            return $result;
        }

        $result['status'] = true;
        $result['data'] = $re;

        return $result;
    }

    /**
     * 异步回调
     * @return array
     */
    function callback()
    {
        $result = ['status' => false, 'data' => [], 'msg' => 'FAILED'];
        trace(input('post.'), 'weixinappupacp');
        //获取通知的数据
        $data = input('post.');
        pay_log('weixinappupacp回调请求参数：', $data);

        if($data)
        {
            $sign = $data['sign'];
            unset($data['sign']);
            if($sign == $this->__makeSign($data))
            {
                $result['msg'] = 'SUCCESS';
                $result['status'] = true;
                $result['data']['payment_id'] = substr($data['merOrderId'], 4);
                $result['data']['money'] = $data['totalAmount']/100;
                $result['data']['code'] = 'weixinappupacp';

                if($data['status'] == 'TRADE_SUCCESS')
                {
                    $result['data']['status'] = 2;// 1未支付，2支付成功，3其他
                    $result['data']['payed_msg'] = $data['billFundsDesc'];
                    $result['data']['trade_no'] = $data['targetOrderId'];
                    $result['data']['gateway_rate'] = $this->config['gateway_rate'];
                }
                else
                {
                    //如果未支付成功，也更新支付单
                    $result['data']['status'] = 3;// 1未支付，2支付成功，3其他
                    $result['data']['payed_msg'] = $data['status'].':'.$data['billFundsDesc'];
                    $result['data']['trade_no'] = '';
                }
            }
        }

        return $result;
    }

    /**
     * 支付结果查询
     * @param $payment_id
     * @return array
     */
    public function query($payment_id)
    {
        $result = ['status' => false, 'data' => [], 'msg' => ''];

        $data['msgType'] = 'query';// 消息类型[微信:wx.unifiedOrder支付宝:trade.create]
        $data['requestTimestamp'] = date('Y-m-d H:i:s', time());// 报文请求时间，格式yyyy-MM-dd HH:mm:ss
        $data['msgSrc'] = $this->config['msgSrc'];// 消息来源
        $data['mid'] = $this->config['mid'];// 商户号
        $data['tid'] = $this->config['tid'];// 终端号
        $data['instMid'] = $this->config['instMid'];// 机构商户号
        $data['merOrderId'] = $this->config['msgSrcId'] . $payment_id;// 商户订单号，商户自行生成，注意长度不要超过32位
        $data['sign'] = $this->__makeSign($data);// 签名

        pay_log('weixinappupacp支付查询参数：', $data);
        pay_log('weixinappupacp支付查询链接：', $this->config['url']);
        $json = json_encode($data);
        $response = $this->__postJsonCurl($json, $this->config['url'], false);
        $re = json_decode($response, true);
        pay_log('weixinappupacp支付查询响应参数：', $re);
        if(empty($re)){
            $result['msg'] = $response;// 把错误信息都返回到前台吧，方便调试。
            return $result;
        }

        if($re['errCode'] != 'SUCCESS')
        {
            // 平台下单接口请求错误
            $result['msg'] = $re['errMsg'];
            $result['data'] = $re['errCode'];
            return $result;
        }

        $result['status'] = true;
        $result['data'] = $re;

        return $result;
    }

    /**
     * 退款
     * @param $refundInfo
     * @param $paymentInfo
     * @return array
     */
    function refund($refundInfo, $paymentInfo)
    {
        $result = ['status' => false, 'data' => [], 'msg' => ''];

        if(!$refundInfo['money'] || $refundInfo['money'] == 0)
        {
            $result['status'] = true;
            $result['msg']    = '退款成功';
            return $result;
        }
        
        $data['msgType'] = 'refund';// 消息类型[微信:wx.unifiedOrder支付宝:trade.create]
        $data['requestTimestamp'] = date('Y-m-d H:i:s', time());// 报文请求时间，格式yyyy-MM-dd HH:mm:ss
        $data['msgSrc'] = $this->config['msgSrc'];// 消息来源
        $data['mid'] = $this->config['mid'];// 商户号
        $data['tid'] = $this->config['tid'];// 终端号
        $data['instMid'] = $this->config['instMid'];// 机构商户号
        $data['merOrderId'] = $this->config['msgSrcId'] . $paymentInfo['payment_id'];// 商户订单号，商户自行生成，注意长度不要超过32位
        $data['refundAmount'] = $refundInfo['money'] * 100;// 总退款金额
        //$data['msgId'] = '';// 总退款金额
        $data['sign'] = $this->__makeSign($data);// 签名

        pay_log('weixinappupacp退款请求参数：', $data);
        pay_log('weixinappupacp退款请求链接：', $this->config['url']);
        $json = json_encode($data);
        $response = $this->__postJsonCurl($json, $this->config['url'], false);
        $re = json_decode($response, true);
        pay_log('weixinappupacp退款响应参数：', $re);
        if(empty($re)){
            $result['msg'] = $response;// 把错误信息都返回到前台吧，方便调试。
            return $result;
        }

        if($re['errCode'] != 'SUCCESS')
        {
            // 平台下单接口请求错误
            $result['msg'] = $re['errMsg'];
            $result['data'] = $re['errCode'];
            return $result;
        }

        $result['status'] = true;
        $result['data'] = $re;
        $result['msg']    = '退款成功';

        return $result;
    }

    /**
     *
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

    /**
     * 获取交易类型
     * @param $params
     * @return array
     */
    private function __getTradeType($params)
    {
        $result = [
            'status' => false,
            'data' => '',
            'msg' => ''
        ];

        //判断是否是指定的交易类型
        if(!in_array($params['trade_type'], array('MINI')))
        {
            $result['msg'] = '参数错误，trade_type为非法值';
            return $result;
        }
        $trade_type = $params['trade_type'];

        $result['data'] = $trade_type;
        $result['status'] = true;
        return $result;
    }

    /**
     * 获取openid
     * @param $user_id
     * @param $type
     * @return array
     */
    private function __getOpenId($user_id, $type)
    {
        $result = [
            'status' => false,
            'data' => '',
            'msg' => ''
        ];

        $userWxModel = new UserWx();
        if($type == 'MINI')
        {
            $type = $userWxModel::TYPE_MINIPROGRAM;
        }
        else
        {
            $result['msg'] = '不需要获取openid';
            return $result;
        }

        $userWxInfo = $userWxModel->where(['type'=>$type,'user_id'=>$user_id])->find();
        if(!$userWxInfo)
        {
            $result['msg'] = "请用户先进行微信登陆或绑定";
            return $result;
        }
        $result['data'] = $userWxInfo['openid'];
        $result['status'] = true;
        return $result;
    }

    /**
     * 格式化参数格式化成url参数
     * @param $value
     * @return string
     */
    private function __toUrlParams($value)
    {
        $buff = "";
        foreach ($value as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 生成签名
     * @param $values
     * @return string
     */
    private function __makeSign($values)
    {
        //签名步骤一：按字典序排序参数
        ksort($values);
        $string = $this->__ToUrlParams($values);
        //签名步骤二：在string后加入KEY
        $string = $string . $this->config['secret_key'];
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 以post方式提交json到对应的接口url
     * @param $json         需要post的json数据
     * @param $url          url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     * @return mixed|string
     */
    private function __postJsonCurl($json, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        //如果有配置代理这里就设置代理
        /*if(WxPayConfig::CURL_PROXY_HOST != "0.0.0.0"
            && WxPayConfig::CURL_PROXY_PORT != 0)
        {
            curl_setopt($ch,CURLOPT_PROXY, WxPayConfig::CURL_PROXY_HOST);
            curl_setopt($ch,CURLOPT_PROXYPORT, WxPayConfig::CURL_PROXY_PORT);
        }*/
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if($useCert == true)
        {
            $cert_dir = ROOT_PATH.DS."config".DS."payment_cert".DS."wechatpay".DS;
            if(
                !file_exists($cert_dir."apiclient_cert.pem") ||
                !file_exists($cert_dir."apiclient_key.pem")
            )
            {
                return "";
            }
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, $cert_dir."apiclient_cert.pem");
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, $cert_dir."apiclient_key.pem");
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        //运行curl
        $data = curl_exec($ch);

        //返回结果
        if($data)
        {
            curl_close($ch);
            return $data;
        }
        else
        {
            $error = curl_errno($ch);
            curl_close($ch);
            return "curl错误码:{$error}";
        }
    }
}