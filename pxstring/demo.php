<?php
/**
 * Demo
 */
include_once 'PXString.php';

/*获取指定长度的随机字符串*/
var_dump(PXString::getRandChar(4));echo '<br>';

/*去除字符串中所有的空格*/
var_dump(PXString::trimALl(' a b c '));echo '<br>';