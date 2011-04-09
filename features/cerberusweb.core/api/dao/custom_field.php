<?php
/***********************************************************************
 | Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2011, WebGroup Media LLC
 |   unless specifically noted otherwise.
 |
 | This source code is released under the Devblocks Public License.
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
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */

class DAO_CustomField extends DevblocksORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const TYPE = 'type';
	const GROUP_ID = 'group_id';
	const CONTEXT = 'context';
	const POS = 'pos';
	const OPTIONS = 'options';
	
	const CACHE_ALL = 'ch_customfields'; 
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO custom_field () ".
			"VALUES ()"
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		$id = $db->LastInsertId(); 

		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'custom_field', $fields);
		
		self::clearCache();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_CustomField|null
	 */
	static function get($id) {
		$fields = self::getAll();
		
		if(isset($fields[$id]))
			return $fields[$id];
			
		return null;
	}
	
	static function getByContextAndGroupId($context, $group_id) {
		$fields = self::getAll();

		// Filter out groups that don't match
		foreach($fields as $field_id => $field) { /* @var $field Model_CustomField */
			if($group_id != $field->group_id || $context != $field->context) {
				unset($fields[$field_id]);
			}
		}
		
		return $fields;
	}
	
	static function getByContext($context) {
		$fields = self::getAll();
		
		// Filter fields to only the requested source
		foreach($fields as $idx => $field) { /* @var $field Model_CustomField */
			if(0 != strcasecmp($field->context, $context))
				unset($fields[$idx]);
		}
		
		return $fields;
	}
	
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();
		
		if(null === ($objects = $cache->load(self::CACHE_ALL))) {
			$db = DevblocksPlatform::getDatabaseService();
			$sql = "SELECT id, name, type, context, group_id, pos, options ".
				"FROM custom_field ".
				"ORDER BY group_id ASC, pos ASC "
			;
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
			
			$objects = self::_createObjectsFromResultSet($rs);
			
			$cache->save($objects, self::CACHE_ALL);
		}
		
		return $objects;
	}
	
	private static function _createObjectsFromResultSet($rs) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_CustomField();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->type = $row['type'];
			$object->context = $row['context'];
			$object->group_id = intval($row['group_id']);
			$object->pos = intval($row['pos']);
			$object->options = DevblocksPlatform::parseCrlfString($row['options']);
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	public static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		if(empty($ids))
			return;
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$id_string = implode(',', $ids);
		
		$sql = sprintf("DELETE QUICK FROM custom_field WHERE id IN (%s)",$id_string);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		if(is_array($ids))
		foreach($ids as $id) {
			DAO_CustomFieldValue::deleteByFieldId($id);
		}
		
		self::clearCache();
	}
	
	public static function clearCache() {
		// Invalidate cache on changes
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
};

class DAO_CustomFieldValue extends DevblocksORMHelper {
	const FIELD_ID = 'field_id';
	const CONTEXT = 'context';
	const CONTEXT_ID = 'context_id';
	const FIELD_VALUE = 'field_value';
	
	public static function getValueTableName($field_id) {
		$field = DAO_CustomField::get($field_id);
		
		// Determine value table by type
		$table = null;
		switch($field->type) {
			// stringvalue
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_DROPDOWN:	
			case Model_CustomField::TYPE_MULTI_CHECKBOX:	
			case Model_CustomField::TYPE_URL:
				$table = 'custom_field_stringvalue';	
				break;
			// clobvalue
			case Model_CustomField::TYPE_MULTI_LINE:
				$table = 'custom_field_clobvalue';
				break;
			// number
			case Model_CustomField::TYPE_CHECKBOX:
			case Model_CustomField::TYPE_DATE:
			case Model_CustomField::TYPE_NUMBER:
			case Model_CustomField::TYPE_WORKER:
				$table = 'custom_field_numbervalue';
				break;	
		}
		
		return $table;
	}
	
