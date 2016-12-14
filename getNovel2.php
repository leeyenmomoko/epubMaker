<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set("Asia/Taipei");
require('tools/simple_html_dom.php');
include_once("tools/epub2/EPub.php");

//$bookSet = array('serial'=>'1773488', 'book_name'=>'天珠變', 'author'=>'唐家三少');
//$bookSet['serial'] = array('1309610', '凡人修仙傳', '忘語');
//$bookSet = array('serial'=>'1309610', 'book_name'=>"凡人修仙", 'author'=>'忘語');
//$bookSet = array('serial'=>'1231011', 'book_name'=>"千極變", 'author'=>'東方三少爺');
//$bookSet = array('serial'=>'1063827', 'book_name'=>"星辰變", 'author'=>'我吃西紅柿');
//$bookSet = array('serial'=>'1598979', 'book_name'=>"仙逆", 'author'=>'耳根');
//$bookSet = array('serial'=>'1264983', 'book_name'=>"星辰變後傳", 'author'=>'不吃西紅柿');
//$bookSet = array('serial'=>'1633208', 'book_name'=>'混沌雷修', 'author'=>'唐家三少');
//$bookSet = array('serial'=>'2134427', 'book_name'=>'異界大陸-神印王座', 'author'=>'唐家三少');
//$bookSet = array('serial'=>'1815376', 'book_name'=>"吞天", 'author'=>'妖白菜');
//$bookSet = array('serial'=>'1738426', 'book_name'=>"盤龍", 'author'=>'我吃西紅柿');
//$bookSet = array('serial'=>'1753100', 'book_name'=>"叱吒風雲", 'author'=>'高樓大廈');
//$bookSet = array('serial'=>'756553', 'book_name'=>"神墓", 'author'=>'晨東');
//$bookSet = array('serial'=>'985901', 'book_name'=>"寸芒", 'author'=>'我吃西紅柿');
//$bookSet = array('serial'=>'1298066', 'book_name'=>"武唐攻略", 'author'=>'府天');
//$bookSet = array('serial'=>'1672860', 'book_name'=>"永生", 'author'=>'夢入神機');
$bookSet = array('serial'=>'1543646', 'book_name'=>"武神", 'author'=>'蒼天白鶴');

$f_var['numbers'] = array('零', '一', '兩', '三', '四', '五', '六', '七', '八', '九', '十', '佰', '千', '萬', '億', '兆', '京', '垓', '秭', '穰', '溝', '澗', '正', '載', '極', '恆河沙', '阿僧祇', '那由他', '不可思議', '無量大數');
$f_var['numbers2'] = array('1234567890', '零', '一', '二', '兩', '三', '四', '五', '六', '七', '八', '九', '十', '百', '佰', '千', '萬', '億', '兆', '京', '垓');
$localbooks = list_local_books();
$localbooks = array();
if(!in_array($bookSet['serial'].'.txt', $localbooks)){
  get_book($bookSet, $f_var);
}

if($article = read_book($bookSet['serial'])){
  //print_r($article);
  foreach($article as $val){
    //echo $val['title'].'<br />';
  }
  //print_r($article[2]);
  ob_start();
  mkEpub($bookSet['book_name'], $bookSet['author'], $article, $f_var);
  $epub = ob_get_contents();
  ob_end_clean();
  $handle = fopen($bookSet['book_name'].".epub", 'w');
  fwrite($handle, $epub);
  fclose($handle);
}

