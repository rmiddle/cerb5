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

abstract class C4_AbstractView {
	public $id = 0;
	public $is_ephemeral = 0;
	public $name = "";
	
	public $view_columns = array();
	private $_columnsHidden = array();
	
	private $_paramsEditable = array();
	private $_paramsDefault = array();
	private $_paramsRequired = array();
	private $_paramsHidden = array();
	
	public $renderPage = 0;
	public $renderLimit = 10;
	public $renderTotal = true;
	public $renderSortBy = '';
	public $renderSortAsc = 1;

	public $renderFilters = null;
	public $renderSubtotals = null;
	
	public $renderTemplate = null;

	abstract function getData();
	function getDataSample($size) {}
	
	private $_placeholderLabels = array();
	private $_placeholderValues = array();
	
	protected function _doGetDataSample($dao_class, $size, $id_col = 'id') {
		$db = DevblocksPlatform::getDatabaseService();

		if(!method_exists($dao_class,'getSearchQueryComponents'))
			return array();
		
		$query_parts = call_user_func_array(
			array($dao_class,'getSearchQueryComponents'),
			array(
				$this->view_columns,
				$this->getParams(),
				$this->renderSortBy,
				$this->renderSortAsc
			)
		);
		
		$select_sql = sprintf("SELECT %s.%s ", 
			$query_parts['primary_table'],
			$id_col
		);
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = sprintf("ORDER BY RAND() LIMIT %d ", $size);
		
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? sprintf("GROUP BY %s.id ", $query_parts['primary_table']) : '').
			$sort_sql;

		$rs = $db->Execute($sql);
		
		$objects = array();
		while($row = mysql_fetch_row($rs)) {
			$objects[] = $row[0];
		}		
		
