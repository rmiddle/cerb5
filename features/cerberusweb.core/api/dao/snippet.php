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

class DAO_Snippet extends C4_ORMHelper {
	const ID = 'id';
	const TITLE = 'title';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	const CONTEXT = 'context';
	const CONTENT = 'content';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO snippet () ".
			"VALUES ()"
		);
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'snippet', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('snippet', $fields, $where);
	}
	
	static function incrementUse($id, $worker_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("UPDATE snippet_usage SET hits = hits + 1 WHERE snippet_id = %d AND worker_id = %d",
			$id,
			$worker_id
		);
		
		if(!$db->Execute($sql) || 0==$db->Affected_Rows()) {
			$sql = sprintf("INSERT INTO snippet_usage (snippet_id, worker_id, hits) VALUES (%d, %d, 1)",
				$id,
				$worker_id
			);
			return $db->Execute($sql);
		}
		
		return TRUE;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Snippet[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, title, context, owner_context, owner_context_id, content ".
			"FROM snippet ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_Snippet
	 */
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
	 * @return Model_Snippet[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_Snippet();
			$object->id = $row['id'];
			$object->title = $row['title'];
			$object->context = $row['context'];
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = $row['owner_context_id'];
			$object->content = $row['content'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$sql = "DELETE QUICK snippet_usage FROM snippet_usage LEFT JOIN worker ON snippet_usage.worker_id = worker.id WHERE worker.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' snippet_usage records.');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM snippet WHERE id IN (%s)", $ids_list));
		$db->Execute(sprintf("DELETE FROM snippet_usage WHERE snippet_id IN (%s)", $ids_list));
		
		// Fire event
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'context.delete',
                array(
                	'context' => CerberusContexts::CONTEXT_SNIPPET,
                	'context_ids' => $ids
                )
            )
	    );
		
		return true;
	}
	
	public static function random() {
		return self::_getRandom('snippet');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Snippet::getFields();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || !in_array($sortBy,$columns))
			$sortBy=null;

        list($tables, $wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"snippet.id as %s, ".
			"snippet.title as %s, ".
			"snippet.context as %s, ".
			"snippet.owner_context as %s, ".
			"snippet.owner_context_id as %s, ".
			"snippet.content as %s",
				SearchFields_Snippet::ID,
				SearchFields_Snippet::TITLE,
				SearchFields_Snippet::CONTEXT,
				SearchFields_Snippet::OWNER_CONTEXT,
				SearchFields_Snippet::OWNER_CONTEXT_ID,
				SearchFields_Snippet::CONTENT
			);
			
		if(isset($tables['snippet_usage']) && !empty($active_worker)) {
			$select_sql .= sprintf(
				", ".
				"snippet_usage.hits as %s",
				SearchFields_Snippet::USAGE_HITS
			);
		}
		
		$join_sql = " FROM snippet ".
		((isset($tables['snippet_usage']) && !empty($active_worker)) ? sprintf("LEFT JOIN snippet_usage ON (snippet_usage.snippet_id=snippet.id AND snippet_usage.worker_id=%d) ",$active_worker->id) : " ")
		;
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'snippet.id',
			$select_sql,
			$join_sql
		);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
		
		// Virtuals
		foreach($params as $param) {
			if(!is_a($param,'DevblocksSearchCriteria'))
				continue;
			
			$param_key = $param->field;
			settype($param_key, 'string');

			switch($param_key) {
				case SearchFields_Snippet::VIRTUAL_OWNER:
					if(!is_array($param->value))
						break;
					
					$wheres = array();
						
					foreach($param->value as $owner_context) {
						@list($context, $context_id) = explode(':', $owner_context);
						
						if(empty($context))
							continue;
						
						$wheres[] = sprintf("(snippet.owner_context = %s AND snippet.owner_context_id = %d)",
							C4_ORMHelper::qstr($context),
							$context_id
						);
					}
					
					if(!empty($wheres))
						$where_sql .= 'AND ' . implode(' OR ', $wheres);
					
					break;
			}
		}
		
		$result = array(
			'primary_table' => 'snippet',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => false,
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
			($has_multiple_values ? 'GROUP BY snippet.id ' : '').
			$sort_sql;
			
		// [TODO] Could push the select logic down a level too
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
			$object_id = intval($row[SearchFields_Snippet::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT snippet.id) " : "SELECT COUNT(snippet.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_Snippet implements IDevblocksSearchFields {
	const ID = 's_id';
	const TITLE = 's_title';
	const CONTEXT = 's_context';
	const OWNER_CONTEXT = 's_owner_context';
	const OWNER_CONTEXT_ID = 's_owner_context_id';
	const CONTENT = 's_content';
	
	const USAGE_HITS = 'su_hits';
	
	const VIRTUAL_OWNER = '*_owner';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'snippet', 'id', $translate->_('common.id')),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'snippet', 'title', $translate->_('common.title')),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'snippet', 'context', $translate->_('common.type')),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'snippet', 'owner_context', $translate->_('dao.snippet.owner_context')),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'snippet', 'owner_context_id', $translate->_('dao.snippet.owner_context_id')),
			self::CONTENT => new DevblocksSearchField(self::CONTENT, 'snippet', 'content', $translate->_('common.content')),
			
			self::USAGE_HITS => new DevblocksSearchField(self::USAGE_HITS, 'snippet_usage', 'hits', $translate->_('dao.snippet_usage.hits')),
			
			self::VIRTUAL_OWNER => new DevblocksSearchField(self::VIRTUAL_OWNER, '*', 'owner', $translate->_('common.owner')),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_SNIPPET);
		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		}
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;		
	}
};

