<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('memory_limit', '2048M');
date_default_timezone_set("Asia/Taipei");
require_once('configs/db_set_epubMaker.php');
require_once('libs/phpTools/dir_operation.php');
require_once('libs/phpTools/string_operation.php');
require_once('libs/simpleHtmlDom/simple_html_dom.php');
require_once("libs/EPub/EPub.php");

$f_var['book_path'] = 'books/';
$f_var['mode'] = 'db';
$f_var['refreash'] = true;
try{
    $f_var['db_connet'] = new PDO(DB_TYPE.':host='.DB_HOST.'; dbname='.DB_DATABASE, DB_USER, DB_PASSWORD);
}
catch(PDOException $e){
  require_once("setting.php");
  $f_var['mode'] = 'standard';
  echo "Could not connect to db.\r\n<br />";
  if(isset($_POST['source']) && $_POST['source'] !== '' &&
    isset($_POST['id']) && $_POST['id'] !== '' &&
    isset($_POST['title']) && $_POST['title'] !== '' &&
    isset($_POST['author']) && $_POST['author'] !== ''
  ){
    empty($f_var['books']);
    $f_var['books'][] = array('serial'=>$_POST['id'], 'name'=>$_POST['title'], 'author'=>$_POST['author'], 'source'=>$_POST['source']);
  }
  else{
    echo "No POST data too, use setting.php to make the epub.\r\n<br />";
  }
}
if('db'===$f_var['mode']){
  $query = 'SELECT *
            FROM books
            WHERE enabled = 1
            ORDER BY id' ;
  $result = $f_var['db_connet']->query($query);
  while($row = $result->fetch(PDO::FETCH_ASSOC)){
    $f_var['books'][] = $row;
  }
  $query = 'SELECT *
            FROM source
            ORDER BY id' ;
  $result = $f_var['db_connet']->query($query);
  while($row = $result->fetch(PDO::FETCH_ASSOC)){
    $row['removeTags'] = json_decode($row['removeTags'], true);
    $row['title_spliter'] = json_decode($row['title_spliter'], true);
    $row['ajax'] = json_decode($row['ajax'], true);
    $f_var['sources'][$row['name']] = $row;
  }
}

if( isset($f_var['books']) && 0<count($f_var['books'])){
  foreach($f_var['books'] as $f_var['bookSet']){
    if(isset($f_var['sources'][$f_var['bookSet']['source']])){
      $f_var['source_setting'] = $f_var['sources'][$f_var['bookSet']['source']];
      if( chk_and_mkdir($f_var['book_path'].'json/') &&
          chk_and_mkdir($f_var['book_path'].'epub/')
      ){
        $book_list = list_dir_file($f_var['book_path']."json", array('.json'));
        if($f_var['refreash'] || !in_array($f_var['bookSet']['serial'].'.json',$book_list)){
          getBook($f_var);
        }

        if($articles = read_book($f_var['bookSet']['serial'])){
          if('db'===$f_var['mode']){
            $query = 'UPDATE books
                      SET chapters='.count($articles).'
                      WHERE serial="'.$f_var['bookSet']['serial'].'"
                        AND enabled=1' ;
            $result = $f_var['db_connet']->query($query);
          }
          echo "start to make epub.\r\n<br />";
          $epub = mkEpub($f_var['bookSet']['name'], $f_var['bookSet']['author'], $articles, $f_var);
          $handle = fopen($f_var['book_path'].'epub/'.$f_var['bookSet']['name'].".epub", 'w');
          fwrite($handle, $epub);
          fclose($handle);
          echo $f_var['bookSet']['name'].".epub is completed. \r\n<br />";
          echo "URL: <a href='" . $f_var['book_path'].'epub/'.$f_var['bookSet']['name'].".epub' target='_blank'>Download</a>"
        }
      }
    }
    else{
      echo "source ".$f_var['bookSet']['source']." is not existed. \r\n<br />";
    }
  }
}

