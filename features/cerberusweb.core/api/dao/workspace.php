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

class DAO_WorkspacePage extends C4_ORMHelper {
	const _CACHE_ALL = 'ch_workspace_pages';
	
	const ID = 'id';
	const NAME = 'name';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();

		$sql = "INSERT INTO workspace_page () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();

		self::update($id, $fields);

		return $id;
	}

	static function update($ids, $fields) {
		parent::_update($ids, 'workspace_page', $fields);
		self::clearCache();
	}

	static function updateWhere($fields, $where) {
		parent::_updateWhere('workspace_page', $fields, $where);
		self::clearCache();
	}

	static function getAll($nocache=false) {
	    $cache = DevblocksPlatform::getCacheService();
	    
	    if($nocache || null === ($pages = $cache->load(self::_CACHE_ALL))) {
    	    $pages = self::getWhere(
    	    	null,
    	    	DAO_WorkspacePage::NAME,
    	    	true
			);
    	    
    	    $cache->save($pages, self::_CACHE_ALL);
	    }
	    
	    return $pages;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_WorkspacePage[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);

		// SQL
		$sql = "SELECT id, name, owner_context, owner_context_id ".
			"FROM workspace_page ".
			$where_sql.
			$sort_sql.
			$limit_sql
			;
			$rs = $db->Execute($sql);

		return self::_getObjectsFromResult($rs);
	}

	static function getByOwner($context, $context_id, $sortBy=null, $sortAsc=true, $limit=null) {
		$pages = array();
		
		$all_pages = self::getAll();
		foreach($all_pages as $page_id => $page) { /* @var $page Model_WorkspacePage */
			if($page->owner_context == $context
				&& $page->owner_context_id == $context_id) {
				
				$pages[$page_id] = $page;
			}
		}

		return $pages;
	}

	static function getByWorker($worker) {
		if(is_a($worker,'Model_Worker')) {
			// This is what we want
		} elseif(is_numeric($worker)) {
			$worker = DAO_Worker::get($worker);
		} else {
			return array();
		}

		$memberships = $worker->getMemberships();
		$roles = $worker->getRoles();
		
		$pages = array();
		$all_pages = self::getAll();
		
		foreach($all_pages as $page_id => $page) { /* @var $page Model_WorkspacePage */
			switch($page->owner_context) {
				case CerberusContexts::CONTEXT_ROLE:
					if(isset($roles[$page->owner_context_id]))
						$pages[$page_id] = $page;
					break;
					
				case CerberusContexts::CONTEXT_GROUP:
					if(isset($memberships[$page->owner_context_id]))
						$pages[$page_id] = $page;
					break;
					
				case CerberusContexts::CONTEXT_WORKER:
					if($worker->id == $page->owner_context_id)
						$pages[$page_id] = $page;
					break;
			}
		}

		return $pages;
	}

	/**
	 * @param integer $id
	 * @return Model_WorkspacePage
	 */
	static function get($id) {
		$objects = self::getAll();
		
		if(isset($objects[$id]))
			return $objects[$id];

		return null;
	}

	/**
	 * @param resource $rs
	 * @return Model_WorkspacePage[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();

		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_WorkspacePage();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = $row['owner_context_id'];
			$objects[$object->id] = $object;
		}

		mysql_free_result($rs);

		return $objects;
	}

	static function deleteByOwner($owner_context, $owner_context_ids) {
		if(!is_array($owner_context_ids))
			$owner_context_ids = array($owner_context_ids);

		foreach($owner_context_ids as $owner_context_id) {
			$pages = DAO_WorkspacePage::getByOwner($owner_context, $owner_context_id);
			DAO_WorkspacePage::delete(array_keys($pages));
		}
	}

	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();

		if(empty($ids))
			return;

		$ids_list = implode(',', $ids);

		// Cascade delete tabs and lists
		DAO_WorkspaceTab::deleteByPage($ids);
		
		// Delete pages
		$db->Execute(sprintf("DELETE FROM workspace_page WHERE id IN (%s)", $ids_list));

		self::clearCache();
		
		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_WorkspacePage::getFields();

		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);

		$select_sql = sprintf("SELECT ".
			"workspace_page.id as %s, ".
			"workspace_page.name as %s, ".
			"workspace_page.owner_context as %s, ".
			"workspace_page.owner_context_id as %s ",
			SearchFields_WorkspacePage::ID,
			SearchFields_WorkspacePage::NAME,
			SearchFields_WorkspacePage::OWNER_CONTEXT,
			SearchFields_WorkspacePage::OWNER_CONTEXT_ID
		);
			
		$join_sql = "FROM workspace_page ";

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";

		return array(
			'primary_table' => 'workspace_page',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => false,
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

		// Virtuals
		array_walk_recursive(
			$params,
			array('DAO_WorkspacePage', '_translateVirtualParameters'),
			array(
				'join_sql' => &$join_sql,
				'where_sql' => &$where_sql,
				'has_multiple_values' => &$has_multiple_values
			)
		);
		
		$sql =
		$select_sql.
		$join_sql.
		$where_sql.
		($has_multiple_values ? 'GROUP BY workspace_page.id ' : '').
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
			$object_id = intval($row[SearchFields_WorkspacePage::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql =
			($has_multiple_values ? "SELECT COUNT(DISTINCT workspace_page.id) " : "SELECT COUNT(workspace_page.id) ").
			$join_sql.
			$where_sql;
			$total = $db->GetOne($count_sql);
		}

		mysql_free_result($rs);

		return array($results,$total);
	}

	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_WorkspacePage::VIRTUAL_OWNER:
				if(!is_array($param->value))
					break;
				
				$wheres = array();
				$args['has_multiple_values'] = true;
					
				foreach($param->value as $owner_context) {
					@list($context, $context_id) = explode(':', $owner_context);
					
					if(empty($context))
						continue;
					
					$wheres[] = sprintf("(workspace_page.owner_context = %s AND workspace_page.owner_context_id = %d)",
						C4_ORMHelper::qstr($context),
						$context_id
					);
				}
				
				if(!empty($wheres))
					$args['where_sql'] .= 'AND ' . implode(' OR ', $wheres);
				
				break;
		}
	}	
	
	public static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();

		$sql = "DELETE QUICK workspace_tab FROM workspace_tab LEFT JOIN workspace_page ON (workspace_tab.workspace_page_id = workspace_page.id) WHERE workspace_page.id IS NULL";
		$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' workspace_tab records.');
	}

	static function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::_CACHE_ALL);
	}
};

class DAO_WorkspaceTab extends C4_ORMHelper {
	const _CACHE_ALL = 'ch_workspace_tabs';
	
	const ID = 'id';
	const NAME = 'name';
	const WORKSPACE_PAGE_ID = 'workspace_page_id';
	const POS = 'pos';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO workspace_tab () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'workspace_tab', $fields);
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('workspace_tab', $fields, $where);
		self::clearCache();
	}
	
	static function getAll($nocache=false) {
	    $cache = DevblocksPlatform::getCacheService();
	    
	    if($nocache || null === ($tabs = $cache->load(self::_CACHE_ALL))) {
    	    $tabs = self::getWhere(
    	    	null,
    	    	DAO_WorkspaceTab::NAME,
    	    	true
			);
    	    $cache->save($tabs, self::_CACHE_ALL);
	    }
	    
	    return $tabs;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_WorkspaceTab[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, workspace_page_id, pos ".
			"FROM workspace_tab ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 * @param integer $id
	 * @return Model_WorkspaceTab
	 */
	static function get($id) {
		$objects = self::getAll();
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	static function getByPage($page_id) {
		$all_tabs = self::getAll();
		$tabs = array();
		
		foreach($all_tabs as $tab_id => $tab) { /* @var $tab Model_WorkspaceTab */
			if($tab->workspace_page_id == $page_id)
				$tabs[$tab_id] = $tab;
		}

		return $tabs;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_WorkspaceTab[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_WorkspaceTab();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->workspace_page_id = $row['workspace_page_id'];
			$object->pos = $row['pos'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM workspace_list WHERE workspace_tab_id IN (%s)", $ids_list));
		
		$db->Execute(sprintf("DELETE FROM workspace_tab WHERE id IN (%s)", $ids_list));
		
		self::clearCache();
		
		return true;
	}
	
	static function deleteByPage($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		// Find tab IDs by given page IDs
		$rows = $db->GetArray(sprintf("SELECT id FROM workspace_tab WHERE workspace_page_id IN (%s)", $ids_list));

		// Loop tab IDs and delete
		if(is_array($rows))
		foreach($rows as $row)
			self::delete($row['id']);
		
		return true;		
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Workspace::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"workspace_tab.id as %s, ".
			"workspace_tab.name as %s, ".
			"workspace_tab.workspace_page_id as %s, ".
			"workspace_tab.pos as %s ",
				SearchFields_WorkspaceTab::ID,
				SearchFields_WorkspaceTab::NAME,
				SearchFields_WorkspaceTab::WORKSPACE_PAGE_ID,
				SearchFields_WorkspaceTab::POS
			);
			
		$join_sql = "FROM workspace_tab ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		return array(
			'primary_table' => 'workspace_tab',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => false,
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
			($has_multiple_values ? 'GROUP BY workspace_tab.id ' : '').
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
			$object_id = intval($row[SearchFields_WorkspaceTab::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT workspace_tab.id) " : "SELECT COUNT(workspace_tab.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}
	
	public static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$sql = "DELETE QUICK workspace_list FROM workspace_list LEFT JOIN workspace_tab ON (workspace_list.workspace_tab_id = workspace_tab.id) WHERE workspace_tab.id IS NULL";
		$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' workspace_list records.');
	}

	static function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::_CACHE_ALL);
	}
	
};

