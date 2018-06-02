<?php

define('FORUM', 23);

require('config.php');
require('helper.php');

sstart();

require('bbinit.php');

# -----------------------------------------------------------------------------------------------------------------------

if (!isset($_SESSION['core_data'])) {
    brave_send_response(100, "Session has no core data.");
    return;
}
$cd = $_SESSION['core_data'];

if (!isset($_SESSION['core_token'])) {
    brave_send_response(101, "Session has no core token.");
    return;
}
$token = $_SESSION['core_token'];

if (!$request->is_set('act')) {
    brave_send_response(102, "Request has no action.");
    return;
}
$act = $request->variable('act', "", false,  \phpbb\request\request_interface::GET);

$dbr = brave_db();
if (!$dbr) {
    brave_send_response(103, "Database init failed.");
}

# -----------------------------------------------------------------------------------------------------------------------

if ($act == 'characters') {
    $rows = brave_get_character_childs($cd->character->name);
    if ($rows === false) {
	brave_send_response(110, "Failed to query childs.");
        return false;
    }
    $connected_ids = array();
    foreach($rows as $row) {
	$connected_ids[] = $row['character_id'];
    }

    $primary = array("character_name" => $cd->character->name, "character_id" => $cd->character->id);
    $alts = array();
    foreach ($cd->characters as $c) {
	if ($c->character->id == $cd->character->id) {
	    continue;
	}
	$alts[] = array("character_name" => $c->character->name, "character_id" => $c->character->id, "state" => in_array($c->character->id, $connected_ids) ? "Connected" : "Ignored");
    }

    brave_send_response(0, "OK", array("primary" => $primary, "alts" => $alts));
    return true;
}


if ($act == 'reset') {
    $password = brave_bb_account_create_or_activate($cd->character->name, $cd->character->id, true);
    brave_send_response(0, "OK", array("pass" => $password));
    return true;
}


if ($act == 'toggle') {
    if (!$request->is_set('character_id')) {
	brave_send_response(120, "Toggle request has no character.");
	return false;
    }
    $cid = (int)$request->variable('character_id', "", false,  \phpbb\request\request_interface::GET);
    if ($cid == $cd->character->id) {
	brave_send_response(121, "Tried to toggle primary character: " . $cid);
	return false;
    }
    $cdata = false;
    foreach ($cd->characters as $c) {
	if ($c->character->id == $cid) {
	    $cdata = $c;
	}
    }
    if (!$cdata) {
	brave_send_response(123, "Tried to toggle unauthorized character: " . $cid);
	return false;
    }

    // Do we have a parent?
    $row = brave_get_character_main($cd->character->name);
    if ($row === false || empty($row)) {
	brave_send_response(125, "Parent doesn't exist: ". $cd->character->id);
	return false;
    }

    // Does this child exist?
    $row = brave_get_character($cdata->character->name);
    if ($row !== false && !empty($row)) {
	if ($row['parent_id'] != $cd->character->id) {
	    brave_send_response(127, "Tried to toggle character with different parent: " . $cid);
	    return false;
	}

	$stm = $dbr->prepare('DELETE FROM auth_core WHERE character_id = :character_id');
	$stm->bindValue(':character_id', $cid);
	if (!brave_do_generic($stm)) {
	    brave_send_response(129, "Failed to delete child: " . $cid);
	    return false;
	}

	brave_bb_account_update($cd->character->name);
	brave_send_response(0, "OK", array("state" => "Ignored"));
	return true;
    }

    if (!brave_character_insert_or_update($cdata, $cd->character->id, $token)) {
	brave_send_response(130, "Failed to insert character: " . $cdata->character->id);
	return false;
    }

    brave_bb_account_update($cd->character->name);
    brave_send_response(0, "OK", array("state" => "Connected"));
    return true;
}

brave_send_response(105, "Unknown action");

?>
