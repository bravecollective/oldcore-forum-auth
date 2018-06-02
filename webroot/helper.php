<?php if (!defined('FORUM')) die('go away'); ?>

<?php

function sstart() {
    ini_set('session.gc_maxlifetime', '42000');
    session_start();
}

function sdestroy() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
}

# -----------------------------------------------------------------------------------------------------------------------

function brave_db() {
    global $cfg_sql_url, $cfg_sql_user, $cfg_sql_pass;

    try {
	return new PDO($cfg_sql_url, $cfg_sql_user, $cfg_sql_pass);
    } catch (PDOException $e) {
	error_log('DB failure:' . $e);
	return false;
    }
}

function brave_ip_get() {
    global $request;
    return (empty($request->server('HTTP_CLIENT_IP'))?(empty($request->server('HTTP_X_FORWARDED_FOR'))?$request->server('REMOTE_ADDR'):$request->server('HTTP_X_FORWARDED_FOR')):$request->server('HTTP_CLIENT_IP'));
}

function brave_password_generate($length) {
    $alphabet = "abcdefghkmnpqrstuvwxyzABCDEFGHKMNPQRSTUVWXYZ23456789";
    $pass = "";
    for($i = 0; $i < $length; $i++) {
        $pass = $pass . substr($alphabet, hexdec(bin2hex(openssl_random_pseudo_bytes(1))) % strlen($alphabet), 1);
    }
    return $pass;
}

function brave_send_response($code, $msg, $data = false) {
    header('Content-Type: application/json');
    $result = array(
        'code' => (int)$code,
        'msg' => (string)$msg,
	'data' => $data,
    );
    echo json_encode($result);
}

# -----------------------------------------------------------------------------------------------------------------------

function brave_bb_user_name_to_id($user_name) {
    $user_names = array($user_name);
    $user_ids = array();
    $result = user_get_id_name($user_ids, $user_names);
    if ($result) {
	return false;
    }
    if (sizeof($user_ids) == 1) {
	return $user_ids[0];
    }

    return false;
}

function brave_bb_account_create_or_activate($user_name, $character_id, $setpass = false) {
    $password = brave_password_generate(10);
    $user_id = brave_bb_user_name_to_id($user_name);
    if (!$user_id) {
	$user_id = brave_bb_account_create($character_id, $user_name, $password);
	brave_bb_account_update($user_name);
	return $password;
    }

    if ($setpass) {
	brave_bb_account_password($user_id, $password);
    }

    brave_bb_account_activate($user_name);
    return $password;
}

function brave_bb_account_activate($user_name) {
    $user_id = brave_bb_user_name_to_id($user_name);
    if (!$user_id) {
	return;
    }

    user_active_flip('activate', $user_id);
    brave_bb_account_update($user_name);
}

function brave_bb_account_deactivate($user_name) {
    $user_id = brave_bb_user_name_to_id($user_name);
    if (!$user_id) {
	return;
    }

    user_active_flip('deactivate', $user_id);

    brave_bb_account_update($user_name);
}

function brave_bb_account_create($character_id, $user_name, $password) {
    global $db, $phpbb_container, $config, $cfg_bb_groups;

    $passwords_manager = $phpbb_container->get('passwords.manager');

    $user = array(
	'username'		=> $user_name,
	'user_password'		=> $passwords_manager->hash($password),
	'user_email'		=> '',
	'group_id'		=> $cfg_bb_groups['register'],
	'user_type'		=> USER_NORMAL,
	'user_ip'		=> brave_ip_get(),
	'user_new'		=> ($config['new_member_post_limit']) ? 1 : 0,
	'user_avatar'		=> 'https://image.eveonline.com/Character/' . $character_id . '_128.jpg',
	'user_avatar_type'	=> 2,
	'user_avatar_width'	=> 128,
	'user_avatar_height'	=> 128,
	);

    user_add($user);

    $user_id = brave_bb_user_name_to_id($user_name);
    add_log('user', $user_id, 'LOG_USER_GENERAL', 'Created user through CORE');
    return $user_id;
}

function brave_get_character($character_name) {
    global $dbr;

    $stm = $dbr->prepare('SELECT * FROM auth_core WHERE character_name = :character_name');
    $stm->bindValue(':character_name', $character_name);
    $rows = brave_get_generic($stm);
    if ($rows === false) {
	return false;
    }
    if (empty($rows)) {
	return array();
    }
    return $rows[0];
}

