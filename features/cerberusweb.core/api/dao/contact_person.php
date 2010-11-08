<?php
class DAO_ContactPerson extends DevblocksORMHelper {
	const ID = 'id';
	const EMAIL_ID = 'email_id';
	const CREATED = 'created';
	const LAST_LOGIN = 'last_login';
	const AUTH_SALT = 'auth_salt';
	const AUTH_PASSWORD = 'auth_password';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO contact_person () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'contact_person', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('contact_person', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ContactPerson[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, email_id, created, last_login, auth_salt, auth_password ".
			"FROM contact_person ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_ContactPerson	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_ContactPerson[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_ContactPerson();
			$object->id = intval($row['id']);
			$object->email_id = intval($row['email_id']);
			$object->created = intval($row['created']);
			$object->last_login = intval($row['last_login']);
			$object->auth_salt = $row['auth_salt'];
			$object->auth_password = $row['auth_password'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM contact_person WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ContactPerson::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"contact_person.id as %s, ".
			"contact_person.email_id as %s, ".
			"contact_person.created as %s, ".
			"contact_person.last_login as %s, ".
			"contact_person.auth_salt as %s, ".
			"contact_person.auth_password as %s, ".
			"address.first_name as %s, ".
			"address.last_name as %s, ".
			"address.email as %s ",
				SearchFields_ContactPerson::ID,
				SearchFields_ContactPerson::EMAIL_ID,
				SearchFields_ContactPerson::CREATED,
				SearchFields_ContactPerson::LAST_LOGIN,
				SearchFields_ContactPerson::AUTH_SALT,
				SearchFields_ContactPerson::AUTH_PASSWORD,
				SearchFields_ContactPerson::ADDRESS_FIRST_NAME,
				SearchFields_ContactPerson::ADDRESS_LAST_NAME,
				SearchFields_ContactPerson::ADDRESS_EMAIL
			);
			
		$join_sql = "FROM contact_person ".
			"INNER JOIN address ON (contact_person.email_id=address.id) ".
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.contact_person' AND context_link.to_context_id = contact_person.id) " : " ")
			;
		
		// Custom field joins
		//list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
		//	$tables,
		//	$params,
		//	'contact_person.id',
		//	$select_sql,
		//	$join_sql
		//);
		$has_multiple_values = false; // [TODO] Temporary when custom fields disabled
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		return array(
			'primary_table' => 'contact_person',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
	}
	
    /**
     * Enter description here...
     *
     * @param array $columns
     * @param DevblocksSearchCriteria[] $params
     * @param integer $limit
     * @param integer $page
     * @param string $sortBy
     * @param boolean $sortAsc
     * @param boolean $withCounts
     * @return array
     */
    static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY contact_person.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
            $total = mysql_num_rows($rs);
		}
		
		$results = array();
		$total = -1;
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$object_id = intval($row[SearchFields_ContactPerson::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT contact_person.id) " : "SELECT COUNT(contact_person.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_ContactPerson implements IDevblocksSearchFields {
	const ID = 'c_id';
	const EMAIL_ID = 'c_email_id';
	const CREATED = 'c_created';
	const LAST_LOGIN = 'c_last_login';
	const AUTH_SALT = 'c_auth_salt';
	const AUTH_PASSWORD = 'c_auth_password';
	
	const ADDRESS_EMAIL = 'a_email';
	const ADDRESS_FIRST_NAME = 'a_first_name';
	const ADDRESS_LAST_NAME = 'a_last_name';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'contact_person', 'id', $translate->_('common.id')),
			self::EMAIL_ID => new DevblocksSearchField(self::EMAIL_ID, 'contact_person', 'email_id'),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'contact_person', 'created', $translate->_('common.created')),
			self::LAST_LOGIN => new DevblocksSearchField(self::LAST_LOGIN, 'contact_person', 'last_login', $translate->_('dao.contact_person.last_login')),
			self::AUTH_SALT => new DevblocksSearchField(self::AUTH_SALT, 'contact_person', 'auth_salt', $translate->_('dao.contact_person.auth_salt')),
			self::AUTH_PASSWORD => new DevblocksSearchField(self::AUTH_PASSWORD, 'contact_person', 'auth_password', $translate->_('dao.contact_person.auth_password')),
			
			self::ADDRESS_EMAIL => new DevblocksSearchField(self::ADDRESS_EMAIL, 'address', 'email', $translate->_('common.email')),
			self::ADDRESS_FIRST_NAME => new DevblocksSearchField(self::ADDRESS_FIRST_NAME, 'address', 'first_name', $translate->_('address.first_name')),
			self::ADDRESS_LAST_NAME => new DevblocksSearchField(self::ADDRESS_LAST_NAME, 'address', 'last_name', $translate->_('address.last_name')),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
		);
		
		// Custom Fields
		//$fields = DAO_CustomField::getByContext(CerberusContexts::XXX);

		//if(is_array($fields))
		//foreach($fields as $field_id => $field) {
		//	$key = 'cf_'.$field_id;
		//	$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		//}
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;		
	}
};

