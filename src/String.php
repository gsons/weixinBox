<?php

namespace Gsons;


class String {

    static public function randString($len=6,$type='-1',$addChars='') {
        $str ='';
        switch($type) {
            case 0:
                $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.$addChars;
                break;
            case 1:
                $chars= str_repeat('0123456789',3);
                break;
            case 2:
                $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ'.$addChars;
                break;
            case 3:
                $chars='abcdefghijklmnopqrstuvwxyz'.$addChars;
                break;
            default :
                $chars='ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'.$addChars;
                break;
        }
        if($len>10 ) {//位数过长重复字符串一定次数
            $chars= $type==1? str_repeat($chars,$len) : str_repeat($chars,5);
        }
        if($type!=4) {
            $chars   =   str_shuffle($chars);
            $str     =   substr($chars,0,$len);
        }
        return $str;
    }

    static public function gbkEcho($msg)
    {
        $msg = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
        echo @iconv('UTF-8', 'gbk//IGNORE', $msg) . PHP_EOL;
    }
}