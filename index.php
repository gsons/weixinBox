<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/11/011
 * Time: 2:17
 */
require __DIR__ . '/vendor/autoload.php';

use Gsons\MpBox;
use think\Cache;

session_start();
date_default_timezone_set('PRC');
Cache::init(['type' => 'File', 'path' => './cache/', 'prefix' => '', 'expire' => 0]);
$action = isset($_GET['action']) ? $_GET['action'] : 'qrCode';

if ($action == 'qrCode') {
    action_qrCode();
} elseif ($action == 'checkLogin') {
    action_checkLogin();
} elseif ($action == 'exportExcel') {
    action_exportContactExcel();
} elseif ($action == 'loginOut') {
    action_loginOut();
} elseif ($action == 'sendText') {
    action_sendText();
} elseif ($action == 'cacheContact') {
    action_cacheContact();
}


function action_qrCode()
{
    $wxBox = new MpBox();
    $uuid = $wxBox->getUidByJsLogin();
    $wxBox->fetchQRCode($uuid);
    $_SESSION['uuid'] = $uuid;
    unset($_SESSION['wx_cookie']);
    unset($_SESSION['wx_loginData']);
}

function action_checkLogin()
{
    if (!isset($_SESSION['uuid'])) {
        $res = ['msg' => '未初始化UUID', 'status' => 202, 'success' => false];
        exit(json_encode($res));
    }
    if (isset($_SESSION['wx_cookie'])) {
        $res = ['msg' => '已经登录了', 'status' => 200, 'success' => true];
        exit(json_encode($res));
    }
    $wxBox = new MpBox();
    $wxBox->onSuccess = function ($cookies, $loginData) use ($wxBox) {
        $_SESSION['wx_cookie'] = json_encode($cookies);
        $_SESSION['wx_loginData'] = json_encode($loginData);
    };
    $res = $wxBox->checkLogin($_SESSION['uuid']);
    exit(json_encode($res));
}



function action_cacheContact()
{
    if (!checkLogin()) {
        exit('尚未登录!!!');
    }
    $cookies = json_decode($_SESSION['wx_cookie'], true);
    $loginData = json_decode($_SESSION['wx_loginData'], true);
    $wxBox = new MpBox();
    $memberList = $wxBox->getContact($cookies);
    Cache::set('memberList_' . $_SESSION['uuid'], $memberList,60*30);
    $user = $wxBox->webInit($cookies,$loginData);
    Cache::set('user_' . $_SESSION['uuid'], $user);
}

function action_exportContactExcel()
{
    if (!checkLogin()) {
        exit('尚未登录!!!');
    }
    $memberList =getMemberList($_SESSION['uuid']);
    $userList = [];
    foreach ($memberList as $vo) {
        $vo['Sex'] == 0 ? $sex = '保密' : $vo['Sex'] == 1 ? $sex = '男' : $sex = '女';
        $userList[] = [
            $vo['NickName'],
            $vo['RemarkName'],
            $sex,
            $vo['Signature'],
            $vo['Province'],
            $vo['City']
        ];
    }
    $titleArr = ['昵称', '备注', '性别', '个性签名', '省份', '城市'];
    exportExcel($titleArr, $userList, '微信好友列表', '微信好友列表');
}

function action_loginOut()
{
    if (checkLogin()) {
        $cookies = json_decode($_SESSION['wx_cookie'], true);
    } else {
        exit('尚未登录!!!');
    }
    $wxBox = new MpBox();
    echo $wxBox->loginOut($cookies);
}

function action_sendText()
{
//    set_time_limit(0);
    $wxBox = new MpBox();
    $cookies = json_decode($_SESSION['wx_cookie'], true);
    $loginData = json_decode($_SESSION['wx_loginData'], true);
    $memberList =getMemberList($_SESSION['uuid']);
    foreach ($memberList as $contact) {
        if ($contact['NickName'] == '迷之微笑゛') {
            $user=getUser($_SESSION['uuid']);
            $fromUser = $user['UserName'];
            $arr=str_split('abjgbdfhghdfghfhfhhfhfhfhhfh');
            foreach($arr as $v){
//                sleep(1);
                $wxBox->sendMsg($cookies, $loginData, $fromUser, $contact['UserName'],$v);
            }
            break;
        }
    }
}

function getMemberList($uuid){
    $memberList = Cache::get('memberList_' . $uuid);
    if(!$memberList){
        $cookies = json_decode($_SESSION['wx_cookie'], true);
        $wxBox = new MpBox();
        $memberList=$wxBox->getContact($cookies);
    }
    return $memberList;
}
function getUser($uuid){
    $user = Cache::get('user_' . $uuid);
    if(!$user){
        $cookies = json_decode($_SESSION['wx_cookie'], true);
        $loginData = json_decode($_SESSION['wx_loginData'], true);
        $wxBox = new MpBox();
        $user=$wxBox->webInit($cookies,$loginData);
    }
    return $user;
}

function checkLogin()
{
    if (isset($_SESSION['wx_cookie']) && isset($_SESSION['uuid']) && isset($_SESSION['wx_loginData'])) {
        return true;
    }
    return false;
}

/**
 * 数据导出
 * @param array $titleArr
 * @param array $dataArr
 * @param string $fileName
 * @param string $sheetName
 * @throws PHPExcel_Exception
 * @throws PHPExcel_Reader_Exception
 */
function exportExcel($titleArr = array(), $dataArr = array(), $fileName, $sheetName)
{
    $excel = new PHPExcel();
    $columnLen = count($titleArr);
    if (!empty($dataArr) && $columnLen != count($dataArr[0]) || $columnLen > 26) throw new PHPExcel_Exception('参数不合法!');
    $excel->getActiveSheet()->setTitle($sheetName);
    $cellArr = range('A', 'Z');
    //第一行头部
    $row = 1;
    for ($i = 0; $i < $columnLen; $i++) {
        $excel->setActiveSheetIndex(0)->setCellValue($cellArr[$i] . $row, $titleArr[$i]);
    }
    //body部分
    foreach ($dataArr as $data) {
        $row++;
        for ($i = 0; $i < $columnLen; $i++) {
            $excel->setActiveSheetIndex(0)->setCellValue($cellArr[$i] . $row, $data[$i]);
        }
    }
    $objWrite = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    header('pragma:public');
    header("Content-Disposition:attachment;filename=$fileName.xlsx");
    $objWrite->save('php://output');
}