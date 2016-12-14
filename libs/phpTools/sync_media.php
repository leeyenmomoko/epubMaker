<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
header("Content-Type:text/html; charset=utf-8");
require_once '/Library/Server/Web/Data/Sites/lib/AWSSDKforPHP/sdk.class.php';
require_once("../configs/db_set.php");
require_once("/Library/Server/Web/Data/Sites/lib/YenZ/include.php");

$transferFile = new TransferFile("../inbox/*");

$transferFile->toAWSS3("leeyenmusics.leeyen.idv.tw");
$transferFile->toLocal("/Users/leeyen/Sites/www.leeyen.idv.tw/shared/");

try{
  $db_connet = new PDO('mysql:host='.DB_HOST.'; dbname='.DB_DATABASE, DB_USER, DB_PASSWORD);
}
catch(PDOException $e){
  echo "Could not connect to db.";
  exit;
}

$query = "REPLACE INTO MD5_files(name, ext, MD5, type, size, source, path)
	            VALUES(:name, :ext, :md5, :type, :size, :source, :path )";
foreach($transferFile->files as $file){
  if(0<count($file['link'])){
    $valid_link = 0;
    foreach($file['link'] as $source=>$link){
      if(''!==$link){
        try{
      		$stmt = $db_connet->prepare($query);
      		if($stmt){
      		   $inserts = array(
              ":name"=>$file['name'],
              ":ext"=>$file['ext'],
              ":md5"=>$file['md5'],
              ":type"=>$file['type'],
              ":size"=>$file['size'],
              ":source"=>$source,
              ":path"=>$link
              
            );
      		  $transfer_result[] = $result = $stmt->execute($inserts);
      		  if($result){
      		    $valid_link++;
      		  }
      		  else{
          		$error = $stmt->errorInfo();
          		echo "Query failed with message: ".$error[2];
        		}
      		}
        }
        catch(PDOException $e){
          echo "Error : database problem has occurred: ".$e->getMessage();
        }
      }//end of $link!==''
    }//end of $file['link'] foreach
    if($valid_link == count($file['link'])){
      //unlink($file['path']);
    }
  }  
}


?>