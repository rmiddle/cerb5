<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class DAO_Bucket extends DevblocksORMHelper {
	const CACHE_ALL = 'cerberus_cache_buckets_all';
	
    const ID = 'id';
    const POS = 'pos';
    const NAME = 'name';
    const GROUP_ID = 'group_id';
    const REPLY_ADDRESS_ID = 'reply_address_id';
    const REPLY_PERSONAL = 'reply_personal';
    const REPLY_SIGNATURE = 'reply_signature';
    const IS_ASSIGNABLE = 'is_assignable';
    
	static function getGroups() {
		$buckets = self::getAll();
		$group_buckets = array();
		
		foreach($buckets as $bucket) {
			$group_buckets[$bucket->group_id][$bucket->id] = $bucket;
		}
		
		return $group_buckets;
	}
	
	/**
	 * 
	 * @param bool $nocache
	 * @return Model_Bucket[]
	 */
	static function getAll($nocache=false) {
	    $cache = DevblocksPlatform::getCacheService();
	    if($nocache || null === ($buckets = $cache->load(self::CACHE_ALL))) {
    	    $buckets = self::getList();
    	    $cache->save($buckets, self::CACHE_ALL);
	    }
	    
	    return $buckets;
	}
	
	/**
	 * 
	 * @param integer $id
	 * @return Model_Bucket
	 */
	static function get($id) {
		$buckets = self::getAll();
	
		if(isset($buckets[$id]))
			return $buckets[$id];
			
		return null;
	}
	
	static function getNextPos($group_id) {
		if(empty($group_id))
			return 0;
		
		$db = DevblocksPlatform::getDatabaseService();
		if(null != ($next_pos = $db->GetOne(sprintf("SELECT MAX(pos)+1 FROM bucket WHERE group_id = %d", $group_id))))
			return $next_pos;
			
		return 0;
	}
	
	static function getList($ids=array()) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT bucket.id, bucket.pos, bucket.name, bucket.group_id, bucket.is_assignable, bucket.reply_address_id, bucket.reply_personal, bucket.reply_signature ".
			"FROM bucket ".
			"INNER JOIN worker_group ON (bucket.group_id=worker_group.id) ".
			(!empty($ids) ? sprintf("WHERE bucket.id IN (%s) ", implode(',', $ids)) : "").
			"ORDER BY worker_group.name ASC, bucket.pos ASC "
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$buckets = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$bucket = new Model_Bucket();
			$bucket->id = intval($row['id']);
			$bucket->pos = intval($row['pos']);
			$bucket->name = $row['name'];
			$bucket->group_id = intval($row['group_id']);
			$bucket->is_assignable = intval($row['is_assignable']);
			$bucket->reply_address_id = $row['reply_address_id'];
			$bucket->reply_personal = $row['reply_personal'];
			$bucket->reply_signature = $row['reply_signature'];
			$buckets[$bucket->id] = $bucket;
		}
		
		mysql_free_result($rs);
		
		return $buckets;
	}
	
	static function getByGroup($group_ids) {
		if(!is_array($group_ids))
			$group_ids = array($group_ids);
		
		$group_buckets = array();
		
		$buckets = self::getAll();
		foreach($buckets as $bucket) {
			if(false !== array_search($bucket->group_id, $group_ids)) {
				$group_buckets[$bucket->id] = $bucket;
			}
		}
		return $group_buckets;
	}
	
	static function getAssignableBuckets($group_ids=null) {
		if(!is_null($group_ids) && !is_array($group_ids)) 
			$group_ids = array($group_ids);
		
		if(empty($group_ids)) {
			$buckets = self::getAll();
		} else {
			$buckets = self::getByGroup($group_ids);
		}
		
		// Remove buckets that aren't assignable
		if(is_array($buckets))
		foreach($buckets as $id => $bucket) {
			if(!$bucket->is_assignable)
				unset($buckets[$id]);
		}
		
		return $buckets;
	}
	
	static function create($name, $group_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Check for dupes
		$buckets = self::getAll();
		if(is_array($buckets))
		foreach($buckets as $bucket) {
			if(0==strcasecmp($name, $bucket->name) && $group_id == $bucket->group_id) {
				return $bucket->id;
			}
		}

		$next_pos = self::getNextPos($group_id);
		
		$sql = sprintf("INSERT INTO bucket (pos,name,group_id,is_assignable) ".
			"VALUES (%d,%s,%d,1)",
			$next_pos,
			$db->qstr($name),
			$group_id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		$id = $db->LastInsertId(); 

		self::clearCache();
		
		return $id;
	}
	
	static function update($id,$fields) {
		parent::_update($id,'bucket',$fields);

		self::clearCache();
	}
	
	static function delete($ids) {
	    if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		/*
		 * Notify anything that wants to know when buckets delete.
		 */
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'bucket.delete',
                array(
                    'bucket_ids' => $ids,
                )
            )
	    );
		
		$sql = sprintf("DELETE QUICK FROM bucket WHERE id IN (%s)", implode(',',$ids));
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		// Reset any tickets using this bucket
		$sql = sprintf("UPDATE ticket SET bucket_id = 0 WHERE bucket_id IN (%s)", implode(',',$ids));
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		self::clearCache();
	}
	
	static public function maint() {
		// Fire event
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'context.maint',
                array(
                	'context' => CerberusContexts::CONTEXT_BUCKET,
                	'context_table' => 'bucket',
                	'context_key' => 'id',
                )
            )
	    );
	}
	
	static public function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
	
};

