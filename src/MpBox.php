<?php
/**
 * 微信模拟登录工具
 * Created by PhpStorm.
 * User: gsons
 * Date: 2018/11/11/011
 * Time: 0:24
 */

namespace Gsons;

use Curl\Curl;
use Exception;

class MpBox
{
    const URL_JS_LOGIN = 'https://login.wx.qq.com/jslogin';
    const URL_Fetch_QRCode = 'https://login.weixin.qq.com/qrcode';
    const URL_LOGIN_CHECK = 'https://login.wx.qq.com/cgi-bin/mmwebwx-bin/login';
    const URL_CONTACT = 'https://wx2.qq.com/cgi-bin/mmwebwx-bin/webwxgetcontact';
    const URL_SEND = 'https://wx2.qq.com/cgi-bin/mmwebwx-bin/webwxsendmsg';
    const URL_LOGIN_OUT = 'https://wx2.qq.com/cgi-bin/mmwebwx-bin/webwxlogout';
    const URL_WEB_INIT = 'https://wx2.qq.com/cgi-bin/mmwebwx-bin/webwxinit';

    private $curl;// @var  CURL请求对象
    public $onSuccess;// @var callable 登录成功后的回调

    /**
     * WxBox constructor.
     * @throws Exception
     */
    public function __construct()
    {

        $this->curl = new Curl();
        $this->curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $this->onSuccess = null;
    }

    /**
     * 获取二维码uuid
     * @return string
     */
    public function getUidByJsLogin()
    {
        $paramArr = [
            'appid' => 'wx782c26e4c19acffb',
            'redirect_uri' => 'https%3A%2F%2Fwx.qq.com%2Fcgi-bin%2Fmmwebwx-bin%2Fwebwxnewloginpage',
            'fun' => 'new',
            'lang' => 'zh_CN',
            '_' => time()
        ];
        $this->curl->get(self::URL_JS_LOGIN, $paramArr);
        if ($this->curl->error) {
            return false;
        } else {
            $patten = '/window.QRLogin.code = 200; window.QRLogin.uuid = "(.*?)";/';
            $res = $this->curl->response;
            $matchesArr = [];
            $matchRes = preg_match($patten, $res, $matchesArr);
            if ($matchRes && isset($matchesArr[1])) {
                return $matchesArr[1];
            } else {
                return false;
            }
        }
    }

    /**
     * 输出登录二维码
     * @param string $wx_uuid 二维码uuid
     */
    public function fetchQRCode($wx_uuid)
    {
        $this->curl->get(self::URL_Fetch_QRCode . '/' . $wx_uuid);
        if ($this->curl->error) {
           echo '';
        } else {
            header('Content-type:image/png');
            echo $this->curl->response;
        }
    }


    /**
     * 检查是否已经登录
     * @param string $wx_uuid 二维码uuid
     * @param int $timeout
     * @return array
     */
    public function checkLogin($wx_uuid, $timeout = 1)
    {
        $param = [
            'loginicon' => 'true',
            'uuid' => $wx_uuid,
            'tip' => '0',
            'r' => String::randString(),
            '_' => time()
        ];
        $this->curl->setTimeout($timeout);
        $this->curl->get(self::URL_LOGIN_CHECK, $param);
        if ($this->curl->error) {
            $error = 'UUID[' . $wx_uuid . ']' . '获取登录状态失败,' . 'Curl Error: ' . $this->curl->errorCode . ': ' . $this->curl->errorMessage;
            return ['success' => 'false', 'status' => 201, 'msg' => $error];
        } else {
            $patten = '/window.redirect_uri="(.*?)";/';
            $res = $this->curl->response;
            $matchesArr = [];
            $matchRes = preg_match($patten, $res, $matchesArr);
            if ($matchRes && isset($matchesArr[1])) {
                $this->curl->setDefaultTimeout();
                $this->curl->get($matchesArr[1]);
                if ($this->curl->error) {
                    return ['success' => 'false', 'status' => 202, 'msg' => '获取登录票据失败,' . 'Curl Error: ' . $this->curl->errorCode . ': ' . $this->curl->errorMessage];
                } else {
                    $xmlArr = json_decode(json_encode(simplexml_load_string($this->curl->getResponse())), true);
                    if (isset($xmlArr['wxuin'])) {
                        $this->onSuccess && call_user_func($this->onSuccess, $this->curl->getResponseCookies(), $xmlArr);
                        return ['success' => 'true', 'status' => 200, 'msg' => 'UUID[' . $wx_uuid . ']登录成功'];
                    } else {
                        return ['success' => 'false', 'status' => 202, 'msg' => 'UUID[' . $wx_uuid . ']登录失败'];
                    }
                }
            } else {
                return ['success' => 'false', 'status' => 202, 'msg' => 'UUID[' . $wx_uuid . '] ' . $res];
            }
        }
    }

