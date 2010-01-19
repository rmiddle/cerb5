<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db); /* @var $datadict ADODB_DataDict */ // ,'mysql' 

$tables = $datadict->MetaTables();
$tables = array_flip($tables);

// `address` ========================
$columns = $datadict->MetaColumns('address');
$indexes = $datadict->MetaIndexes('address',false);

if(isset($columns['CONTACT_ID'])) {
    $sql = $datadict->DropColumnSQL('address', 'contact_id');
    $datadict->ExecuteSQLArray($sql);
}

if(isset($columns['PERSONAL'])) {
    $sql = $datadict->DropColumnSQL('address', 'personal');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['FIRST_NAME'])) {
    $sql = $datadict->AddColumnSQL('address', "first_name C(32) DEFAULT '' NOTNULL");
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['LAST_NAME'])) {
    $sql = $datadict->AddColumnSQL('address', "last_name C(32) DEFAULT '' NOTNULL");
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['PHONE'])) {
    $sql = $datadict->AddColumnSQL('address', "phone C(32) DEFAULT '' NOTNULL");
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['CONTACT_ORG_ID'])) {
    $sql = $datadict->AddColumnSQL('address', "contact_org_id I4 DEFAULT 0 NOTNULL");
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['NUM_SPAM'])) {
    $sql = $datadict->AddColumnSQL('address', "num_spam I4 DEFAULT 0 NOTNULL");
    $datadict->ExecuteSQLArray($sql);
    
    // Update totals
	$sql = "SELECT count(id) as hits,first_wrote_address_id FROM ticket WHERE spam_training = 'S' GROUP BY first_wrote_address_id,spam_training";
	$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	
	if(is_a($rs,'ADORecordSet'))
	while(!$rs->EOF) {
		$hits = intval($rs->fields['hits']);
		$address_id = intval($rs->fields['first_wrote_address_id']);
		$db->Execute(sprintf("UPDATE address SET num_spam = %d WHERE id = %d", $hits, $address_id));
		$rs->MoveNext();
	}
	@$rs->Free();
}

if(!isset($columns['NUM_NONSPAM'])) {
    $sql = $datadict->AddColumnSQL('address', "num_nonspam I4 DEFAULT 0 NOTNULL");
    $datadict->ExecuteSQLArray($sql);
    
    // Update totals
	$sql = "SELECT count(id) as hits,first_wrote_address_id FROM ticket WHERE spam_training = 'N' GROUP BY first_wrote_address_id,spam_training";
	$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	
	if(is_a($rs,'ADORecordSet'))
	while(!$rs->EOF) {
		$hits = intval($rs->fields['hits']);
		$address_id = intval($rs->fields['first_wrote_address_id']);
		$db->Execute(sprintf("UPDATE address SET num_nonspam = %d WHERE id = %d", $hits, $address_id));
		$rs->MoveNext();
	}
	@$rs->Free();
}

if(!isset($columns['IS_BANNED'])) {
    $sql = $datadict->AddColumnSQL('address', "is_banned I1 DEFAULT 0 NOTNULL");
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['SLA_ID'])) {
    $sql = $datadict->AddColumnSQL('address', "sla_id I4 DEFAULT 0 NOTNULL");
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['SLA_EXPIRES'])) {
    $sql = $datadict->AddColumnSQL('address', "sla_expires I4 DEFAULT 0 NOTNULL");
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['LAST_AUTOREPLY'])) {
    $sql = $datadict->AddColumnSQL('address', "last_autoreply I4 DEFAULT 0 NOTNULL");
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['email'])) {
    $sql = $datadict->CreateIndexSQL('email','address','email',array('UNIQUE'));
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['contact_org_id'])) {
    $sql = $datadict->CreateIndexSQL('contact_org_id','address','contact_org_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['sla_id'])) {
    $sql = $datadict->CreateIndexSQL('sla_id','address','sla_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['num_spam'])) {
    $sql = $datadict->CreateIndexSQL('num_spam','address','num_spam');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['num_nonspam'])) {
    $sql = $datadict->CreateIndexSQL('num_nonspam','address','num_nonspam');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['is_banned'])) {
    $sql = $datadict->CreateIndexSQL('is_banned','address','is_banned');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['last_autoreply'])) {
    $sql = $datadict->CreateIndexSQL('last_autoreply','address','last_autoreply');
    $datadict->ExecuteSQLArray($sql);
}