class SearchFields_WorkspacePage implements IDevblocksSearchFields {
	const ID = 'w_id';
	const NAME = 'w_name';
	const OWNER_CONTEXT = 'w_owner_context';
	const OWNER_CONTEXT_ID = 'w_owner_context_id';
	
	const VIRTUAL_OWNER = '*_owner';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'workspace_page', 'id', $translate->_('common.id')),
			self::NAME => new DevblocksSearchField(self::NAME, 'workspace_page', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'workspace_page', 'owner_context', null),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'workspace_page', 'owner_context_id', null),
				
			self::VIRTUAL_OWNER => new DevblocksSearchField(self::VIRTUAL_OWNER, '*', 'owner', $translate->_('common.owner'), 'WS'),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;		
	}
};

class SearchFields_WorkspaceTab implements IDevblocksSearchFields {
	const ID = 'w_id';
	const NAME = 'w_name';
	const WORKSPACE_PAGE_ID = 'w_workspace_page_Id';
	const POS = 'w_pos';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'workspace_tab', 'id', $translate->_('common.id')),
			self::NAME => new DevblocksSearchField(self::NAME, 'workspace_tab', 'name', $translate->_('common.name')),
			self::WORKSPACE_PAGE_ID => new DevblocksSearchField(self::WORKSPACE_PAGE_ID, 'workspace_tab', 'workspace_page_id', null),
			self::POS => new DevblocksSearchField(self::POS, 'workspace_tab', 'pos', null),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;		
	}
};

