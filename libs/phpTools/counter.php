<?php
//取得ip位址
$ip = $_SERVER['REMOTE_ADDR'];
setcookie("ever", 1, time()+7200);
//設定連結資料庫相關變數
require_once("../configs/db_set.php");
//建立資料庫連結
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
$query= 'SELECT * FROM `visitor` WHERE`ip`="'.$ip.'" ORDER BY `id` DESC';
$result = $mysqli->query($query);
//$row2 = mysqli_fetch_assoc($result);
$result->close();
//執行 SQL 命令，新增此記錄
if($_COOKIE["ever"]!=1 || !$result)
{
		$query = "INSERT INTO visitor (ip) VALUES ('$ip')";
		$result = $mysqli->query($query);
}
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
$query = "SELECT * FROM `visitor` WHERE `ip`='".$ip."' ORDER BY `id` DESC";
$result = $mysqli->query($query);
$row = mysqli_fetch_assoc($result);
$count=$row['id'];
$result->close();
$mysqli->close();
// we'll generate XML output
header('Content-Type: text/xml');
// generate XML header
echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
// create the <response> element
echo '<response>';
echo $count;
echo '</response>';
?>