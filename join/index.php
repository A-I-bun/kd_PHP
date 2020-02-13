<?php
session_start();
require('../dbconnect.php');
require('../function.php');

//入力情報が空じゃなければ以下の各項目チェック
if(!empty($_POST)) {
  //error項目の確認
  if($_POST['name'] == '') {
    $error['name'] ='blank';
  }
  if($_POST['email'] == '') {
    $error['email'] ='blank';
  }
  if(strlen($_POST['password']) <4) {
    $error['password'] ='length';
  }
  if($_POST['password'] =='') {
    $error['password'] ='blank';
  }
  
  $fileName=$_FILES['image']['name'];
  if(!empty($fileName)) {
    $ext=substr($fileName,-3);
    if($ext != 'jpg' && $ext != 'gif' && $ext !='png') {
      $error['image'] = 'type';
    }
  }

  //重複チェック
  if(empty($error)) {
    $member=$db->prepare('SELECT COUNT(*) AS cnt FROM members WHERE email=?');
    $member->execute(array($_POST['email']));
    $record = $member->fetch();
    if ($record['cnt'] > 0) {
      $error['email']='duplicate';
    }
  }
  
  if(empty($error)) {
    //画像をアップロード
    $image=date('YmdHis') . $_FILES['image']['name'];
    move_uploaded_file($_FILES['image']['tmp_name'],'../member_picture/' . $image);

    $_SESSION['join'] = $_POST;
    $_SESSION['join']['image'] = $image;

    header('Location:check.php');
    exit();
  }
}
    var_dump($_FILES);
    var_dump($image);
    var_dump($error);

//書き直す時
if($_REQUEST['action'] == 'rewrite') {
  $_POST = $_SESSION['join'];
  $error['rewrite'] = true;
  print_r($_SESSION);
  
}


?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>ひとこと掲示板</title>
</head>
<body>
<p>次のフォームに必要事項をご記入ください。</p>
<form action="" method="post" enctype="multipart/form-data">
<!--ファイルの送信フォームがあるとき必須enc~-->

<dl>
<dt>ニックネーム<span class="required">必須</span></dt>
<dd><input type="text" name="name" size="35" maxlength="255" value="<?php echo h($_POST['name']); ?>">
<?php if ($error['name'] == 'blank'): ?>
<p class="error">※　ニックネームを入力してください</p>
<?php endif; ?>
</dd>

<dt>メールアドレス<span class="required">必須</span></dt>
<dd><input type="text" name="email" size="35" maxlength="255" value="<?php echo h($_POST['email']); ?>">
<?php if ($error['email'] == 'blank'): ?>
<p class="error">*　メールアドレスを入力してください</p>
<?php endif; ?>
<?php if ($error['email'] == 'duplicate'): ?>
<p class="error">*　指定されたメールアドレスはすでに登録されています</p>
<?php endif; ?>
</dd>

<dt>パスワード<span class="required">必須</span</dt>
<dd><input type="password" name="password" size="10" maxlength="20" value="<?php echo h($_POST['password']); ?>">
<?php if ($error['password']=='blank'): ?>
<p class="error">* パスワードを入力してください</p>
<?php endif; ?>
<?php if($error['password']=='length'): ?>
<p class="error">* パスワードは4文字以上で入力してください</p>
<?php endif; ?>
</dd>

<dt>写真など</dt>
<dd><input type="file" name="image" size="35" /></dd>
<?php if($error['image'] == 'type'): ?>
<p class="error">* 写真などは「．gif」または「.jpg」,「.png」の画像を指定してください</p>
<?php endif; ?>
<?php if(!empty($error)): ?> <!--正にしないとrewriteのときエラー文章でないんだな-->
<p class="error">* 恐れ入りますが、画像を改めて指定してください</p>
<?php endif; ?>
</dl>
<div><input type="submit" value="入力内容を確認する" /></div></dl>
</form>
</body>
</html>