class Model_Bucket {
	public $id;
	public $pos=0;
	public $name = '';
	public $group_id = 0;
	public $is_assignable = 1;
	public $reply_address_id;
	public $reply_personal;
	public $reply_signature;
	
	/**
	 * 
	 * @param integer $bucket_id
	 * @return Model_AddressOutgoing
	 */
	public function getReplyTo() {
		$from_id = 0;
		$froms = DAO_AddressOutgoing::getAll();
		
		// Cascade to bucket
		$from_id = $this->reply_address_id;
		
		// Cascade to group
		if(empty($from_id)) {
			$group = DAO_Group::get($this->group_id);
			$from_id = $group->reply_address_id;
		}
		
		// Cascade to global
		if(empty($from_id) || !isset($froms[$from_id])) {
			$from = DAO_AddressOutgoing::getDefault();
			$from_id = $from->address_id;
		}
			
		// Last check
		if(!isset($froms[$from_id]))
			return null;
		
		return $froms[$from_id];
	}
	
	public function getReplyFrom() {
		$from_id = 0;
		$froms = DAO_AddressOutgoing::getAll();
		
		// Cascade to bucket
		$from_id = $this->reply_address_id;
		
		// Cascade to group
		if(empty($from_id)) {
			$group = DAO_Group::get($this->group_id);
			$from_id = $group->reply_address_id;
		}
		
		// Cascade to global
		if(empty($from_id) || !isset($froms[$from_id])) {
			$from = DAO_AddressOutgoing::getDefault();
			$from_id = $from->address_id;
		}
			
		return $from_id;
	}
	
	public function getReplyPersonal($worker_model=null) {
		$froms = DAO_AddressOutgoing::getAll();
		
		// Cascade to bucket
		$personal = $this->reply_personal;
		
		// Cascade to bucket address
		if(empty($personal) && !empty($this->reply_address_id) && isset($froms[$this->reply_address_id])) {
			$from = $froms[$this->reply_address_id];
			$personal = $from->reply_personal;
		}

		// Cascade to group
		if(empty($personal)) {
			$group = DAO_Group::get($this->group_id);
			$personal = $group->reply_personal;
			
			// Cascade to group address
			if(empty($personal) && !empty($group->reply_address_id) && isset($froms[$group->reply_address_id])) {
				$from = $froms[$group->reply_address_id];
				$personal = $from->reply_personal;
			}
		}
		
		// Cascade to global
		if(empty($personal)) {
			$from = DAO_AddressOutgoing::getDefault();
			$personal = $from->reply_personal;
		}
		
		// If we have a worker model, convert template tokens
		if(empty($worker_model))
			$worker_model = new Model_Worker();
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$token_labels = array();
		$token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_model, $token_labels, $token_values);
		$personal = $tpl_builder->build($personal, $token_values);
		
		return $personal;
	}
	
	public function getReplySignature($worker_model=null) {
		$froms = DAO_AddressOutgoing::getAll();
		
		// Cascade to bucket
		$signature = $this->reply_signature;
		
		// Cascade to bucket address
		if(empty($signature) && !empty($this->reply_address_id) && isset($froms[$this->reply_address_id])) {
			$from = $froms[$this->reply_address_id];
			$signature = $from->reply_signature;
		}

		// Cascade to group
		if(empty($signature)) {
			$group = DAO_Group::get($this->group_id);
			$signature = $group->reply_signature;
			
			// Cascade to group address
			if(empty($signature) && !empty($group->reply_address_id) && isset($froms[$group->reply_address_id])) {
				$from = $froms[$group->reply_address_id];
				$signature = $from->reply_signature;
			}
		}
		
		// Cascade to global
		if(empty($signature)) {
			$from = DAO_AddressOutgoing::getDefault();
			$signature = $from->reply_signature;
		}
		
		// If we have a worker model, convert template tokens
		if(!empty($worker_model)) {
			$tpl_builder = DevblocksPlatform::getTemplateBuilder();
			$token_labels = array();
			$token_values = array();
			CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_model, $token_labels, $token_values);
			$signature = $tpl_builder->build($signature, $token_values);
		}
		
		return $signature;
	}	
};