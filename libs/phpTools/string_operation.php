<?php
/*
Function Name : number2chtstr
  The function will translate latin number to chinese number
Creator : Lee Yen
Date : 2012/11/24
*/

function number2chtstr($number){
  $numbers_str_setting = array('零', '一', '兩', '三', '四', '五', '六', '七', '八', '九', '十', '佰', '千', '萬', '億', '兆', '京', '垓', '秭', '穰', '溝', '澗', '正', '載', '極', '恆河沙', '阿僧祇', '那由他', '不可思議', '無量大數');
  if(isset($number) && 72>=strlen($number)){
    $output = '';
    $count = 0;
    $strlen = strlen($number);
    $numbers_arr = array();
    for($count = 0; $number; $count++){
      array_unshift($numbers_arr, substr($number, -4));
      if(4>strlen(substr($number, -4))){
        break;
      }
      $number = substr($number, 0, strlen($number)-4);
    }
    //print_r($numbers_arr);
    foreach($numbers_arr as $val){
      $strlen = strlen($val);
      $cht_number = '';
      $zero_count = 0;
      for($count = 0; $count<$strlen; $count++){
        $temp = $numbers_str_setting[substr($val, $count, 1)];
        if('零'==$temp){
          $zero_count++;
        }
        if(0!=$count && '兩'==$temp){
          $temp = '二';
        }
        $cht_number .= $temp;
        if($count!=$strlen && $strlen-$count>1 && $temp!='零'){
          $cht_number .= $numbers_str_setting[$strlen+8-$count];
        }
      }
      
        $cht_number = preg_replace("/^([零]{2,})/i", '零', $cht_number);
        $cht_number = preg_replace("/(零){1,}/i", "零", $cht_number);
        $cht_number = preg_replace("/(零){1,}$/i", "", $cht_number);
      $cht_numbers[] = $cht_number;
    }
    //print_r($cht_numbers);
    if(is_array($cht_numbers)){
      $count = count($cht_numbers);
      foreach($cht_numbers as $val){
        switch(mb_substr($val, 0, 2,"UTF-8")){
          case '一十':
            $output .= str_replace('一十', '十', $val);
            break;
          default:
            $output .= $val;
        }
        
        if(1<$count && ''!=$val){
          $output .= $numbers_str_setting[11+$count];
        }
        $count--;
      }
      $output = str_replace('零零', '零', $output);
      return $output;
   }
  }
  else{
    return false;
  }
}
?>