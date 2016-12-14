<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set("Asia/Taipei");
require('tools/simple_html_dom.php');
 
//$bookSet = array('serial'=>'1773488', 'book_name'=>'天珠變', 'author'=>'唐家三少');
//$bookSet = array('serial'=>'1063827', 'book_name'=>"星辰變", 'author'=>'我吃西紅柿');
//$bookSet = array('serial'=>'1598979', 'book_name'=>"仙逆", 'author'=>'耳根');
//$bookSet = array('serial'=>'1264983', 'book_name'=>"星辰變後傳", 'author'=>'不吃西紅柿');
//$bookSet = array('serial'=>'1633208', 'book_name'=>'混沌雷修', 'author'=>'唐家三少');
//$bookSet = array('serial'=>'2134427', 'book_name'=>'異界大陸-神印王座', 'author'=>'唐家三少');
//$bookSet = array('serial'=>'1309610', 'book_name'=>"凡人修仙", 'author'=>'忘語');
//$bookSet = array('serial'=>'1231011', 'book_name'=>"千極變", 'author'=>'東方三少爺');
//$bookSet = array('serial'=>'1815376', 'book_name'=>"吞天", 'author'=>'妖白菜');
//$bookSet = array('serial'=>'1738426', 'book_name'=>"盤龍", 'author'=>'我吃西紅柿');
//$bookSet = array('serial'=>'1753100', 'book_name'=>"叱吒風雲", 'author'=>'高樓大廈');
//$bookSet = array('serial'=>'756553', 'book_name'=>"神墓", 'author'=>'晨東');
//$bookSet = array('serial'=>'985901', 'book_name'=>"寸芒", 'author'=>'我吃西紅柿');
//$bookSet = array('serial'=>'1298066', 'book_name'=>"武唐攻略", 'author'=>'府天');
//$bookSet = array('serial'=>'1672860', 'book_name'=>"永生", 'author'=>'夢入神機');
$bookSet = array('serial'=>'1543646', 'book_name'=>"武神", 'author'=>'蒼天白鶴');

$removeTags = array('img'=>'', 'a'=>'', 'i.pstatus'=>'', 'i'=>'', 
                    'div.t_attach'=>'', 'span'=>'', "<br>\n<br>"=>'<br />');


$dom = file_get_html('http://ck101.com/thread-'.$bookSet['serial'].'-1-1.html');
$result = $dom->find('div.pgs div.pg strong');
foreach($result as $target){
  $startPage = $target->innertext;
}
$result = $dom->find('div.pgs div.pg a.last');
foreach($result as $target){
  $endPage = str_replace('... ', '', $target->innertext);
}
$dom->clear();
unset($dom);

for($targetPage = $startPage; $targetPage<=$endPage; $targetPage++){
  echo "start to processed ".$targetPage."\r\n";
  if(!is_dir('books/html/'.$bookSet['serial'])){
  	mkdir('books/html/'.$bookSet['serial']);
  	echo "Dir ".$bookSet['serial']." is crerated.\r\n";
  	chmod('books/html/'.$bookSet['serial'], 0777);
  	echo "Dir ".$bookSet['serial']." is change to 777.\r\n";
  }
  $dom = file_get_html('http://ck101.com/thread-'.$bookSet['serial'].'-'.$targetPage.'-1.html');

  /*foreach($removeTags as $key=>$val){
    $result = $dom->find($key);
    foreach($result as $target){
      $target->outertext = $val;
    }
  }*/
  $result = $dom->find('div.t_fsz');
  if(0<count($result)){
    $handle = fopen('books/html/'.$bookSet['serial'].'/'.$bookSet['serial'].'-'.$targetPage.'.html', 'w');
    fwrite($handle, '<html><head></head><body>');
    foreach($result as $content){
      foreach($removeTags as $key=>$val){
        $result2 = $content->find($key);
        foreach($result2 as $target){
          $target->outertext = $val;
        }
      }
      fwrite($handle, $content->outertext);
    }
    fwrite($handle, '</body></html>');
    fclose($handle);
  }
  echo "It's ".$targetPage."/".$endPage." is processed.\r\n";
  $dom->clear();
  unset($dom);
}

?>
