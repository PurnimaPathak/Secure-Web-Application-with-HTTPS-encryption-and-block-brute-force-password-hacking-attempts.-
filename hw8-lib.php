<?php



function connect(){
    $mycnf='hw-mysql.conf';
    if (!file_exists($mycnf)) {
        echo "File Not Found: $mycnf";
        exit;
    }

    $mysql_ini_array=parse_ini_file($mycnf);
    $db_host=$mysql_ini_array["host"];
    $db_user=$mysql_ini_array["user"];
    $db_pass=$mysql_ini_array["pass"];
    $db_port=$mysql_ini_array["port"];
    $db_name=$mysql_ini_array["dbName"];

    $db= mysqli_connect(
        $db_host,
        $db_user,
        $db_pass,
        $db_name,
        $db_port
    );
    mysqli_autocommit($db, TRUE);

    if (!$db) {
        echo "Error Connecting to DB".mysqli_connect_error();
        exit;
    }
    return $db;
}

$blacklistIp= array();
$whitelistIp = array();
$ip=isset ( $_REQUEST['ip'] ) ? $ip = strip_tags($_REQUEST['ip']) : $ip = "";
$s= isset ( $_REQUEST['s'] ) ? $s = strip_tags($_REQUEST['s']) : $s = "";
$sid= isset ( $_REQUEST['sid'] ) ? $sid = strip_tags($_REQUEST['sid']) : $sid = "";
$bid= isset ( $_REQUEST['bid'] ) ? $bid = strip_tags($_REQUEST['bid']) : $bid = "";
$cid= isset ( $_REQUEST['cid'] ) ? $cid = strip_tags($_REQUEST['cid']) : $cid = "";
$characterRace= isset ( $_REQUEST['characterRace'] ) ? $characterRace = strip_tags($_REQUEST['characterRace']) : $characterRace = "";
$characterSide= isset ( $_REQUEST['characterSide'] ) ? $characterSide = strip_tags($_REQUEST['characterSide']) : $characterSide = "";
$characterPicture= isset ( $_REQUEST['characterPicture'] ) ? $characterPicture = strip_tags($_REQUEST['characterPicture']) : $characterPicture = "";
$characterName= isset ( $_REQUEST['characterName'] ) ? $characterName = strip_tags($_REQUEST['characterName']) : $characterName = "";
$postUser= isset ( $_REQUEST['postUser'] )? $postUser = strip_tags($_REQUEST['postUser']) : $postUser = "";
$postPass=isset ( $_REQUEST['postPass'] ) ? $postPass = strip_tags($_REQUEST['postPass']) : $postPass = "";
$message= isset ( $_REQUEST['message'] ) ? $message = strip_tags($_REQUEST['message']) : $message = "";
$newUser=isset ( $_REQUEST['newUser'] ) ? $newUser = strip_tags($_REQUEST['newUser']) : $newUser = "";
$newPass=isset ( $_REQUEST['newPass'] ) ? $newPass = strip_tags($_REQUEST['newPass']) : $newPass = "";
$newEmail=isset ( $_REQUEST['newEmail'] ) ? $newEmail = strip_tags($_REQUEST['newEmail']) : $newEmail = "";



function icheck($i) {
    if(!is_numeric($i)) {
        print "<b> ERROR: </b> Invalid Syntax. ";
        exit;
    }
}

function authenticate($db, $postUser, $postPass, $whitelistIp, $blacklistIp){

   checkAuth($db, $whitelistIp, $blacklistIp);

    $postUser= mysqli_real_escape_string($db, $postUser);
    $postPass=mysqli_real_escape_string($db, $postPass);
    $query= "select userid, email, password, salt from users where username=?";
    if ($stmt = mysqli_prepare($db, $query)) {
        mysqli_stmt_bind_param($stmt, "s", $postUser);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $userid, $email, $password, $salt);
        while (mysqli_stmt_fetch($stmt)) {
            $userid = htmlspecialchars($userid);
            $password = htmlspecialchars($password);
            $salt = htmlspecialchars($salt);
            $email = htmlspecialchars($email);
        }
        mysqli_stmt_close($stmt);
        $epass = hash('sha256', $postPass . $salt);
        if ($epass == $password) {
            $_SESSION['userid'] = $userid;
            $_SESSION['email'] = $email;
            $_SESSION['authenticated'] = "yes";
            $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
            $ip = $_SERVER['REMOTE_ADDR'];
            recordLogin($db, $ip, $postUser, "success");
        } else {
            recordLogin($db, $_SERVER['REMOTE_ADDR'], $postUser, "fail");
            error_log("***Error***: Tolkien App has failed login from " . $_SERVER['REMOTE_ADDR'], 0);
            header("Location: login.php?message=Wrong Username or Password");
            exit;
        }
        header("Location: add.php");
    }
}