function getBook($f_var){
  // get the threads of first and last in page link.
  $book_link = str_replace('[serial]', $f_var['bookSet']['serial'], $f_var['source_setting']['link']);
  $dom = file_get_html(str_replace('[page]', '1', $book_link));
  $result = $dom->find($f_var['source_setting']['first_page_selector']);
  if(0>=count($result)){
    echo 'can not find first page with pattern '.$f_var['source_setting']['first_page_selector']." .\r\n<br />";
    exit;
  }
  foreach($result as $target){
    $startPage = $target->innertext;
  }

  $result = $dom->find($f_var['source_setting']['pages_container']);
  if(0>=count($result)){
    echo 'can not find page container with pattern '.$f_var['source_setting']['pages_container']." .\r\n<br />";
    exit;
  }
  $pages = array();
  foreach($result as $target){
    $page_nubmer = str_replace('... ', '', $target->innertext);
    if(!in_array($page_nubmer, $pages) && preg_match('/^\d*$/i', $page_nubmer)){
      $pages[] = $page_nubmer;
    }
  }
  $endPage = max($pages);
  $dom->clear();
  unset($dom);

	//start of page operate
	for($targetPage = $startPage; $targetPage<=$endPage; $targetPage++){
    echo "process ".$targetPage."(".$endPage.")";
    $dom_str = file_get_contents(str_replace('[page]', $targetPage, $book_link));

    if(isset($f_var['source_setting']['ajax'][$targetPage])){
      $dom_str .= file_get_contents(str_replace('[serial]', $f_var['bookSet']['serial'], $f_var['source_setting']['ajax'][$targetPage]['data']));
    }
    //$dom = file_get_html(str_replace('[page]', $targetPage, $book_link));
    $dom = str_get_html($dom_str);

    //replce the tag and content in removeTags array
    foreach($f_var['source_setting']['removeTags'] as $key=>$val){
        $result = $dom->find($key);
        foreach($result as $target){
          switch($val){
            case 'removeTag':
              $target->outertext = $target->innertext;
              break;
            default:
              $target->outertext = $val;
          }
        }
      }

    //find the main content to save
    $result = $dom->find($f_var['source_setting']['main_selector']);

    if(0<count($result)){
        foreach($result as $target){
          $content = '';
          $title = '';

          //find chapter title
          if($postmessage = $target->find('h2', 0)){
            $postmessage = str_replace('&nbsp;', '', $postmessage->innertext);
            $postmessage = str_replace(' ', '', $postmessage);
            if(chk_title($postmessage)){
              $spliter_pos = array();
              foreach($f_var['source_setting']['title_spliter'] as $spliter){
                $spliter_pos[]= mb_strrpos($postmessage, $spliter, 0, 'UTF-8')+1;
              }
              $title = strip_tags(mb_substr($postmessage, max($spliter_pos), mb_strlen($postmessage), 'UTF-8'));
            }
          }
          //find the main content
          $temp2 = $target->find($f_var['source_setting']['main_content_container'], 0);
          if($temp2){
            $temp = explode('<br />', $temp2->innertext );
            foreach($temp as $str){
              $str = trim($str);
              $str = str_replace(' ', '', $str);
              $str = str_replace('&nbsp;', '', $str);
              $str = str_replace('　', '', $str);
              if(''!=$str){
                if(chk_title($str)){
                  $spliter_pos = array();
                  foreach($f_var['source_setting']['title_spliter'] as $spliter){
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
              }
            }
            if(''!=trim($content)){
              $article[] = array('title'=>$title, 'content'=>$content);
            }
          }
        }
      }
    echo " completed.\r";
    $dom->clear();
    unset($dom);
  }//end of page operate
  echo "\n";
  //encode articles to json and save
  if(isset($article) && is_array($article)){

    $handle = fopen('books/json/'.$f_var['bookSet']['serial'].'.json', 'w');
    fwrite($handle, json_encode($article));
    fclose($handle);
  }
}

function read_book($book_serial){
  $contents = '';
  if(isset($book_serial) && is_file('books/json/'.$book_serial.'.json')){
    $contents = file_get_contents('books/json/'.$book_serial.'.json');
  }
  if(''==$contents){
    return false;
  }
  else{
    return json_decode($contents, true);
  }
}

function mkEpub($name, $author, $articles, $f_var){
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
  foreach($articles as $data){
    //$f_var['input_number'] = $ch;
    $chapter = $content_start . "<h1>第".number2chtstr($ch).'章 - '.$data['title']."</h1>\n"
    ."<h2></h2>\n"
    .$data['content']
    .$bookEnd;
    $book->addChapter("第".number2chtstr($ch).'章 - '.$data['title'], "Chapter".str_pad($ch, 5, "0", STR_PAD_LEFT).".html", $chapter);
    $ch++;
     //echo $chapter." is completed.\r\n<br />";
  }
  $book->finalize();
  return $book->getBook();
  //$zipData = $book->sendBook($name);
}

function chk_title($title){
  $numbers = array('1234567890', '零', '一', '二', '兩', '三', '四', '五', '六', '七', '八', '九', '十', '百', '佰', '千', '萬', '億', '兆', '京', '垓');
  if(preg_match('/.*第['.implode('', $numbers).']*章.*/i', $title)
    //|| preg_match('/.*第['.implode('', $numbers).']*節/i', $title)
    || preg_match('/.*ch .*/i', $title)
  ){
    return true;
  }
  else{
    return false;
  }
}

?>