class Model_Snippet {
	public $id;
	public $title;
	public $context;
	public $owner_context;
	public $owner_context_id;
	public $content;
	
	public function incrementUse($worker_id) {
		return DAO_Snippet::incrementUse($this->id, $worker_id);
	}

	function isReadableByWorker($worker) {
		if(is_a($worker, 'Model_Worker')) {
			// This is what we want
		} elseif (is_numeric($worker)) {
			if(null == ($worker = DAO_Worker::get($worker)))
				return false;
		} else {
			return false;
		}
		
		// Superusers can do anything
		if($worker->is_superuser)
			return true;
		
		switch($this->owner_context) {
			case CerberusContexts::CONTEXT_GROUP:
				if(in_array($this->owner_context_id, array_keys($worker->getMemberships())))
					return true;
				break;
				
			case CerberusContexts::CONTEXT_ROLE:
				if(in_array($this->owner_context_id, array_keys($worker->getRoles())))
					return true;
				break;
				
			case CerberusContexts::CONTEXT_WORKER:
				if($worker->id == $this->owner_context_id)
					return true;
				break;
		}
		
		return false;
	}
	
	function isWriteableByWorker($worker) {
		if(is_a($worker, 'Model_Worker')) {
			// This is what we want
		} elseif (is_numeric($worker)) {
			if(null == ($worker = DAO_Worker::get($worker)))
				return false;
		} else {
			return false;
		}
		
		// Superusers can do anything
		if($worker->is_superuser)
			return true;
		
		switch($this->owner_context) {
			case CerberusContexts::CONTEXT_GROUP:
				if(in_array($this->owner_context_id, array_keys($worker->getMemberships())))
					if($worker->isGroupManager($this->owner_context_id))
						return true;
				break;
				
			case CerberusContexts::CONTEXT_ROLE:
				if($worker->is_superuser)
					return true;
				break;
				
			case CerberusContexts::CONTEXT_WORKER:
				if($worker->id == $this->owner_context_id)
					return true;
				break;
		}
		
		return false;
	}	
	
};

