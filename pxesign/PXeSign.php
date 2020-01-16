<?php
/**
 * E签包相关业务接口
 * @author peak xin<xinyflove@gmail.com>
 * @created 2020-01-13
 * @referenceDoc https://qianxiaoxia.yuque.com/books/share/8bc54c0d-ebaa-4036-957e-b9a353f86486/shiming
 */

namespace org;


class PXeSign
{
    const TOKEN_CACHED_KEY = 'esign_access';// 缓存key值

    public $faceAuthMode;// 人脸认证方式
    private $__domain;// API域名
    private $__appId;// 应用ID
    private $__secret;// APP KEY
    private $__tokenExpireIn;// token过期时间

    /**
     * eSign constructor.
     * @author peak xin
     */
    public function __construct()
    {
        $this->faceAuthMode = config('setting.esign.face_auth_mode');
        $this->__domain = 'https://smlopenapi.esign.cn';
        $this->__appId = '';
        $this->__secret = '';
        $this->__tokenExpireIn = 7000// 可从配置文件获取;
    }

    /**
     * 获取Token
     * @return bool|mixed|null
     * @author peak xin
     */
    public function getToken()
    {
        $token = $this->__getTokenFromCache();
        if (!$token)
        {
            return $this->__getTokenFromApi();
        }
        return $token;
    }

    /**
     * 发起个人刷脸核身
     * @param $params
     *  real_name->姓名(必选)
     *  id_number->身份证号(必选)
     *  face_auth_mode->人脸认证方式(必选)
     *  callback_url->刷脸完成后业务重定向地址(必选)，接收参数
     *      passed->'true':通过,'false':失败
     *      result->'1':通过,'0':失败
     *  context_id->对接方业务上下文id
     *  notify_url->认证结束后异步通知地址
     * @return array
     *  authUrl->e签宝短链接地址
     * @author peak xin
     */
    public function face($params)
    {
        $result = ['status' => false, 'data' => [], 'msg' => '发起个人刷脸核身失败'];

        if ( empty($params['real_name']) || empty($params['id_number']) || empty($params['face_auth_mode']) || empty($params['callback_url']) )
        {
            $result['msg'] = '参数real_name,id_number,face_auth_mode,callback_url不能为空';
            return $result;
        }

        $url = $this->__domain . config('setting.esign.face_url');
        $header = $this->__header();

        $data = array(
            "name" => $params['real_name'],
            "idNo" => $params['id_number'],
            "faceauthMode" => $params['face_auth_mode'],
            "callbackUrl" => $params['callback_url']
        );
        if (!empty($params['context_id']))
        {
            //"contextId"=>"f0a7927d-c5f9-4652-a053-1130d86c8fa8"
            $data['contextId'] = $params['context_id'];
        }
        if (!empty($params['notify_url']))
        {
            //"notifyUrl"=>"http://xxx.xx.cn/esign/realname/callback"
            $data['notifyUrl'] = $params['notify_url'];
        }

        try {
            $this->__log('发起个人刷脸核身请求:', json_encode(['url'=>$url,'data'=>$data, 'header'=>$header]));
            $PXcURL = new PXcURL();
            $res = $PXcURL->post($url, $httpCode, $data, $header);
            $this->__log('发起个人刷脸核身响应:', $res);
        } catch (\Exception $e) {
            $this->__log('发起个人刷脸核身响应:', $e->getMessage());
            $result['msg'] = $e->getMessage();
            return $result;
        }

        $res_arr = json_decode($res, true);
        if ( $res_arr['code'] )
        {
            return $result;
        }

        $result['status'] = true;
        $result['msg'] = '发起个人刷脸核身成功';
        $result['data'] = $res_arr['data'];
        return $result;
    }

    /**
     * 发起个人刷脸核身回调
     * @param $data [获取通知的数据]
     * @return array
     * @author peak xin
     */
    public function faceCallback($data)
    {
        $result = ['status' => false, 'data' => [], 'msg' => 'FAILED'];
        $this->__log('发起个人刷脸核身回调：', $data);
        
        if($data)
        {
            $result['msg'] = 'SUCCESS';
            $result['status'] = true;

            if ($data['passed'] == 'true' || $data['result'] == '1')
            {
                //TODO 处理验证成功
            }
            else
            {
                //TODO 处理验证失败
            }
        }
        
        return $result;
    }