// `address_auth` =============================
if(!isset($tables['address_auth'])) {
    $flds = "
		address_id I4 DEFAULT 0 NOTNULL PRIMARY,
		confirm C(16) DEFAULT '' NOTNULL,
		pass C(32) DEFAULT '' NOTNULL
	";
    $sql = $datadict->CreateTableSQL('address_auth',$flds);
    $datadict->ExecuteSQLArray($sql);
}

// `address_to_worker` =============================
if(!isset($tables['address_to_worker'])) {
    $flds = "
		address C(128) DEFAULT '' NOTNULL PRIMARY,
		worker_id I4 DEFAULT 0 NOTNULL,
		is_confirmed I1 DEFAULT 0 NOTNULL,
		code C(32) DEFAULT '' NOTNULL,
		code_expire I4 DEFAULT 0 NOTNULL
	";
    $sql = $datadict->CreateTableSQL('address_to_worker',$flds);
    $datadict->ExecuteSQLArray($sql);
    
    // Migrate any existing workers
	$rs = $db->Execute("SELECT id, email FROM worker");
	
	if(is_a($rs,'ADORecordSet'))
	while(!$rs->EOF) {
		$db->Execute(sprintf("INSERT INTO address_to_worker (address, worker_id, is_confirmed, code_expire) ".
			"VALUES (%s,%d,1,0)",
			$db->qstr($rs->fields['email']),
			intval($rs->fields['id'])
		));
		$rs->MoveNext();
	}
}

