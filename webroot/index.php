<?php
    define('FORUM', 23);

    require('config.php');
    require('helper.php');
    require('auth/core_auth.php');

    sstart();
?>

<!DOCTYPE html>
<html lang="en"><head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="description" content="BRAVE Forum Management">
    <meta name="author" content="kiu Nakamura">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="shortcut icon" href="favicon.png">
    <title>BRAVE Forum Management</title>
    <link href="css/bootstrap-cyborg.css" rel="stylesheet">
    <link href="css/forum.css" rel="stylesheet">

    <script src="js/jquery-1.10.2.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/handlebars-v3.0.3.js"></script>
  </head>

<body>


<!-- CONTENT -->

<?php
    $ecode = 0;
    $emsg = "";

    if (isset($_GET['act']) && $_GET['act'] == 'init') {
	unset($_SESSION['core_data']);
	unset($_SESSION['core_token']);
	$r = coreInit($ecode, $emsg);
	if (!$r) {
	    include('inc_error.php');
	}
    } else if (isset($_GET['act']) && $_GET['act'] == 'verify') {
	$dbr = brave_db();
	$r = coreVerify(false, $ecode, $emsg);
	if (!$r) {
	    include('inc_error.php');
	} else {
	    $token = $_GET['token'];

	    $_SESSION['core_data'] = $r;
	    $_SESSION['core_token'] = $token;
	    require('bbinit.php');
	    if (!refreshByCoreData($r, $token, $ecode, $emsg)) {
		include('inc_error.php');
	    } else {
		header("Location: " . $cfg_url_base);
	    }
	}
    } else if (isset($_GET['act']) && $_GET['act'] == 'failed') {
	$ecode = 302;
	$emsg = "CORE authoriztation declined.";
	include('inc_error.php');
    } else if (isset($_GET['act']) && $_GET['act'] == 'error_json') {
	$ecode = 303;
	$emsg = "Failed to load character data.";
	include('inc_error.php');
    } else if (isset($_GET['act']) && $_GET['act'] == 'logout') {
	sdestroy();
	header("Location: " . $cfg_url_base);
    } else if (isset($_SESSION['core_data']) && isset($_SESSION['core_token'])) {
	$dbr = brave_db();
	include('inc_success.php');
    } else {
	include('inc_start.php');
    }
?>

<!-- CONTENT -->

    <div style="font-size:70%; position:fixed; bottom:1px; right:5px; z-index:23;">Brought to you by <a href="http://evewho.com/pilot/kiu+Nakamura" target="_blank">kiu Nakamura</a> / <a href="http://evewho.com/alli/Brave+Collective" target="_blank">Brave Collective</a></div>

</body>
</html>
