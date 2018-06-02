<?php if (!defined('FORUM')) die('go away'); ?>

<?php
    define('IN_PHPBB', true);
    $php_ext ="php";
    $phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : $cfg_bb_path;
    $phpEx = substr(strrchr(__FILE__, '.'), 1);
    include($phpbb_root_path . 'common.' . $phpEx);
    include($phpbb_root_path . 'includes/functions_user.' . $php_ext);
?>
