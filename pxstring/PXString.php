<?php

/**
 * 字符串处理类
 * @author peak xin<xinyflove@gmail.com>
 * @create 2020-01-16
 */


class PXString
{
    /**
     * 获取指定长度的随机字符串
     * @param $length
     * @param int $mode
     *  0:数字和大小写字母;
     *  1:数字;
     *  2:大写字母;
     *  3:小写字母;
     *  4:数字和大写字母;
     *  5:数字和小写字母;
     *  6:大写字母和小写字母.
     * @return null|string
     * @author peak xin
     */
    public static function getRandChar($length, $mode=0)
    {
        $str = null;
        $strPol1 = '0123456789';
        $strPol2 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $strPol3 = 'abcdefghijklmnopqrstuvwxyz';
        $strPol = "";
        
        if ( in_array($mode, array(0, 1, 4, 5)) )
        {
            $strPol .= $strPol1;
        }
        if ( in_array($mode, array(0, 2, 4, 6)) )
        {
            $strPol .= $strPol2;
        }
        if ( in_array($mode, array(0, 3, 5, 6)) )
        {
            $strPol .= $strPol3;
        }
        
        $max = strlen($strPol) - 1;

        for ($i = 0;
            $i < $length;
            $i++) {
            $str .= $strPol[rand(0, $max)];
        }

        return $str;
    }

    /**
     * 去除字符串中所有的空格
     * @param $str
     * @return mixed
     * @author peak xin
     */
    public static function trimALl($str)
    {
        return str_replace(' ', '', $str);
    }
}