//1,1000,0020,1004
/*if(isset($_GET['no']) && ''!=$_GET['no']){
  $f_var['input_number'] = $_GET['no'];
}
else{
  $f_var['input_number'] = '100260306000010000500006156153123984560123486406504561064894430545604560';
}
echo '<fieldset>'.number2chtstr($f_var).'</fieldset>';*/
function number2chtstr($f_var){
  $number = $f_var['input_number'];
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
        $temp = $f_var['numbers'][substr($val, $count, 1)];
        if('零'==$temp){
          $zero_count++;
        }
        if(0!=$count && '兩'==$temp){
          $temp = '二';
        }
        $cht_number .= $temp;
        if($count!=$strlen && $strlen-$count>1 && $temp!='零'){
          $cht_number .= $f_var['numbers'][$strlen+8-$count];
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
          $output .= $f_var['numbers'][11+$count];
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
function list_local_books(){
  $output = array();
  $valid = array('..', '.', '.txt');
  $dir = dir('books/txt');
  while($entry = $dir->read()){
    if(!in_array($entry, $valid)){
      $output[] = $entry;
    }
  }
  return $output;
}
function get_book($bookSet, $f_var){
  $removeTags = array('font'=>'', 'strong'=>'', 'img'=>'', 'a'=>'', 'i.pstatus'=>'', 
                      'i'=>'', 'div.t_attach'=>'', 'span'=>'', 
                      "<br>\n<br>"=>'<br />');
  $content = '';
  // 產生DOM物件
  
  for($targetPage = 1; is_file('books/html/'.$bookSet['serial'].'/'.$bookSet['serial'].'-'.$targetPage.'.html'); $targetPage++){
    $dom = file_get_html('books/html/'.$bookSet['serial'].'/'.$bookSet['serial'].'-'.$targetPage.'.html');
    //echo 'page'.$targetPage." is operating.\r\n";
    foreach($removeTags as $key=>$val){
      $result = $dom->find($key);
      foreach($result as $target){
        switch($key){
          case 'strong':
            $target->outertext = $target->innertext;
            break;
          case 'font':
            $target->outertext = $target->innertext;
            break;
          default:
            $target->outertext = $val;
        }
      }
    }
    $result = $dom->find('div.t_fsz');
    
    $title_spliter = array('章', '節');
    foreach($result as $target){
      //echo $target->innertext;
      $content = '';
      $title = '';
      if($postmessage = $target->find('h2', 0)){
        $postmessage = str_replace('&nbsp;', '', $postmessage->innertext);
        $postmessage = str_replace(' ', '', $postmessage);
        //echo $postmessage;
        if(chk_title($f_var, $postmessage)){
          $spliter_pos = array();
          foreach($title_spliter as $spliter){
            $spliter_pos[]= mb_strrpos($postmessage, $spliter, 0, 'UTF-8')+1;
          }
          $title = strip_tags(mb_substr($postmessage, max($spliter_pos), mb_strlen($postmessage), 'UTF-8'));
        }
      }
      $temp2 = $target->find('td.t_f', 0);
      if($temp2){
        $temp = explode('<br />', $temp2->innertext );
        foreach($temp as $str){
          $str = trim($str);
          $str = str_replace(' ', '', $str);
          $str = str_replace('&nbsp;', '', $str);
          $str = str_replace('　', '', $str);
          if(''!=$str){
            if(chk_title($f_var, $str)){
              $spliter_pos = array();
              foreach($title_spliter as $spliter){
                $spliter_pos[]= mb_strrpos($str, $spliter, 0, 'UTF-8')+1;
              }
              if(''!=trim($content) && ''!=trim($title)){
                $article[] = array('title'=>$title, 'content'=>$content);
                $content = '';
              }
              $title = strip_tags(mb_substr($str, max($spliter_pos), mb_strlen($str), 'UTF-8'));
            }
            else{
              $content .= "<p>".$str."</p>";
            }
            //$all .= $str;
          }
        }
        //$f_var['input_number'] = $article_ch;
        if(''!=trim($content)){
          $article[] = array('title'=>$title, 'content'=>$content);
          //echo 'title: '.$title." is get.\r\n";
        }
      }
    }
    /*if(2<$targetPage){
      break;
    }
    */
  }
  //print_r($article);
  if(isset($article) && is_array($article)){
    $handle = fopen('books/txt/'.$bookSet['serial'].'.txt', 'w');
    fwrite($handle, json_encode($article));
    fclose($handle);
    //echo 'book: '.$bookSet['book_name']." is translated to txt.\r\n";
  }
  //echo "Total=". count($article);
}

function read_book($book_serial){
  $contents = '';
  if(isset($book_serial) && is_file('books/txt/'.$book_serial.'.txt')){
    $contents = file_get_contents('books/txt/'.$book_serial.'.txt'); 
  }
  if(''==$contents){
    return false;
  }
  else{
    //echo json_decode($contents);
    return json_decode($contents, true);
  }
}

function mkEpub($name, $author, $article, $f_var){
  //echo 'making  '.$bookSet['book_name']." to epub .\r\n";
  $fileDir = './';
  $book = new EPub();
  $book->setTitle($name);
  $book->setIdentifier("http://JohnJaneDoePublications.com/books/TestBook.html", EPub::IDENTIFIER_URI); // Could also be the ISBN number, prefered for published books, or a UUID.
  $book->setLanguage("tw"); // Not needed, but included for the example, Language is mandatory, but EPub defaults to "en". Use RFC3066 Language codes, such as "en", "da", "fr" etc.
  $book->setDescription("This is a brief description\nA test ePub book as an example of building a book in PHP");
  $book->setAuthor($author, $author); 
  $book->setPublisher($author, "http://web.leeyen.idv.tw/"); // I hope this is a non existant address :) 
  $book->setDate(time()); // Strictly not needed as the book date defaults to time().
  $book->setRights("Copyright and licence information specific for the book."); // As this is generated, this _could_ contain the name or licence information of the user who purchased the book, if needed. If this is used that way, the identifier must also be made unique for the book.
  $book->setSourceURL("http://web.leeyen.idv.tw/");

  //include_once 'tools/epub2/EPubChapterSplitter.php';
  //$splitter = new EPubChapterSplitter();
  $cssData = "body {\n  margin-left: .5em;\n  margin-right: .5em;\n  text-align: justify;\n}\n\n
              p {\n font-family: serif;\n  font-size: 10pt;\n  text-align: justify;\n  text-indent: 1em;\n  margin-top: 0px;\n  margin-bottom: 1ex;\n}\n\n
              h1, h2 {\n  font-family: sans-serif;\n  font-style: italic;\n  text-align: center;\n  background-color: #white;\n  color: black;\n  width: 100%;\n}\n\n
              h1 {\n    margin-bottom: 2px;\n}\n\nh2 {\n    margin-top: -2px;\n    margin-bottom: 2px;\n}\n";
  $book->addCSSFile("styles.css", "css1", $cssData);
  $content_start =
    "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
	. "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
	. "    \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
	. "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"
	. "<head>"
	. "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n"
	. "<link rel=\"stylesheet\" type=\"text/css\" href=\"styles.css\" />\n"
	. "<title>".$name."</title>\n"
	. "</head>\n"
	. "<body>\n";
  $bookEnd = "</body>\n</html>\n";
  $cover = $content_start . "<h1>".$name."</h1>\n<h2>By: ".$author."</h2>\n"
    . "</body>\n</html>\n";
  $book->addChapter("Notices", "Cover.html", $cover);
  $ch = 1;
  foreach($article as $data){
    $f_var['input_number'] = $ch;
    $chapter = $content_start . "<h1>第".number2chtstr($f_var).'章 - '.$data['title']."</h1>\n"
    ."<h2></h2>\n"
    .$data['content']
    .$bookEnd;
    $book->addChapter("第".number2chtstr($f_var).'章 - '.$data['title'], "Chapter".str_pad($ch, 5, "0", STR_PAD_LEFT).".html", $chapter);
    $ch++;
     //echo $chapter." is completed.\r\n";
  }
  $book->finalize();
  $zipData = $book->sendBook($name);
}
function chk_title($f_var, $title){
  //echo '/.*第['.implode('',$f_var['numbers2']).']*章.*/i';
  if(preg_match('/.*第['.implode('',$f_var['numbers2']).']*章.*/i', $title)
    || preg_match('/.*第['.implode('',$f_var['numbers2']).']*節/i', $title) 
    //|| preg_match('/.*['.implode('',$f_var['numbers']).']*節.*/i', $title) 
    || preg_match('/.*ch .*/i', $title)
    /*|| preg_match('/.*['.implode('',$f_var['numbers2']).']*章/i', $title)*/){
    return true;
  }
  else{
    return false;
  }
}


?>
