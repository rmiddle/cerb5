<?php
class DAO_Comment extends DevblocksORMHelper {
	const ID = 'id';
	const CONTEXT = 'context';
	const CONTEXT_ID = 'context_id';
	const CREATED = 'created';
	const ADDRESS_ID = 'address_id';
	const COMMENT = 'comment';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute("INSERT INTO comment () VALUES ()");
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		// New task
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'comment.action.create',
                array(
                    'comment_id' => $id,
                	'fields' => $fields,
                )
            )
	    );
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'comment', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('comment', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Comment[]
	 */
	static function getWhere($where=null, $sortBy='created', $sortAsc=false, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, context, context_id, created, address_id, comment ".
			"FROM comment ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}
	
	static function getByContext($context, $context_ids) {
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
		
		if(empty($context_ids))
			return array();

		return self::getWhere(sprintf("%s = %s AND %s IN (%s)",
			self::CONTEXT,
			C4_ORMHelper::qstr($context),
			self::CONTEXT_ID,
			implode(',', $context_ids)
		));
	}

	/**
	 * @param integer $id
	 * @return Model_Comment	 */
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
	 * @return Model_Comment[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_Comment();
			$object->id = $row['id'];
			$object->context = $row['context'];
			$object->context_id = $row['context_id'];
			$object->created = $row['created'];
			$object->address_id = $row['address_id'];
			$object->comment = $row['comment'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function deleteByContext($context, $context_ids) {
		if(!is_array($context_ids)) 
			$context_ids = array($context_ids);
		
		if(empty($context_ids))
			return;
			
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute(sprintf("DELETE FROM comment WHERE context = %s AND context_id IN (%s) ", 
			$db->qstr($context),
			implode(',', $context_ids)
		));
		
		return true;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM comment WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Comment::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]) || '*'==substr($sortBy,0,1))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"comment.id as %s, ".
			"comment.context as %s, ".
			"comment.context_id as %s, ".
			"comment.created as %s, ".
			"comment.address_id as %s, ".
			"comment.comment as %s ",
				SearchFields_Comment::ID,
				SearchFields_Comment::CONTEXT,
				SearchFields_Comment::CONTEXT_ID,
				SearchFields_Comment::CREATED,
				SearchFields_Comment::ADDRESS_ID,
				SearchFields_Comment::COMMENT
			);
			
		$join_sql = "FROM comment ";
		
		// Custom field joins
		//list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
		//	$tables,
		//	$params,
		//	'comment.id',
		//	$select_sql,
		//	$join_sql
		//);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
		
		$result = array(
			'primary_table' => 'comment',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
		
		return $result;
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
			//($has_multiple_values ? 'GROUP BY comment.id ' : '').
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
			$object_id = intval($row[SearchFields_Comment::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT comment.id) " : "SELECT COUNT(comment.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_Comment implements IDevblocksSearchFields {
	const ID = 'c_id';
	const CONTEXT = 'c_context';
	const CONTEXT_ID = 'c_context_id';
	const CREATED = 'c_created';
	const ADDRESS_ID = 'c_address_id';
	const COMMENT = 'c_comment';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'comment', 'id', $translate->_('common.id')),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'comment', 'context', null),
			self::CONTEXT_ID => new DevblocksSearchField(self::CONTEXT_ID, 'comment', 'context_id', null),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'comment', 'created', $translate->_('common.created')),
			self::ADDRESS_ID => new DevblocksSearchField(self::ADDRESS_ID, 'comment', 'address_id', $translate->_('common.email')),
			self::COMMENT => new DevblocksSearchField(self::COMMENT, 'comment', 'comment', $translate->_('common.comment')),
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

class Model_Comment {
	public $id;
	public $context;
	public $context_id;
	public $created;
	public $address_id;
	public $comment;
	
	public $_email_record = null;
	
	public function getAddress() {
		// Cache repeated calls
		if(null == $this->_email_record) {
			$this->_email_record = DAO_Address::get($this->address_id);
		}
		
		return $this->_email_record;
	}
};

class View_Comment extends C4_AbstractView {
	const DEFAULT_ID = 'comment';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		// [TODO] Name the worklist view
		$this->name = $translate->_('Comment');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Comment::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Comment::ID,
			SearchFields_Comment::CONTEXT,
			SearchFields_Comment::CONTEXT_ID,
			SearchFields_Comment::CREATED,
			SearchFields_Comment::ADDRESS_ID,
			SearchFields_Comment::COMMENT,
		);
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Comment::search(
			array(),
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// [TODO] Set your template path
		$tpl->display('devblocks:/path/to/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		// [TODO] Move the fields into the proper data type
		switch($field) {
			case SearchFields_Comment::ID:
			case SearchFields_Comment::CONTEXT:
			case SearchFields_Comment::CONTEXT_ID:
			case SearchFields_Comment::CREATED:
			case SearchFields_Comment::ADDRESS_ID:
			case SearchFields_Comment::COMMENT:
			case 'placeholder_string':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case 'placeholder_number':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case 'placeholder_date':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
			default:
				echo '';
				break;
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
		return SearchFields_Comment::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		// [TODO] Move fields into the right data type
		switch($field) {
			case SearchFields_Comment::ID:
			case SearchFields_Comment::CONTEXT:
			case SearchFields_Comment::CONTEXT_ID:
			case SearchFields_Comment::CREATED:
			case SearchFields_Comment::ADDRESS_ID:
			case SearchFields_Comment::COMMENT:
			case 'placeholder_string':
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case 'placeholder_number':
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
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
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
					//$change_fields[DAO_Comment::EXAMPLE] = 'some value';
					break;
				default:
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Comment::search(
				array(),
				$this->params,
				100,
				$pg++,
				SearchFields_Comment::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			DAO_Comment::update($batch_ids, $change_fields);

			// Custom Fields
			//self::_doBulkSetCustomFields(ChCustomFieldSource_Comment::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};