    /**
     * 获取联系人列表
     * @param $cookies
     * @return array
     */
    public function getContact($cookies)
    {
        $paramArr = [
            'r' => time(),
            'seq' => '0',
            'skey' => $cookies['wxsid'],
            'pass_ticket' => $cookies['webwx_auth_ticket'],
        ];
        if ($cookies) $this->curl->setCookies($cookies);
        $this->curl->get(self::URL_CONTACT, $paramArr);
        if ($this->curl->error) {
            return [];
        } else {
            $res = json_decode($this->curl->response, true);
            return $res['MemberList'];
        }
    }

    /**
     * @param $cookies
     * @return mixed
     * @throws Exception
     */
    public function loginOut($cookies)
    {
        if ($cookies) $this->curl->setCookies($cookies);
        $paramArr = [
            'redirect' => '1',
            'type' => '1',
            'skey' => '',
        ];
        $payLoad = [
            'sid' => $cookies['wxsid'],
            'uin' => $cookies['wxuin']
        ];
        $this->curl->post(self::URL_LOGIN_OUT . '?' . http_build_query($payLoad), $paramArr);
        if ($this->curl->error) {
            return false;
        } else {
            return $this->curl->response;
        }
    }

    /**
     * @param $cookies
     * @param $loginData
     * @param $fromUserName
     * @param $toUserName
     * @param $text
     * @return array|string
     */
    public function sendMsg($cookies, $loginData, $fromUserName, $toUserName, $text)
    {
        $jsonData = [
            'BaseRequest' => [
                'Uin' => $loginData['wxuin'],
                'Sid' => $loginData['wxsid'],
                'Skey' => $loginData['skey'],
                'DeviceID' => String::randString()
            ],
            'Msg' => [
                'Type' => 1,
                'Content' => $text,
                'FromUserName' => $fromUserName,
                'ToUserName' => $toUserName,
                'LocalID' => time() . '000',
                'ClientMsgId' => time() . '000',
            ],
            'Scene' => 0
        ];
        $this->curl->setCookies($cookies);
        $this->curl->setHeader('content-type', 'application/json');
        $this->curl->post(self::URL_SEND, $jsonData);
        if ($this->curl->error) {
            return false;
        } else {
            return json_decode($this->curl->response, true);
        }
    }

    /**
     * @param $loginData
     * @param $cookies
     * @return array|string
     */
    public function webInit($cookies, $loginData)
    {
        $rd = String::randString();
        $pass_ticket = $loginData['pass_ticket'];
        $param = "?pass_ticket={$pass_ticket}&r={$rd}";
        $paramArr = [
            'BaseRequest' => [
                'Uin' => $loginData['wxuin'],
                'Sid' => $loginData['wxsid'],
                'Skey' => $loginData['skey'],
                'DeviceID' => String::randString()
            ]
        ];
        $this->curl->setHeader("content-type", "application/json");
        $this->curl->setCookies($cookies);
        $this->curl->post(self::URL_WEB_INIT . $param, $paramArr);
        if ($this->curl->error) {
            return false;
        } else {
            $res = json_decode($this->curl->response, true);
            return $res['User'];
        }
    }
}