    /**
     * 发起银行卡4要素核身认证
     * @param $params
     * @return array
     * @author peak xin
     */
    public function bankCard4Factors($params)
    {
        $result = ['status' => false, 'data' => [], 'msg' => '发起银行卡4要素核身认证失败'];

        if ( empty($params['real_name']) || empty($params['id_number']) || empty($params['mobile_no']) || empty($params['bank_card_no']) )
        {
            $result['msg'] = '参数real_name,id_number,mobile_no,bank_card_no不能为空';
            return $result;
        }

        $url = $this->__domain . config('setting.esign.bank_card_4_factors');
        $header = $this->__header();

        $data = array(
            "name" => $params['real_name'],
            "idNo" => $params['id_number'],
            "mobileNo" => $params['mobile_no'],
            "bankCardNo" => $params['bank_card_no']
        );
        if (!empty($params['context_id']))
        {
            //"contextId"=>"f0a7927d-c5f9-4652-a053-1130d86c8fa8"
            $data['contextId'] = $params['context_id'];
        }
        if (!empty($params['notify_url']))
        {
            //"notifyUrl"=>"http://xxx.xx.cn/esign/realname/callback"
            $data['notifyUrl'] = $params['notify_url'];
        }

        try {
            $this->__log('发起银行卡4要素核身认证请求:', json_encode(['url'=>$url,'data'=>$data, 'header'=>$header]));
            $PXcURL = new PXcURL();
            $res = $PXcURL->post($url, $httpCode, $data, $header);
            $this->__log('发起银行卡4要素核身认证响应:', $res);
        } catch (\Exception $e) {
            $this->__log('发起银行卡4要素核身认证响应:', $e->getMessage());
            $result['msg'] = $e->getMessage();
            return $result;
        }

        $res_arr = json_decode($res, true);
        if ( $res_arr['code'] )
        {
            return $result;
        }

        $result['status'] = true;
        $result['msg'] = '发起银行卡4要素核身认证成功';
        $result['data'] = $res_arr['data'];
        return $result;
    }

    /**
     * 银行预留手机号验证码校验
     * @param $params
     * @return array
     * @author peak xin
     */
    public function flowIdBankCard4Factors($params)
    {
        $result = ['status' => false, 'data' => [], 'msg' => '银行预留手机号验证码校验失败'];

        if ( empty($params['auth_code']) || empty($params['flow_id']) )
        {
            $result['msg'] = '参数auth_code,flow_id不能为空';
            return $result;
        }

        $url = $this->__domain . config('setting.esign.flow_id_bank_card_4_factors');
        $url = sprintf($url, $params['flow_id']);
        $header = $this->__header();

        $data = array(
            "authcode" => $params['auth_code']
        );

        try {
            $this->__log('银行预留手机号验证码校验请求:', json_encode(['url'=>$url,'data'=>$data, 'header'=>$header]));
            $PXcURL = new PXcURL();
            $res = $PXcURL->put($url, $httpCode, $data, $header);
            $this->__log('银行预留手机号验证码校验响应:', $res);
        } catch (\Exception $e) {
            $this->__log('银行预留手机号验证码校验响应:', $e->getMessage());
            $result['msg'] = $e->getMessage();
            return $result;
        }

        $res_arr = json_decode($res, true);
        if ( $res_arr['code'] )
        {
            return $result;
        }

        $result['status'] = true;
        $result['msg'] = '银行预留手机号验证码校验成功';
        $result['data'] = $res_arr['data'];
        return $result;
    }

    /**
     * 获取请求header参数
     * @return array
     * @author xinyufeng
     */
    private function __header()
    {
        $token = $this->getToken();

        $header = array(
            'Content-Type: application/json',
            'X-Tsign-Open-App-Id: ' . $this->__appId,
            'X-Tsign-Open-Token: ' . $token
        );

        return $header;
    }

    /**
     * 从缓存获取Token
     * @return mixed|null
     * @author peak xin
     */
    private function __getTokenFromCache()
    {
        $token = cache(self::TOKEN_CACHED_KEY);
        if (!config('app.app_debug') && $token)
        {
            return $token;
        }
        return null;
    }

    /**
     * 从API获取鉴权Token
     * @return bool
     * @author peak xin
     */
    private function __getTokenFromApi()
    {
        $url = $this->__domain . config('setting.esign.access_token_url');
        $url = sprintf($url, $this->__appId, $this->__secret);
        $this->__log('获取鉴权Token请求:', json_encode(['url'=>$url]));
        try {
            $PXcURL = new PXcURL();
            $res = $PXcURL->get($url);
            $this->__log('获取鉴权Token响应:', $res);
        } catch (\Exception $e) {
            $this->__log('获取鉴权Token响应:', $e->getMessage());
            return false;
        }


        $res_arr = json_decode($res, true);
        if ( $res_arr['code'] == 0 )
        {
            $token = $res_arr['data']['token'];
            $expireIn = substr($res_arr['data']['expiresIn'], 0, 10);
            $expire = $expireIn - time();
            $this->__saveTokenToCache($token, $expire);
            return $token;
        }

        return false;
    }

    /**
     * 保存Token到缓存
     * @param $token
     * @param $expire
     * @author peak xin
     */
    private function __saveTokenToCache($token, $expire=0)
    {
        $expire <= 0 && $expire = $this->__tokenExpireIn;
        cache(self::TOKEN_CACHED_KEY, $token, $expire);
    }

    /**
     * 记录请求日志，目录为 runtime\log\esign
     * @param string $title
     * @param string $params
     * @author peak xin
     */
    private function __log($title='', $params='')
    {
        file_log($title, $params, 'esign');
    }
}