		return $objects;		
	}

	function getColumnsAvailable() {
		$columns = $this->getFields();
		
		foreach($this->getColumnsHidden() as $col)
			unset($columns[$col]);
			
		return $columns;
	}
	
	// Columns Hidden

	function getColumnsHidden() {
		$columnsHidden = $this->_columnsHidden;
		
		if(!is_array($columnsHidden))
			$columnsHidden = array();
			
		return $columnsHidden;
	}
	
	function addColumnsHidden($columnsToHide, $replace=false) {
		if($replace)
			$this->_columnsHidden = $columnsToHide;
		else
			$this->_columnsHidden = array_unique(array_merge($this->getColumnsHidden(), $columnsToHide));
	}
	
	// Params Editable
	
	function getParamsAvailable() {
		$params = $this->getFields();
		
		if(is_array($this->_paramsHidden))
		foreach($this->_paramsHidden as $param)
			unset($params[$param]);
		
		return $params;
	}
	
	function getParams($parse_placeholders=true) {
		$params = $this->_paramsEditable;
		
		// Required should supersede editable
		if(is_array($this->_paramsRequired))
		foreach($this->_paramsRequired as $key => $param)
			$params['req_'.$key] = $param;
		
		if($parse_placeholders) {
			// Translate snippets in filters
			array_walk_recursive(
				$params,
				array('C4_AbstractView', '_translatePlaceholders'),
				array(
					'placeholder_values' => $this->getPlaceholderValues(),
				)
			);
		}
		
		return $params;
	}
	
	function getEditableParams() {
		return $this->_paramsEditable;
	}
	
	function addParam($param, $key=null) {
		if(empty($key) && $param instanceof DevblocksSearchCriteria)
			$key = $param->field;
		
		$this->_paramsEditable[$key] = $param;
	}
	
	function addParams($params, $replace=false) {
		if($replace)
			$this->removeAllParams();
		
		if(is_array($params))
		foreach($params as $key => $param) {
			$key = (!is_string($key) && is_object($param)) ? $param->field : $key;
			$this->addParam($param, $key);	
		}	
	}
	
	function removeParam($key) {
		if(isset($this->_paramsEditable[$key]))
			unset($this->_paramsEditable[$key]);
	}
	
	function removeAllParams() {
		$this->_paramsEditable = array();
	}
	
	// Params Default
	
	function addParamsDefault($params, $replace=false) {
		if($replace)
			$this->_paramsDefault = $params;
		else
			$this->_paramsDefault = array_merge($this->_paramsDefault, $params);
	}
	
	function getParamsDefault() {
		return $this->_paramsDefault;
	}
	
	// Params Required
	
	function addParamsRequired($params, $replace=false) {
		if($replace)
			$this->_paramsRequired = $params;
		else
			$this->_paramsRequired = array_merge($this->_paramsRequired, $params);
	}
	
	function getParamsRequired() {
		return $this->_paramsRequired;
	}
	
	// Params Hidden
	
	function addParamsHidden($params, $replace=false) {
		if($replace)
			$this->_paramsHidden = $params;
		else
			$this->_paramsHidden = array_unique(array_merge($this->_paramsHidden, $params));
	}
	
	function getParamsHidden() {
		return $this->_paramsHidden;
	}
	
	// Placeholders
	
	function setPlaceholderLabels($labels) {
		if(is_array($labels))
			$this->_placeholderLabels = $labels;
	}
	
	function getPlaceholderLabels() {
		return $this->_placeholderLabels;
	}
	
	function setPlaceholderValues($values) {
		if(is_array($values))
			$this->_placeholderValues = $values;
	}
	
	function getPlaceholderValues() {
		return $this->_placeholderValues;
	}
	
	protected static function _translatePlaceholders(&$param, $key, $args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;

		$param_key = $param->field;
		settype($param_key, 'string');

		$tpl_builder = DevblocksPlatform::getTemplateBuilder();

		if(is_string($param->value)) {
			if(false !== ($value = $tpl_builder->build($param->value, $args['placeholder_values']))) {
				$param->value = $value;
			}
			
		} elseif(is_array($param->value)) {
			foreach($param->value as $k => $v) {
				if(!is_string($v))
					continue;
				
				if(false !== ($value = $tpl_builder->build($v, $args['placeholder_values']))) {
					$param->value[$k] = $value;
				}
			}			
		}
	}		
	
	// Render
	
	function render() {
		echo ' '; // Expect Override
	}

	function renderCriteria($field) {
		echo ' '; // Expect Override
	}

	protected function _renderCriteriaCustomField($tpl, $field_id) {
		$field = DAO_CustomField::get($field_id);
		
		switch($field->type) {
			case Model_CustomField::TYPE_DROPDOWN:
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
				$tpl->assign('field', $field);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__cfield_picklist.tpl');
				break;
			case Model_CustomField::TYPE_CHECKBOX:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__cfield_checkbox.tpl');
				break;
			case Model_CustomField::TYPE_DATE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
			case Model_CustomField::TYPE_NUMBER:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case Model_CustomField::TYPE_WORKER:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
			default:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
		}
	}
	
	protected function _renderCriteriaParamBoolean($param) {
		$translate = DevblocksPlatform::getTranslationService();
		
		$strings = array();
		
		$values = is_array($param->value) ? $param->value : array($param->value);
		
		foreach($values as $v) {
			$strings[] = sprintf("<b>%s</b>",
				(!empty($v) ? $translate->_('common.yes') : $translate->_('common.no')) 
			);
		}
		
		echo implode(' or ', $strings);
	}
	
	protected function _renderCriteriaParamWorker($param) {
		$workers = DAO_Worker::getAll();
		$strings = array();
		
		foreach($param->value as $worker_id) {
			if(isset($workers[$worker_id]))
				$strings[] = '<b>'.$workers[$worker_id]->getName().'</b>';
			else {
				$strings[] = '<b>'.$worker_id.'</b>';
			}
		}
		
		if(empty($param->value)) {
			switch($param->operator) {
				case DevblocksSearchCriteria::OPER_IN:
				case DevblocksSearchCriteria::OPER_IN_OR_NULL:
				case DevblocksSearchCriteria::OPER_NIN_OR_NULL:
					$param->operator = DevblocksSearchCriteria::OPER_IS_NULL;
					break;
				case DevblocksSearchCriteria::OPER_NIN:
					$param->operator = DevblocksSearchCriteria::OPER_IS_NOT_NULL;
					break;
			}
		}
		
		$list_of_strings = implode(' or ', $strings);
		
		if(count($strings) > 2) {
			$list_of_strings = sprintf("any of <abbr style='font-weight:bold;' title='%s'>(%d people)</abbr>",
				htmlentities(strip_tags($list_of_strings)),
				count($strings)
			);
		}
		
		echo sprintf("%s", $list_of_strings);
	}	
	
	protected function _renderVirtualWatchers($param) {
		$workers = DAO_Worker::getAll();
		$strings = array();
		
		foreach($param->value as $worker_id) {
			if(isset($workers[$worker_id]))
				$strings[] = '<b>'.$workers[$worker_id]->getName().'</b>';
			else {
				$strings[] = '<b>'.$worker_id.'</b>';
			}
		}
		
		if(empty($param->value)) {
			switch($param->operator) {
				case DevblocksSearchCriteria::OPER_IN:
				case DevblocksSearchCriteria::OPER_IN_OR_NULL:
				case DevblocksSearchCriteria::OPER_NIN_OR_NULL:
					$param->operator = DevblocksSearchCriteria::OPER_IS_NULL;
					break;
				case DevblocksSearchCriteria::OPER_NIN:
					$param->operator = DevblocksSearchCriteria::OPER_IS_NOT_NULL;
					break;
			}
		}
		
		$list_of_strings = implode(' or ', $strings);
		
		if(count($strings) > 2) {
			$list_of_strings = sprintf("any of <abbr style='font-weight:bold;' title='%s'>(%d people)</abbr>",
				htmlentities(strip_tags($list_of_strings)),
				count($strings)
			);
		}
		
		switch($param->operator) {
			case DevblocksSearchCriteria::OPER_IS_NULL:
				echo "There are no <b>watchers</b>";
				break;
			case DevblocksSearchCriteria::OPER_IS_NOT_NULL:
				echo "There are <b>watchers</b>";
				break;
			case DevblocksSearchCriteria::OPER_IN:
				echo sprintf("Watcher is %s", $list_of_strings);
				break;
			case DevblocksSearchCriteria::OPER_IN_OR_NULL:
				echo sprintf("Watcher is blank or %s", $list_of_strings);
				break;
			case DevblocksSearchCriteria::OPER_NIN:
				echo sprintf("Watcher is not %s", $list_of_strings);
				break;
			case DevblocksSearchCriteria::OPER_NIN_OR_NULL:
				echo sprintf("Watcher is blank or not %s", $list_of_strings);
				break;
		}		
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $field
	 * @param string $oper
	 * @param string $value
	 * @abstract
	 */
	function doSetCriteria($field, $oper, $value) {
		// Expect Override
	}

	protected function _doSetCriteriaString($field, $oper, $value) {
		// force wildcards if none used on a LIKE
		if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
		&& false === (strpos($value,'*'))) {
			$value = $value.'*';
		}
		return new DevblocksSearchCriteria($field, $oper, $value);
	}
	
	protected function _doSetCriteriaDate($field, $oper) {
		@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','big bang');
		@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','now');

		if(is_null($from) || (!is_numeric($from) && @false === strtotime(str_replace('.','-',$from))))
			$from = 'big bang';
			
		if(is_null($to) || (!is_numeric($to) && @false === strtotime(str_replace('.','-',$to))))
			$to = 'now';
		
		return new DevblocksSearchCriteria($field,$oper,array($from,$to));
	}
	
	protected function _doSetCriteriaWorker($field, $oper) {
		@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
		
		switch($oper) {
			case DevblocksSearchCriteria::OPER_IN:
				if(empty($worker_ids)) {
					$oper = DevblocksSearchCriteria::OPER_EQ;
					$worker_ids = 0;
				}
				break;
			case DevblocksSearchCriteria::OPER_IN_OR_NULL:
				$oper = DevblocksSearchCriteria::OPER_IN;
				if(!in_array('0', $worker_ids))
					$worker_ids[] = '0';
				break;
			case DevblocksSearchCriteria::OPER_NIN:
				if(empty($worker_ids)) {
					$oper = DevblocksSearchCriteria::OPER_NEQ;
					$worker_ids = 0;
				}
				break;
			case 'not in and not null':
				$oper = DevblocksSearchCriteria::OPER_NIN;
				if(!in_array('0', $worker_ids))
					$worker_ids[] = '0';
				break;
		}
		
		return new DevblocksSearchCriteria($field, $oper, $worker_ids);
	}
	
	protected function _doSetCriteriaCustomField($token, $field_id) {
		$field = DAO_CustomField::get($field_id);
		@$oper = DevblocksPlatform::importGPC($_POST['oper'],'string','');
		@$value = DevblocksPlatform::importGPC($_POST['value'],'string','');
		
		$criteria = null;
		
		switch($field->type) {
			case Model_CustomField::TYPE_DROPDOWN:
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
				if(!empty($options)) {
					$criteria = new DevblocksSearchCriteria($token,$oper,$options);
				} else {
					$criteria = new DevblocksSearchCriteria($token,DevblocksSearchCriteria::OPER_IS_NULL);
				}
				break;
			case Model_CustomField::TYPE_CHECKBOX:
				$criteria = new DevblocksSearchCriteria($token,$oper,!empty($value) ? 1 : 0);
				break;
			case Model_CustomField::TYPE_NUMBER:
				$criteria = new DevblocksSearchCriteria($token,$oper,intval($value));
				break;
			case Model_CustomField::TYPE_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');
	
				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';
	
				$criteria = new DevblocksSearchCriteria($token,$oper,array($from,$to));
				break;
			case Model_CustomField::TYPE_WORKER:
				@$oper = DevblocksPlatform::importGPC($_REQUEST['oper'],'string','eq');
				@$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'],'array',array());
				
				if(empty($worker_ids)) {
					switch($oper) {
						case DevblocksSearchCriteria::OPER_IN:
							$oper = DevblocksSearchCriteria::OPER_IS_NULL;
							$worker_ids = null;
							break;
						case DevblocksSearchCriteria::OPER_NIN:
							$oper = DevblocksSearchCriteria::OPER_IS_NOT_NULL;
							$worker_ids = null;
							break;
					}
				}
				
				$criteria = new DevblocksSearchCriteria($token,$oper,$worker_ids);
				break;
			default: // TYPE_SINGLE_LINE || TYPE_MULTI_LINE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($token,$oper,$value);
				break;
		}
		
		return $criteria;
	}
	
	/**
	 * This method automatically fixes any cached strange options, like 
	 * deleted custom fields.
	 *
	 */
	protected function _sanitize() {
		$fields = $this->getColumnsAvailable();
		$custom_fields = DAO_CustomField::getAll();
		$needs_save = false;
		
		$params = $this->getParams();
		
		// Parameter sanity check
		if(is_array($params))
		foreach($params as $pidx => $null) {
			if(substr($pidx,0,3)!="cf_")
				continue;
				
			if(0 != ($cf_id = intval(substr($pidx,3)))) {
				// Make sure our custom fields still exist
				if(!isset($custom_fields[$cf_id])) {
					$this->removeParam($pidx);
					$needs_save = true;
				}
			}
		}
		unset($params);
		
		// View column sanity check
		if(is_array($this->view_columns))
		foreach($this->view_columns as $cidx => $c) {
			// Custom fields
			if(substr($c,0,3) == "cf_") {
				if(0 != ($cf_id = intval(substr($c,3)))) {
					// Make sure our custom fields still exist
					if(!isset($custom_fields[$cf_id])) {
						unset($this->view_columns[$cidx]);
						$needs_save = true;
					}
				}
			} else {
				// If the column no longer exists (rare but worth checking)
				if(!isset($fields[$c])) {
					unset($this->view_columns[$cidx]);
					$needs_save = true;
				}
			}
		}
		
		// Sort by sanity check
		if(substr($this->renderSortBy,0,3)=="cf_") {
			if(0 != ($cf_id = intval(substr($this->renderSortBy,3)))) {
				if(!isset($custom_fields[$cf_id])) {
					$this->renderSortBy = null;
					$needs_save = true;
				}
			}
    	}
    	
    	if($needs_save) {
    		C4_AbstractViewLoader::setView($this->id, $this);
    	}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$vals = $param->value;

		if(!is_array($vals))
			$vals = array($vals);
		
		// Do we need to do anything special on custom fields?
		if('cf_'==substr($field,0,3)) {
			$field_id = intval(substr($field,3));
			$custom_fields = DAO_CustomField::getAll();
			
			$translate = DevblocksPlatform::getTranslationService(); 
			
			switch($custom_fields[$field_id]->type) {
				case Model_CustomField::TYPE_CHECKBOX:
					foreach($vals as $idx => $val) {
						$vals[$idx] = !empty($val) ? $translate->_('common.yes') : $translate->_('common.no');
					}
					break;
					
				case Model_CustomField::TYPE_WORKER:
					$workers = DAO_worker::getAll();
					foreach($vals as $idx => $worker_id) {
						if(isset($workers[$worker_id]))
							$vals[$idx] = $workers[$worker_id]->getName(); 
					}
					break;
			}
		}
		
		// HTML escape
		if(is_array($vals))
		foreach($vals as $k => $v) {
			$vals[$k] = htmlspecialchars($v, ENT_QUOTES, LANG_CHARSET_CODE);
		}
		
		echo implode(' or ', $vals);
	}

	/**
	 * All the view's available fields
	 *
	 * @return array
	 */
	function getFields() {
		// Expect Override
		return array();
	}

	function doCustomize($columns, $num_rows=10) {
		$this->renderLimit = $num_rows;

		$viewColumns = array();
		foreach($columns as $col) {
			if(empty($col))
				continue;
			$viewColumns[] = $col;
		}

		$this->view_columns = $viewColumns;
	}

	function doSortBy($sortBy) {
		$iSortAsc = intval($this->renderSortAsc);

		// [JAS]: If clicking the same header, toggle asc/desc.
		if(0 == strcasecmp($sortBy,$this->renderSortBy)) {
			$iSortAsc = (0 == $iSortAsc) ? 1 : 0;
		} else { // [JAS]: If a new header, start with asc.
			$iSortAsc = 1;
		}

		$this->renderSortBy = $sortBy;
		$this->renderSortAsc = $iSortAsc;
	}

	function doPage($page) {
		$this->renderPage = $page;
	}

	function doRemoveCriteria($key) {
		$this->removeParam($key);
		$this->renderPage = 0;
	}

	function doResetCriteria() {
		$this->addParams($this->_paramsDefault, true);
		$this->renderPage = 0;
	}
	
	function getPresets() {
		if(null == ($active_worker = CerberusApplication::getActiveWorker()))
			return;
		
		// Presets
		// [TODO] Cache?
		return DAO_ViewFiltersPreset::getWhere(
			sprintf("%s = %s AND %s = %d",
				DAO_ViewFiltersPreset::VIEW_CLASS,
				C4_ORMHelper::qstr(get_class($this)),
				DAO_ViewFiltersPreset::WORKER_ID,
				$active_worker->id
			)
		);
	}
	
	function renderSubtotals() {
		if(!$this instanceof IAbstractView_Subtotals)
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $this->id);
		$tpl->assign('view', $this);

		$fields = $this->getSubtotalFields();
		$tpl->assign('subtotal_fields', $fields);
		
		$counts = $this->getSubtotalCounts($this->renderSubtotals);
		$tpl->assign('subtotal_counts', $counts);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/sidebar.tpl');
	}
	
	protected function _canSubtotalCustomField($field_key) {
		$custom_fields = DAO_CustomField::getAll();
		
		if('cf_' != substr($field_key,0,3))
			return false;
		
		$cfield_id = substr($field_key,3);
		
		if(!isset($custom_fields[$cfield_id]))
			return false;
			
		$cfield = $custom_fields[$cfield_id]; /* @var $cfield Model_CustomField */

		$pass = false;
		
		switch($cfield->type) {
			case Model_CustomField::TYPE_CHECKBOX:
			case Model_CustomField::TYPE_DROPDOWN:
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_WORKER:
				$pass = true;
				break;
		}

		return $pass;
	}
	
	protected function _getSubtotalDataForColumn($dao_class, $field_key) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$fields = $this->getFields();
		$columns = $this->view_columns;
		$params = $this->getParams();
		
		if(!isset($params[$field_key])) {
			$new_params = array(
				$field_key => new DevblocksSearchCriteria($field_key, DevblocksSearchCriteria::OPER_TRUE),
			);
			$params = array_merge($new_params, $params);
		} else {
			switch($params[$field_key]->operator) {
				case DevblocksSearchCriteria::OPER_EQ:
				case DevblocksSearchCriteria::OPER_IS_NULL:
					$params[$field_key] = new DevblocksSearchCriteria($field_key, DevblocksSearchCriteria::OPER_TRUE);
					break;
				case DevblocksSearchCriteria::OPER_IN:
					if(is_array($params[$field_key]->value) && count($params[$field_key]->value) < 2)
						$params[$field_key] = new DevblocksSearchCriteria($field_key, DevblocksSearchCriteria::OPER_TRUE);
					break;
			}
		}
		
		if(!method_exists($dao_class,'getSearchQueryComponents'))
			return array();
		
		$query_parts = call_user_func_array(
			array($dao_class,'getSearchQueryComponents'),
			array(
				$columns,
				$params,
				$this->renderSortBy,
				$this->renderSortAsc
			)
		);
		
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];				
		
		$sql = sprintf("SELECT %s.%s as label, count(*) as hits ", //SQL_CALC_FOUND_ROWS
				$fields[$field_key]->db_table,
				$fields[$field_key]->db_column
			).
			$join_sql.
			$where_sql. 
			"GROUP BY label ".
			"ORDER BY hits DESC ".
			"LIMIT 0,20 "
		;
		
		$results = $db->GetArray($sql);
