<?php
namespace hypergo\utils;

class Code {
  /**
   * 返回字符串的ASCII码
   * 
   * @param string $str 需要转码的字符串
   * @return string
   */
  public static function stringToASCII(string $str):string {
    $str = mb_convert_encoding($str, "UTF-8");
    $change_after = "";
    for($i = 0; $i < strlen($str); $i++) {
      $temp_str = dechex(ord($str[$i]));
      $change_after .= $temp_str[1].$temp_str[0];
    }
    
    return strtoupper($change_after);
  }
  
  /**
   * 返回ASCII码的字符串
   * 
   * @param string $ascii ASCII码
   * @return string
   */
  public static function ASCIIToString(string $ascii):string {
    $asc_arr = str_split(strtolower($ascii), 2);
    $str = "";
    for($i = 0; $i < count($asc_arr); $i++) {
      $str .= chr(hexdec($asc_arr[$i][1].$asc_arr[$i][0]));
    }
    
    return mb_convert_encoding($str, "UTF-8");
  }
  
  /**
   * 返回随机码
   * 
   * @param int $type 0为纯数字，1为纯字母，2为数字字母混合
   * @param int $length 随机码长度
   * @return string
   */
  public static function getRandomCode(int $type, int $length):string {
    if ($length < 0) {
      return "";
    }

    $codeList = [];
    
    switch($type) {
      case 0:
        $codeList = range(0, 9);
        break;
        
      case 1:
        $codeList = array_merge(range("a", "z"), range("A", "Z"));
        break;
      
      case 2:
        $codeList = array_merge(range("a", "z"), range("A", "Z"), range(0, 9));
        break;
        
      default:
        return $codeList;
        break;
    }
    
    $max = count($codeList) - 1;
    $randomCode = "";
    
    for ($i = 0; $i < $length; $i++) {
      $randomCode .= $codeList[mt_rand(0, $max)];
    }
    
    return $randomCode;
  }
}
?>
