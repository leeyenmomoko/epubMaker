<?php
/*
Function Name : chk_and_mkdir
  The function will check all of the path directors is exist,
  if not created it.
Creator : Lee Yen
Date : 2012/11/24
*/
function chk_and_mkdir($path){
  $original_dir = getcwd();
  $path = trim($path);
  $owner = exec('whoami');
  $dirs = explode(DIRECTORY_SEPARATOR, $path);
  if(is_array($dirs)){
    $dirs_count = count($dirs);
    for($index = 0; $index<$dirs_count; $index++){
      if(!is_dir(getcwd(). DIRECTORY_SEPARATOR .$dirs[$index]) ){
        if( !is_writeable(getcwd()) || !mkdir(getcwd(). DIRECTORY_SEPARATOR .$dirs[$index]) ){
          return false;
        }
      }
      $file_group = posix_getpwuid(filegroup(getcwd(). DIRECTORY_SEPARATOR .$dirs[$index]));
      if($owner==$file_group['name']){
        if(!chmod(getcwd(). DIRECTORY_SEPARATOR .$dirs[$index], 0775)){
          echo getcwd(). DIRECTORY_SEPARATOR .$dirs[$index]." chmod failed";
          return false;
        }
      }
      if(!chdir($dirs[$index]. DIRECTORY_SEPARATOR)){
        return false;
      }
    }
    chdir($original_dir);
    return true;
  }
}

/*
Function Name : list_dir_file
  The function will list all valid files in the dir.
Creator : Lee Yen
Date : 2012/11/24
*/
function list_dir_file($target_dir, $valid_patterns = array('')){
  $output = array();
  $dir = dir($target_dir);
  while($entry = $dir->read()){
    $valid = false;
    foreach($valid_patterns as $valid_pattern){
      if(preg_match('/^.*'.quotemeta($valid_pattern).'$/i', $entry)){
        $valid = true;
        break;
      }
    }
    if($valid){
      $output[] = $entry;
    }
  }
  return $output;
}
?>