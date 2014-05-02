<?php
define("PASSWORD", "pass");
$message = "";

session_start();

if(isset($_COOKIE["TEST_COOKIE"]) && $_COOKIE["TEST_COOKIE"] != ""){

    $_SESSION["TEST"] = $_COOKIE["TEST_COOKIE"];
}

if(isset($_SESSION["TEST"]) && $_SESSION["TEST"] != null && sha1(PASSWORD) === $_SESSION["TEST"]){
    $message = "Login success";
    //print_r($_SESSION);
    //print_r($_COOKIE);
}else{
    session_destroy();//セッション破棄
    header("Location:login.php");
}

if(isset($_POST['logout'])){
    
    // セッション変数を全て解除する
    $_SESSION = array();

    setcookie("TEST_COOKIE", "", time() - 3600*24*14);
    //print_r($_COOKIE);
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    //print_r($_SESSION);
    //echo "<br>";
    //print_r($_COOKIE);
    //echo "<br>";
    //セッションを破壊してリダイレクト
    session_destroy();

    header("Location:login.php");
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    <title>Admin</title>
</head>
<body>
    <h1>Admin</h1>
    <?php
        if($message!=""){
            print "<p class=\"message\">".$message."</p>\n";
        }

    ?>
    <form action="" method="post">
        <p><button type="submit" name="logout">ログアウト</button></p>
    </form>
</body>
</html>