//		$total = count($results);
//		$total = ($total < 20) ? $total : $db->GetOne("SELECT FOUND_ROWS()");

		return $results;
	}
	
	protected function _getSubtotalCountForStringColumn($dao_class, $field_key, $label_map=array(), $value_oper='=', $value_key='value') {
		$counts = array();
		$results = $this->_getSubtotalDataForColumn($dao_class, $field_key);
		
		foreach($results as $result) {
			$label = $result['label'];
			$hits = $result['hits'];

			if(isset($label_map[$result['label']]))
				$label = $label_map[$result['label']];
			
			// Null strings
			if(empty($label)) {
				$label = '(none)';
				if(!isset($counts[$label]))
					$counts[$label] = array(
						'hits' => $hits,
						'label' => $label,
						'filter' => 
							array(
								'field' => $field_key,
								'oper' => DevblocksSearchCriteria::OPER_IN_OR_NULL,
								'values' => null,
							),
						'children' => array()
					);
				
			// Anything else
			} else {
				if(!isset($counts[$label]))
					$counts[$label] = array(
						'hits' => $hits,
						'label' => $label,
						'filter' => 
							array(
								'field' => $field_key,
								'oper' => $value_oper,
								'values' => array($value_key => $result['label']),
							),
						'children' => array()
					);
				
			}
			
		}
		
		return $counts;
	}
	
	protected function _getSubtotalCountForBooleanColumn($dao_class, $field_key) {
		$translate = DevblocksPlatform::getTranslationService();
		
		$counts = array();
		$results = $this->_getSubtotalDataForColumn($dao_class, $field_key);
		
		foreach($results as $result) {
			$label = $result['label'];
			$hits = $result['hits'];

			if(!empty($label)) {
				$label = $translate->_('common.yes');
				$value = 1;
			} else {
				$label = $translate->_('common.no');
				$value = 0;
			}
			
			if(!isset($counts[$label]))
				$counts[$label] = array(
					'hits' => $hits,
					'label' => $label,
					'filter' => 
						array(
							'field' => $field_key,
							'oper' => '=',
							'values' => array('bool' => $value),
						),
					'children' => array()
				);
		}
		
		return $counts;
	}
	
	protected function _getSubtotalDataForWatcherColumn($dao_class, $field_key) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$fields = $this->getFields();
		$columns = $this->view_columns;
		$params = $this->getParams();
		
		if(!isset($params[$field_key])) {
			$new_params = array(
				$field_key => new DevblocksSearchCriteria($field_key, DevblocksSearchCriteria::OPER_TRUE),
			);
			$params = array_merge($new_params, $params);
		} else {
			switch($params[$field_key]->operator) {
				case DevblocksSearchCriteria::OPER_EQ:
				case DevblocksSearchCriteria::OPER_IS_NULL:
					$params[$field_key] = new DevblocksSearchCriteria($field_key, DevblocksSearchCriteria::OPER_TRUE);
					break;
				case DevblocksSearchCriteria::OPER_IN:
					if(is_array($params[$field_key]->value) && count($params[$field_key]->value) < 2)
						$params[$field_key] = new DevblocksSearchCriteria($field_key, DevblocksSearchCriteria::OPER_TRUE);
					break;
			}
		}
		
		if(!method_exists($dao_class,'getSearchQueryComponents'))
			return array();
		
		$query_parts = call_user_func_array(
			array($dao_class,'getSearchQueryComponents'),
			array(
				$columns,
				$params,
				$this->renderSortBy,
				$this->renderSortAsc
			)
		);
		
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];				
		
		$sql = "SELECT context_watcher.to_context_id as watcher_id, count(*) as hits ". //SQL_CALC_FOUND_ROWS
			$join_sql.
			$where_sql. 
			"GROUP BY watcher_id ".
			"ORDER BY hits DESC ".
			"LIMIT 0,20 "
		;
		
		$results = $db->GetArray($sql);

		return $results;
	}	
	
	protected function _getSubtotalCountForWatcherColumn($dao_class, $field_key) {
		$workers = DAO_Worker::getAll();
		
		$counts = array();
		$results = $this->_getSubtotalDataForWatcherColumn($dao_class, $field_key);
		
		foreach($results as $result) {
			$watcher_id = $result['watcher_id'];
			$hits = $result['hits'];
			$label = '';

			if(isset($workers[$watcher_id])) {
				$label = $workers[$watcher_id]->getName();
				$oper = DevblocksSearchCriteria::OPER_IN;
				$values = array('worker_id[]' => $watcher_id);
			} else {
				$label = '(nobody)';
				$oper = DevblocksSearchCriteria::OPER_IS_NULL;
				$values = array('');
			}
			
			if(!isset($counts[$label]))
				$counts[$label] = array(
					'hits' => $hits,
					'label' => $label,
					'filter' => 
						array(
							'field' => $field_key,
							'oper' => $oper,
							'values' => $values,
						),
					'children' => array()
				);
		}
		
		return $counts;
	}	
	
	protected function _getSubtotalCountForCustomColumn($dao_class, $field_key, $primary_key) {
		$db = DevblocksPlatform::getDatabaseService();
		$translate = DevblocksPlatform::getTranslationService();
		
		$counts = array();
		$fields = $this->getFields();
		$custom_fields = DAO_CustomField::getAll();
		$columns = $this->view_columns;
		$params = $this->getParams();

		$field_id = substr($field_key,3);

		// If the custom field id is invalid, abort.
		if(!isset($custom_fields[$field_id]))
			return array();

		// Load the custom field
		$cfield = $custom_fields[$field_id];

		// Always join the custom field so we have quick access to values
		if(!isset($params[$field_key])) {
			$add_param = array(
				$field_key => new DevblocksSearchCriteria($field_key,DevblocksSearchCriteria::OPER_TRUE),
			);
			$params = array_merge($params, $add_param); 
		} else {
			switch($params[$field_key]->operator) {
				case DevblocksSearchCriteria::OPER_EQ:
				case DevblocksSearchCriteria::OPER_IS_NULL:
					$params[$field_key] = new DevblocksSearchCriteria($field_key, DevblocksSearchCriteria::OPER_TRUE);
					break;
				case DevblocksSearchCriteria::OPER_IN:
					if(is_array($params[$field_key]->value) && count($params[$field_key]->value) < 2)
						$params[$field_key] = new DevblocksSearchCriteria($field_key, DevblocksSearchCriteria::OPER_TRUE);
					break;
			}
		}
		
		// ... and that the DAO object is valid
		if(!method_exists($dao_class,'getSearchQueryComponents'))
			return array();

		// Construct the shared query components
		$query_parts = call_user_func_array(
			array($dao_class,'getSearchQueryComponents'),
			array(
				$columns,
				$params,
				$this->renderSortBy,
				$this->renderSortAsc
			)
		);
		
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];				
			
		switch($cfield->type) {
			
			case Model_CustomField::TYPE_CHECKBOX:
				$select = sprintf(
					"SELECT COUNT(*) AS hits, %s.field_value AS %s ",
					$field_key,
					$field_key
				);
				
				$sql =
					$select. 
					$join_sql.
					$where_sql.
					sprintf(
						"GROUP BY %s ",
						$field_key
					).
					"ORDER BY hits DESC "
				;
		
				$results = $db->GetArray($sql);
		
				foreach($results as $result) {
					$label = '';
					$oper = DevblocksSearchCriteria::OPER_EQ;
					$values = null;
					
					switch($result[$field_key]) {
						case '':
							$label = '(no data)';
							$oper = DevblocksSearchCriteria::OPER_IS_NULL;
							break;
						case '0':
							$label = $translate->_('common.no');
							$values = array('value' => $result[$field_key]);
							break;
						case '1':
							$label = $translate->_('common.yes');
							$values = array('value' => $result[$field_key]);
							break;
					}
					
					$counts[$result[$field_key]] = array(
						'hits' => $result['hits'],
						'label' => $label,
						'filter' =>
							array(
								'field' => $field_key,
								'oper' => $oper,
								'values' => $values,
							),
					);
				}
				break;
				
			case Model_CustomField::TYPE_DROPDOWN:
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
			case Model_CustomField::TYPE_SINGLE_LINE:
				$select = sprintf(
					"SELECT COUNT(*) AS hits, %s.field_value AS %s ", //SQL_CALC_FOUND_ROWS
					$field_key,
					$field_key
				);
				
				$sql = 
					$select.
					$join_sql.
					$where_sql.
					sprintf(
						"GROUP BY %s ",
						$field_key
					).
					"ORDER BY hits DESC ".
					"LIMIT 20 "
				;
				
				$results = $db->GetArray($sql);
//				$total = count($results);
//				$total = ($total < 20) ? $total : $db->GetOne("SELECT FOUND_ROWS()");
				
				foreach($results as $result) {
					$label = '';
					$oper = DevblocksSearchCriteria::OPER_IN;
					$values = '';

					if(!empty($result[$field_key])) {
						$label = $result[$field_key];
						switch($cfield->type) {
							case Model_CustomField::TYPE_SINGLE_LINE:
								$oper = DevblocksSearchCriteria::OPER_EQ;
								$values = array('value' => $label);
								break;
							case Model_CustomField::TYPE_DROPDOWN:
							case Model_CustomField::TYPE_MULTI_CHECKBOX:
								$oper = DevblocksSearchCriteria::OPER_IN;
								$values = array('options[]' => $label);
								break;
						}
					}
					
					if(empty($label)) {
						$label = '(no data)';
						$oper = DevblocksSearchCriteria::OPER_EQ_OR_NULL;
						$values = array('value' => '');
					}
					
					$counts[$result[$field_key]] = array(
						'hits' => $result['hits'],
						'label' => $label,
						'filter' =>
							array(
								'field' => $field_key,
								'oper' => $oper,
								'values' => $values,
							),
					);
				}				
				break;
				
			case Model_CustomField::TYPE_WORKER:
				$workers = DAO_Worker::getAll();
				
				$sql = 
					sprintf(
						"SELECT COUNT(*) AS hits, (SELECT field_value FROM custom_field_numbervalue WHERE %s=context_id AND field_id=%d LIMIT 1) AS %s ", //SQL_CALC_FOUND_ROWS
						$primary_key,
						$field_id,
						$field_key
					).
					$join_sql.
					$where_sql.
					sprintf(
						"GROUP BY %s ",
						$field_key
					).
					"ORDER BY hits DESC ".
					"LIMIT 20 "
				;
				
				$results = $db->GetArray($sql);
//				$total = count($results);
//				$total = ($total < 20) ? $total : $db->GetOne("SELECT FOUND_ROWS()");
		
				foreach($results as $result) {
					$label = '';
					$oper = DevblocksSearchCriteria::OPER_EQ;
					$values = '';

					if(!empty($result[$field_key])) {
						$worker_id = $result[$field_key];
						if(isset($workers[$worker_id])) {
							$label = $workers[$worker_id]->getName();
							$oper = DevblocksSearchCriteria::OPER_IN;
							$values = array('worker_id[]' => $worker_id);
						}
					}
					
					if(empty($label)) {
						$label = '(nobody)';
						$oper = DevblocksSearchCriteria::OPER_IS_NULL;
						$values = '';
					}
					
					$counts[$result[$field_key]] = array(
						'hits' => $result['hits'],
						'label' => $label,
						'filter' =>
							array(
								'field' => $field_key,
								'oper' => $oper,
								'values' => $values,
							),
					);
				}				
				break;
				
		}
		
		return $counts;
	}
	
	public static function _doBulkSetCustomFields($context,$custom_fields, $ids) {
		$fields = DAO_CustomField::getAll();
		
		if(!empty($custom_fields))
		foreach($custom_fields as $cf_id => $params) {
			if(!is_array($params) || !isset($params['value']))
				continue;
				
			$cf_val = $params['value'];
			
			// Data massaging
			switch($fields[$cf_id]->type) {
				case Model_CustomField::TYPE_DATE:
					$cf_val = intval(@strtotime($cf_val));
					break;
				case Model_CustomField::TYPE_CHECKBOX:
				case Model_CustomField::TYPE_NUMBER:
					$cf_val = (0==strlen($cf_val)) ? '' : intval($cf_val);
					break;
			}

			// If multi-selection types, handle delta changes
			if(Model_CustomField::TYPE_MULTI_CHECKBOX==$fields[$cf_id]->type) {
				if(is_array($cf_val))
				foreach($cf_val as $val) {
					$op = substr($val,0,1);
					$val = substr($val,1);
				
					if(is_array($ids))
					foreach($ids as $id) {
						if($op=='+')
							DAO_CustomFieldValue::setFieldValue($context,$id,$cf_id,$val,true);
						elseif($op=='-')
							DAO_CustomFieldValue::unsetFieldValue($context,$id,$cf_id,$val);
					}
				}
					
			// Otherwise, set/unset as a single field
			} else {
				if(is_array($ids))
				foreach($ids as $id) {
					if(0 != strlen($cf_val))
						DAO_CustomFieldValue::setFieldValue($context,$id,$cf_id,$cf_val);
					else
						DAO_CustomFieldValue::unsetFieldValue($context,$id,$cf_id);
				}
			}
		}
	}
};

