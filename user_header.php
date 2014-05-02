<?php

//session_start();
print_r($_SESSION);

// OAuthオブジェクト生成
$to = new TwitterOAuth($consumer_key,$consumer_secret,$_SESSION['oauth_token'],$_SESSION['oauth_token_secret']);

// home_timelineの取得。TwitterからXML形式が返ってくる
$req = $to->OAuthRequest("https://api.twitter.com/1.1/statuses/home_timeline.json","GET",array("count"=>"10"));
//print_r($req);
if($req->Code!=200){
    echo "OAuthRequest Error ".$req->Code."<br>";
}else{
    // json文字列をオブジェクトに代入する
    $result = json_decode($req->Body);
    //print_r($result);

    // foreachで呟きの分だけループする
    foreach($result as $status){
        $status_id = $status->id_str; // 呟きのステータスID
        $text = $status->text; // 呟き
        $user_id = $status->user->id_str; // ID（数字）
        $screen_name = $status->user->screen_name; // ユーザーID（いわゆる普通のTwitterのID）
        $name = $status->user->name; // ユーザーの名前（HNなど）
        echo "<p><b>".$screen_name." / ".$name."</b> <a href=\"http://twitter.com/".$screen_name."/status/".$status_id."\">この呟きのパーマリンク</a><br />\n".$text."</p>\n";
    }
}
?> 
