<?php if (!defined('FORUM')) die('go away'); ?>

<?php

define('USE_EXT', 'GMP');
require('vendor/autoload.php');

// -----------------------------------------------

function coreInit(&$ecode, &$emsg) {
    global $cfg_core_endpoint, $cfg_core_application_id, $cfg_core_private_key, $cfg_core_public_key;
    global $cfg_url_auth_success, $cfg_url_auth_fail;

    try {
	$api = new Brave\API($cfg_core_endpoint, $cfg_core_application_id, $cfg_core_private_key, $cfg_core_public_key);

	$info_data = array(
	    'success' => $cfg_url_auth_success,
	    'failure' => $cfg_url_auth_fail
	);

	$result = $api->core->authorize($info_data);
	header("Location: " . $result->location);
    } catch(\Exception $e) {
	$ecode = 300;
	$emsg = 'Core initialization failed.';
	error_log($e);
	return false;
    }

    return true;
}

// -----------------------------------------------

function coreVerify($token = false, &$ecode, &$emsg) {
    global $cfg_core_endpoint, $cfg_core_application_id, $cfg_core_private_key, $cfg_core_public_key;

    if ($token === false) {
	if (!isset($_GET['token'])) {
	    $ecode = 301;
	    $emsg = 'Token is missing.';
	    return false;
	}
	$token = $_GET['token'];
    }

    $token = preg_replace("/[^A-Za-z0-9]/", '', $token);
    if (empty($token)) {
	$ecode = 302;
	$emsg = 'Core initialization failed.';
	return false;
    }

    try {
	$api = new Brave\API($cfg_core_endpoint, $cfg_core_application_id, $cfg_core_private_key, $cfg_core_public_key);
	$result = $api->core->info(array('token' => $token));
    } catch(\Exception $e) {
	$ecode = 303;
	$emsg = 'Token request failed.';
	error_log($e);
	return false;
    }

    if (isset($result->message)) {
	$ecode = 304;
	$emsg = 'Token verification failed: ' . $result->message;
	return false;
    }

    return $result;
}

?>
