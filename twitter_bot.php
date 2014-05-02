<?php
/*
	twitter_bot.php by トメ
	SDN Project - http://www.sdn-project.net/
*/

// twitteroauth.phpを読み込む。パスはあなたが置いた適切な場所に変更してください。フルパスの方がいいかも。
require_once("twitteroauth.php");
class Twitter_Bot{
	var $user;
	var $TO;
	var $times;
	function Twitter_Bot($usr,$consumer_key,$consumer_secret,$oauth_token,$oauth_token_secret){
		$this->user = $usr;
		$this->TO = new TwitterOAuth($consumer_key,$consumer_secret,$oauth_token,$oauth_token_secret);
		$this->times = array_sum(explode(" ",microtime()));
	}
	function Request($url,$method = "POST",$opt = array()){
		$req = $this->TO->OAuthRequest("https://api.twitter.com/1.1/".$url,$method,$opt);
		if($req){$result = json_decode($req);} else {$result = null;}
		return $result;
	}
	// データを読み込む。ここをSQLiteやMySQLなどにデータを保存するように書き換えてもいいかもしれない。
	function Get_data($type){
		$dat = $this->user."_".$type.".dat";
		if(!file_exists($dat)){
            echo $dat."を作成<br>";
			touch($dat);
			chmod($dat,0666);
			return null;
		}
		$data = file($dat);
        return $data[0];
	}
	// データを書き込む。ここをSQLiteやMySQLなどにデータを保存するように書き換えてもいいかもしれない。
	function Save_data($type,$data){
		$dat = $this->user."_".$type.".dat";
        echo "$dat <br>";
		if(!file_exists($dat)){
			touch($dat);
			chmod($dat,0666);
		}
        $old = file($dat);
        print_r($old);
        
		$fdat = fopen($dat,"w");
		        
        echo "old:".$old[0]."<br>";
        echo "new:".$data."<br>";
        if($data > $old[0] || !$old[0]){
            echo "Writing! ".$dat."<br>";
            flock($fdat,LOCK_EX);
            fputs($fdat,$data);
            flock($fdat,LOCK_UN);
        }else{
            flock($fdat,LOCK_EX);
            fputs($fdat,$old[0]);
            flock($fdat,LOCK_UN);
        }
		fclose($fdat);
	}
	// 呟きをPOSTする。$statusには発言内容。
	// $repは相手にリプライする場合にリプライ元の呟きのIDを指定する。リプライ元の呟きとリプライする相手のユーザー名が一致しないといけない。
	function Post($status,$rep = null){
		$opt = array();
		$opt['status'] = $status;
		if($rep){$opt['in_reply_to_status_id'] = $rep;}
		$req = $this->Request("statuses/update.json","POST",$opt);
		if(!$req){die('Post(): $req is NULL');}
		//$code = $req->Code;
		//$xml = json_decode($req->Body) or die("Error: ".$code);
		if($rep){$this->Save_data("Since",$rep); return;}
		if($req->errors){die("Error:".$req->errors[0]->message." Code:".$req->errors[0]->code);}
	}
    function Get_api(){
        $req = $this->Request("application/rate_limit_status.json?resources=statuses","GET");
        return $req;
    }
	// タイムラインなどを取得する。$typeにはhome_timeline、friends_timeline、mentionsなど。詳しくはAPI仕様書を。
	// $sidは呟きのID。$sidで指定した呟きのIDより後の呟きを取得するようにさせる。
	// $countは一度に呟きをどれだけ取得するか。最大200。
	function Get_TL($type,$sid = null,$count = 30){
		$opt = array();
        $opt['include_entities'] = "true";
		$opt['count'] = $count;
		if($sid){$opt['since_id'] = $sid;}      
        //print_r($opt);
		$result = $this->Request("statuses/".$type.".json","GET",$opt);	// JSON形式の方がちょっと扱いやすい
		
        /*
        if($req->error){
			if($req->Code != "200"){die("Error: ".$req->Code);}
			$result = str_replace(":NULL,",':"NULL",',$req->Body);
		} else {die('Get_TL(): $req is NULL');}*/
		//$result = json_decode($result);
		if($result->errors){die("Error:".$result->errors[0]->message." Code:".$result->errors[0]->code);}
        //print_r($result);
		return $result;
	}
	// フォロー・リムーブする。$uidはフォロー、リムーブしたいユーザーナンバー又はユーザー名。$flgは「true」ならフォロー、「false」ならリムーブ。
	// 返り値は「ok」、「already」、「error」の3種類。「ok」は正常にフォロー、リムーブが完了。「already」は既にそのユーザーをフォロー、リムーブしている。「error」はTwitter側の何かしらのエラー。
	function Follow($uid,$flg = true){
		$result = "ok";
		$req = $this->Request("friendships/".($flg?"create":"destroy")."/".$uid.".json");
		if($req){
			if($req->Code != "200"){$result = "error";}
			$xml = $req->Body;
			if($xml->error){$result = "already";}
		} else {$result = "error";}
		return $result;
	}
	// 呟きをお気に入りに追加する。$sidはお気に入りに追加したい呟きのID。
	function Favorite($sid){
		$req = $this->Request("favorites/create/".$sid.".json");
		if(!$req){die('Favorite(): $req is NULL');}
		if($req->Code != "200"){die("Error: ".$req->Code);}
	}
    
