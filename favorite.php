<?php
session_start();
require('dbconnect.php');
require('function.php');
?>

<?php
//ログインしてるか
if(isset($_SESSION['id'])&&($_GET['id'])) {

// ファボID＆ファボした人の組み合わせの重複確認
  $favDuplicates=$db->prepare
  ('SELECT post_id,reacted_member_id,COUNT(1) AS duplicate FROM favorites f WHERE post_id=? AND reacted_member_id=?');
  $favDuplicates->execute(array(
    $_GET['id'],
    $_SESSION['id']
  ));
  $favDup=$favDuplicates->fetch();
  var_dump($favDup);
  
    if((int)$favDup['duplicate']>0) {
      //duplicateが0じゃないなら既にファボってるのでテーブルから削除
      $favDeletes=$db->prepare
      ('DELETE FROM favorites WHERE post_id=? && reacted_member_id=?');
      $favDeletes->execute(array(
        $_GET['id'],
        $_SESSION['id']
      ));
      
    } else {
      //ファボってないならテーブルに登録
      $favActions=$db->prepare
      ('INSERT INTO favorites SET post_id=?,reacted_member_id=?,created=NOW()');
      $favActions->execute(array(
        $_GET['id'],
        $_SESSION['id']
      ));
    
    }

    header('Location:index.php');
    exit();
}
?>