class View_Snippet extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'snippet';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Snippet');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Snippet::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Snippet::TITLE,
			SearchFields_Snippet::CONTEXT,
			SearchFields_Snippet::VIRTUAL_OWNER,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Snippet::ID,
			SearchFields_Snippet::CONTENT,
			SearchFields_Snippet::OWNER_CONTEXT,
			SearchFields_Snippet::OWNER_CONTEXT_ID,
		));
		
		$this->addParamsHidden(array(
			SearchFields_Snippet::ID,
			SearchFields_Snippet::USAGE_HITS,
			SearchFields_Snippet::OWNER_CONTEXT,
			SearchFields_Snippet::OWNER_CONTEXT_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Snippet::search(
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

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable();
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				case SearchFields_Snippet::CONTEXT:
					$pass = true;
					break;
				
				// Valid custom fields
				default:
					if('cf_' == substr($field_key,0,3))
						$pass = $this->_canSubtotalCustomField($field_key);
					break;
			}
			
			if($pass)
				$fields[$field_key] = $field_model;
		}
		
		return $fields;
	}
	
	function getSubtotalCounts($column) {
		$counts = array();
		$fields = $this->getFields();

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_Snippet::CONTEXT:
				$label_map = array(
					'' => 'Plaintext'
				);
				$contexts = Extension_DevblocksContext::getAll(false);
				
				foreach($contexts as $k => $mft) {
					$label_map[$k] = $mft->name;
				}
				
				$counts = $this->_getSubtotalCountForStringColumn('DAO_Snippet', $column, $label_map, 'in', 'contexts[]');
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_Snippet', $column, 's.id');
				}
				
				break;
		}
		
		return $counts;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$contexts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('contexts', $contexts);
		
		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/snippets/views/default.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
		
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		switch($field) {
			case SearchFields_Snippet::ID:
			case SearchFields_Snippet::TITLE:
			case SearchFields_Snippet::CONTENT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case 'placeholder_number':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case SearchFields_Snippet::CONTEXT:
				$contexts = Extension_DevblocksContext::getAll(false);
				
				// [TODO] [HACK!] Fake plaintext
				$plain = new stdClass();
				$plain->id = '';
				$plain->name = 'Plaintext';
				$contexts = array_merge(array(''=>$plain), $contexts);
				$tpl->assign('contexts', $contexts);
				
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context.tpl');
				break;
				
			case SearchFields_Snippet::VIRTUAL_OWNER:
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				$roles = DAO_WorkerRole::getAll();
				$tpl->assign('roles', $roles);
				
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$tpl->display('devblocks:cerberusweb.core::internal/snippets/views/criteria/virtual_owner.tpl');
				break;
				
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
		}
	}
	
	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		$translate = DevblocksPlatform::getTranslationService();
		
		switch($key) {
			case SearchFields_Snippet::VIRTUAL_OWNER:
				echo sprintf("%s %s ", 
					mb_convert_case($translate->_('common.owner'), MB_CASE_TITLE),
					$param->operator
				);
				
				$objects = array();
				
				if(is_array($param->value))
				foreach($param->value as $v) {
					@list($context, $context_id) = explode(':', $v);
					
					if(empty($context) || empty($context_id))
						continue;
					
					if(null == ($ext = Extension_DevblocksContext::get($context)))
						return;
					
					$meta = $ext->getMeta($context_id);
					
					if(empty($meta))
						return;
					
					$objects[] = sprintf("<b>%s (%s)</b>",
						$meta['name'],
						$ext->manifest->name
					);
				}
				
				echo implode('; ', $objects);
				
				break;
		}
	}	

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Snippet::CONTEXT:
				$contexts = Extension_DevblocksContext::getAll(false);
				$strings = array();
				
				foreach($param->value as $context_id) {
					if(empty($context_id)) {
						$strings[] = '<b>Plaintext</b>';
					} elseif(isset($contexts[$context_id])) {
						$strings[] = '<b>'.$contexts[$context_id]->name.'</b>';
					}
				}
				
				echo implode(' or ', $strings);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Snippet::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Snippet::ID:
			case SearchFields_Snippet::TITLE:
			case SearchFields_Snippet::CONTENT:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case 'placeholder_number':
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Snippet::CONTEXT:
				@$in_contexts = DevblocksPlatform::importGPC($_REQUEST['contexts'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$in_contexts);
				break;
				
			case SearchFields_Snippet::VIRTUAL_OWNER:
				@$owner_contexts = DevblocksPlatform::importGPC($_REQUEST['owner_context'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$owner_contexts);
				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
		
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
				case 'owner':
					@list($context, $context_id) = explode(':', $v);
					
					if(empty($context))
						break;
					
					$change_fields[DAO_Snippet::OWNER_CONTEXT] = $context;
					$change_fields[DAO_Snippet::OWNER_CONTEXT_ID] = $context_id;
					break;
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Snippet::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_Snippet::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!empty($change_fields))
				DAO_Snippet::update($batch_ids, $change_fields);

			// Custom Fields
			self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_SNIPPET, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};

