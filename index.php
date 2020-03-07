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

// 元のSQL('SELECT m.name,m.picture,p.* FROM members m,posts p WHERE m.id=p.member_id ORDER BY p.created DESC LIMIT ?,5'); 
$posts=$db->prepare
('SELECT a.post_id,p.message,p.member_id,m.name AS m_name,rm.reacted_member_id,rm.name AS r_name,m.picture,min.minCreated,fc.favCnt,rc.rtCnt 
FROM posts p
LEFT JOIN members m ON p.member_id=m.id
-- ↓"SELECT p.id,p.created from posts p UNION SELECT r.post_id,r.created FROM retweets r"(投稿者本人の投稿・RTを全部縦に連結用)のビュー
LEFT JOIN allCreated a ON p.id=a.post_id
-- ↓postidごとの、投稿者本人が投稿した日付表示をRT分に適用させる用
LEFT JOIN (SELECT a.post_id,MIN(a.created) AS minCreated FROM allCreated a GROUP BY a.post_id) AS min ON p.id=min.post_id
-- ↓RT者の名前表示用
LEFT JOIN (SELECT m.name,r.reacted_member_id,r.created FROM members m JOIN retweets r ON m.id=r.reacted_member_id) AS rm ON a.created=rm.created
-- ↓ファボ数出力
LEFT JOIN (SELECT post_id, COUNT(post_id) AS favCnt FROM favorites GROUP BY post_id) AS fc ON p.id=fc.post_id
-- ↓RT数出力
LEFT JOIN (SELECT post_id, COUNT(post_id) AS rtCnt FROM retweets GROUP BY post_id) AS rc ON p.id=rc.post_id
ORDER BY a.created DESC LIMIT ?,5');
$posts->bindParam(1,$start,PDO::PARAM_INT);
$posts->execute();

//ファボ色分岐用
  $favColors=$db->prepare('SELECT post_id FROM favorites WHERE reacted_member_id=?');
  $favColors->execute(array(
    $_SESSION['id']
  ));
  $favColor=$favColors->fetchall();
  // var_dump($favColor);

//RT色分岐
  $rtColors=$db->prepare('SELECT post_id FROM retweets WHERE reacted_member_id=?');
  $rtColors->execute(array(
    $_SESSION['id']
  ));
  $rtColor=$rtColors->fetchall();
  
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
<p class="logout"><a href="logout.php">ログアウト</a></p>
<!-- 投稿画面 -->
<form action="" method="post">
  <p><?php echo h($member['name']); ?>さん、メッセージをどうぞ</p>
  <textarea name="message" cols="30" rows="5"><?php echo h($message); ?></textarea>
  <input type="hidden" name="reply_post_id" value="<?php echo h($_REQUEST['res']); ?>">
  <div>
  <input type="submit" value="投稿する">
  </div>
</form>

<div class="page_crearfix">
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
</div>

<!-- 投稿内容反映部分 -->
<?php
foreach($posts as $post):
  // print_r($post);
?>
<div class="msg">
<!-- 画像＆投稿内容&リプ -->
<?php
if(isset($post['reacted_member_id'])):
if($_SESSION['id']===$post['reacted_member_id']):
?>
  <p>リツイート済み</p>
<?php else: ?>
  <p>&#x21B9;<?php echo h($post['r_name']);?>  さんがリツイート</p>
<?php 
endif;
endif;
?>

  <img src="member_picture/<?php echo h($post['picture']);?>" width="48" height="50" alt="<?php echo h($post['m_name']); ?>">
  <p><?php echo makeLink(h($post['message'])); ?>
  <span class="name">(<?php echo h($post['m_name']); ?>)</span></p>

<div class="re_del_flex">
<!-- 返信&日付の部分 -->
  [<a class="reply" href="index.php?res=<?php echo h($post['post_id']); ?>">Re</a>]
  
  <p class="day"><a href="view.php?id=<?php echo h($post['post_id']); ?>">
  <?php echo h($post['minCreated']); ?></a></p>

  <?php
  if($post['reply_post_id'] > 0):
  ?>
    <a href="view.php?id=<?php echo h($post['reply_post_id']); ?>">返信元のメッセージ</a>
  <?php endif; ?>

<!-- 削除部分 -->
  <?php
  if($_SESSION['id']==$post['member_id']):
  ?>
    [<a class="delete" href="delete.php?id=<?php echo h($post['post_id']); ?>">削除</a>]
  <?php endif; ?>
</div> 
</div>

<div class="rt_fav">
<div class="retweet">
  <?php
  $rtBranch=0;
  foreach($rtColor as $rtCol){
  if($post['post_id']===$rtCol['post_id']){
    $rtBranch=$rtCol['post_id'];
    }
  }
  if($rtBranch>0):
  ?>
    <a class="r_yes" href="retweet.php?id=<?php echo h($post['post_id']); ?>">&#x21B9;
    <?php echo h($post['rtCnt']) ?>
    </a>
  <?php else: ?>
    <a class="r_no" href="retweet.php?id=<?php echo h($post['post_id']); ?>">&#x21B9;
    <?php echo h($post['rtCnt']) ?>
    </a>
  <?php endif; ?>
</div>

<!-- ファボ -->
<!-- aタグ分岐・ -->
<div class="favorite">  
<?php
  $fBranch=0;
  foreach($favColor as $fCol){
  if($post['post_id']===$fCol['post_id']){
    $fBranch=$fCol['post_id'];
  }
}
if($fBranch>0):
?>
  <a class ="f_yes" href="favorite.php?id=<?php echo h($post['post_id']); ?>">★
  <?php echo h($post['favCnt']) ?>
  </a>
<?php else: ?>
  <a class ="f_no" href="favorite.php?id=<?php echo h($post['post_id']); ?>">☆
  <?php echo h($post['favCnt']) ?></a>
<?php endif; ?>
</div> <!--favoriteクラス-->
</div><!--rt_fav-->
</div>
<?php endforeach; ?>

</body>
</html>