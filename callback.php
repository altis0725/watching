<?php

	
session_name("member");
session_start();

require_once("twitteroauth.php");

// Consumer keyの値

$consumer_key = "qnnhUFIVVxPzqkwjBByVQ";

// Consumer secretの値

$consumer_secret = "zExEaRwlZDjJlqpC38f52h6uBgt9BRbUrQGmkLOw58";

// パラメータからoauth_verifierを取得

$verifier = $_GET['oauth_verifier'];

// OAuthオブジェクト生成
//echo "Object<br>";
print_r($_SESSION);
echo "<br>";
$to = new TwitterOAuth($consumer_key,$consumer_secret,$_SESSION['request_token'],$_SESSION['request_token_secret']);
print_r($to);
echo "<br>";
// oauth_verifierを使ってAccess tokenを取得
//echo "$verifier<br>";
$access_token = $to->getAccessToken($verifier);

// token keyとtoken secret, user_id, screen_nameをセッションに保存

$_SESSION['oauth_token'] = $access_token['oauth_token'];

$_SESSION['oauth_token_secret'] = $access_token['oauth_token_secret'];

//TwitterのID(数値です)

$_SESSION['user_id'] = $access_token['user_id'];

//スクリーンネーム(いわゆる、アドレスバーに表示される部分です)

$_SESSION['screen_name'] = $access_token['screen_name'];

print_r($_SESSION);
echo "<br><a href='twtest.html'>戻る</a>"
?>
