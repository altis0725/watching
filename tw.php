<?php
require_once("twitteroauth.php");
// Consumer keyの値
$consumer_key = "qnnhUFIVVxPzqkwjBByVQ";

// Consumer secretの値
$consumer_secret = "zExEaRwlZDjJlqpC38f52h6uBgt9BRbUrQGmkLOw58";

$call_back_url = "http://localhost/watching/callback.php";

session_start();
// セッションにアクセストークンがなかったらloginページに飛ぶ
if($_SESSION['oauth_token']==null || $_SESSION['oauth_token_secret']==null){
    
    
    echo "OAuth作成<br>";
    // OAuthオブジェクト生成
    $to = new TwitterOAuth($consumer_key,$consumer_secret);
    //print_r($to);
    //echo "<br>";
    // callbackURLを指定してRequest tokenを取得
    $tok = $to->getRequestToken($call_back_url);
    //print_r($tok);
    echo "<br>session<br>";
    // セッションに保存
    $_SESSION['request_token']=$token=$tok['oauth_token'];
    $_SESSION['request_token_secret'] = $tok['oauth_token_secret'];
    print_r($_SESSION);
    echo "<br>sing in<br>";
    // サインインするためのURLを取得
    $url = $to->getAuthorizeURL($token);
    printf ("<a href='%s'>サインイン</a>",$url);
}else{
    //サインインしていればヘッダーを出力
    include("user_header.php");
}
?>