	/**
	 * 
	 * @param object $context
	 * @param object $context_id
	 * @param object $values
	 * @return 
	 */
	public static function formatAndSetFieldValues($context, $context_id, $values, $is_blank_unset=true, $delta=false, $autoadd_options=false) {
		if(empty($context) || empty($context_id) || !is_array($values))
			return;

		$fields = DAO_CustomField::getByContext($context);

		foreach($values as $field_id => $value) {
			if(!isset($fields[$field_id]))
				continue;

			$field =& $fields[$field_id]; /* @var $field Model_CustomField */
			$is_delta = ($field->type==Model_CustomField::TYPE_MULTI_CHECKBOX) 
					? $delta 
					: false
					;

			// if the field is blank
			if(
				(is_array($value) && empty($value))
				||
				(!is_array($value) && 0==strlen($value))
			) {
				// ... and blanks should unset
				if($is_blank_unset && !$is_delta)
					self::unsetFieldValue($context, $context_id, $field_id);
				
				// Skip setting
				continue;
			}

			switch($field->type) {
				case Model_CustomField::TYPE_SINGLE_LINE:
				case Model_CustomField::TYPE_URL:
					$value = (strlen($value) > 255) ? substr($value,0,255) : $value;
					self::setFieldValue($context, $context_id, $field_id, $value);
					break;

				case Model_CustomField::TYPE_MULTI_LINE:
					self::setFieldValue($context, $context_id, $field_id, $value);
					break;

				case Model_CustomField::TYPE_DROPDOWN:
					// If we're setting a field that doesn't exist yet, add it.
					if($autoadd_options && !in_array($value, $field->options) && !empty($value)) {
						$field->options[] = $value;
						DAO_CustomField::update($field_id, array(DAO_CustomField::OPTIONS => implode("\n",$field->options)));
					}
					
					// If we're allowed to add/remove fields without touching the rest
					if(in_array($value, $field->options))
						self::setFieldValue($context, $context_id, $field_id, $value); 
					
					break;
					
				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					if(!is_array($value))
						$value = array($value);

					// If we're setting a field that doesn't exist yet, add it.
					foreach($value as $v) {
						if($autoadd_options && !in_array($v, $field->options) && !empty($v)) {
							$field->options[] = $v;
							DAO_CustomField::update($field_id, array(DAO_CustomField::OPTIONS => implode("\n",$field->options)));
						}
					}

					if(!$delta) {
						self::unsetFieldValue($context, $context_id, $field_id);
					}
					
					// Protect from injection in cases where it's not desireable (controlled above)
					foreach($value as $idx => $v) {
						if(!in_array($v, $field->options))
							continue;

						$is_unset = ('-'==substr($v,0,1)) ? true : false;
						$v = ltrim($v,'+-');
							
						if($is_unset) {
							if($delta)
								self::unsetFieldValue($context, $context_id, $field_id, $v);
						} else {
							self::setFieldValue($context, $context_id, $field_id, $v, true);
						}
					}

					break;

				case Model_CustomField::TYPE_CHECKBOX:
					$value = !empty($value) ? 1 : 0;
					self::setFieldValue($context, $context_id, $field_id, $value);
					break;

				case Model_CustomField::TYPE_DATE:
					@$value = strtotime($value);
					self::setFieldValue($context, $context_id, $field_id, $value);
					break;

				case Model_CustomField::TYPE_NUMBER:
					$value = intval($value);
					self::setFieldValue($context, $context_id, $field_id, $value);
					break;
					
				case Model_CustomField::TYPE_WORKER:
					$value = intval($value);
					self::setFieldValue($context, $context_id, $field_id, $value);
					break;
			}
		}
		
	}
	
	public static function setFieldValue($context, $context_id, $field_id, $value, $delta=false) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(null == ($field = DAO_CustomField::get($field_id)))
			return FALSE;
		
		if(null == ($table_name = self::getValueTableName($field_id)))
			return FALSE;

		// Data formating
		switch($field->type) {
			case 'D': // dropdown
			case 'S': // string
			case 'U': // URL
				if(255 < strlen($value))
					$value = substr($value,0,255);
				break;
			case 'E': // date
			case 'N': // number
			case 'W': // worker
				$value = intval($value);
		}
		
		// Clear existing values (beats replace logic)
		self::unsetFieldValue($context, $context_id, $field_id, ($delta?$value:null));

		// Set values consistently
		if(!is_array($value))
			$value = array($value);
			
		foreach($value as $v) {
			$sql = sprintf("INSERT INTO %s (field_id, context, context_id, field_value) ".
				"VALUES (%d, %s, %d, %s)",
				$table_name,
				$field_id,
				$db->qstr($context),
				$context_id,
				$db->qstr($v)
			);
			$db->Execute($sql);
		}
		