class Context_Snippet extends Extension_DevblocksContext {
	function getRandom() {
		return DAO_Snippet::random();
	}
	
	function getMeta($context_id) {
		$snippet = DAO_Snippet::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		return array(
			'id' => $context_id,
			'name' => $snippet->title,
			'permalink' => '', //$url_writer->writeNoProxy('c=tasks&action=display&id='.$task->id, true),
		);
	}
	
	function getContext($snippet, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Snippet:';
		
		$translate = DevblocksPlatform::getTranslationService();
		//$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TASK);

		// Polymorph
		if(is_numeric($snippet)) {
			$snippet = DAO_Snippet::get($snippet);
		} elseif($snippet instanceof Model_Snippet) {
			// It's what we want already.
		} else {
			$snippet = null;
		}
		
		// Token labels
		$token_labels = array(
//			'completed|date' => $prefix.$translate->_('task.completed_date'),
		);
		
//		if(is_array($fields))
//		foreach($fields as $cf_id => $field) {
//			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
//		}

		// Token values
		$token_values = array();
		
		if($snippet) {
//			$token_values['completed'] = $task->completed_date;
			
//			$token_values['custom'] = array();
			
//			$field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_TASK, $task->id));
//			if(is_array($field_values) && !empty($field_values)) {
//				foreach($field_values as $cf_id => $cf_val) {
//					if(!isset($fields[$cf_id]))
//						continue;
//					
//					// The literal value
//					if(null != $task)
//						$token_values['custom'][$cf_id] = $cf_val;
//					
//					// Stringify
//					if(is_array($cf_val))
//						$cf_val = implode(', ', $cf_val);
//						
//					if(is_string($cf_val)) {
//						if(null != $task)
//							$token_values['custom_'.$cf_id] = $cf_val;
//					}
//				}
//			}
		}

		return true;
	}

	function getChooserView() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// View
		$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Snippets';
		$view->view_columns = array(
			SearchFields_Snippet::TITLE,
			SearchFields_Snippet::CONTEXT,
			SearchFields_Snippet::VIRTUAL_OWNER,
			SearchFields_Snippet::USAGE_HITS,
		);
		
		$params_required = array();
		
		$worker_group_ids = array_keys($active_worker->getMemberships());
		$worker_role_ids = array_keys(DAO_WorkerRole::getRolesByWorker($active_worker->id));
		
		// Restrict owners
		$param_ownership = array(
			DevblocksSearchCriteria::GROUP_OR,
			array(
				DevblocksSearchCriteria::GROUP_AND,
				SearchFields_Snippet::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_WORKER),
				SearchFields_Snippet::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_EQ,$active_worker->id),
			),
			array(
				DevblocksSearchCriteria::GROUP_AND,
				SearchFields_Snippet::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_GROUP),
				SearchFields_Snippet::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_IN,$worker_group_ids),
			),
			array(
				DevblocksSearchCriteria::GROUP_AND,
				SearchFields_Snippet::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_ROLE),
				SearchFields_Snippet::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_IN,$worker_role_ids),
			),
		);
		$params_required['_ownership'] = $param_ownership;
		
		// If we're being given contexts to filter down to
		if(isset($_REQUEST['contexts'])) {
			$contexts = DevblocksPlatform::parseCsvString(DevblocksPlatform::importGPC($_REQUEST['contexts'],'string',''));
			$contexts[] = '';
			if(is_array($contexts) && !empty($contexts)) {
				$params_required[SearchFields_Snippet::CONTEXT] = new DevblocksSearchCriteria(SearchFields_Snippet::CONTEXT, DevblocksSearchCriteria::OPER_IN, $contexts);
			}
		}
		
		$view->addParamsRequired($params_required, true);
		
		$view->renderSortBy = SearchFields_Snippet::USAGE_HITS;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		$view->renderFilters = true;
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;		
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id; 
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Snippets';

		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				//new DevblocksSearchCriteria(SearchFields_Snippet::CONTEXT_LINK,'=',$context),
				//new DevblocksSearchCriteria(SearchFields_Snippet::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};
