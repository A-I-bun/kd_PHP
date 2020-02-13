<?php
try {
  $db=new PDO('mysql:dbname=reactions_mini_bbs;host=localhost;charset=utf8','root','root');
} catch(PDOException $e) {
  echo 'DB接続エラー：' .$e->getMessage();
  print_r($e);
}
?>