// `contact_org` =============================
if(!isset($tables['contact_org'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		account_number C(32) DEFAULT '' NOTNULL,
		name C(128) DEFAULT '' NOTNULL,
		street C(128) DEFAULT '' NOTNULL,
		city C(64) DEFAULT '' NOTNULL,
		province C(64) DEFAULT '' NOTNULL,
		postal C(20) DEFAULT '' NOTNULL,
		country C(64) DEFAULT '' NOTNULL,
		phone C(32) DEFAULT '' NOTNULL,
		fax C(32) DEFAULT '' NOTNULL,
		website C(128) DEFAULT '' NOTNULL,
		created I4 DEFAULT 0 NOTNULL
	";
    $sql = $datadict->CreateTableSQL('contact_org',$flds);
    $datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('contact_org');
$indexes = $datadict->MetaIndexes('contact_org',false);

if(!isset($columns['SLA_ID'])) {
    $sql = $datadict->AddColumnSQL('contact_org','sla_id I4 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['SLA_EXPIRES'])) {
    $sql = $datadict->AddColumnSQL('contact_org','sla_expires I4 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['name'])) {
    $sql = $datadict->CreateIndexSQL('name','contact_org','name'); // ,array('UNIQUE')
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['account_number'])) {
    $sql = $datadict->CreateIndexSQL('account_number','contact_org','account_number'); // array('UNIQUE')
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['sla_id'])) {
    $sql = $datadict->CreateIndexSQL('sla_id','contact_org','sla_id'); // ,array('UNIQUE')
    $datadict->ExecuteSQLArray($sql);
}

// `contact_person` =============================
if(isset($tables['contact_person'])) {
	$sql = $datadict->DropTableSQL('contact_person');
	$datadict->ExecuteSQLArray($sql);
}

if(isset($tables['contact_person_seq'])) {
	$sql = $datadict->DropTableSQL('contact_person_seq');
	$datadict->ExecuteSQLArray($sql);
}

// `fnr_external_resource` =======================
if(!isset($tables['fnr_external_resource'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		name C(64) DEFAULT '' NOTNULL,
		url C(255) DEFAULT '' NOTNULL,
		topic_id I4 DEFAULT 0 NOTNULL
	";
    $sql = $datadict->CreateTableSQL('fnr_external_resource',$flds);
    $datadict->ExecuteSQLArray($sql);
}

// `fnr_topic` =======================
if(!isset($tables['fnr_topic'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		name C(64) DEFAULT '' NOTNULL
	";
    $sql = $datadict->CreateTableSQL('fnr_topic',$flds);
    $datadict->ExecuteSQLArray($sql);
}

// `fnr_query` =======================
if(!isset($tables['fnr_query'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		query C(255) DEFAULT '' NOTNULL,
		created I4 DEFAULT 0 NOTNULL,
		source C(32) DEFAULT '' NOTNULL,
		no_match I1 DEFAULT 0 NOTNULL
	";
    $sql = $datadict->CreateTableSQL('fnr_query',$flds);
    $datadict->ExecuteSQLArray($sql);
}

// `category` ========================
$columns = $datadict->MetaColumns('category');
$indexes = $datadict->MetaIndexes('category',false);

if(!isset($columns['RESPONSE_HRS'])) {
	$sql = $datadict->AddColumnSQL('category','response_hrs I2 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

// `group_setting` =======================
if(!isset($tables['group_setting'])) {
    $flds = "
		group_id I4 DEFAULT 0 NOTNULL PRIMARY,
		setting C(64) DEFAULT '' NOTNULL PRIMARY,
		value B
	";
    $sql = $datadict->CreateTableSQL('group_setting',$flds);
    $datadict->ExecuteSQLArray($sql);
}

// `mail_template` =======================
if(!isset($tables['mail_template'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		title C(64) DEFAULT '' NOTNULL,
		description C(255) DEFAULT '' NOTNULL,
		folder C(64) DEFAULT '' NOTNULL,
		template_type I1 DEFAULT 0 NOTNULL,
		owner_id I4 DEFAULT 0 NOTNULL,
		content XL
	";
    $sql = $datadict->CreateTableSQL('mail_template',$flds);
    $datadict->ExecuteSQLArray($sql);
}

// `mail_template_reply` =======================
if(isset($tables['mail_template_reply'])) {
	$rs = $db->Execute("SELECT id,title,description,folder,owner_id,content FROM mail_template_reply");
	
	while(!$rs->EOF) {
		$db->Execute(sprintf("INSERT INTO mail_template (id,title,description,folder,template_type,owner_id,content) ".
			"VALUES (%d,%s,%s,%s,%d,%d,%s)",
			$rs->fields['id'],
			$db->qstr($rs->fields['title']),
			$db->qstr($rs->fields['description']),
			$db->qstr($rs->fields['folder']),
			2, // reply
			$rs->fields['owner_id'],
			$db->qstr($rs->fields['content'])
		));
		$rs->MoveNext();
	}
	
    $sql = $datadict->DropTableSQL('mail_template_reply');
    $datadict->ExecuteSQLArray($sql);
}

// `message_content` =====================
//$columns = $datadict->MetaColumns('message_content', false, false);
if(!isset($tables['message_content'])) {
    $flds = "
		message_id I4 DEFAULT 0 NOTNULL PRIMARY,
		content B
	";
    $sql = $datadict->CreateTableSQL('message_content',$flds);
    $datadict->ExecuteSQLArray($sql);
}

// `message_header` =====================
$indexes = $datadict->MetaIndexes('message_header',false);
if(!isset($tables['message_header'])) {
    $flds = "
		message_id I4 DEFAULT 0 NOTNULL PRIMARY,
		header_name C(64) DEFAULT '' NOTNULL PRIMARY,
		ticket_id I4 DEFAULT 0 NOTNULL,
		header_value B
	";
    $sql = $datadict->CreateTableSQL('message_header',$flds);
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['header_name'])) {
    $sql = $datadict->CreateIndexSQL('header_name','message_header','header_name');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['ticket_id'])) {
    $sql = $datadict->CreateIndexSQL('ticket_id','message_header','ticket_id');
    $datadict->ExecuteSQLArray($sql);
}

// `message_note` ==================
if(!isset($tables['message_note'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		message_id I4 DEFAULT 0 NOTNULL,
		worker_id I4 DEFAULT 0 NOTNULL,
		created I4 DEFAULT 0 NOTNULL,
		content B
	";
    $sql = $datadict->CreateTableSQL('message_note',$flds);
    $datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('message_note');
$indexes = $datadict->MetaIndexes('message_note',false);

if(!isset($columns['TYPE'])) {
    $sql = $datadict->AddColumnSQL('message_note','type I1 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['type'])) {
    $sql = $datadict->CreateIndexSQL('type','message_note','type');
    $datadict->ExecuteSQLArray($sql);
}

// `message` ========================
$columns = $datadict->MetaColumns('message');

if(isset($columns['HEADERS'])) {
    $sql = $datadict->DropColumnSQL('message','headers');
    $datadict->ExecuteSQLArray($sql);
}

if(isset($columns['MESSAGE_ID'])) {
    $sql = $datadict->DropColumnSQL('message','message_id');
    $datadict->ExecuteSQLArray($sql);
}

if(isset($columns['IS_ADMIN'])) {
    $sql = $datadict->DropColumnSQL('message','is_admin');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['IS_OUTGOING'])) {
    $sql = $datadict->AddColumnSQL('message','is_outgoing I1 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
    
    // Gather Helpdesk/Group addresses
	try {
		$froms = array();
		
		$sql = sprintf("SELECT value FROM setting WHERE setting = %s",
			$db->qstr(CerberusSettings::DEFAULT_REPLY_FROM)
		);
		if(null != ($default_from = $db->GetOne($sql))) {
			$froms[$default_from] = 1;
		}
		
		if(null != ($group_settings = DAO_GroupSettings::getSettings()) && is_array($group_settings)) {
			foreach($group_settings as $group_id => $gs) {
				if(is_array($gs) && isset($gs[DAO_GroupSettings::SETTING_REPLY_FROM])) {
					$group_from = $gs[DAO_GroupSettings::SETTING_REPLY_FROM];
					if(!empty($group_from))
						$froms[$group_from] = 1;
				}
			}
		}
		
		if(is_array($froms) && !empty($froms)) {
			$froms = array_keys($froms);
			$sql = sprintf("SELECT id FROM address WHERE email IN ('%s')",
				implode("','", $froms)
			);
			$rs = $db->Execute($sql);
			
			while(!$rs->EOF) {
   				$address_id = intval($rs->fields['id']);
				$db->Execute(sprintf("UPDATE message SET is_outgoing = 1 WHERE address_id = %d",
		    		$address_id
		    	));
				$rs->MoveNext();
			}
		}
		
	} catch(Exception $e) {}
}

if(!isset($columns['WORKER_ID'])) {
    $sql = $datadict->AddColumnSQL('message','worker_id I4 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
    
    // Link direct replies from worker addresses as outgoing messages (Cerb 1,2,3.x)
    $sql = "SELECT a.id as address_id,w.id as worker_id FROM address a INNER JOIN worker w ON (a.email=w.email)";
    $rs = $db->Execute($sql);
    
    while(!$rs->EOF) {
    	$address_id = intval($rs->fields['address_id']);
    	$worker_id = intval($rs->fields['worker_id']);
    	$db->Execute(sprintf("UPDATE message SET is_outgoing = 1 AND worker_id = %d WHERE address_id = %d",
    		$worker_id,
    		$address_id
    	));
    	$rs->MoveNext();
    }
}

if(isset($columns['MESSAGE_TYPE'])) {
    $sql = $datadict->DropColumnSQL('message','message_type');
    $datadict->ExecuteSQLArray($sql);
}

if(isset($columns['CONTENT'])) {
    // insert into message_content (message_id, content) select id,content FROM message
    $sql = $datadict->DropColumnSQL('message','content');
    $datadict->ExecuteSQLArray($sql);
}

$indexes = $datadict->MetaIndexes('message',false);

if(!isset($indexes['created_date'])) {
    $sql = $datadict->CreateIndexSQL('created_date','message','created_date');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['ticket_id'])) {
    $sql = $datadict->CreateIndexSQL('ticket_id','message','ticket_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['is_outgoing'])) {
    $sql = $datadict->CreateIndexSQL('is_outgoing','message','is_outgoing');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['worker_id'])) {
    $sql = $datadict->CreateIndexSQL('worker_id','message','worker_id');
    $datadict->ExecuteSQLArray($sql);
}

// `requester` ========================
$columns = $datadict->MetaColumns('requester');
$indexes = $datadict->MetaIndexes('requester',false);

if(!isset($indexes['address_id'])) {
    $sql = $datadict->CreateIndexSQL('address_id','requester','address_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['ticket_id'])) {
    $sql = $datadict->CreateIndexSQL('ticket_id','requester','ticket_id');
    $datadict->ExecuteSQLArray($sql);
}

// `setting` ==================================
$columns = $datadict->MetaColumns('setting');
//$indexes = $datadict->MetaIndexes('setting',false);

if(255 == $columns['VALUE']->max_length) {
	$datadict->ExecuteSQLArray($datadict->RenameColumnSQL('setting', 'value', 'value_old',"value_old C(255) DEFAULT '' NOTNULL"));
	$datadict->ExecuteSQLArray($datadict->AddColumnSQL('setting', "value B"));
	
	$sql = "SELECT setting, value_old FROM setting ";
	$rs = $db->Execute($sql);
	
	if($rs)
	while(!$rs->EOF) {
		@$db->UpdateBlob(
			'setting',
			'value',
			$rs->fields['value_old'],
			sprintf("setting = %s",
				$db->qstr($rs->fields['setting'])
			)
		);
		$rs->MoveNext();
	}
	
	if($rs)
		$datadict->ExecuteSQLArray($datadict->DropColumnSQL('setting', 'value_old'));
}

// `sla` ========================
if(!isset($tables['sla'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		name C(64) DEFAULT '' NOTNULL,
		priority I1 DEFAULT 0 NOTNULL
	";
    $sql = $datadict->CreateTableSQL('sla',$flds);
    $datadict->ExecuteSQLArray($sql);
}

// `task` =============================
if(!isset($tables['task'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		title C(255) DEFAULT '' NOTNULL,
		worker_id I4 DEFAULT 0 NOTNULL,
		priority I1 DEFAULT 4 NOTNULL,
		due_date I4 DEFAULT 0 NOTNULL,
		is_completed I1 DEFAULT 0 NOTNULL,
		completed_date I4 DEFAULT 0 NOTNULL,
		source_extension C(255) DEFAULT '' NOTNULL,
		source_id I4 DEFAULT 0 NOTNULL,
		content XL
	";
    $sql = $datadict->CreateTableSQL('task',$flds);
    $datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('task');
$indexes = $datadict->MetaIndexes('task',false);

if(!isset($indexes['is_completed'])) {
    $sql = $datadict->CreateIndexSQL('is_completed','task','is_completed');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['completed_date'])) {
    $sql = $datadict->CreateIndexSQL('completed_date','task','completed_date');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['priority'])) {
    $sql = $datadict->CreateIndexSQL('priority','task','priority');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['worker_id'])) {
    $sql = $datadict->CreateIndexSQL('worker_id','task','worker_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['source_extension'])) {
    $sql = $datadict->CreateIndexSQL('source_extension','task','source_extension');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['source_id'])) {
    $sql = $datadict->CreateIndexSQL('source_id','task','source_id');
    $datadict->ExecuteSQLArray($sql);
}

/*
// `task_to_object` =============================
if(!isset($tables['task_to_object'])) {
    $flds = "
		task_id I4 DEFAULT 0 NOTNULL PRIMARY,
		namespace C(64) DEFAULT '' NOTNULL,
		object_id I4 DEFAULT 0 NOTNULL
	";
    $sql = $datadict->CreateTableSQL('task_to_object',$flds);
    $datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('task_to_object');
$indexes = $datadict->MetaIndexes('task_to_object',false);

if(!isset($indexes['namespace'])) {
    $sql = $datadict->CreateIndexSQL('namespace','task_to_object','namespace');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['entity_id'])) {
    $sql = $datadict->CreateIndexSQL('entity_id','task_to_object','entity_id');
    $datadict->ExecuteSQLArray($sql);
}
*/

// `team_routing_rule` ========================
$columns = $datadict->MetaColumns('team_routing_rule');
$indexes = $datadict->MetaIndexes('team_routing_rule',false);

if(!isset($columns['DO_ASSIGN'])) {
    $sql = $datadict->AddColumnSQL('team_routing_rule', "do_assign I8 DEFAULT '0' NOTNULL");
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['team_id'])) {
    $sql = $datadict->CreateIndexSQL('team_id','team_routing_rule','team_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['pos'])) {
    $sql = $datadict->CreateIndexSQL('pos','team_routing_rule','pos');
    $datadict->ExecuteSQLArray($sql);
}

// `ticket` ========================
$columns = $datadict->MetaColumns('ticket');
$indexes = $datadict->MetaIndexes('ticket',false);

if(isset($columns['OWNER_ID'])) {
    $sql = $datadict->DropColumnSQL('ticket', 'owner_id');
    $datadict->ExecuteSQLArray($sql);
}

if(isset($columns['PRIORITY'])) {
    $sql = $datadict->DropColumnSQL('ticket', 'priority');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['IS_WAITING'])) {
    $sql = $datadict->AddColumnSQL('ticket', 'is_waiting I1 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

if(isset($columns['IMPORT_PILE'])) {
	$sql = $datadict->DropColumnSQL('ticket', 'import_pile');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['LAST_WORKER_ID'])) {
    $sql = $datadict->AddColumnSQL('ticket', 'last_worker_id I4 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['LAST_ACTION_CODE'])) {
    $sql = $datadict->AddColumnSQL('ticket', "last_action_code C(1) DEFAULT 'O' NOTNULL");
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['NEXT_WORKER_ID'])) {
    $sql = $datadict->AddColumnSQL('ticket', 'next_worker_id I4 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
    
    $db->Execute("UPDATE ticket SET next_worker_id = last_worker_id");
}

if(!isset($columns['SLA_ID'])) {
    $sql = $datadict->AddColumnSQL('ticket', 'sla_id I4 DEFAULT 0 NOTNULL');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['SLA_PRIORITY'])) {
    $sql = $datadict->AddColumnSQL('ticket', 'sla_priority I1 DEFAULT 0 NOTNULL');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['FIRST_MESSAGE_ID'])) {
    $sql = $datadict->AddColumnSQL('ticket', 'first_message_id I4 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

   // [JAS]: Populate our new foreign key
   $sql = "SELECT m.ticket_id, min(m.id) as first_message_id ".
       "FROM message m ".
       "INNER JOIN ticket t ON (t.id=m.ticket_id) ".
 	   "WHERE t.first_message_id = 0 ".
 	   "GROUP BY ticket_id";
   $rs = $db->Execute($sql); /* @var $rs ADORecordSet */
   
   while(!$rs->EOF) {
       if(empty($rs->fields['first_message_id'])) {
           continue;
       }
       
       $sql = sprintf("UPDATE ticket SET first_message_id = %d WHERE id = %d", 
			intval($rs->fields['first_message_id']),
			intval($rs->fields['ticket_id'])
			);
       $db->Execute($sql);
       
       $rs->MoveNext();
   }


if(!isset($indexes['first_message_id'])) {
    $sql = $datadict->CreateIndexSQL('first_message_id','ticket','first_message_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['mask'])) {
    $sql = $datadict->CreateIndexSQL('mask','ticket','mask');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['is_waiting'])) {
    $sql = $datadict->CreateIndexSQL('is_waiting','ticket','is_waiting');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['sla_id'])) {
    $sql = $datadict->CreateIndexSQL('sla_id','ticket','sla_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['sla_priority'])) {
    $sql = $datadict->CreateIndexSQL('sla_priority','ticket','sla_priority');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['team_id'])) {
    $sql = $datadict->CreateIndexSQL('team_id','ticket','team_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['created_date'])) {
    $sql = $datadict->CreateIndexSQL('created_date','ticket','created_date');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['updated_date'])) {
    $sql = $datadict->CreateIndexSQL('updated_date','ticket','updated_date');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['first_wrote_address_id'])) {
    $sql = $datadict->CreateIndexSQL('first_wrote_address_id','ticket','first_wrote_address_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['last_wrote_address_id'])) {
    $sql = $datadict->CreateIndexSQL('last_wrote_address_id','ticket','last_wrote_address_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['is_closed'])) {
    $sql = $datadict->CreateIndexSQL('is_closed','ticket','is_closed');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['category_id'])) {
    $sql = $datadict->CreateIndexSQL('category_id','ticket','category_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['last_worker_id'])) {
    $sql = $datadict->CreateIndexSQL('last_worker_id','ticket','last_worker_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['next_worker_id'])) {
    $sql = $datadict->CreateIndexSQL('next_worker_id','ticket','next_worker_id');
    $datadict->ExecuteSQLArray($sql);
}

// `ticket_comment` =============================
if(!isset($tables['ticket_comment'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		ticket_id I4 DEFAULT 0 NOTNULL,
		worker_id I4 DEFAULT 0 NOTNULL,
		created I4 DEFAULT 0 NOTNULL,
		comment XL
	";
    $sql = $datadict->CreateTableSQL('ticket_comment',$flds);
    $datadict->ExecuteSQLArray($sql);
}

// `ticket_field` ==================
if(!isset($tables['ticket_field'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		name C(32) DEFAULT '' NOTNULL,
		type C(1) DEFAULT 'S' NOTNULL,
		group_id I4 DEFAULT 0 NOTNULL,
		pos I2 DEFAULT 0 NOTNULL,
		options XL
	";
    $sql = $datadict->CreateTableSQL('ticket_field',$flds);
    $datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('ticket_field');
$indexes = $datadict->MetaIndexes('ticket_field',false);

if(!isset($indexes['group_id'])) {
    $sql = $datadict->CreateIndexSQL('group_id','ticket_field','group_id');
    $datadict->ExecuteSQLArray($sql);
}

// `ticket_field_value` ==================
if(!isset($tables['ticket_field_value'])) {
    $flds = "
		field_id I4 DEFAULT 0 NOTNULL PRIMARY,
		ticket_id I4 DEFAULT 0 NOTNULL PRIMARY,
		field_value XL
	";
    $sql = $datadict->CreateTableSQL('ticket_field_value',$flds);
    $datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('ticket_field_value');
$indexes = $datadict->MetaIndexes('ticket_field_value',false);

if(!isset($indexes['ticket_id'])) {
    $sql = $datadict->CreateIndexSQL('ticket_id','ticket_field_value','ticket_id');
    $datadict->ExecuteSQLArray($sql);
}

// `ticket_rss` ========================
if(!isset($tables['ticket_rss'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		hash C(32) DEFAULT '' NOTNULL,
		title C(128) DEFAULT '' NOTNULL,
		worker_id I4 DEFAULT 0 NOTNULL,
		created I4 DEFAULT 0 NOTNULL,
		params B
	";
    $sql = $datadict->CreateTableSQL('ticket_rss',$flds);
    $datadict->ExecuteSQLArray($sql);
}

// `worker`
$columns = $datadict->MetaColumns('worker');
$indexes = $datadict->MetaIndexes('worker',false);

if(!isset($columns['CAN_DELETE'])) {
    $sql = $datadict->AddColumnSQL('worker', 'can_delete I1 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

// `worker_to_team`
$columns = $datadict->MetaColumns('worker_to_team');
$indexes = $datadict->MetaIndexes('worker_to_team',false);

if(!isset($columns['IS_MANAGER'])) {
    $sql = $datadict->AddColumnSQL('worker_to_team', 'is_manager I1 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

// `worker_workspace_list` =============================
if(!isset($tables['worker_workspace_list'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		worker_id I4 DEFAULT 0 NOTNULL,
		workspace C(32) DEFAULT '' NOTNULL,
		list_view XL
	";
    $sql = $datadict->CreateTableSQL('worker_workspace_list',$flds);
    $datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('worker_workspace_list');
$indexes = $datadict->MetaIndexes('worker_workspace_list',false);

if(!isset($columns['LIST_POS'])) {
	$sql = $datadict->AddColumnSQL('worker_workspace_list', 'list_pos I2 DEFAULT 0');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['worker_id'])) {
    $sql = $datadict->CreateIndexSQL('worker_id','worker_workspace_list','worker_id');
    $datadict->ExecuteSQLArray($sql);
}
if(!isset($indexes['workspace'])) {
    $sql = $datadict->CreateIndexSQL('workspace','worker_workspace_list','workspace');
    $datadict->ExecuteSQLArray($sql);
}

// ***** CloudGlue

if(!isset($tables['tag_to_content'])) {
    $flds = "
		index_id I2 DEFAULT 0 NOTNULL PRIMARY,
		tag_id I4 DEFAULT 0 NOTNULL PRIMARY,
		content_id I8 DEFAULT 0 NOTNULL PRIMARY
	";
    $sql = $datadict->CreateTableSQL('tag_to_content',$flds);
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($tables['tag_index'])) {
    $flds = "
		id I2 DEFAULT 0 NOTNULL PRIMARY,
		name C(64) DEFAULT '' NOTNULL 
	";
    $sql = $datadict->CreateTableSQL('tag_index',$flds);
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($tables['tag'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		name C(32) DEFAULT '' NOTNULL 
	";
    $sql = $datadict->CreateTableSQL('tag',$flds);
    $datadict->ExecuteSQLArray($sql);
}

// Remove any worker addresses from deleted workers
$sql = "SELECT DISTINCT atw.worker_id ".
	"FROM address_to_worker atw ".
	"LEFT JOIN worker w ON (w.id=atw.worker_id) ".
	"WHERE w.id IS NULL";
$rs = $db->Execute($sql);

while(!$rs->EOF) {
	$sql = sprintf("DELETE FROM address_to_worker WHERE worker_id = %d",
		$rs->fields['worker_id']
	);
	$db->Execute($sql);
	$rs->MoveNext();
}

// Remove any group settings from deleted groups
$sql = "SELECT DISTINCT gs.group_id ".
	"FROM group_setting gs ".
	"LEFT JOIN team t ON (t.id=gs.group_id) ".
	"WHERE t.id IS NULL";
$rs = $db->Execute($sql);

while(!$rs->EOF) {
	$sql = sprintf("DELETE FROM group_setting WHERE group_id = %d",
		$rs->fields['group_id']
	);
	$db->Execute($sql);
	$rs->MoveNext();
}

// Recover any tickets assigned to a NULL bucket
$sql = "SELECT DISTINCT t.category_id as id ".
	"FROM ticket t ".
	"LEFT JOIN category c ON (t.category_id=c.id) ".
	"WHERE c.id IS NULL AND t.category_id > 0";
$rs = $db->Execute($sql);

while(!$rs->EOF) {
	$sql = sprintf("UPDATE ticket SET category_id = 0 WHERE category_id = %d",
		$rs->fields['id']
	);
	$db->Execute($sql);
	$rs->MoveNext();
}

// Merge any addresses that managed to get into the DB mixed case
$rs = $db->Execute("SELECT count(id) AS hits, lower(email) AS email FROM address GROUP BY lower(email) HAVING count(id) > 1"); /* @var $rs ADORecordSet */

if(!$rs->EOF) {
	while(!$rs->EOF) {
		$rs2 = $db->Execute(sprintf("SELECT id,email,lower(email) as orig_email FROM address WHERE lower(email) = %s",
			$db->qstr($rs->fields['email'])
		));
	
		$ids = array();
		$best_id = 0;
		$ids_not_best = array();
		
		if(!$rs2->EOF)
		while(!$rs2->EOF) {
			$ids[] = intval($rs2->fields['id']);
			if(0==strcmp($rs2->fields['orig_email'], $rs2->fields['email'])) {
				$best_id = intval($rs2->fields['id']);
			} else {
				$ids_not_best[] = intval($rs2->fields['id']);
			}
			$rs2->MoveNext();
		}
		
		if(empty($ids_not_best))
			$best_id = array_shift($ids_not_best);

		if(!empty($best_id) && !empty($ids_not_best)) {
			// Address Auth (remove dupes)
			$db->Execute(sprintf("DELETE FROM address_auth WHERE address_id IN (%s)",
				implode(',', $ids_not_best)
			));
	
			// Messages (merge dupe senders)
			$db->Execute(sprintf("UPDATE message SET address_id = %d WHERE address_id IN (%s)",
				$best_id,
				implode(',', $ids_not_best)
			));
			
			// Requester (merge dupe reqs)
			$db->Execute(sprintf("UPDATE requester SET address_id = %d WHERE address_id IN (%s)",
				$best_id,
				implode(',', $ids_not_best)
			));
			$db->Execute(sprintf("DELETE FROM requester WHERE address_id IN (%s)",
				implode(',', $ids_not_best)
			));
			
			// Ticket: First Wrote (merge dupe reqs)
			$db->Execute(sprintf("UPDATE ticket SET first_wrote_address_id = %d WHERE first_wrote_address_id IN (%s)",
				$best_id,
				implode(',', $ids_not_best)
			));
			$db->Execute(sprintf("DELETE FROM ticket WHERE first_wrote_address_id IN (%s)",
				implode(',', $ids_not_best)
			));
	
			// Ticket: Last Wrote (merge dupe reqs)
			$db->Execute(sprintf("UPDATE ticket SET last_wrote_address_id = %d WHERE last_wrote_address_id IN (%s)",
				$best_id,
				implode(',', $ids_not_best)
			));
			$db->Execute(sprintf("DELETE FROM ticket WHERE last_wrote_address_id IN (%s)",
				implode(',', $ids_not_best)
			));
	
			// Addresses
			$db->Execute(sprintf("DELETE FROM address WHERE id IN (%s)",
				implode(',', $ids_not_best)
			));
		}
		
		$rs->MoveNext();
	}
}

// Fix blank ticket.first_message_id links (compose)
$rs = $db->Execute('select t.id,max(m.id) as max_id,min(m.id) as min_id from ticket t inner join message m on (m.ticket_id=t.id) where t.first_message_id = 0 group by t.id;');
while(!$rs->EOF) {
	$db->Execute(sprintf("UPDATE ticket SET first_message_id = %d WHERE id = %d",
		$rs->fields['max_id'],
		$rs->fields['id']
	));
	$rs->MoveNext();
}

// [TODO] This should probably be checked (though MySQL needs special BINARY syntax)
$db->Execute("UPDATE address SET email = LOWER(email)");

// Enable heartbeat cron
if(null != ($cron_mf = DevblocksPlatform::getExtension('cron.heartbeat'))) {
	if(null != ($cron = $cron_mf->createInstance())) {
		$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, true);
		$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '5');
		$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'm');
		$cron->setParam(CerberusCronPageExtension::PARAM_LASTRUN, strtotime('Yesterday'));
	}
}

return TRUE;
?>