interface IAbstractView_Subtotals {
	function getSubtotalCounts($column);
	function getSubtotalFields();
};

/**
 * Used to persist a C4_AbstractView instance and not be encumbered by
 * classloading issues (out of the session) from plugins that might have
 * concrete AbstractView implementations.
 */
class C4_AbstractViewModel {
	public $class_name = '';

	public $id = '';
	public $name = "";
	public $is_ephemeral = 0;
	
	public $view_columns = array();
	public $columnsHidden = array();
	
	public $paramsEditable = array();
	public $paramsDefault = array();
	public $paramsRequired = array();
	public $paramsHidden = array();

	public $renderPage = 0;
	public $renderLimit = 10;
	public $renderTotal = true;
	public $renderSortBy = '';
	public $renderSortAsc = 1;
	
	public $renderFilters = null;
	public $renderSubtotals = null;
	
	public $renderTemplate = null;
	
	public $placeholderLabels = array();
	public $placeholderValues = array();
};

/**
 * This is essentially an AbstractView Factory
 */
class C4_AbstractViewLoader {
	/**
	 * Enter description here...
	 *
	 * @param string $class C4_AbstractView
	 * @param string $view_label ID
	 * @return C4_AbstractView or null
	 */
	static function getView($view_id, C4_AbstractViewModel $defaults=null) {
		$worker_id = 0;
		
		if(null !== ($active_worker = CerberusApplication::getActiveWorker()))
			$worker_id = $active_worker->id; 

		// Check if we've ever persisted this view
		if(false !== ($model = DAO_WorkerViewModel::getView($worker_id, $view_id))) {
			return self::unserializeAbstractView($model);
			
		} elseif(!empty($defaults) && $defaults instanceof C4_AbstractViewModel) {
			// Load defaults if they were provided
			if(null != ($view = self::unserializeAbstractView($defaults)))  {
				self::setView($view_id, $view);
				return $view;
			}
		}
		
		return NULL;
	}

