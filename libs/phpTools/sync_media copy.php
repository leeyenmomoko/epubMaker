<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
header("Content-Type:text/html; charset=utf-8");
require_once '/Library/Server/Web/Data/Sites/lib/AWSSDKforPHP/sdk.class.php';
require_once("../configs/db_set.php");
require_once("/Library/Server/Web/Data/Sites/lib/YenZ/include.php");


try{
  $db_connet = new PDO('mysql:host='.DB_HOST.'; dbname='.DB_DATABASE, DB_USER, DB_PASSWORD);
}
catch(PDOException $e){
  echo "Could not connect to db.";
  exit;
}
$shared_path = "/Users/leeyen/Sites/www.leeyen.idv.tw/shared/";
$shared_path = realpath($shared_path).DIRECTORY_SEPARATOR;

// Instantiate the AmazonS3 class
$s3 = new AmazonS3();
$bucket = 'leeyenmusics.leeyen.idv.tw';
$timeout = 0;
$exists = $s3->if_bucket_exists($bucket);
while (!$exists){
	// Not yet? Sleep for 1 second, then check again
	sleep(1);
	$exists = $s3->if_bucket_exists($bucket);
	if(++$timeout>=20){
  	die("Error : s3 bucket '".$bucket."' not exist.\r\n");
  	break;
	}
}

$local_files = array_filter( array_map('filePath', glob("../musics/datas/*/*") ) ) ;
foreach($local_files as $file){
  $transfer_result = array();

  $file_name = array_pop(explode(DIRECTORY_SEPARATOR, $file));
  $base_file_name = substr($file_name, 0, strrpos($file_name, "."));
  echo "start to process ".$file_name."\r\n";
  $file_ext = substr($file_name, strrpos($file_name, "."));
  $md5_file = md5_file($file);
  echo "MD5: ".$md5_file."\r\n";
  $inserts = array(
    ":name"=>$file_name,
    ":type"=>mime_content_type($file),
    ":link"=>"",
    ":md5"=>$md5_file
  );
  
  //shared by S3
  if(!$s3->if_object_exists($bucket, $md5_file)){
    echo "uploading to S3 ".$md5_file;
    $respose = $s3->create_object($bucket, $md5_file, array(
		  'fileUpload' => $file,
			'acl'=>AmazonS3::ACL_PUBLIC,
		  'contentType'=>$inserts[':type']
		));
		if((int) $respose->isOK()){
		  echo " isOK.\r\n"; 
		}
  }
  if( ''!==$inserts[":link"] = $s3->get_object_url($bucket, $md5_file) ){
    $query = "REPLACE INTO MD5_files(name, MD5, type, path)
  	            VALUES(:name, :md5, :type, :link )";
  	try{
  		$stmt = $db_connet->prepare($query);
  		if($stmt){
  		  $transfer_result[] = $result = $stmt->execute($inserts);
  		  if(!$result){
      		$error = $stmt->errorInfo();
      		echo "Query failed with message: ".$error[2];
    		}
  		}
    }
    catch(PDOException $e){
      echo "Error : database problem has occurred: ".$e->getMessage();
    }
  }
  
  //shared by localhost
  if(!file_exists($shared_path.$md5_file)){
    copy($file, $shared_path.$md5_file);
  }
  if(file_exists($shared_path.$md5_file)){
    $inserts[":link"] = $shared_path.$md5_file;
    $query = "REPLACE INTO MD5_files(name, MD5, type, path)
  	            VALUES(:name, :md5, :type, :link )";
  	try{
  		$stmt = $db_connet->prepare($query);
  		if($stmt){
  		  $transfer_result[] = $result = $stmt->execute($inserts);
  		  if(!$result){
      		$error = $stmt->errorInfo();
      		echo "Query failed with message: ".$error[2];
    		}
  		}
    }
    catch(PDOException $e){
      echo "Error : database problem has occurred: ".$e->getMessage();
    }
  }
  
  //if every shared point is ready, clean the file.
  if(false === array_search(false, $transfer_result)){
    unlink($file);
  }
  
}


?>