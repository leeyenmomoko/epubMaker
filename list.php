<?php
  require_once('../../lib/YenZ/include.php');
  $files = findFile('books/json', '/.*\.json$/', 'REGEX');
  print_r($files);
?>