class Model_WorkspacePage {
	public $id;
	public $name;
	public $owner_context;
	public $owner_context_id;
	
	function getTabs(Model_Worker $as_worker=null) {
		$tabs = DAO_WorkspaceTab::getByPage($this->id);
		
		// Order by given worker prefs
		if(!empty($as_worker)) {
			$available_tabs = $tabs;
			$tabs = array();
			
			// Do we have prefs?
			@$json = DAO_WorkerPref::get($as_worker->id, 'page_tabs_' . $this->id . '_json', null);
			$tab_ids = json_decode($json);
			
			if(!is_array($tab_ids) || empty($json))
				return $available_tabs;
			
			// Sort tabs by the worker's preferences
			foreach($tab_ids as $tab_id) {
				if(isset($available_tabs[$tab_id])) {
					$tabs[$tab_id] = $available_tabs[$tab_id];
					unset($available_tabs[$tab_id]);
				}
			}

			// Add anything left to the end that the worker didn't explicitly sort
			if(!empty($available_tabs))
				$tabs += $available_tabs;
		}
		
		return $tabs;
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
		//if($worker->is_superuser)
		//	return true;
	
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

class Model_WorkspaceTab {
	public $id;
	public $name;
	public $workspace_page_id;
	public $pos;
	
	function getWorklists() {
		return DAO_WorkspaceList::getWhere(sprintf("%s = %d",
			DAO_WorkspaceList::WORKSPACE_TAB_ID,
			$this->id
		));
	}
};

class DAO_WorkspaceList extends DevblocksORMHelper {
	const ID = 'id';
	const WORKSPACE_TAB_ID = 'workspace_tab_id';
	const CONTEXT = 'context';
	const LIST_VIEW = 'list_view';
	const LIST_POS = 'list_pos';
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($fields))
			return NULL;
		
		$sql = sprintf("INSERT INTO workspace_list () ".
			"VALUES ()"
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		$id = $db->LastInsertId();

		self::update($id, $fields);
		
		return $id;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_WorkspaceList
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
	 * Enter description here...
	 *
	 * @param string $where
	 * @return Model_WorkspaceList[]
	 */
	static function getWhere($where) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, workspace_tab_id, context, list_view, list_pos ".
			"FROM workspace_list ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : " ").
			"ORDER BY list_pos ASC";
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_WorkspaceList();
			$object->id = intval($row['id']);
			$object->workspace_tab_id = intval($row['workspace_tab_id']);
			$object->context = $row['context'];
			$object->list_pos = intval($row['list_pos']);
			
			$list_view = $row['list_view'];
			if(!empty($list_view)) {
				@$object->list_view = unserialize($list_view);
			}
			
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'workspace_list', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('workspace_list', $fields, $where);
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		if(empty($ids))
			return;
		
		$db = DevblocksPlatform::getDatabaseService();
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE QUICK FROM workspace_list WHERE id IN (%s)", $ids_list)) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		
		// Delete worker view prefs
		foreach($ids as $id) {
			$db->Execute(sprintf("DELETE FROM worker_view_model WHERE view_id = 'cust_%d'", $id));
		}
	}
};

class Model_WorkspaceList {
	public $id = 0;
	public $workspace_tab_id = 0;
	public $context = '';
	public $list_view = '';
	public $list_pos = 0;
};

class Model_WorkspaceListView {
	public $title = 'New List';
	public $columns = array();
	public $num_rows = 10;
	public $params = array();
	public $params_required = array();
	public $sort_by = null;
	public $sort_asc = 1;
};

class View_WorkspacePage extends C4_AbstractView {
	const DEFAULT_ID = 'workspace_page';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();

