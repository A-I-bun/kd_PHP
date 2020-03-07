<?php
session_start();
require('dbconnect.php');
require('function.php');
?>

<?php
//ログインしてるか
if(isset($_SESSION['id'])&&($_GET['id'])) {

// RTID＆RTした人の組み合わせの重複確認
  $rtDuplicates=$db->prepare
  ('SELECT post_id,reacted_member_id,COUNT(post_id) AS duplicate FROM retweets WHERE post_id=? AND reacted_member_id=?');
  $rtDuplicates->execute(array(
    $_GET['id'],
    $_SESSION['id']
  ));
  $rtDup=$rtDuplicates->fetch();
  var_dump($rtDup);
  
    if((int)$rtDup['duplicate']>0) {
      //duplicateが0じゃないならテーブルから削除
      $rtDeletes=$db->prepare
      ('DELETE FROM retweets WHERE post_id=? AND reacted_member_id=?');
      $rtDeletes->execute(array(
        $_GET['id'],
        $_SESSION['id']
      ));
      
    } else {
      //RTしてないならテーブルに登録
      $rtActions=$db->prepare
      ('INSERT INTO retweets SET post_id=?,reacted_member_id=?,created=NOW()');
      $rtActions->execute(array(
        $_GET['id'],
        $_SESSION['id']
      ));
    
    }

    header("Location:".$_SERVER['HTTP_REFERER']);
    exit();
}
?>