<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/11/011
 * Time: 0:37
 */

require __DIR__ . '/vendor/autoload.php';

use Gsons\MpBox;
use Curl\Curl;

$curl = new Curl();
$curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
$cookies = [
    'wxuin' => '1331014114',
    'wxsid' => 'krenuI2ZqAcDGeWT',
    'wxloadtime' => '1541930707',
    'mm_lang' => 'zh_CN',
    'webwx_data_ticket' => 'gSf+s2UZoPPCElsKo6NH0DyR',
    'webwxuvid' => '69b5312634e4dbfdc8acd9f83e1d70a38aa5f167cf76836e07f2869e7b0d0e839a95dc77ce15029a5e4a23c4d7358886',
    'webwx_auth_ticket' => 'CIsBEJbL/5gKGoABhZUlsNekt+OSUrsKpENMrE0eW48Gfvp3biUoJjptg768ZfXmB38LtIqtbT/Mn25zjHllmBsXb1DXuDJTj8nqUwS/6jK08qnE2AU4cH9zUarCLVNM/ORBFjZBUuCXAEIPE008b9T1G7csabF+EoFJSZjEQo6TtmemNRHG7DkbN7s='
];
//$curl->setCookies($cookies);
$curl->setHeader('content-type', 'application/json');
echo $curl->post('https://wx2.qq.com/cgi-bin/mmwebwx-bin/webwxsendmsg', json_decode('{"BaseRequest":{"Uin":"1331014114","Sid":"krenuI2ZqAcDGeWT","Skey":"@crypt_536302d2_ad149c07536d29408086c41c3e690f84","DeviceID":"YzX6B4"},"Msg":{"Type":1,"Content":"hell22o222","FromUserName":"@34730555274b8a25290127b32cf61a0c911ae618ebbacd1bb51e4197b777d9d0","ToUserName":"@3faec09508ad0e9e18f1daa903c318b57453c0b3b6fe4e30b0496102fe50ee62","LocalID":"1541930970200","ClientMsgId":"1541930970200"},"Scene":0}', true));
exit;
try {
    $mp = new MpBox();
} catch (\Exception $e) {
    exit($e->getMessage());
}
$mp->onSuccess = function ($cookies) use ($mp) {
    $contact = $mp->getContact($cookies);
    print_r($contact);
};
while (1) {
    sleep(1);
    $res = $mp->checkLogin('QZEOblNwfQ==');
    echo json_encode($res) . PHP_EOL;
    if ($res['status'] == '200') break;
}
