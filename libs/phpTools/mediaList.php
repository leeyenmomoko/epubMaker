<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once("../configs/db_set.php");
require_once("../../lib/YenZ/include.php");
require_once("obj_ajax_response.php");
$site_root = "/Library/Server/Web/Data/Sites/www.leeyen.idv.tw/";

$data = array(); //output_data


$query = "SELECT media_info.*, MD5_files.type, MD5_files.path as path, MD5_files.source, media_subtitle.media_subtitle as subtitle 
          FROM `media_info`
          LEFT JOIN MD5_files ON ((MD5_files.name=media_info.name) OR (MD5_files.name=media_info.album)) AND MD5_files.enable = 1 
          LEFT JOIN media_subtitle ON MD5_files.MD5 = media_subtitle.md5_file
          ORDER BY id ASC, MD5_files.source DESC, MD5_files.type DESC" ;
try{
  $db_connet = new PDO('mysql:host='.DB_HOST.'; dbname='.DB_DATABASE, DB_USER, DB_PASSWORD);
}
catch(PDOException $e){
  echo "Could not connect to db.";
  exit;
}
$result = $db_connet->query($query);
while($row = $result->fetch(PDO::FETCH_ASSOC)){
  $row = trimArray($row);
  if('local' == $row['source']){
    $row['path'] = str_replace($site_root, "", $row['path']);
  }
  $row['path'] = str_replace("http://leeyenmusics.leeyen.idv.tw.s3.amazonaws.com", "https://d3g324yhbtbw6c.cloudfront.net", $row['path']);
  switch($row['type']){
    case 'application/ogg':
      $row['type'] = "audio/ogg";
      break;
    case 'application/mp3':
      $row['type'] = "audio/mp3";
      break;
    case 'application/octet-stream':
      $row['type'] = "audio/mp3";
      break;  
  }
  if( !isset($data[$row['id']]) ){
    $valid = array();
    $valid['id'] = $row['id'];
    $valid['name'] = $row['name'];
    $valid['artist'] = $row['artist'];
    $valid['album'] = $row['album'];
    if(strstr($row['type'], 'image/')){
      $valid['cover'] = $row['path'];
    }
    else{
      $valid['sources'][] = array("src" => $row['path'], "type"=>$row['type'], "subtitle"=>$row['subtitle']);
    }
    $data[$row['id']] = $valid;
  }
  else{
    if(strstr($row['type'], 'image/')){
      if(!isset($data[$row['id']]['cover'])){
        $data[$row['id']]['cover'] = $row['path'];
      }
    }
    else{
      $data[$row['id']]['sources'][] = array("src" => $row['path'], "type"=>$row['type'], "subtitle"=>$row['subtitle']);
    }
  }
}

$output = new Ajax_media(array_values($data));

$resouce_type = 'xml';
if(isset($_REQUEST['resource_type'])){
  $resouce_type = $_REQUEST['resource_type'];
}
switch($resouce_type){
  case 'xml':
    header("Content-Type:text/xml; charset=utf-8");
    print_r($output->toXml());
    break;
  case 'json':
    header("Content-Type:text/plain; charset=utf-8");
    print_r($output->toJson());
    break;
  default:
    print_r($output->toXml());
}

?>