	/**
	 * Enter description here...
	 *
	 * @param string $class C4_AbstractView
	 * @param string $view_label ID
	 * @param C4_AbstractView $view
	 */
	static function setView($view_id, C4_AbstractView $view) {
		$worker_id = 0;
		
		if(null !== ($active_worker = CerberusApplication::getActiveWorker()))
			$worker_id = $active_worker->id; 

		$model = self::serializeAbstractView($view);
		DAO_WorkerViewModel::setView($worker_id, $view_id, $model);
	}

	static function deleteView($view_id, $worker_id=null) {
		$worker_id = 0;
		
		if(null !== ($active_worker = CerberusApplication::getActiveWorker()))
			$worker_id = $active_worker->id; 

		DAO_WorkerViewModel::deleteView($worker_id, $view_id);
	}
	
	static function serializeAbstractView($view) {
		if(!$view instanceof C4_AbstractView)
			return NULL;

		$model = new C4_AbstractViewModel();
			
		$model->class_name = get_class($view);

		$model->id = $view->id;
		$model->is_ephemeral = $view->is_ephemeral;
		$model->name = $view->name;
		
		$model->view_columns = $view->view_columns;
		$model->columnsHidden = $view->getColumnsHidden();
		
		$model->paramsEditable = $view->getEditableParams();
		$model->paramsDefault = $view->getParamsDefault();
		$model->paramsRequired = $view->getParamsRequired();
		$model->paramsHidden = $view->getParamsHidden();
		
		$model->renderPage = $view->renderPage;
		$model->renderLimit = $view->renderLimit;
		$model->renderTotal = $view->renderTotal;
		$model->renderSortBy = $view->renderSortBy;
		$model->renderSortAsc = $view->renderSortAsc;

		$model->renderFilters = $view->renderFilters;
		$model->renderSubtotals = $view->renderSubtotals;
		
		$model->renderTemplate = $view->renderTemplate;
		
		$model->placeholderLabels = $view->getPlaceholderLabels();
		$model->placeholderValues = $view->getPlaceholderValues();
		
		return $model;
	}

