<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

list($columns, $indexes) = $db->metaTable('timetracking_entry');

// ===========================================================================
// Expand timetracking_entry max size from 255 to longtext

if(isset($columns['notes'])) {
	$db->Execute("ALTER TABLE timetracking_entry CHANGE COLUMN notes notes longtext DEFAULT '' NOT NULL");
}

return TRUE;