function brave_get_character_main($character_name) {
    global $dbr;

    $main = brave_get_character($character_name);
    if ($main === false) {
	return false;
    }
    if (empty($main)) {
	return array();
    }
    if ($main['parent_id'] != 0) {
	return array();
    }

    return $main;
}

function brave_get_character_childs($character_name) {
    global $dbr;

    $main = brave_get_character_main($character_name);
    if ($main === false) {
	return false;
    }
    if (empty($main)) {
	return array();
    }

    $stm = $dbr->prepare('SELECT * FROM auth_core WHERE parent_id = :parent_id');
    $stm->bindValue(':parent_id', $main['character_id']);
    $rows = brave_get_generic($stm);
    if ($rows === false) {
	return false;
    }
    return $rows;
}

function brave_get_generic($stm) {
    if (!$stm->execute()) {
	$arr = $stm->ErrorInfo();
	error_log('SQL failure:'.$arr[0].':'.$arr[1].':'.$arr[2]);
	return false;
    }

    $rows = array();
    while ($row = $stm->fetch()) {
	$rows[] = $row;
    }
    return $rows;
}

function brave_do_generic($stm, $ignore = false) {
    if (!$stm->execute()) {
	if (!$ignore) {
	    $arr = $stm->ErrorInfo();
	    error_log('SQL failure:'.$arr[0].':'.$arr[1].':'.$arr[2]);
	}
	return false;
    }
    return true;
}

function brave_character_insert_or_update($cd, $parent_id, $ct, $updateonly = false) {
    global $dbr;

    if (!$updateonly) {
	$stm = $dbr->prepare('INSERT INTO auth_core (character_id, character_name, corporation_id, corporation_name, alliance_id, alliance_name, parent_id, core_token, core_tags, core_perms, core_updated_at) VALUES (:character_id, :character_name, :corporation_id, :corporation_name, :alliance_id, :alliance_name, :parent_id, :core_token, :core_tags, :core_perms, :core_updated_at)');
	$stm->bindValue(':character_id', $cd->character->id);
	$stm->bindValue(':character_name', $cd->character->name);
	$stm->bindValue(':corporation_id', $cd->corporation->id);
	$stm->bindValue(':corporation_name', $cd->corporation->name);
	$stm->bindValue(':alliance_id', ($cd->alliance) ? $cd->alliance->id : "");
	$stm->bindValue(':alliance_name', ($cd->alliance) ? $cd->alliance->name : "");
	$stm->bindValue(':parent_id',  $parent_id);
	$stm->bindValue(':core_token', $ct);
	$stm->bindValue(':core_tags', implode(",", $cd->tags));
	$stm->bindValue(':core_perms', implode(",", $cd->perms));
	$stm->bindValue(':core_updated_at', time());
	if (brave_do_generic($stm, true)) {
	    return true;
	}
    }

    $stm = $dbr->prepare('UPDATE auth_core SET character_name = :character_name, corporation_id = :corporation_id, corporation_name = :corporation_name, alliance_id = :alliance_id, alliance_name = :alliance_name, parent_id = :parent_id, core_token = :core_token, core_tags = :core_tags, core_perms = :core_perms, core_updated_at = :core_updated_at WHERE character_id = :character_id');
    $stm->bindValue(':character_id', $cd->character->id);
    $stm->bindValue(':character_name', $cd->character->name);
    $stm->bindValue(':corporation_id', $cd->corporation->id);
    $stm->bindValue(':corporation_name', $cd->corporation->name);
    $stm->bindValue(':alliance_id', ($cd->alliance) ? $cd->alliance->id : "");
    $stm->bindValue(':alliance_name', ($cd->alliance) ? $cd->alliance->name : "");
    $stm->bindValue(':parent_id',  $parent_id);
    $stm->bindValue(':core_token', $ct);
    $stm->bindValue(':core_tags', implode(",", $cd->tags));
    $stm->bindValue(':core_perms', implode(",", $cd->perms));
    $stm->bindValue(':core_updated_at', time());
    if (!brave_do_generic($stm)) {
	return false;
    }

    return true;
}

