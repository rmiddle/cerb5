<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// requester.is_active

if(!isset($tables['requester']))
	return FALSE;
	
list($columns, $indexes) = $db->metaTable('requester');

if(!isset($columns['is_active'])) {
	$db->Execute("ALTER TABLE requester ADD COLUMN is_active TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL"); 
	$db->Execute("UPDATE requester SET is_active=1");
}



return TRUE;