	static function unserializeAbstractView(C4_AbstractViewModel $model) {
		if(!class_exists($model->class_name, true))
			return null;
		
		if(null == ($inst = new $model->class_name))
			return null;

		/* @var $inst C4_AbstractView */
		
		if(!empty($model->id))
			$inst->id = $model->id;
		if(null !== $model->is_ephemeral)
			$inst->is_ephemeral = $model->is_ephemeral;
		if(!empty($model->name))
			$inst->name = $model->name;
		
		if(is_array($model->view_columns) && !empty($model->view_columns))
			$inst->view_columns = $model->view_columns;
		if(is_array($model->columnsHidden))
			$inst->addColumnsHidden($model->columnsHidden, true);
		
		if(is_array($model->paramsEditable))
			$inst->addParams($model->paramsEditable, true);
		if(is_array($model->paramsDefault))
			$inst->addParamsDefault($model->paramsDefault, true);
		if(is_array($model->paramsRequired))
			$inst->addParamsRequired($model->paramsRequired, true);
		if(is_array($model->paramsHidden))
			$inst->addParamsHidden($model->paramsHidden, true);

		if(null !== $model->renderPage)
			$inst->renderPage = $model->renderPage;
		if(null !== $model->renderLimit)
			$inst->renderLimit = $model->renderLimit;
		if(null !== $model->renderTotal)
			$inst->renderTotal = $model->renderTotal;
		if(!empty($model->renderSortBy))
			$inst->renderSortBy = $model->renderSortBy;
		if(null !== $model->renderSortBy)
			$inst->renderSortAsc = $model->renderSortAsc;

		$inst->renderFilters = $model->renderFilters;
		$inst->renderSubtotals = $model->renderSubtotals;
			
		$inst->renderTemplate = $model->renderTemplate;
		
		if(is_array($model->placeholderLabels))
			$inst->setPlaceholderLabels($model->placeholderLabels);
		if(is_array($model->placeholderValues))
			$inst->setPlaceholderValues($model->placeholderValues);
		
		// Enforce class restrictions
		$parent = new $model->class_name;
		$inst->addColumnsHidden($parent->getColumnsHidden());
		$inst->addParamsHidden($parent->getParamsHidden());
		$inst->addParamsRequired($parent->getParamsRequired());
		
		return $inst;
	}
};