function brave_tag_to_group_ids($tag) {
    global $cfg_bb_groups, $cfg_bb_group_by_tag;

    $shorts = $cfg_bb_group_by_tag[$tag];
    if (!$shorts) {
	return array();
    }
    if(!is_array($shorts)) {
	$shorts = array($shorts);
    }

    $ids = array();
    foreach ($shorts as $short) {
	$id = $cfg_bb_groups[$short];
	if (!$id) {
	    continue;
	}
	$ids[] = $id;
    }

    return $ids;
}

function brave_bb_account_update($user_name) {
    global $phpbb_container, $cfg_bb_groups, $cfg_bb_group_by_tag, $cfg_bb_group_default_by_tag;

    $user_id = brave_bb_user_name_to_id($user_name);
    if (!$user_id) {
	return false;
    }

    $main = brave_get_character_main($user_name);
    $childs = brave_get_character_childs($user_name);

    $alt_names = array();
    if ($childs) {
	foreach ($childs as $alt) {
	    $alt_names[] = $alt['character_name'] . " (" . $alt['corporation_name'] . ")";
	}
	asort($alt_names);
    }

    $cp = $phpbb_container->get('profilefields.manager');
    $cp_data = array();
    $cp_data['pf_core_corp_name'] = ($main) ? $main['corporation_name'] : "";
    $cp_data['pf_core_alli_name'] = ($main) ? $main['alliance_name'] : "";
    $cp_data['pf_core_alt_names'] = implode("\n", $alt_names);
    $cp->update_profile_field_data($user_id, $cp_data);

    // DO GROUP MAGIC

    $tags = array();
    if ($main) {
	$tags = array_merge($tags, explode(",", $main['core_tags']));
	$tags[] = "auth-corp-" . $main['corporation_id'];
	$tags[] = "auth-alli-" . $main['alliance_id'];
    }
    if ($childs) {
	foreach ($childs as $alt) {
	    $tags = array_merge($tags, explode(",", $alt['core_tags']));
	    $tags[] = "auth-corp-" . $alt['corporation_id'];
	    if ($alt['alliance_id']) {
		$tags[] = "auth-alli-" . $alt['alliance_id'];
	    }
	}
    }
    $tags = array_unique($tags);
    asort($tags);

    $gid_default = $cfg_bb_groups[$cfg_bb_group_default_by_tag['default'][1]];
    if (!$main) {
	$gid_default = $cfg_bb_groups['inactive'];
    }

    $i = 0;
    foreach ($tags as $tag) {
	$gs = $cfg_bb_group_default_by_tag[$tag];
	if (!$gs) {
	    continue;
	}
	$gid = $cfg_bb_groups[$gs[1]];
	if (!$gid || $gs[0] < $i) {
	    continue;
	}
	$i = $gs[0];
	$gid_default = $gid;
    }

    $gids_want = array();
    $gids_want[] = $gid_default;
    $gids_want[] = $cfg_bb_groups['register'];
    foreach ($tags as $t) {
	$ids = brave_tag_to_group_ids($t);
	foreach($ids as $id) {
	    $gids_want[] = $id;
	}
    }
    $gids_want = array_unique($gids_want);

    $gids_has = array();
    foreach (group_memberships(false, array($user_id), false) as $g) {
	$gid = $g['group_id'];
	if (!in_array($gid, $gids_want)) {
	    group_user_del($gid, $user_id);
	    continue;
	}
	$gids_has[] = $gid;
    }

    foreach($gids_want as $gid) {
	if (in_array($gid, $gids_has)) {
	    continue;
	}
	group_user_add($gid, $user_id, false, false, false);
    }

    group_set_user_default($gid_default, array($user_id), false, true);
}

function brave_bb_account_password($user_id, $password) {
    global $db, $phpbb_container, $user;
    $passwords_manager = $phpbb_container->get('passwords.manager');

    $sql_ary = array(
	'user_password'         => $passwords_manager->hash($password),
	'user_passchg'          => time(),
    );

    $sql = 'UPDATE ' . USERS_TABLE . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . ' WHERE user_id = ' . $user_id;
    $db->sql_query($sql);
    $user->reset_login_keys($user_id);
    add_log('user', $user_id, 'LOG_USER_NEW_PASSWORD', 'Reset password throuh CORE');
}