    function Search($str,$sid = null,$count = 30){
        
        $opt = array();
        //$opt['include_entities'] = "ture";
		$opt['count'] = $count;
        $opt['q'] = $str;
        $opt['result_type'] = "recent";
        
		if($sid){$opt['since_id'] = $sid;}
        //print_r($opt);
		$result = $this->Request("search/tweets.json","GET",$opt);	// JSON形式の方がちょっと扱いやすい
        /* 
		if($req){
			if($req->Code != "200"){die("Error: ".$req->Code);}
			$result = str_replace(":NULL,",':"NULL",',$req->Body);
		} else {die('Get_TL(): $req is NULL');}*/
		//$result = json_decode($result);
		if($result->errors){die("Error:".$result->errors[0]->message." Code:".$result->errors[0]->code);}
		return $result->statuses;
         
        /*
        $response = $this->TO->get( "search/tweets", array("q" => $str, "result_type" => "recent", "count" => $count, "include_entities" => "true") );
        $http_info = $this->TO->http_info;

        if ($http_info["http_code"] == "200" && !empty( $response->statuses ) ) {
            return $response;
        }else{
            return "Error";
        }*/
                
    }
    
	// 呟きを消す。$sidは消したい呟きのID。自分の呟き以外はエラーになります。
	function Delete($sid){
		$req = $this->Request("statuses/destroy/".$sid.".json","DELETE");
		if($req){
			if($req->Code != "200"){die("Error: ".$req->Code);}
			$xml = $req->Body;
			if($xml->error){die($xml->error);}
		} else {die('Delete(): $req is NULL');}
	}
	// 呟きをRTする。$sidはRTしたい呟きのID。
	function RT($sid){
		$req = $this->Request("statuses/retweet/".$sid.".json","POST");
		if($req){
			if($req->Code != "200"){die("Error: ".$req->Code);}
			$xml = $req->Body;
			if($xml->error){die($xml->error);}
		} else {die('RT(): $req is NULL');}
	}
	// DMを送る。$uidはDMを送りたいユーザーナンバー又はユーザー名。$textは本文。
	function DM($uid,$text){
		$req = $this->Request("direct_messages/new.json","POST",array("user"=>$uid,"text"=>$text));
		if($req){
			if($req->Code != "200"){die("Error: ".$req->Code);}
			$xml = $req->Body;
			if($xml->error){die($xml->error);}
		} else {die('DM(): $req is NULL');}
	}
	// 終わりの処理
	function End($sid){
		$this->Save_data("Since",$sid);
		echo "Normal termination: ".sprintf("%0.4f",array_sum(explode(" ",microtime())) - $this->times)." sec, ".date("H:i:s");
	}
}
// 配列$arrからランダムに一つ取り出す
function Rrt($arr){
	if(!is_array($arr)){return $arr;}
	$rand = array_rand($arr,1);
	return $arr[$rand];
}
?>