		return TRUE;
	}
	
	public static function unsetFieldValue($context, $context_id, $field_id, $value=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(null == ($field = DAO_CustomField::get($field_id)))
			return FALSE;
		
		if(null == ($table_name = self::getValueTableName($field_id)))
			return FALSE;
		
		if(!is_array($value))
			$value = array($value);
			
		foreach($value as $v) {
			// Delete all values or optionally a specific given value
			$sql = sprintf("DELETE QUICK FROM %s WHERE context = '%s' AND context_id = %d AND field_id = %d %s",
				$table_name,
				$context,
				$context_id,
				$field_id,
				(!is_null($v) ? sprintf("AND field_value = %s ",$db->qstr($v)) : "")
			);
			$db->Execute($sql);
		}
		
		return TRUE;
	}
	
	public static function handleBulkPost($do) {
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'],'array',array());

		$fields = DAO_CustomField::getAll();
		
		if(is_array($field_ids))
		foreach($field_ids as $field_id) {
			if(!isset($fields[$field_id]))
				continue;
			
			switch($fields[$field_id]->type) {
				case Model_CustomField::TYPE_MULTI_LINE:
				case Model_CustomField::TYPE_SINGLE_LINE:
				case Model_CustomField::TYPE_URL:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_NUMBER:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$field_value = (0==strlen($field_value)) ? '' : intval($field_value);
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_DROPDOWN:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_CHECKBOX:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'integer',0);
					$do['cf_'.$field_id] = array('value' => !empty($field_value) ? 1 : 0);
					break;

				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'array',array());
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_DATE:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_WORKER:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
			}
		}
		
		return $do;
	}
	
	public static function handleFormPost($context, $context_id, $field_ids) {
		$fields = DAO_CustomField::getByContext($context);
		
		if(is_array($field_ids))
		foreach($field_ids as $field_id) {
			if(!isset($fields[$field_id]))
				continue;
			
			switch($fields[$field_id]->type) {
				case Model_CustomField::TYPE_MULTI_LINE:
				case Model_CustomField::TYPE_SINGLE_LINE:
				case Model_CustomField::TYPE_URL:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					if(0 != strlen($field_value)) {
						DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, $field_value);
					} else {
						DAO_CustomFieldValue::unsetFieldValue($context, $context_id, $field_id);
					}
					break;
					
				case Model_CustomField::TYPE_DROPDOWN:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					if(0 != strlen($field_value)) {
						DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, $field_value);
					} else {
						DAO_CustomFieldValue::unsetFieldValue($context, $context_id, $field_id);
					}
					break;
					
				case Model_CustomField::TYPE_CHECKBOX:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'integer',0);
					$set = !empty($field_value) ? 1 : 0;
					DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, $set);
					break;

				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'array',array());
					if(!empty($field_value)) {
						DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, $field_value);
					} else {
						DAO_CustomFieldValue::unsetFieldValue($context, $context_id, $field_id);
					}
					break;
				
				case Model_CustomField::TYPE_DATE:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					@$date = strtotime($field_value);
					if(!empty($date)) {
						DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, $date);
					} else {
						DAO_CustomFieldValue::unsetFieldValue($context, $context_id, $field_id);
					}
					break;

				case Model_CustomField::TYPE_NUMBER:
				case Model_CustomField::TYPE_WORKER:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					if(0 != strlen($field_value)) {
						DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, intval($field_value));
					} else {
						DAO_CustomFieldValue::unsetFieldValue($context, $context_id, $field_id);
					}
					break;
			}
		}
		
		return true;
	}
	
	public static function getValuesByContextIds($context, $context_ids) {
		if(is_null($context_ids))
			return array();
		elseif(!is_array($context_ids))
			$context_ids = array($context_ids);

		if(empty($context_ids))
			return array();
			
		$db = DevblocksPlatform::getDatabaseService();
		
		$results = array();
		
		$fields = DAO_CustomField::getAll();
			
		// [TODO] This is inefficient (and redundant)
			
		// STRINGS
		$sql = sprintf("SELECT context_id, field_id, field_value ".
			"FROM custom_field_stringvalue ".
			"WHERE context = '%s' AND context_id IN (%s)",
			$context,
			implode(',', $context_ids)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		while($row = mysql_fetch_assoc($rs)) {
			$context_id = intval($row['context_id']);
			$field_id = intval($row['field_id']);
			$field_value = $row['field_value'];
			
			if(!isset($results[$context_id]))
				$results[$context_id] = array();
				
			$ptr =& $results[$context_id];
			
			// If multiple value type (multi-checkbox)
			if($fields[$field_id]->type=='X') {
				if(!isset($ptr[$field_id]))
					$ptr[$field_id] = array();
					
				$ptr[$field_id][$field_value] = $field_value;
				
			} else { // single value
				$ptr[$field_id] = $field_value;
				
			}
		}
		
		mysql_free_result($rs);
		
		// CLOBS
		$sql = sprintf("SELECT context_id, field_id, field_value ".
			"FROM custom_field_clobvalue ".
			"WHERE context = '%s' AND context_id IN (%s)",
			$context,
			implode(',', $context_ids)
		);
		
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		while($row = mysql_fetch_assoc($rs)) {
			$context_id = intval($row['context_id']);
			$field_id = intval($row['field_id']);
			$field_value = $row['field_value'];
			
			if(!isset($results[$context_id]))
				$results[$context_id] = array();
				
			$ptr =& $results[$context_id];
			$ptr[$field_id] = $field_value;
		}
		
		mysql_free_result($rs);

		// NUMBERS
		$sql = sprintf("SELECT context_id, field_id, field_value ".
			"FROM custom_field_numbervalue ".
			"WHERE context = '%s' AND context_id IN (%s)",
			$context,
			implode(',', $context_ids)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		while($row = mysql_fetch_assoc($rs)) {
			$context_id = intval($row['context_id']);
			$field_id = intval($row['field_id']);
			$field_value = $row['field_value'];
			
			if(!isset($results[$context_id]))
				$results[$context_id] = array();
				
			$ptr =& $results[$context_id];
			$ptr[$field_id] = $field_value;
		}
		
		mysql_free_result($rs);
		
		return $results;
	}
	
	public static function deleteByContextIds($context, $context_ids) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(!is_array($context_ids)) $context_ids = array($context_ids);
		$ids_list = implode(',', $context_ids);

		$tables = array('custom_field_stringvalue','custom_field_clobvalue','custom_field_numbervalue');
		
		if(!empty($context_ids))
		foreach($tables as $table) {
			$sql = sprintf("DELETE QUICK FROM %s WHERE context = %s AND context_id IN (%s)",
				$table,
				$db->qstr($context),
				implode(',', $context_ids)
			);
			$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		}
	}
	
	public static function deleteByFieldId($field_id) {
		$db = DevblocksPlatform::getDatabaseService();

		$tables = array('custom_field_stringvalue','custom_field_clobvalue','custom_field_numbervalue');

		foreach($tables as $table) {
			$sql = sprintf("DELETE QUICK FROM %s WHERE field_id = %d",
				$table,
				$field_id
			);
			$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		}

	}
};

class Model_CustomField {
	const TYPE_CHECKBOX = 'C';
	const TYPE_DROPDOWN = 'D';
	const TYPE_DATE = 'E';
	const TYPE_NUMBER = 'N';
	const TYPE_SINGLE_LINE = 'S';
	const TYPE_MULTI_LINE = 'T';
	const TYPE_URL = 'U';
	const TYPE_WORKER = 'W';
	const TYPE_MULTI_CHECKBOX = 'X';
	
	public $id = 0;
	public $name = '';
	public $type = '';
	public $group_id = 0;
	public $context = '';
	public $pos = 0;
	public $options = array();
	
	static function getTypes() {
		return array(
			self::TYPE_SINGLE_LINE => 'Text: Single Line',
			self::TYPE_MULTI_LINE => 'Text: Multi-Line',
			self::TYPE_NUMBER => 'Number',
			self::TYPE_DATE => 'Date',
			self::TYPE_DROPDOWN => 'Picklist',
			self::TYPE_CHECKBOX => 'Checkbox',
			self::TYPE_MULTI_CHECKBOX => 'Multi-Checkbox',
			self::TYPE_WORKER => 'Worker',
			self::TYPE_URL => 'URL',
//			self::TYPE_FILE => 'File',
		);
	}
};