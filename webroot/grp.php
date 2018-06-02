<?php

if (php_sapi_name() !== 'cli') { die("go away"); }

define('FORUM', 23);

require('config.php');
require('helper.php');

sstart();

require('bbinit.php');

# -----------------------------------------------------------------------------------------------------------------------

$dbr = brave_db();

$sql = 'SELECT group_id, group_name FROM ' . GROUPS_TABLE;
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result)) {
    print_r($row);
}
$db->sql_freeresult($result);

?>