class DAO_WorkerViewModel {
	/**
	 * 
	 * @param string $where
	 * @return C4_AbstractViewModel[]
	 */
	static public function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$objects = array();
		
		$fields = array(
			'worker_id',
			'view_id',
			'is_ephemeral',
			'class_name',
			'title',
			'columns_json',
			'columns_hidden_json',
			'params_editable_json',
			'params_required_json',
			'params_default_json',
			'params_hidden_json',
			'render_page',
			'render_total',
			'render_limit',
			'render_sort_by',
			'render_sort_asc',
			'render_filters',
			'render_subtotals',
			'render_template',
			'placeholder_labels_json',
			'placeholder_values_json',
		);
		
		$rs = $db->Execute(sprintf("SELECT %s FROM worker_view_model %s",
			implode(',', $fields),
			(!empty($where) ? ('WHERE ' . $where) : '')
		));
		
		if(is_resource($rs))
		while($row = mysql_fetch_array($rs)) {
			$model = new C4_AbstractViewModel();
			$model->id = $row['view_id'];
			$model->worker_id = $row['worker_id'];
			$model->is_ephemeral = $row['is_ephemeral'];
			$model->class_name = $row['class_name'];
			$model->name = $row['title'];
			$model->renderPage = $row['render_page'];
			$model->renderTotal = $row['render_total'];
			$model->renderLimit = $row['render_limit'];
			$model->renderSortBy = $row['render_sort_by'];
			$model->renderSortAsc = $row['render_sort_asc'];
			$model->renderFilters = $row['render_filters'];
			$model->renderSubtotals = $row['render_subtotals'];
			$model->renderTemplate = $row['render_template'];
			
			// JSON blocks
			$model->view_columns = json_decode($row['columns_json'], true);
			$model->columnsHidden = json_decode($row['columns_hidden_json'], true);
			$model->paramsEditable = self::decodeParamsJson($row['params_editable_json']);
			$model->paramsRequired = self::decodeParamsJson($row['params_required_json']);
			$model->paramsDefault = self::decodeParamsJson($row['params_default_json']);
			$model->paramsHidden = json_decode($row['params_hidden_json'], true);
			
			$model->placeholderLabels = json_decode($row['placeholder_labels_json'], true);
			$model->placeholderValues = json_decode($row['placeholder_values_json'], true);
			
			// Make sure it's a well-formed view
			if(empty($model->class_name) || !class_exists($model->class_name, true))
				return false;
			
			$objects[] = $model;
		}
			
