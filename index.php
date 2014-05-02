<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        $server = "http://192.168.96.118/ajax_auth/result.php";
        
        define("bitly_id","altis0725");
        define("bitly_apikey","R_067b225eb65e2a78fcf83940c88e40e5");
        define("bitly_apiurl",'http://api.bit.ly/v3/shorten?');
        
        require_once("twitter_bot.php");

        // Botのユーザー名
        $user = "ruka0725";
        // Consumer keyの値
        $consumer_key = "qnnhUFIVVxPzqkwjBByVQ";
        // Consumer secretの値
        $consumer_secret = "zExEaRwlZDjJlqpC38f52h6uBgt9BRbUrQGmkLOw58";
        // Access Tokenの値
        $access_token = "229542951-lMSY7yWWYR0Cif2ifAESNeQjJjM5XYHvKM6rxzZk";
        // Access Token Secretの値
        $access_token_secret = "BObSxmqPmgiNAuJv6EtBrkz2ODajQpUEssP7cyPhqCM";
        
        

        // オブジェクト生成
        $Bot = new Twitter_Bot($user,$consumer_key,$consumer_secret,$access_token,$access_token_secret);
        $ts = array();
        try {
            $ac = new PDO("mysql:host=localhost;dbname=account","altis0725", "keio8000",
                array(
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET CHARACTER SET `utf8`"    
                    )
                );
        } catch (PDOException $e) {
            die($e->getMessage());
        }
        $ac->query("SET NAMES utf8");
        $username = $ac->query("SELECT username FROM user_auth");
        while($row = $username->fetch(PDO::FETCH_ASSOC)){
            //echo "<br>";
            //echo $row['username'];
            //echo "<br>";
            //$uname = mb_convert_encoding($row['username'], "jis");
            try {
                $account = new PDO("mysql:host=localhost;dbname=${row['username']}","altis0725", "keio8000",
                    array(
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET CHARACTER SET `utf8`"    
                        )

                    );
            } catch (PDOException $e) {
                die($e->getMessage());
            }
            $account->query("SET NAMES utf8");
            $table = $account->query("SHOW TABLES");
            while($r = $table->fetch(PDO::FETCH_NUM)){
                $hash = $r[0];
            
                $since_id_mentions = $Bot->Get_data($hash); // 最後に取得したハッシュタグのID
                print_r($since_id_mentions);
                echo $hash."のID取得 <br>";

                $tweets = $Bot->Search("#".$hash,$since_id_mentions,"100");
                //print_r($tweets);

                //echo "ハッシュタグ取得<br>";
                foreach($tweets as $reply){

                    $tx = null;
                    $t = null;
                    // $sid = $reply->id; // 呟きのID。int型
                    $sid = $reply->id_str; // 呟きのID。string型
                    // $uid = $reply->user->id; // ユーザーナンバー。int型
                    $uid = $reply->user->id_str; // ユーザーナンバーstring型
                    $screen_name = $reply->user->screen_name; // ユーザーID
                    $name = $reply->user->name; // ユーザー名
                    //$hashtag = $reply->entities->hashtags[0]->text;
                    $geo_name = $reply->place->name;
                    $geo_place = $reply->geo->coordinates[0].",".$reply->geo->coordinates[1];
                    $time = timezone_change($reply->created_at,9);
                    $reply_name = $reply->in_reply_to_screen_name;
                    echo "90<br>";
                    //echo $hashtag;
                    //echo "<br>";
                    if(strcmp($screen_name,$user) != 0){

                        $hashtag = $r[0];
                        // 呟き内容。余分なスペースを消して、半角カナを全角カナに、全角英数を半角英数に変換。
                        $text = mb_convert_kana(trim($reply->text),"rnKHV","utf-8");
                        $hashtag = mb_convert_kana($hashtag,"rnKHV","utf-8");
                        $userlen = "@".$user." ";
                        echo "100<br>";

                        $tx = str_replace($userlen, "", $text);
                        $tx = str_replace("#".$hashtag, "", $tx);
                        $tx = str_replace("＃".$hashtag, "", $tx);
                        //$tx = preg_replace("/、/", ",", $tx);
                        //$tx = preg_replace("/，/", ",", $tx);
                        //print_r(preg_split("/[\s,]+/", $tx));
                        $data = preg_split('/[\s|\x{3000}]+/u', trim_emspace($tx));
                        $tw = array();
                        for( $i = 0; $i < count( $data ); $i++ ) {
                            $tw[$i] = trim_emspace( $data[ $i ] );
                        }
                        print_r($tw);
                        if(strcasecmp($data[0],"get") != 0){
                            echo "115<br>";
                            $t = Get_DB($hashtag, $tw, $reply, $row["username"]);
                        }
                        
                        echo "<br>";
                        print_r ($reply);
                        echo "<br>";
                        //echo $geo_name."<br>";
                        echo $t."<br>";
                        // $txが空でないのならPOST
                        if($t && !is_array($t)){
                            $Bot->Post("@".$screen_name." ".$t,$sid);
                        } else if($t){
                            $ts[] = $t;
                        }
                    }


                    // 次の呟き取得のために最後に取得した呟きを保存する
                    echo $sid;
                    echo "<br>";
                    if($sid) {
                        $Bot->Save_data($hash,$sid);
                    }else {
                        echo "ok";
                    }
                }
            }
        }
        echo "<br>----------------------------------------<br>";
        
        $since_id_mentions = $Bot->Get_data("Mentions");
        $mentions = $Bot->Get_TL("mentions_timeline",$since_id_mentions); // Bot宛てのリプライ取得
        
        foreach($mentions as $reply){
            $tweet = null;
            // $sid = $reply->id; // 呟きのID。int型
            $sid = $reply->id_str; // 呟きのID。string型
            // $uid = $reply->user->id; // ユーザーナンバー。int型
            $uid = $reply->user->id_str; // ユーザーナンバーstring型
            $screen_name = $reply->user->screen_name; // ユーザーID
            $name = $reply->user->name; // ユーザー名
            $hashtag = $reply->entities->hashtags[0]->text;
            $geo_name = $reply->place->full_name;
            $geo_place = $reply->geo->coordinates[0].",".$reply->geo->coordinates[1];
            $time = timezone_change($reply->created_at,9);
            if($screen_name != $user){                
                if(!$hashtag){
                    $tweet = "ハッシュタグがありません。";
                }else{
                    $text = mb_convert_kana(trim($reply->text),"rnKHV","utf-8");
                    $hashtag = mb_convert_kana($hashtag,"rnKHV","utf-8");
                    $userlen = "@".$user." ";
                    //echo $geo_place;

                    $tx = str_replace($userlen, "", $text);
                    $tx = str_replace("#".$hashtag, "", $tx);
                    $tx = str_replace("＃".$hashtag, "", $tx);
                    //$tx = preg_replace("/、/", ",", $tx);
                    //$tx = preg_replace("/，/", ",", $tx);
                    //print_r(preg_split("/[\s,]+/", $tx));
                    $data = trim_emspace($tx);
                    //print_r($tx);
                    if(strcasecmp($data,"get") == 0){
                        $find = false;
                        echo "174<br>";
                        try {
                            $ac = new PDO("mysql:host=localhost;dbname=account","altis0725", "keio8000",
                                array(
                                    PDO::ATTR_EMULATE_PREPARES => false,
                                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET CHARACTER SET `utf8`"    
                                    )
                                );
                        } catch (PDOException $e) {
                            die($e->getMessage());
                        }
                        $ac->query("SET NAMES utf8");
                        $username = $ac->query("SELECT username FROM user_auth");
                        while($row = $username->fetch(PDO::FETCH_ASSOC)){                      
                            try {
                                $account = new PDO("mysql:host=localhost;dbname=${row['username']}","altis0725", "keio8000",
                                    array(
                                        PDO::ATTR_EMULATE_PREPARES => false,
                                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET CHARACTER SET `utf8`"    
                                        )
                                    );
                            } catch (PDOException $e) {
                                die($e->getMessage());
                            }
                            $account->query("SET NAMES utf8");
                            $table = $account->query("SHOW TABLE STATUS LIKE '$hashtag'");
                            $r = $table->fetch(PDO::FETCH_ASSOC);
                            //while($r = $table->fetch(PDO::FETCH_ASSOC)){
                                print_r($r);
                                echo "<br>";
                                
                                if($r["Name"] === $hashtag){
                                    $twi_long = $server."?server=".$row["username"]."&name=".$r["Name"]."&geo=".$r["Comment"];
                                    echo "long_url: $twi_long";
                                    $twi = GetShortURL($twi_long);
                                    if($twi !== false){
                                        $tweet = $twi;
                                    }else{
                                        $tweet = "urlの短縮に失敗しました。";
                                    }
                                    $find = true;
                                    //break 2;
                                }
                            //}
                        }
                        if($find == false){
                            $tweet = "データベースが見つかりません。";
                        }
                    }else{
                        foreach ($ts as $place){
                            echo $place["ID"]."と".$sid."<br><br>";
                            if($sid == $place["ID"]){
                                $geo_name = str_replace(" ","",$geo_name);
                                echo $geo_name."<br>";
                                $query = str_replace("**place**", $geo_name, $place["query"]);
                                try {
                                    $account = new PDO("mysql:host=localhost;dbname={$place["user"]}","altis0725", "keio8000",
                                        array(
                                            PDO::ATTR_EMULATE_PREPARES => false,
                                            PDO::MYSQL_ATTR_INIT_COMMAND => "SET CHARACTER SET `utf8`"    
                                            )
                                        );
                                } catch (PDOException $e) {
                                    die($e->getMessage());
                                }
                                $account->query("SET NAMES utf8");
                                echo PHP_EOL;
                                echo $query."<br>";
                                $result = $account->query($query);
                                if(!$result){
                                    $tweet = "Error";
                                }else{                                    
                                    $tweet = str_replace("**place**", $geo_name, $place["tweet"]);
                                }
                                break;
                            }
                        }
                    }
                }
                print_r($reply);
                echo "<br><br>";
                print_r($ts);
                echo "<br><br>";
                echo $tweet."<br>";
                // $txが空でないのならPOST
                if($tweet){$Bot->Post("@".$screen_name." ".$tweet,$sid);}
            }
            if($sid) {
                $Bot->Save_data("Mentions",$sid);
            }else {
                echo "ok";
            }
            
        }
        
        
        // 最後に呟きのIDを保存して終わり
        if($sid) $Bot->End($sid);
        
        
        
        function Get_DB($hash,$text,$reply,$user){
            
            // $sid = $reply->id; // 呟きのID。int型
            $sid = $reply->id_str; // 呟きのID。string型
            // $uid = $reply->user->id; // ユーザーナンバー。int型
            $uid = $reply->user->id_str; // ユーザーナンバーstring型
            $screen_name = $reply->user->screen_name; // ユーザーID
            $name = $reply->user->name; // ユーザー名
            //$hashtag = $reply->entities->hashtags[0]->text;
            $geo_name = $reply->place->name;
            $geo_place = $reply->geo->coordinates[0].",".$reply->geo->coordinates[1];
            $time = timezone_change($reply->created_at,9);
            $reply_name = $reply->in_reply_to_screen_name;
            
            try {
                $account = new PDO("mysql:host=localhost;dbname=$user","altis0725", "keio8000",
                    array(
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET CHARACTER SET `utf8`"    
                        )
                    );
            } catch (PDOException $e) {
                die($e->getMessage());
            }
            $account->query("SET NAMES utf8");
            $account->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            /*
            $link_ID = mysql_connect("localhost","altis0725","keio8000") or
                die("Could not connect: " . mysql_error());
            mysql_select_db("$user"); //abc is the database name
            //mysql_query("SET NAMES utf8");
            mysql_set_charset('utf8');*/
            echo "252<br>";
            $str="SHOW FULL COLUMNS FROM `$hash`;" ;
            $result = $account->query($str);
            //$result = mysql_query($str, $link_ID);
            //$num = mysql_num_rows($result);                            
            
            $tweet = null;
            $s1 = "INSERT INTO `$hash`(";
            $s2 = " values (";
            //print_r($time);
            echo "<br>";
            $y = $time['tm_year']+1900;
            $m = sprintf("%02d",$time['tm_mon']+1);
            $d = sprintf("%02d",$time['tm_mday']);
            $H = sprintf("%02d",$time['tm_hour']);
            $i = sprintf("%02d",$time['tm_min']);
            $s = sprintf("%02d",$time['tm_sec']);
            $twtime = $y.$m.$d.$H.$i.$s;
            echo $twtime."<br>";
            
            //$rowCount = $result->rowCount();
            //echo $rowCount."<br>";
            //$txCount = count($text);
            //echo $txCount."<br>";
            /*
            if($rowCount > $txCount){
                return "投稿された要素数が足りません。 #$hash";
            }else if($rowCount < $txCount){
                return "投稿された要素数が多すぎます。 #$hash";
            }else{*/
            $j = 0;
            $place = FALSE;
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $r = $row["Field"];
                
                if($r === "twTime"){
                    $s1 .= "twTime";
                    $s2 .= "'$twtime'";
                }else if($r === "twID"){
                    $s1 .= ",twID";
                    $s2 .= ",'$screen_name'";
                }else if($r === "geo"){
                    if($row["Comment"] === "geo"){
                        if(!$geo_place) return "位置情報がありません。";
                        $s1 .= ",geo";
                        $s2 .= ",'$geo_place'";
                        $tweet .= "位置情報:".$geo_place." ";
                    }else if($row["Comment"] === "place"){
                        $s1 .= ",geo";
                        $s2 .= ",'**place**'";
                        $tweet .= "位置情報:**place** ";
                        $place = TRUE;
                    }else if($row["Comment"] === "tweet"){
                        if(!$text[$j]) return "位置情報がありません。";
                        $s1 .= ",geo";
                        $s2 .= ",'$text[$j]'";
                        $tweet .= "位置情報:".$text[$j]." ";
                        $j++;
                    }else{
                        return "Error #$hash";
                    }
                }else{                                                           
                    if(!$text[$j]) return "投稿された要素数が足りません。 #$hash";
                    if($row["Comment"] === "image"){
                        if(preg_match('/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $text[$j])) {
                            $req = getLongUrl($text[$j]);
                            $tx = getThumbnailHtml($req);
                            $s1 .= ",`$r`";
                            $s2 .= ",'$tx'";
                            $tweet .= $r.":".$text[$j]." ";
                        }else{
                            return ($j+1)."番目の要素がURLではありません。 #$hash";
                        }                                             
                    }else{
                        $tx = $text[$j];
                        $s1 .= ",`$r`";
                        $s2 .= ",'$tx'";
                        $tweet .= $r.":".$tx." ";
                    }
                    
                    $j++;
                }
            }
            //}
            if(isset($text[$j])){
                return "投稿された要素数が多すぎます。 #$hash";
            }        
            
            $tweet = $tweet."で登録完了 #$hash";
            $st = $s1.")".$s2.");";
            echo $st."<br>";
            if($place == TRUE){
                return array("ID" => $sid, "user" => $user, "query" => $st, "tweet" => $tweet);
            }
            $result = $account->query($st);
            $account = null;
            if(!$result) return "error";
            return $tweet;
        }
        

        /*
        "tm_sec" - 秒 (0 から 59) 
        "tm_min" - 分 (0 から 59) 
        "tm_hour" - 時 (0 から 23) 
        "tm_mday" - 月の日付 (1 から 31) 
        "tm_mon" - 月 (0 (1月) から 11 (12月)) 
        "tm_year" - 1900 年からの年数 
        "tm_wday" - 曜日 (0 (日曜日) から 6 (土曜日)) 
        "tm_yday" - 年単位の日付 (0 から 365) 
        "tm_isdst" - 夏時間の適用中かどうか 適用中なら正の数、そうでなければ 0、不明なら負の数。 
            * */

        /*
            Twitterのタイムゾーンを日本時間(東京)に変換する

                時刻を進めたければ、正の整数で、戻したければ、負の整数を指定してください。

                日本時刻にしたければ
                $time = timezone_change('Tue Jul 12 06:27:46 +0000 2011', 9);
                echo $time{"tm_year"};
        */

        function timezone_change($timezone = null, $t = null){
            $year = $month = $day = $hour = $minute = $second = null;

            if(!is_numeric($t) && $t < -12  && $t > 12){
                    return null;
            }

            if(!empty($timezone)){

                $date_arr = explode(" ", $timezone);
                $year = intval($date_arr[5]);

                if($date_arr[1] == "Jan"){
                    $month = 1;
                }else if($date_arr[1] == "Feb"){
                    $month = 2;
                }else if($date_arr[1] == "Mar"){
                    $month = 3;
                }else if($date_arr[1] == "Apr"){
                    $month = 4;
                }else if($date_arr[1] == "May"){
                    $month = 5;
                }else if($date_arr[1] == "Jun"){
                    $month = 6;
                }else if($date_arr[1] == "Jul"){
                    $month = 7;
                }else if($date_arr[1] == "Aug"){
                    $month = 8;
                }else if($date_arr[1] == "Sep"){
                    $month = 9;
                }else if($date_arr[1] == "Oct"){
                    $month = 10;
                }else if($date_arr[1] == "Nov"){
                    $month = 11;
                }else if($date_arr[1] == "Dec"){
                    $month = 12;
                }

                $day = intval(sprintf("%d", $date_arr[2]));

                $arr = explode(":", $date_arr[3]);

                $hour = intval(sprintf("%d", $arr[0]));
                $minute = intval(sprintf("%d", $arr[1]));
                $second = intval(sprintf("%d", $arr[2]));

                $time = mktime($hour, $minute, $second, $month, $day, $year);
                $time = $time + 60 * 60 * $t; # 9時間進める
                $date = localtime($time, true);
                return $date;
            }else{
                return null;                
            }
        }
        
        
        function trim_emspace ($str) {
            // 先頭の半角、全角スペースを、空文字に置き換える
            $str = preg_replace('/^[ 　]+/u', '', $str);
            // 最後の半角、全角スペースを、空文字に置き換える
            $str = preg_replace('/[ 　]+$/u', '', $str);
            return $str;
        }
        
        // ヘッダのロケーションからURLを取得
        function getLongUrl($short_url){
            $h = get_headers($short_url, 1);
            if(isset($h['Location'])){
                $long_url = $h['Location'];
                if(is_array($long_url)){  
                    $long_url = end($long_url);
                }
            }
            return $long_url;  
        }

        function getThumbnailHtml($status_text) {
            $html = '';
            $patterns = array(
                // twitpic
                array('/http:\/\/twitpic[.]com\/(\w+)/', '<img src="http://twitpic.com/show/thumb/$1" width="150" height="150" />'),

                // Mobypicture
                array('/http:\/\/moby[.]to\/(\w+)/', '<img src="http://moby.to/$1:small" />'),

                // yFrog
                array('/http:\/\/yfrog[.]com\/(\w+)/', '<img src="http://yfrog.com/$1.th.jpg" />'),

                // 携帯百景
                array('/http:\/\/movapic[.]com\/pic\/(\w+)/', '<img src="http://image.movapic.com/pic/s_$1.jpeg" />'),

                // はてなフォトライフ
                array('/http:\/\/f[.]hatena[.]ne[.]jp\/(([\w\-])[\w\-]+)\/((\d{8})\d+)/', '<img src="http://img.f.hatena.ne.jp/images/fotolife/$2/$1/$4/$3_120.jpg" />'),

                // PhotoShare
                array('/http:\/\/(?:www[.])?bcphotoshare[.]com\/photos\/\d+\/(\d+)/', '<img src="http://images.bcphotoshare.com/storages/$1/thumb180.jpg" width="180" height="180" />'),

                // PhotoShare の短縮 URL
                array('/http:\/\/bctiny[.]com\/p(\w+)/e', '\'<img src="http://images.bcphotoshare.com/storages/\' . base_convert("$1", 36, 10) . \'/thumb180.jpg" width="180" height="180" />\''),

                // img.ly
                array('/http:\/\/img[.]ly\/(\w+)/', '<img src="http://img.ly/show/thumb/$1" width="150" height="150" />'),

                // brightkite
                array('/http:\/\/brightkite[.]com\/objects\/((\w{2})(\w{2})\w+)/', '<img src="http://cdn.brightkite.com/$2/$3/$1-feed.jpg" />'),

                // Twitgoo
                array('/http:\/\/twitgoo[.]com\/(\w+)/', '<img src="http://twitgoo.com/$1/mini" />'),

                // pic.im
                array('/http:\/\/pic[.]im\/(\w+)/', '<img src="http://pic.im/website/thumbnail/$1" />'),

                // youtube
                array('/http:\/\/(?:www[.]youtube[.]com\/watch(?:\?|#!)v=|youtu[.]be\/)([\w\-]+)(?:[-_.!~*\'()a-zA-Z0-9;\/?:@&=+$,%#]*)/', '<img src="http://i.ytimg.com/vi/$1/hqdefault.jpg" width="240" height="180" />'),


                // imgur
                array('/http:\/\/imgur[.]com\/(\w+)[.]jpg/', '<img src="http://i.imgur.com/$1l.jpg" />'),

                // TweetPhoto, Plixi, Lockerz
                array('/http:\/\/tweetphoto[.]com\/\d+|http:\/\/plixi[.]com\/p\/\d+|http:\/\/lockerz[.]com\/s\/\d+/', '<img src="http://api.plixi.com/api/TPAPI.svc/imagefromurl?size=mobile&url=$0" />'),

                // Ow.ly
                array('/http:\/\/ow[.]ly\/i\/(\w+)/', '<img src="http://static.ow.ly/photos/thumb/$1.jpg" width="100" height="100" />'),

                // Instagram
                array('/http:\/\/instagr[.]am\/p\/([\w\-]+)\//', '<img src="http://instagr.am/p/$1/media/?size=t" width="150" height="150" />'),

                // フォト蔵
                array('/http:\/\/photozou[.]jp\/photo\/show\/\d+\/([\d]+)/', '<img src="http://photozou.jp/p/thumb/$1" />'),

                // ついっぷる フォト
                array('/http:\/\/p[.]twipple[.]jp\/([\w]+)/', '<img src="http://p.twipple.jp/show/thumb/$1" />'),
            );
            echo "477<br>";
            foreach ($patterns as $pattern) {
                if (preg_match($pattern[0], $status_text, $matches)) {
                    $url = $matches[0];
                    $html = preg_replace($pattern[0], $pattern[1], $url);
                    $html = '<a href="' . $url . '" target="_blank">' . $html . '</a>';
                    break;
                }
            }

            return $html;
        }
        
        function GetShortURL($sauce_url) {
            $query = bitly_apiurl.'login='.bitly_id.'&apiKey='.bitly_apikey.'&longUrl='.urlencode($sauce_url).'&format=txt';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $query);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1); 
            $response = curl_exec($ch);
            curl_close($ch); 

            if($response){
                return $response;
            }else{
                return false;
            }
        }
        
        function get_tiny_url($long_url=''){
            $api_url = 'https://www.googleapis.com/urlshortener/v1/url';
            $api_key = 'AIzaSyCFm0ey1e_tmRTkQsA2VoLtBFd0Dus8Fms';
            $curl = curl_init("$api_url?key=$api_key");
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, '{"longUrl":"' . $long_url . '"}');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $res = curl_exec($curl);
            curl_close($curl);
            $json = json_decode($res);
            $tiny_url = $json->id;
            return $tiny_url;
        }

        
        
        
        ?> 
    </body>
</html>
