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
	public $renderSubtotalsClickable = 0;
	
	public $renderTemplate = null;

	abstract function getData();
	function getDataSample($size) {}
	
	protected function _doGetDataSample($dao_class, $size) {
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
		
		$select_sql = sprintf("SELECT %s.id ", $query_parts['primary_table']);
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
	
	function getParams() {
		// Required should override editable
		return array_merge($this->_paramsEditable, $this->_paramsRequired);
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
			$key = !is_string($key) ? $param->field : $key;
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
			case Model_CustomField::TYPE_MULTI_PICKLIST:
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

	protected function _doSetCriteriaCustomField($token, $field_id) {
		$field = DAO_CustomField::get($field_id);
		@$oper = DevblocksPlatform::importGPC($_POST['oper'],'string','');
		@$value = DevblocksPlatform::importGPC($_POST['value'],'string','');
		
		$criteria = null;
		
		switch($field->type) {
			case Model_CustomField::TYPE_DROPDOWN:
			case Model_CustomField::TYPE_MULTI_PICKLIST:
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
			
			switch($custom_fields[$field_id]->type) {
				case Model_CustomField::TYPE_WORKER:
					$workers = DAO_worker::getAll();
					foreach($vals as $idx => $worker_id) {
						if(isset($workers[$worker_id]))
							$vals[$idx] = $workers[$worker_id]->getName(); 
					}
					break;
			}
		}
		
		echo implode(', ', $vals);
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
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Presets
		return DAO_ViewFiltersPreset::getWhere(
			sprintf("%s = %s AND %s = %d",
				DAO_ViewFiltersPreset::VIEW_CLASS,
				C4_ORMHelper::qstr(get_class($this)),
				DAO_ViewFiltersPreset::WORKER_ID,
				$active_worker->id
			)
		);
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
			if(Model_CustomField::TYPE_MULTI_PICKLIST==$fields[$cf_id]->type 
				|| Model_CustomField::TYPE_MULTI_CHECKBOX==$fields[$cf_id]->type) {
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
	public $renderSubtotalsClickable = null;
	
	public $renderTemplate = null;
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
		$active_worker = CerberusApplication::getActiveWorker();

		// Check if we've ever persisted this view
		if(false !== ($model = DAO_WorkerViewModel::getView($active_worker->id, $view_id))) {
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
		$active_worker = CerberusApplication::getActiveWorker();
		$model = self::serializeAbstractView($view);
		DAO_WorkerViewModel::setView($active_worker->id, $view_id, $model);
	}

	static function deleteView($view_id) {
		$active_worker = CerberusApplication::getActiveWorker();
		DAO_WorkerViewModel::deleteView($active_worker->id, $view_id);
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
		$model->renderSubtotalsClickable = $view->renderSubtotalsClickable;
		
		$model->renderTemplate = $view->renderTemplate;
		
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
		
		if(!empty($model->view_columns))
			$inst->view_columns = $model->view_columns;
		if(!empty($model->columnsHidden))
			$inst->addColumnsHidden($model->columnsHidden, true);
		
		if(!empty($model->paramsEditable))
			$inst->addParams($model->paramsEditable, true);
		if(!empty($model->paramsDefault))
			$inst->addParamsDefault($model->paramsDefault, true);
		if(!empty($model->paramsRequired))
			$inst->addParamsRequired($model->paramsRequired, true);
		if(!empty($model->paramsHidden))
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
		$inst->renderSubtotalsClickable = $model->renderSubtotalsClickable;
			
		$inst->renderTemplate = $model->renderTemplate;
		
		return $inst;
	}
};

class DAO_WorkerViewModel {
	// [TODO] Add an 'ephemeral' bit to clear record on login
	
	/**
	 * 
	 * @param integer $worker_id
	 * @param string $view_id
	 * @return C4_AbstractViewModel or false
	 */
	static public function getView($worker_id, $view_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
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
			'render_subtotals_clickable',
			'render_template',
		);
		
		$row = $db->GetRow(sprintf("SELECT %s FROM worker_view_model WHERE worker_id = %d AND view_id = %s",
			implode(',', $fields),
			$worker_id,
			$db->qstr($view_id)
		));
		
		if(!empty($row)) {
			$model = new C4_AbstractViewModel();
			$model->id = $row['view_id'];
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
			$model->renderSubtotalsClickable = $row['render_subtotals_clickable'];
			$model->renderTemplate = $row['render_template'];
			
			// JSON blocks
			$model->view_columns = json_decode($row['columns_json'], true);
			$model->columnsHidden = json_decode($row['columns_hidden_json'], true);
			$model->paramsEditable = self::decodeParamsJson($row['params_editable_json']);
			$model->paramsRequired = self::decodeParamsJson($row['params_required_json']);
			$model->paramsDefault = self::decodeParamsJson($row['params_default_json']);
			$model->paramsHidden = json_decode($row['params_hidden_json'], true);
			
			return $model;
		}
			
		return false; 
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
			'render_filters' => $db->qstr($model->renderFilters),
			'render_subtotals' => $db->qstr($model->renderSubtotals),
			'render_subtotals_clickable' => !empty($model->renderSubtotalsClickable) ? 1 : 0,
			'render_template' => $db->qstr($model->renderTemplate),
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