		return $objects;
	}
	
	/**
	 * 
	 * @param integer $worker_id
	 * @param string $view_id
	 * @return C4_AbstractViewModel|false
	 */
	static public function getView($worker_id, $view_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$results = DAO_WorkerViewModel::getWhere(sprintf("worker_id = %d AND view_id = %s",
			$worker_id,
			$db->qstr($view_id)
		));
		
		if(empty($results) || !is_array($results))
			return false;

		@$model = array_shift($results);
		
		return $model;
	}

	static public function decodeParamsJson($json) {
		$params = array();
		
		if(empty($json) || false === ($params_data = json_decode($json, true)))
			return array();
		
		if(is_array($params_data))
		foreach($params_data as $key => $data) {
			if(is_numeric(key($data))) {
				$params[$key] = self::_recurseParam($data);
			} else {
				$params[$key] = new DevblocksSearchCriteria($data['field'], $data['operator'], $data['value']); 
			}
		}
		
		return $params;
	}
	
	static private function _recurseParam($group) {
		$params = array();
		
		foreach($group as $key => $data) {
			if(is_array($data)) {
				if(is_numeric(key($data))) {
					$params[$key] = array(array_shift($data)) + self::_recurseParam($data);
				} else {
					$param = new DevblocksSearchCriteria($data['field'], $data['operator'], $data['value']);
					$params[$key] = $param;
				}
			} elseif(is_string($data)) {
				$params[$key] = $data;
			}
		}
		
		return $params;
	}
	
	static public function setView($worker_id, $view_id, C4_AbstractViewModel $model) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$fields = array(
			'worker_id' => $worker_id,
			'view_id' => $db->qstr($view_id),
			'is_ephemeral' => !empty($model->is_ephemeral) ? 1 : 0,
			'class_name' => $db->qstr($model->class_name),
			'title' => $db->qstr($model->name),
			'columns_json' => $db->qstr(json_encode($model->view_columns)),
			'columns_hidden_json' => $db->qstr(json_encode($model->columnsHidden)),
			'params_editable_json' => $db->qstr(json_encode($model->paramsEditable)),
			'params_required_json' => $db->qstr(json_encode($model->paramsRequired)),
			'params_default_json' => $db->qstr(json_encode($model->paramsDefault)),
			'params_hidden_json' => $db->qstr(json_encode($model->paramsHidden)),
			'render_page' => abs(intval($model->renderPage)),
			'render_total' => !empty($model->renderTotal) ? 1 : 0,
			'render_limit' => intval($model->renderLimit),
			'render_sort_by' => $db->qstr($model->renderSortBy),
			'render_sort_asc' => !empty($model->renderSortAsc) ? 1 : 0,
			'render_filters' => !empty($model->renderFilters) ? 1 : 0,
			'render_subtotals' => $db->qstr($model->renderSubtotals),
			'render_template' => $db->qstr($model->renderTemplate),
			'placeholder_labels_json' => $db->qstr(json_encode($model->placeholderLabels)),
			'placeholder_values_json' => $db->qstr(json_encode($model->placeholderValues)),
		);
		
		$db->Execute(sprintf("REPLACE INTO worker_view_model (%s)".
			"VALUES (%s)",
			implode(',', array_keys($fields)),
			implode(',', $fields)
		));
	}
	
	static public function deleteView($worker_id, $view_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute(sprintf("DELETE FROM worker_view_model WHERE worker_id = %d AND view_id = %s",
			$worker_id,
			$db->qstr($view_id)
		));
	}
	
	/**
	 * Prepares for a new session by removing ephemeral views and 
	 * resetting all page cursors to the first page of the list.
	 * 
	 * @param integer$worker_id
	 */
	static public function flush($worker_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute(sprintf("DELETE FROM worker_view_model WHERE worker_id = %d and is_ephemeral = 1",
			$worker_id
		));
		$db->Execute(sprintf("UPDATE worker_view_model SET render_page = 0 WHERE worker_id = %d",
			$worker_id
		));
	}
};