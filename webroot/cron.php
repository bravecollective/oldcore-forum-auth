<?php

if (php_sapi_name() !== 'cli') { die("go away"); }

define('FORUM', 23);

require('config.php');
require('helper.php');
require('auth/core_auth.php');

//sstart();

require('bbinit.php');

# -----------------------------------------------------------------------------------------------------------------------

$expire = 60 * 60 * 6;
$sleep = 60 * 60 * 1;

function update_from_bb() {
    global $dbr, $db;

    print("Searching for stale BB accounts:\n");
    $sql = 'SELECT username FROM ' . USERS_TABLE . ' WHERE ' . $db->sql_in_set('user_type', 'USER_NORMAL');
    $result = $db->sql_query($sql);
    while ($row = $db->sql_fetchrow($result)) {
	usleep(10000);

	if ($row['username'] == "Mom Bellicose") {
	    continue;
	}

	$char = brave_get_character($row['username']);
	if ($char === false) {
	    print("Fail: Query failed for: " . $row['username'] . "\n");
	    continue;
	}
	if (!$char) {
	    print("Stale: " . $row['username'] . " (Username does not exist)\n");
	    brave_bb_account_deactivate($row['username']);
	    continue;
	}

	if ($char['parent_id'] != 0) {
	    print("Stale: " . $row['username'] . " (Username is not a main)\n");
	    $stm = $dbr->prepare('SELECT * FROM auth_core WHERE character_id = :character_id');
	    $stm->bindValue(':character_id', $char['parent_id']);
	    $main = brave_get_generic($stm);
	    if ($main === false) {
	        print("Fail: Query failed for character_id: " . $char['parent_id'] . "\n");
		continue;
	    }

	    $stm = $dbr->prepare('DELETE FROM auth_core WHERE character_id = :character_id');
	    $stm->bindValue(':character_id', $char['character_id']);
	    if (!brave_do_generic($stm)) {
	        print("Fail: Delete failed for character_id: " . $char['character_id'] . "\n");
		continue;
	    }

	    brave_bb_account_deactivate($row['username']);
	    if (!empty($main)) {
		brave_bb_account_update($main[0]['character_name']);
	    }
	    continue;
	}

    }
    $db->sql_freeresult($result);
    print("done\n");
}

function update_from_core() {
    global $dbr, $db, $expire;

    print("Refresh outdated Core tokens:\n");
    $stm = $dbr->prepare('SELECT * FROM auth_core WHERE parent_id = 0 AND core_updated_at < :time');
    $stm->bindValue(':time', time() - $expire);
    $rows = brave_get_generic($stm);
    if ($rows === false) {
	print("ERR: Could not retrieve parents.\n");
	continue;
    }
    $cids = array();
    foreach($rows as $row) {
	$cids[] = $row['character_id'];
    }
    print("Found " . sizeof($cids) . " outdated parents...\n");

    foreach($cids as $cid) {
	usleep(10000);

	$stm = $dbr->prepare('SELECT * FROM auth_core WHERE character_id = :character_id');
	$stm->bindValue(':character_id', $cid);
	$rows = brave_get_generic($stm);
	if ($rows === false) {
	    print("ERR: Could not retrieve parent " . $cid . "\n");
	    continue;
	}
	if(empty($rows)) {
	    print("Ignoring: [" . $cid . "]\n");
	    continue;
	}
	$row = $rows[0];

	if ($row['core_updated_at'] > time() - $expire) {
	    print("Ignoring: [" . $cid . "] " . $row['character_name'] . "\n");
	    continue;
	}

	print("Updating: [" . $cid . "] " . $row['character_name'] . " with " . $row['core_token'] . ": ");
	$ecode = 0;
	$emsg = "";
	$res = refreshByCoreToken($row['core_token'], $ecode, $emsg);
	print(($res) ? "Success!\n" : "Fail: [" . $ecode . "] " . $emsg . "\n");
    }
    print("done\n");
}

$first = true;
while(1) {
    if (!$first) {
	sleep($sleep);
    }
    $first = false;

    $dbr = brave_db();
    if (!$dbr) {
	print("ERR: Could not init DB\n");
	continue;
    }

    try {
	print("---- Cycle START\n");
	@update_from_bb();
	@update_from_core();
	print("---- Cycle STOP\n");
    } catch (Exception $e) {
	var_dump($e->getMessage());
    }

}

?>