class Model_ContactPerson {
	public $id;
	public $email_id;
	public $created;
	public $last_login;
	public $auth_salt;
	public $auth_password;
	
	private $_addresses = array();
	
	public function getAddresses() {
		if(empty($this->_addresses) && !empty($this->id)) {
			$this->_addresses = DAO_Address::getWhere(sprintf("%s = %d",
				DAO_Address::CONTACT_PERSON_ID,
				$this->id
			));
		}
		
		return $this->_addresses;
	}

	public function getPrimaryAddress() {
		$addresses = $this->getAddresses();
		if(isset($addresses[$this->email_id]))
			return $addresses[$this->email_id];
			
		return NULL;
	}
};

class View_ContactPerson extends C4_AbstractView {
	const DEFAULT_ID = 'contactperson';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('addy_book.tab.people');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_ContactPerson::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_ContactPerson::ADDRESS_FIRST_NAME,
			SearchFields_ContactPerson::ADDRESS_LAST_NAME,
			SearchFields_ContactPerson::ADDRESS_EMAIL,
			SearchFields_ContactPerson::CREATED,
			SearchFields_ContactPerson::LAST_LOGIN,
		);
		// Filter fields
		$this->addColumnsHidden(array(
			SearchFields_ContactPerson::EMAIL_ID,
			SearchFields_ContactPerson::AUTH_PASSWORD,
			SearchFields_ContactPerson::AUTH_SALT,
		));
		
		// Filter fields
		$this->addParamsHidden(array(
			SearchFields_ContactPerson::EMAIL_ID,
			SearchFields_ContactPerson::AUTH_PASSWORD,
			SearchFields_ContactPerson::AUTH_SALT,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_ContactPerson::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_ContactPerson', $size);
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		//$custom_fields = DAO_CustomField::getByContext(CerberusContexts::XXX);
		//$tpl->assign('custom_fields', $custom_fields);

		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
				$tpl->display('devblocks:cerberusweb.core::contacts/people/view_contextlinks_chooser.tpl');
				break;
			default:
				$tpl->display('devblocks:cerberusweb.core::contacts/people/view.tpl');
				break;
		}
		
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_ContactPerson::ADDRESS_EMAIL:
			case SearchFields_ContactPerson::ADDRESS_FIRST_NAME:
			case SearchFields_ContactPerson::ADDRESS_LAST_NAME:
			case SearchFields_ContactPerson::AUTH_SALT:
			case SearchFields_ContactPerson::AUTH_PASSWORD:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case SearchFields_ContactPerson::ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case 'placeholder_date':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
			/*
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
			*/
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_ContactPerson::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ContactPerson::ADDRESS_EMAIL:
			case SearchFields_ContactPerson::ADDRESS_FIRST_NAME:
			case SearchFields_ContactPerson::ADDRESS_LAST_NAME:
			case SearchFields_ContactPerson::AUTH_SALT:
			case SearchFields_ContactPerson::AUTH_PASSWORD:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_ContactPerson::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case 'placeholder_date':
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			/*
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
			*/
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(0);
	  
		$change_fields = array();
		$custom_fields = array();

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				// [TODO] Implement actions
				case 'example':
					//$change_fields[DAO_ContactPerson::EXAMPLE] = 'some value';
					break;
				/*
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
				*/
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_ContactPerson::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_ContactPerson::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			DAO_ContactPerson::update($batch_ids, $change_fields);

			// Custom Fields
			//self::_doBulkSetCustomFields(ChCustomFieldSource_ContactPerson::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};

class Context_ContactPerson extends Extension_DevblocksContext {
    static function searchInboundLinks($from_context, $from_context_id) {
    	list($results, $null) = DAO_ContactPerson::search(
    		array(
    			SearchFields_ContactPerson::ID,
    		),
    		array(
				new DevblocksSearchCriteria(SearchFields_ContactPerson::CONTEXT_LINK,'=',$from_context),
				new DevblocksSearchCriteria(SearchFields_ContactPerson::CONTEXT_LINK_ID,'=',$from_context_id),
    		),
    		-1,
    		0,
    		SearchFields_ContactPerson::LAST_LOGIN,
    		false,
    		false
    	);
    	
    	return $results;
    }
    
    function getPermalink($context_id) {
    	$url_writer = DevblocksPlatform::getUrlService();
    	return $url_writer->write('c=contacts&tab=people&id='.$context_id, true);
    }
    
	function getContext($person, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Person:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CONTACT_PERSON);
		
		// Polymorph
		if(is_numeric($person)) {
			$person = DAO_ContactPerson::get($person);
		} elseif($person instanceof Model_ContactPerson) {
			// It's what we want already.
		} else {
			$person = null;
		}
			
		// Token labels
		$token_labels = array(
			'id' => $prefix.$translate->_('common.id'),
		);
		
//		if(is_array($fields))
//		foreach($fields as $cf_id => $field) {
//			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
//		}

		// Token values
		$token_values = array();
		
		// Address token values
		if(null != $person) {
			$token_values['id'] = $address->id;
//			if(!empty($address->email))
//				$token_values['address'] = $address->email;
//			if(!empty($address->first_name))
//				$token_values['first_name'] = $address->first_name;
//			if(!empty($address->last_name))
//				$token_values['last_name'] = $address->last_name;
//			$token_values['num_spam'] = $address->num_spam;
//			$token_values['num_nonspam'] = $address->num_nonspam;
//			$token_values['is_banned'] = $address->is_banned;
//			$token_values['custom'] = array();
			
//			$field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ADDRESS, $address->id));
//			if(is_array($field_values) && !empty($field_values)) {
//				foreach($field_values as $cf_id => $cf_val) {
//					if(!isset($fields[$cf_id]))
//						continue;
//					
//					// The literal value
//					if(null != $address)
//						$token_values['custom'][$cf_id] = $cf_val;
//					
//					// Stringify
//					if(is_array($cf_val))
//						$cf_val = implode(', ', $cf_val);
//						
//					if(is_string($cf_val)) {
//						if(null != $address)
//							$token_values['custom_'.$cf_id] = $cf_val;
//					}
//				}
//			}
		}
		
		// Email Org
//		$org_id = (null != $address && !empty($address->contact_org_id)) ? $address->contact_org_id : null;
//		$merge_token_labels = array();
//		$merge_token_values = array();
//		CerberusContexts::getContext(CerberusContexts::CONTEXT_ORG, $org_id, $merge_token_labels, $merge_token_values, null, true);
//
//		CerberusContexts::merge(
//			'org_',
//			'',
//			$merge_token_labels,
//			$merge_token_values,
//			$token_labels,
//			$token_values
//		);		
		
		// [TODO] Link contact
		
		return true;		
	}

	function getChooserView() {
		$translate = DevblocksPlatform::getTranslationService();
		
		// View
		$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = 'View_ContactPerson';
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = $translate->_('addy_book.tab.people');
		
		$view->view_columns = array(
			SearchFields_ContactPerson::ADDRESS_FIRST_NAME,
			SearchFields_ContactPerson::ADDRESS_LAST_NAME,
			SearchFields_ContactPerson::ADDRESS_EMAIL,
		);
		
//		$view->addParamsDefault(array(
//			SearchFields_Address::IS_BANNED => new DevblocksSearchCriteria(SearchFields_Address::IS_BANNED,'=',0),
//		));
//		$view->addParams($view->getParamsDefault(), true);
		
		$view->renderSortBy = SearchFields_ContactPerson::LAST_LOGIN;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;		
	}
	
	function getView($context, $context_id, $options=array()) {
		$translate = DevblocksPlatform::getTranslationService();
		
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id; 
		$defaults->class_name = 'View_ContactPerson';
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = $translate->_('addy_book.tab.people');
		
		$params = array(
			new DevblocksSearchCriteria(SearchFields_ContactPerson::CONTEXT_LINK,'=',$context),
			new DevblocksSearchCriteria(SearchFields_ContactPerson::CONTEXT_LINK_ID,'=',$context_id),
		);
		
		if(isset($options['filter_open']))
			true; // Do nothing
		
		$view->addParams($params, true);
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};