# -----------------------------------------------------------------------------------------------------------------------

function refreshByCoreToken($ct, &$ecode, &$emsg) {
    global $dbr;

    $r = coreVerify($ct, $ecode, $emsg);
    if ($r) {
	return refreshByCoreData($r, $ct, $ecode, $emsg);
    }

    if ($ecode != 304) {
	return false;
    }

    $stm = $dbr->prepare('SELECT * FROM auth_core WHERE core_token = :core_token AND parent_id = 0');
    $stm->bindValue(':core_token', $ct);
    $rows = brave_get_generic($stm);
    if ($rows === false) {
	return false;
    }

    $stm = $dbr->prepare('DELETE FROM auth_core WHERE core_token = :core_token');
    $stm->bindValue(':core_token', $ct);
    if (!brave_do_generic($stm)) {
	return false;
    }

    foreach($rows as $row) {
	brave_bb_account_deactivate($row['character_name']);
    }

    return false;
}

function refreshByCoreData($cd, $ct, &$ecode, &$emsg) {
    global $dbr;

    $main_id = $cd->character->id;
    $main_name = $cd->character->name;
    $toBeRefreshed = array();

    $character_ids = array();
    foreach ($cd->characters as $c) {
	if ($c->character->id == $cd->character->id) {
	    continue;
	}
	$character_ids[] = $c->character->id;
    }

    // Grab the main
    $main = brave_get_character($main_name);
    if ($main === false) {
        return false;
    }

    // Main is a child of somebody, fix the parent.
    if ($main && $main['parent_id'] != 0) {
	$stm = $dbr->prepare('SELECT * FROM auth_core WHERE character_id = :character_id');
	$stm->bindValue(':character_id', $main['parent_id']);
	$rows = brave_get_generic($stm);
	if ($rows === false) {
	    return false;
	}
	if (!brave_character_insert_or_update($cd, 0, $ct)) {
	    return false;
	}
	if (!empty($rows)) {
	    brave_bb_account_update($rows[0]['character_name']);
	}
    } else {
	if (!brave_character_insert_or_update($cd, 0, $ct)) {
	    return false;
	}
    }

    brave_bb_account_create_or_activate($main_name, $main_id, false);


    // Update my real alts
    foreach ($cd->characters as $c) {
	if ($c->character->id == $cd->character->id) {
	    continue;
	}

	$child = brave_get_character($c->character->name);
	if ($child === false) {
	    return false;
	}
	if (empty($child)) {
	    continue;
	}

	if ($child['parent_id'] == 0) {
	    $stm = $dbr->prepare('DELETE FROM auth_core WHERE parent_id = :character_id OR character_id = :character_id');
	    $stm->bindValue(':character_id', $child['character_id']);
	    if (!brave_do_generic($stm)) {
		return false;
	    }

	    brave_bb_account_deactivate($child['character_name']);
	    continue;
	}

	if ($child['parent_id'] != $main_id) {
	    $stm = $dbr->prepare('DELETE FROM auth_core WHERE character_id = :character_id');
	    $stm->bindValue(':character_id', $child['character_id']);
	    if (!brave_do_generic($stm)) {
		return false;
	    }

	    $stm = $dbr->prepare('SELECT * FROM auth_core WHERE character_id = :character_id');
	    $stm->bindValue(':character_id', $child['parent_id']);
	    $rows = brave_get_generic($stm);
	    if ($rows === false) {
		return false;
	    }
	    if (!empty($rows)) {
		brave_bb_account_update($rows[0]['character_name']);
	    }
	    continue;
	}

	if ($child['parent_id'] == $main_id) {
	    if (!brave_character_insert_or_update($c, $main_id, $ct)) {
		return false;
	    }
	    continue;
	}
    }

    // Update my supposed alts
    $childs = brave_get_character_childs($main_name);
    if ($rows === false) {
	return false;
    }
    foreach($childs as $child) {
	if (in_array($child['character_id'], $character_ids)) {
	    continue;
	}

	$stm = $dbr->prepare('DELETE FROM auth_core WHERE character_name = :character_name');
	$stm->bindValue(':character_name', $child['character_name']);
	if (!brave_do_generic($stm)) {
	    return false;
	}
    }

    brave_bb_account_update($main_name);

    return true;
}

?>