function checkAuth($db, $whitelistIp, $blacklistIp)
{
    $ip = $_SERVER['REMOTE_ADDR'];

    if(in_array($ip, $whitelistIp)){
        return;
    }else{
        if(in_array($ip, $blacklistIp)){
            header("Location:login.php?message=Blocked from using the system");
            exit;
        }else {
            checkLogins($db, $ip, $whitelistIp);
            return;
        }
    }
}

function addUser($db, $newUser, $newPass, $newEmail){
    $newUser = mysqli_real_escape_string($db, $newUser);
    $newPass = mysqli_real_escape_string($db, $newPass);
    $newEmail = mysqli_real_escape_string($db, $newEmail);
    $salt= rand(1000000,100000000);
    $password= hash('sha256', $newPass.$salt);
    if ($stmt = mysqli_prepare($db, "insert into users set username=?, password=?, email=?, salt=?")) {
        mysqli_stmt_bind_param($stmt, "ssss", $newUser, $password, $newEmail, $salt);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt);
    }mysqli_stmt_close($stmt);

}

function updatePassword($db, $newPass, $postUser){
    $postUser = mysqli_real_escape_string($db, $postUser);
    $newPass = mysqli_real_escape_string($db, $newPass);
    $salt= rand(1000000,100000000);
    $password= hash('sha256', $newPass.$salt);
    if ($stmt = mysqli_prepare($db, "update users set password=?, salt=? where username=?")) {
        mysqli_stmt_bind_param($stmt, "sss", $password, $salt, $postUser);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt);
    }mysqli_stmt_close($stmt);

}

function recordLogin($db, $ip, $postUser, $action)
{
    if($ip and $postUser){
        $postUser = mysqli_real_escape_string($db, $postUser);
        $ip = mysqli_real_escape_string($db, $ip);
        $action= mysqli_real_escape_string($db, $action);
        if ($stmt = mysqli_prepare($db, "insert into login set ip=?, user=?, action=?, date=NOW()")) {
            mysqli_stmt_bind_param($stmt, "sss", $ip, $postUser, $action);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt);
        }mysqli_stmt_close($stmt);

    }elseif($action){
        $action = mysqli_real_escape_string($db, $action);

        if ($stmt = mysqli_prepare($db, "update login set action=?")) {
            mysqli_stmt_bind_param($stmt, "s",$action);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt);
        }mysqli_stmt_close($stmt);
    }

}

function checkLogins($db, $ip, $whitelistIp)
{
    $ip = mysqli_real_escape_string($db, $ip);
    $count=0;

    if ($stmt = mysqli_prepare($db, "select action from login where ip=? and date_sub(now(), INTERVAL 1 HOUR)")) {
        mysqli_stmt_bind_param($stmt, "s", $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $action);
        while (mysqli_stmt_fetch($stmt)) {
            $action = htmlspecialchars($action);
            if ($action=="fail"){
                $count = $count +1;
            }
        }
        mysqli_stmt_close($stmt);
    }

    if($count >= 5 and !(in_array($ip, $whitelistIp))){
        if ($stmt = mysqli_prepare($db, "insert into blacklist set ip=?")) {
            mysqli_stmt_bind_param($stmt, "s", $ip);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt);
        }
        mysqli_stmt_close($stmt);
        header("Location:login.php?message=Blocked from using the system");
        exit;
    }else{
        return;
    }
}

function insertIntoWhitelist($db, $ip){
    #only by admin
    if ($stmt = mysqli_prepare($db, "insert into whitelist set ip=?")) {
        mysqli_stmt_bind_param($stmt, "s", $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt);
    }
    mysqli_stmt_close($stmt);
    return $ip;
}



?>