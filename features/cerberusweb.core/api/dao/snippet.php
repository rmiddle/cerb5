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
class DAO_Snippet extends C4_ORMHelper {
	const ID = 'id';
	const TITLE = 'title';
	const CONTEXT = 'context';
	const CREATED_BY = 'created_by';
	const LAST_UPDATED = 'last_updated';
	const LAST_UPDATED_BY = 'last_updated_by';
	const IS_PRIVATE = 'is_private';
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
		$sql = "SELECT id, title, context, created_by, last_updated, last_updated_by, is_private, content ".
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
	 * @return Model_Snippet	 */
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
			$object->created_by = $row['created_by'];
			$object->last_updated = $row['last_updated'];
			$object->last_updated_by = $row['last_updated_by'];
			$object->is_private = $row['is_private'];
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
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Snippet::getFields();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Sanitize
		if(!isset($fields[$sortBy]) || '*'==substr($sortBy,0,1) || !in_array($sortBy,$columns))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"snippet.id as %s, ".
			"snippet.title as %s, ".
			"snippet.context as %s, ".
			"snippet.created_by as %s, ".
			"snippet.last_updated as %s, ".
			"snippet.last_updated_by as %s, ".
			"snippet.is_private as %s, ".
			"snippet.content as %s",
				SearchFields_Snippet::ID,
				SearchFields_Snippet::TITLE,
				SearchFields_Snippet::CONTEXT,
				SearchFields_Snippet::CREATED_BY,
				SearchFields_Snippet::LAST_UPDATED,
				SearchFields_Snippet::LAST_UPDATED_BY,
				SearchFields_Snippet::IS_PRIVATE,
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
	const CREATED_BY = 's_created_by';
	const LAST_UPDATED = 's_last_updated';
	const LAST_UPDATED_BY = 's_last_updated_by';
	const IS_PRIVATE = 's_is_private';
	const CONTENT = 's_content';
	
	const USAGE_HITS = 'su_hits';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'snippet', 'id', $translate->_('common.id')),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'snippet', 'title', $translate->_('common.title')),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'snippet', 'context', $translate->_('common.context')),
			self::CREATED_BY => new DevblocksSearchField(self::CREATED_BY, 'snippet', 'created_by', $translate->_('dao.snippet.created_by')),
			self::LAST_UPDATED => new DevblocksSearchField(self::LAST_UPDATED, 'snippet', 'last_updated', $translate->_('dao.snippet.last_updated')),
			self::LAST_UPDATED_BY => new DevblocksSearchField(self::LAST_UPDATED_BY, 'snippet', 'last_updated_by', $translate->_('dao.snippet.last_updated_by')),
			self::IS_PRIVATE => new DevblocksSearchField(self::IS_PRIVATE, 'snippet', 'is_private', $translate->_('dao.snippet.is_private')),
			self::CONTENT => new DevblocksSearchField(self::CONTENT, 'snippet', 'content', $translate->_('common.content')),
			
			self::USAGE_HITS => new DevblocksSearchField(self::USAGE_HITS, 'snippet_usage', 'hits', $translate->_('dao.snippet_usage.hits')),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_SNIPPET);
		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		}
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;		
	}
};

class Model_Snippet {
	public $id;
	public $title;
	public $context;
	public $created_by;
	public $last_updated;
	public $last_updated_by;
	public $is_private;
	public $content;
	
	public function incrementUse($worker_id) {
		return DAO_Snippet::incrementUse($this->id, $worker_id);
	}
};

class View_Snippet extends C4_AbstractView {
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
			SearchFields_Snippet::LAST_UPDATED,
			SearchFields_Snippet::LAST_UPDATED_BY,
		);
		$this->addColumnsHidden(array(
			SearchFields_Snippet::ID,
			SearchFields_Snippet::IS_PRIVATE,
			SearchFields_Snippet::CONTENT,
		));
		
		$this->addParamsHidden(array(
			SearchFields_Snippet::ID,
			SearchFields_Snippet::IS_PRIVATE,
			SearchFields_Snippet::USAGE_HITS,
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

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$contexts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('contexts', $contexts);
		
		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
				$tpl->display('devblocks:cerberusweb.core::mail/snippets/views/view_contextlinks_chooser.tpl');
				break;
			default:
				$tpl->display('devblocks:cerberusweb.core::mail/snippets/views/default.tpl');
				break;
		}
		
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Snippet::ID:
			case SearchFields_Snippet::TITLE:
			case SearchFields_Snippet::CONTENT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case 'placeholder_number':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case SearchFields_Snippet::IS_PRIVATE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_Snippet::LAST_UPDATED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
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
			case SearchFields_Snippet::CREATED_BY:
			case SearchFields_Snippet::LAST_UPDATED_BY:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
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
				
				echo implode(', ', $strings);
				break;
				
			case SearchFields_Snippet::CREATED_BY:
			case SearchFields_Snippet::LAST_UPDATED_BY:
				$workers = DAO_Worker::getAll();
				$strings = array();
			
				foreach($param->value as $worker_id) {
					if(empty($worker_id)) {
						$strings[] = '<b>Nobody</b>';
					} elseif(isset($workers[$worker_id])) {
						$strings[] = '<b>'.$workers[$worker_id]->getName().'</b>';
					}
				}
			
				echo implode(', ', $strings);
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
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case 'placeholder_number':
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Snippet::LAST_UPDATED:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_Snippet::IS_PRIVATE:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Snippet::CONTEXT:
				@$in_contexts = DevblocksPlatform::importGPC($_REQUEST['contexts'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$in_contexts);
				break;
				
			case SearchFields_Snippet::CREATED_BY:
			case SearchFields_Snippet::LAST_UPDATED_BY:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$worker_ids);
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
					//$change_fields[DAO_Snippet::EXAMPLE] = 'some value';
					break;
				default:
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
			
			DAO_Snippet::update($batch_ids, $change_fields);

			// Custom Fields
			self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_SNIPPET, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};

class Context_Snippet extends Extension_DevblocksContext {
    function getPermalink($context_id) {
    	$url_writer = DevblocksPlatform::getUrlService();
    	return NULL;
    }

	function getContext($snippet, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Snippet:';
		
		$translate = DevblocksPlatform::getTranslationService();
		//$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TASK);

		// Polymorph
		if(is_numeric($snippet)) {
			$snippet = DAO_Task::get($snippet);
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
			SearchFields_Snippet::LAST_UPDATED,
			SearchFields_Snippet::LAST_UPDATED_BY,
			SearchFields_Snippet::USAGE_HITS,
		);
		
		// If we're being given contexts to filter down to
		if(isset($_REQUEST['contexts'])) {
			$contexts = DevblocksPlatform::parseCsvString(DevblocksPlatform::importGPC($_REQUEST['contexts'],'string',''));
			$contexts[] = '';
			if(is_array($contexts) && !empty($contexts)) {
				$view->addParamsRequired(
					array(
						SearchFields_Snippet::CONTEXT => new DevblocksSearchCriteria(SearchFields_Snippet::CONTEXT, DevblocksSearchCriteria::OPER_IN, $contexts)
					),
					true
				);
			}
		}
		
		$view->renderSortBy = SearchFields_Snippet::USAGE_HITS;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
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
