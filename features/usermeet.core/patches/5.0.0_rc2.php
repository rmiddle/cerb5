<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// `community_access` 
if(!isset($tables['community_access'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS community_access (
			address_id INT UNSIGNED DEFAULT 0 NOT NULL,
			ticket_id INT UNSIGNED DEFAULT 0 NOT NULL,
			is_active TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (address_id, ticket_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `requester` ========================
list($columns, $indexes) = $db->metaTable('community_access');

if(!isset($indexes['address_id'])) {
	$db->Execute('ALTER TABLE community_access ADD INDEX address_id (address_id)');
}

if(!isset($indexes['ticket_id'])) {
	$db->Execute('ALTER TABLE community_access ADD INDEX ticket_id (ticket_id)');
}

return TRUE;