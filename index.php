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
    header('Location:index.php');
    exit();
  }
}

//ページを取得
$page=$_REQUEST['page'];
if($page=='') {
  $page=1;
}
$page=max($page,1);

$counts=$db->query('SELECT COUNT(*) AS cnt FROM posts');
$cnt=$counts->fetch();
$maxPage=ceil($cnt['cnt']/5);
$page=min($page,$maxPage);

$start=($page-1)*5;

$posts=$db->prepare
('SELECT m.name,m.picture,p.* FROM members m,posts p WHERE m.id=p.member_id ORDER BY p.created DESC LIMIT ?,5');
$posts->bindParam(1,$start,PDO::PARAM_INT);
$posts->execute();

//ファボのカウント取得
  $favCounts=$db->query
  ('SELECT post_id, COUNT(post_id) AS favCnt FROM favorites GROUP BY post_id');
  $favCnt=$favCounts->fetchall();
  // var_dump($favCnt);

//ファボ色分岐用
  $favColors=$db->prepare('SELECT post_id FROM favorites WHERE reacted_member_id=?');
  $favColors->execute(array(
    $_SESSION['id']
  ));
  $favColor=$favColors->fetchall();
  var_dump($favColor);

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
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css">
  <link rel="stylesheet" href="style.css">
  <title>会員ページ</title>
</head>

<body>
<div id="content">
<div style="text-align:right"><a href="logout.php">ログアウト</a></div>
<!-- 投稿画面 -->
<form action="" method="post">
  <p><?php echo h($member['name']); ?>さん、メッセージをどうぞ</p>
  <textarea name="message" cols="30" rows="5"><?php echo h($message); ?></textarea>
  <input type="hidden" name="reply_post_id" value="<?php echo h($_REQUEST['res']); ?>">
  <div>
  <input type="submit" value="投稿する">
  </div>
</form>

<ul class="paging">
  <?php if($page>1): ?>
    <li><a href="index.php?page=<?php print($page-1); ?>">前のページへ</a></li>
  <?php else: ?>
    <li>前のページへ</li>
  <?php endif; ?>
  
  <?php if($page<$maxPage): ?>
    <li><a href="index.php?page=<?php print($page+1); ?>">次のページへ</a></li>
  <?php else: ?>
    <li>次のページへ</li>
  <?php endif; ?>
</ul>

<!-- 投稿内容反映部分 -->
<?php foreach($posts as $post): ?>
<div class="msg">
<!-- 画像＆投稿内容&リプ -->
  <img src="member_picture/<?php echo h($post['picture']);?>" width="48" height="50" alt="<?php echo h($post['name']); ?>">
  <p><?php echo makeLink(h($post['message'])); ?>
  <span class="name">(<?php echo h($post['name']); ?>)</span></p>

<div class="re_del_flex">
<!-- 返信の部分 -->
  [<a class="reply" href="index.php?res=<?php echo h($post['id']); ?>">Re</a>]
  <p class="day"><a href="view.php?id=<?php echo h($post['id']); ?>"><?php echo h($post['created']); ?></a></p>
  <?php
  if($post['reply_post_id'] > 0):
  ?>
    <a href="view.php?id=<?php echo h($post['reply_post_id']); ?>">返信元のメッセージ</a>
  <?php endif; ?>

<!-- 削除部分 -->
  <?php
  if($_SESSION['id']==$post['member_id']):
  ?>
    [<a class="delete" href="delete.php?id=<?php echo h($post['id']); ?>" style="color:#F33;">削除</a>]
  <?php endif; ?>
</div> 
</div>
<!-- ファボ -->
<!-- aタグ分岐・ -->
<div class="favorite">  
  <?php
  foreach($favColor as $fCol):
  if($post['id']===$fCol['post_id']):
  ?>
  <?php
  $fBranch=$fCol['post_id'];
  var_dump($fBranch);
  ?>
  <?php
  endif; 
  endforeach;
  ?>
  <?php if($fBranch>0):
    var_dump($fBranch>0); ?>
    <a class ="f_yes" href="favorite.php?id=<?php echo h($post['id']); ?>">★
  <?php
    foreach($favCnt as $fCnt){
      if($post['id']===$fCnt['post_id']){
      echo h($fCnt['favCnt']);
      }
    }?>
  </a>
  <?php else: ?>
  <a class ="f_no" href="favorite.php?id=<?php echo h($post['id']); ?>">☆
  <?php
  foreach($favCnt as $fCnt){
    if($post['id']===$fCnt['post_id']){
    echo h($fCnt['favCnt']);
    }
  }?></a>
  <?php endif; ?>
</div>

</div>
<?php endforeach; ?>

</body>
</html>