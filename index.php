<?php
session_start();
require('dbconnect.php');
require('function.php');

//IDセッションに記録されているかと現在時刻より60分経過していないか
if(isset($_SESSION['id']) && $_SESSION['time']+3600>time()) {
  $_SESSION['time'] = time();
  $members = $db->prepare('SELECT * FROM members WHERE id=?');
  $members->execute(array($_SESSION['id']));
  $member=$members->fetch();
} else {
  //ログインしてない
  
  header('Location: login.php');
  exit();
}

//投稿を記録
if(!empty($_POST)) {
if($_POST['message'] !='') {
  $message=$db->prepare('INSERT INTO posts SET member_id=?,message=?,reply_post_id=?,created=NOW()');
  $message->execute(array(
    $member['id'],
    $_POST['message'],
    $_POST['reply_post_id']
  ));
  print_r($_POST);
  header('Location:index.php'); exit();
}
}

//投稿を取得
$page=$_REQUEST['page'];
if($page=='') {
  $page=1;
}
$page=max($page,1);

//最終ページを取得
$counts=$db->query('SELECT COUNT(*) AS cnt FROM posts');
$cnt=$counts->fetch();
$maxPage=ceil($cnt['cnt']/5);
$page=min($page,$maxPage);

$start=($page-1)*5;

$posts=$db->prepare('SELECT m.name,m.picture,p.* FROM members m,posts p WHERE m.id=p.member_id ORDER BY p.created DESC LIMIT ?,5');
$posts->bindParam(1,$start,PDO::PARAM_INT);
$posts->execute();

//返信
if(isset($_REQUEST['res'])) {
  $response=$db->prepare('SELECT m.name,m.picture,p.* FROM members m, posts p WHERE m.id=p.member_id AND p.id=? ORDER BY p.created DESC');
  $response->execute(array($_REQUEST['res']));
  $table=$response->fetch();
  $message='@'.$table['name'].'  '.$table['message'];
  
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <link rel="stylesheet" href="style2.css">
  <title>会員ページ</title>
</head>
<body>
<div id="content">
<div style="text-align:right"><a href="logout.php">ログアウト</a></div>
<form action="" method="post">
<p><?php echo h($member['name']); ?>さん、メッセージをどうぞ</p>
<textarea name="message" cols="50" rows="10"><?php echo h($message); ?></textarea>
<input type="hidden" name="reply_post_id" value="<?php echo h($_REQUEST['res']); ?>">
<div>
<input type="submit" value="投稿する">
</div>
</form>

<?php foreach($posts as $post): ?>
<div class="msg">
<img src="member_picture/<?php echo h($post['picture']);?>" width="48" height="50" alt="<?php echo h($post['name']); ?>">
<p><?php echo makeLink(h($post['message'])); ?><span class="name">(<?php echo h($post['name']); ?>)</span></p>
[<a href="index.php?res=<?php echo h($post['id']); ?>">Re</a>]
<p class="day"><a href="view.php?id=<?php echo h($post['id']); ?>"><?php echo h($post['created']); ?></a></p>
<?php
if($post['reply_post_id'] > 0):
?>
<a href="view.php?id=<?php echo h($post['reply_post_id']); ?>">返信元のメッセージ</a>
<?php endif; ?>
<?php
//ログイン者と投稿者が同じかの判断
if($_SESSION['id']==$post['member_id']):
?>
[<a href="delete.php?id=<?php echo h($post['id']); ?>" style="color:#F33;">削除</a>]
<?php endif; ?>

<p class="favorite">fav</p>
</div>
<?php endforeach; ?>

<ul class="paging">
  <?php
  if($page>1) {
  ?>
  <li><a href="index.php?page=<?php print($page-1); ?>">前のページへ</a></li>
  <?php
  } else {
  ?>
  <li>前のページへ</li>
  <?php
  }
  ?>
  <?php
  if($page<$maxPage) {
  ?>
  <li><a href="index.php?page=<?php print($page+1); ?>">次のページへ</a></li>
  <?php
  } else {
  ?>
  <li>次のページへ</li>
<?php
  }
?>
</ul>
</div>
</body>
</html>