		$this->id = self::DEFAULT_ID;
		// [TODO] Name the worklist view
		$this->name = $translate->_('Pages');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_WorkspacePage::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_WorkspacePage::NAME,
			SearchFields_WorkspacePage::VIRTUAL_OWNER,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_WorkspacePage::ID,
			SearchFields_WorkspacePage::OWNER_CONTEXT,
			SearchFields_WorkspacePage::OWNER_CONTEXT_ID,
		));

		$this->addParamsHidden(array(
			SearchFields_WorkspacePage::ID,
			SearchFields_WorkspacePage::OWNER_CONTEXT,
			SearchFields_WorkspacePage::OWNER_CONTEXT_ID,
		));

		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_WorkspacePage::search(
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
		return $this->_doGetDataSample('DAO_WorkspacePage', $size);
	}

	function render() {
		$this->_sanitize();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$tpl->display('devblocks:cerberusweb.core::pages/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_WorkspacePage::NAME:
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
				
			case SearchFields_WorkspacePage::VIRTUAL_OWNER:
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				$roles = DAO_WorkerRole::getAll();
				$tpl->assign('roles', $roles);
				
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_owner.tpl');
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		$translate = DevblocksPlatform::getTranslationService();
		
		switch($key) {
			case SearchFields_WorkspacePage::VIRTUAL_OWNER:
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
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_WorkspacePage::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_WorkspacePage::NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;

			case 'placeholder_number':
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case 'placeholder_date':
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_WorkspacePage::VIRTUAL_OWNER:
				@$owner_contexts = DevblocksPlatform::importGPC($_REQUEST['owner_context'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$owner_contexts);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};
