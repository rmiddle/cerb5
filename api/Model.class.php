<?php
/***********************************************************************
 | Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2010, WebGroup Media LLC
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
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Joe Geck, Scott Luther,
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
	
	function addColumnsHidden($columnsToHide) {
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
	
	function addParamsDefault($params) {
		$this->_paramsDefault = array_merge($this->_paramsDefault, $params);
	}
	
	function getParamsDefault() {
		return $this->_paramsDefault;
	}
	
	// Params Required
	
	function addParamsRequired($params) {
		$this->_paramsRequired = array_merge($this->_paramsRequired, $params);
	}
	
	function getParamsRequired() {
		return $this->_paramsRequired;
	}
	
	// Params Hidden
	
	function addParamsHidden($params) {
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
			$inst->addColumnsHidden($model->columnsHidden);
		
		if(!empty($model->paramsEditable))
			$inst->addParams($model->paramsEditable, true);
		if(!empty($model->paramsDefault))
			$inst->addParamsDefault($model->paramsDefault);
		if(!empty($model->paramsRequired))
			$inst->addParamsRequired($model->paramsRequired);
		if(!empty($model->paramsHidden))
			$inst->addParamsHidden($model->paramsHidden);

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

		$inst->renderSubtotals = $model->renderSubtotals;
		$inst->renderSubtotalsClickable = $model->renderSubtotalsClickable;
			
		$inst->renderTemplate = $model->renderTemplate;
		
		return $inst;
	}
};

class View_DevblocksTemplate extends C4_AbstractView {
	const DEFAULT_ID = 'templates';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Templates';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_DevblocksTemplate::PATH;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_DevblocksTemplate::PLUGIN_ID,
//			SearchFields_DevblocksTemplate::TAG,
			SearchFields_DevblocksTemplate::LAST_UPDATED,
		);
		
		$this->doResetCriteria();
	}

	function getData() {
		return DAO_DevblocksTemplate::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

//		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WORKER);
//		$tpl->assign('custom_fields', $custom_fields);

		$tpl->display('devblocks:usermeet.core::community/display/tabs/templates/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_DevblocksTemplate::CONTENT:
			case SearchFields_DevblocksTemplate::PATH:
			case SearchFields_DevblocksTemplate::PLUGIN_ID:
			case SearchFields_DevblocksTemplate::TAG:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case SearchFields_DevblocksTemplate::LAST_UPDATED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
			default:
				// Custom Fields
//				if('cf_' == substr($field,0,3)) {
//					$this->_renderCriteriaCustomField($tpl, substr($field,3));
//				} else {
//					echo ' ';
//				}
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
//			case SearchFields_WorkerEvent::WORKER_ID:
//				$workers = DAO_Worker::getAll();
//				$strings = array();
//
//				foreach($values as $val) {
//					if(empty($val))
//					$strings[] = "Nobody";
//					elseif(!isset($workers[$val]))
//					continue;
//					else
//					$strings[] = $workers[$val]->getName();
//				}
//				echo implode(", ", $strings);
//				break;
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_DevblocksTemplate::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_DevblocksTemplate::CONTENT:
			case SearchFields_DevblocksTemplate::PATH:
			case SearchFields_DevblocksTemplate::PLUGIN_ID:
			case SearchFields_DevblocksTemplate::TAG:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_DevblocksTemplate::LAST_UPDATED:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			default:
				// Custom Fields
//				if(substr($field,0,3)=='cf_') {
//					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
//				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}

	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // [TODO] Temp!
	  
		$change_fields = array();
		$deleted = false;
		$custom_fields = array();

		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;

		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'deleted':
					$deleted = true;
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
			list($objects,$null) = DAO_DevblocksTemplate::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				DAO_DevblocksTemplate::ID,
				true,
				false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!$deleted)
				DAO_DevblocksTemplate::update($batch_ids, $change_fields);
			else
				DAO_DevblocksTemplate::delete($batch_ids);
			
			// Custom Fields
//			self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_WORKER, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}
		
		if($deleted) {
			// Clear compiled templates
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->utility->clearCompiledTemplate();
			$tpl->cache->clearAll();
		}

		unset($ids);
	}

};

class View_DevblocksStorageProfile extends C4_AbstractView {
	const DEFAULT_ID = 'devblocksstorageprofile';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Storage Profiles';

		$this->view_columns = array(
			SearchFields_DevblocksStorageProfile::NAME,
			SearchFields_DevblocksStorageProfile::EXTENSION_ID,
		);
		$this->_columnsHidden = array(
			SearchFields_DevblocksStorageProfile::PARAMS_JSON,
		);
		
		$this->_paramsHidden = array(
			SearchFields_DevblocksStorageProfile::ID,
			SearchFields_DevblocksStorageProfile::PARAMS_JSON,
		);
		
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_DevblocksStorageProfile::ID;
		$this->renderSortAsc = true;
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_DevblocksStorageProfile::search(
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

		$tpl->display('devblocks:cerberusweb.core::configuration/tabs/storage/profiles/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_DevblocksStorageProfile::NAME:
			case SearchFields_DevblocksStorageProfile::EXTENSION_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case SearchFields_DevblocksStorageProfile::ID:
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
		return SearchFields_DevblocksStorageProfile::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_DevblocksStorageProfile::NAME:
			case SearchFields_DevblocksStorageProfile::EXTENSION_ID:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_DevblocksStorageProfile::ID:
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
					//$change_fields[DAO_DevblocksStorageProfile::EXAMPLE] = 'some value';
					break;
				default:
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_DevblocksStorageProfile::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_DevblocksStorageProfile::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			DAO_DevblocksStorageProfile::update($batch_ids, $change_fields);

			// Custom Fields
			//self::_doBulkSetCustomFields(ChCustomFieldSource_DevblocksStorageProfile::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};

class Model_WorkerWorkspaceList {
	public $id = 0;
	public $worker_id = 0;
	public $workspace = '';
	public $source_extension = '';
	public $list_view = '';
	public $list_pos = 0;
};

class Model_WorkerWorkspaceListView {
	public $title = 'New List';
//	public $workspace = '';
	public $columns = array();
	public $num_rows = 10;
	public $params = array();
	public $sort_by = null;
	public $sort_asc = 1;
};

class Model_Activity {
	public $translation_code;
	public $params;

	public function __construct($translation_code='activity.default',$params=array()) {
		$this->translation_code = $translation_code;
		$this->params = $params;
	}

	public function toString(Model_Worker $worker=null) {
		if(null == $worker)
			return;
			
		$translate = DevblocksPlatform::getTranslationService();
		$params = $this->params;

		// Prepend the worker name to the activity's param list
		array_unshift($params, sprintf("<b>%s</b>%s",
			$worker->getName(),
			(!empty($worker->title) 
				? (' (' . $worker->title . ')') 
				: ''
			)
		));
		
		return vsprintf(
			$translate->_($this->translation_code), 
			$params
		);
	}
};

class Model_MailToGroupRule {
	public $id = 0;
	public $pos = 0;
	public $created = 0;
	public $name = '';
	public $criteria = array();
	public $actions = array();
	public $is_sticky = 0;
	public $sticky_order = 0;
	
	static function getMatches(Model_Address $fromAddress, CerberusParserMessage $message) {
		$matches = array();
		$rules = DAO_MailToGroupRule::getWhere();
		$message_headers = $message->headers;
		$custom_fields = DAO_CustomField::getAll();
		
		// Lazy load when needed on criteria basis
		$address_field_values = null;
		$org_field_values = null;
		
		// Check filters
		if(is_array($rules))
		foreach($rules as $rule) { /* @var $rule Model_MailToGroupRule */
			$passed = 0;

			// check criteria
			foreach($rule->criteria as $crit_key => $crit) {
				@$value = $crit['value'];
							
				switch($crit_key) {
					case 'dayofweek':
						$current_day = strftime('%w');
//						$current_day = 1;

						// Forced to English abbrevs as indexes
						$days = array('sun','mon','tue','wed','thu','fri','sat');
						
						// Is the current day enabled?
						if(isset($crit[$days[$current_day]])) {
							$passed++;
						}
							
						break;
						
					case 'timeofday':
						$current_hour = strftime('%H');
						$current_min = strftime('%M');
//						$current_hour = 17;
//						$current_min = 5;

						if(null != ($from_time = @$crit['from']))
							list($from_hour, $from_min) = explode(':', $from_time);
						
						if(null != ($to_time = @$crit['to']))
							if(list($to_hour, $to_min) = explode(':', $to_time));

						// Do we need to wrap around to the next day's hours?
						if($from_hour > $to_hour) { // yes
							$to_hour += 24; // add 24 hrs to the destination (1am = 25th hour)
						}
							
						// Are we in the right 24 hourly range?
						if((integer)$current_hour >= $from_hour && (integer)$current_hour <= $to_hour) {
							// If we're in the first hour, are we minutes early?
							if($current_hour==$from_hour && (integer)$current_min < $from_min)
								break;
							// If we're in the last hour, are we minutes late?
							if($current_hour==$to_hour && (integer)$current_min > $to_min)
								break;
								
							$passed++;
						}

						break;					
					
					case 'tocc':
						$tocc = array();
						$destinations = DevblocksPlatform::parseCsvString($value);

						// Build a list of To/Cc addresses on this message
						@$to_list = imap_rfc822_parse_adrlist($message_headers['to'],'localhost');
						@$cc_list = imap_rfc822_parse_adrlist($message_headers['cc'],'localhost');
						
						if(is_array($to_list))
						foreach($to_list as $addy) {
							$tocc[] = $addy->mailbox . '@' . $addy->host;
						}
						if(is_array($cc_list))
						foreach($cc_list as $addy) {
							$tocc[] = $addy->mailbox . '@' . $addy->host;
						}
						
						$dest_flag = false; // bail out when true
						if(is_array($destinations) && is_array($tocc))
						foreach($destinations as $dest) {
							if($dest_flag) 
								break;
								
							$regexp_dest = DevblocksPlatform::strToRegExp($dest);
							
							foreach($tocc as $addy) {
								if(@preg_match($regexp_dest, $addy)) {
									$passed++;
									$dest_flag = true;
									break;
								}
							}
						}
						break;
						
					case 'from':
						$regexp_from = DevblocksPlatform::strToRegExp($value);
						if(@preg_match($regexp_from, $fromAddress->email)) {
							$passed++;
						}
						break;
						
					case 'subject':
						// [TODO] Decode if necessary
						@$subject = $message_headers['subject'];

						$regexp_subject = DevblocksPlatform::strToRegExp($value);
						if(@preg_match($regexp_subject, $subject)) {
							$passed++;
						}
						break;

					case 'body':
						// Line-by-line body scanning (sed-like)
						$lines = preg_split("/[\r\n]/", $message->body);
						if(is_array($lines))
						foreach($lines as $line) {
							if(@preg_match($value, $line)) {
								$passed++;
								break;
							}
						}
						break;
						
					case 'header1':
					case 'header2':
					case 'header3':
					case 'header4':
					case 'header5':
						@$header = strtolower($crit['header']);

						if(empty($header)) {
							$passed++;
							break;
						}
						
						if(empty($value)) { // we're checking for null/blanks
							if(!isset($message_headers[$header]) || empty($message_headers[$header])) {
								$passed++;
							}
							
						} elseif(isset($message_headers[$header]) && !empty($message_headers[$header])) {
							$regexp_header = DevblocksPlatform::strToRegExp($value);
							
							// Flatten CRLF
							if(@preg_match($regexp_header, str_replace(array("\r","\n"),' ',$message_headers[$header]))) {
								$passed++;
							}
						}
						
						break;
						
					default: // ignore invalids
						// Custom Fields
						if(0==strcasecmp('cf_',substr($crit_key,0,3))) {
							$field_id = substr($crit_key,3);

							// Make sure it exists
							if(null == (@$field = $custom_fields[$field_id]))
								continue;

							// Lazy values loader
							$field_values = array();
							switch($field->context) {
								case CerberusContexts::CONTEXT_ADDRESS:
									if(null == $address_field_values)
										$address_field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ADDRESS, $fromAddress->id));
									$field_values =& $address_field_values;
									break;
								case CerberusContexts::CONTEXT_ORG:
									if(null == $org_field_values)
										$org_field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ORG, $fromAddress->contact_org_id));
									$field_values =& $org_field_values;
									break;
							}
							
							// No values, default.
							if(!isset($field_values[$field_id]))
								continue;
							
							// Type sensitive value comparisons
							switch($field->type) {
								case 'S': // string
								case 'T': // clob
								case 'U': // URL
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : '';
									$oper = isset($crit['oper']) ? $crit['oper'] : "=";
									
									if($oper == "=" && @preg_match(DevblocksPlatform::strToRegExp($value, true), $field_val))
										$passed++;
									elseif($oper == "!=" && @!preg_match(DevblocksPlatform::strToRegExp($value, true), $field_val))
										$passed++;
									break;
								case 'N': // number
									if(!isset($field_values[$field_id]))
										break;

									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : 0;
									$oper = isset($crit['oper']) ? $crit['oper'] : "=";
									
									if($oper=="=" && intval($field_val)==intval($value))
										$passed++;
									elseif($oper=="!=" && intval($field_val)!=intval($value))
										$passed++;
									elseif($oper==">" && intval($field_val) > intval($value))
										$passed++;
									elseif($oper=="<" && intval($field_val) < intval($value))
										$passed++;
									break;
								case 'E': // date
									$field_val = isset($field_values[$field_id]) ? intval($field_values[$field_id]) : 0;
									$from = isset($crit['from']) ? $crit['from'] : "0";
									$to = isset($crit['to']) ? $crit['to'] : "now";
									
									if(intval(@strtotime($from)) <= $field_val && intval(@strtotime($to)) >= $field_val) {
										$passed++;
									}
									break;
								case 'C': // checkbox
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : 0;
									if(intval($value)==intval($field_val))
										$passed++;
									break;
								case 'D': // dropdown
								case 'X': // multi-checkbox
								case 'M': // multi-picklist
								case 'W': // worker
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : array();
									if(!is_array($value)) $value = array($value);
										
									if(is_array($field_val)) { // if multiple things set
										foreach($field_val as $v) { // loop through possible
											if(isset($value[$v])) { // is any possible set?
												$passed++;
												break;
											}
										}
										
									} else { // single
										if(isset($value[$field_val])) { // is our set field in possibles?
											$passed++;
											break;
										}
										
									}
									break;
							}
						}
						break;
				}
			}
			
			// If our rule matched every criteria, stop and return the filter
			if($passed == count($rule->criteria)) {
				DAO_MailToGroupRule::increment($rule->id); // ++ the times we've matched
				$matches[$rule->id] = $rule;
				
				// Bail out if this rule had a move action
				if(isset($rule->actions['move']))
					return $matches;
			}
		}
		
		// If we're at the end of rules and didn't bail out yet
		if(!empty($matches))
			return $matches;
		
		// No matches
		return NULL;
	}
	
	/**
	 * @param integer[] $ticket_ids
	 */
	function run($ticket_ids) {
		if(!is_array($ticket_ids)) $ticket_ids = array($ticket_ids);
		
		$fields = array();
		$field_values = array();

		$groups = DAO_Group::getAll();
		$buckets = DAO_Bucket::getAll();
//		$workers = DAO_Worker::getAll();
		$custom_fields = DAO_CustomField::getAll();
		
		// actions
		if(is_array($this->actions))
		foreach($this->actions as $action => $params) {
			switch($action) {
//				case 'status':
//					if(isset($params['is_waiting']))
//						$fields[DAO_Ticket::IS_WAITING] = intval($params['is_waiting']);
//					if(isset($params['is_closed']))
//						$fields[DAO_Ticket::IS_CLOSED] = intval($params['is_closed']);
//					if(isset($params['is_deleted']))
//						$fields[DAO_Ticket::IS_DELETED] = intval($params['is_deleted']);
//					break;

				case 'move':
					if(isset($params['group_id']) && isset($params['bucket_id'])) {
						$g_id = intval($params['group_id']);
						$b_id = intval($params['bucket_id']);
						if(isset($groups[$g_id]) && (0==$b_id || isset($buckets[$b_id]))) {
							$fields[DAO_Ticket::TEAM_ID] = $g_id;
							$fields[DAO_Ticket::CATEGORY_ID] = $b_id;
						}
					}
					break;
					
//				case 'spam':
//					if(isset($params['is_spam'])) {
//						if(intval($params['is_spam'])) {
//							foreach($ticket_ids as $ticket_id)
//								CerberusBayes::markTicketAsSpam($ticket_id);
//						} else {
//							foreach($ticket_ids as $ticket_id)
//								CerberusBayes::markTicketAsNotSpam($ticket_id);
//						}
//					}
//					break;

				default:
					// Custom fields
					if(substr($action,0,3)=="cf_") {
						$field_id = intval(substr($action,3));
						
						if(!isset($custom_fields[$field_id]) || !isset($params['value']))
							break;

						$field_values[$field_id] = $params;
					}
					break;
			}
		}

		if(!empty($ticket_ids)) {
			if(!empty($fields))
				DAO_Ticket::update($ticket_ids, $fields);
			
			// Custom Fields
			C4_AbstractView::_doBulkSetCustomFields(CerberusContexts::CONTEXT_TICKET, $field_values, $ticket_ids);
		}
	}
	
};

class CerberusVisit extends DevblocksVisit {
	private $worker_id;

	const KEY_ACTIVITY_TAB = 'activity_tab';
	const KEY_VIEW_LAST_ACTION = 'view_last_action';
	const KEY_MY_WORKSPACE = 'view_my_workspace';
	const KEY_MAIL_MODE = 'mail_mode';
	const KEY_WORKFLOW_FILTER = 'workflow_filter';

	public function __construct() {
		$this->worker_id = null;
	}

	/**
	 * @return Model_Worker
	 */
	public function getWorker() {
		if(empty($this->worker_id))
			return null;
			
		return DAO_Worker::get($this->worker_id);
	}
	
	public function setWorker(Model_Worker $worker=null) {
		$this->worker_id = $worker->id;
	}
};

class Model_WorkerRole {
	public $id;
	public $name;
};

class Model_ViewRss {
	public $id = 0;
	public $title = '';
	public $hash = '';
	public $worker_id = 0;
	public $created = 0;
	public $source_extension = '';
	public $params = array();
};

class Model_TicketViewLastAction {
	// [TODO] Recycle the bulk update constants for these actions?
	const ACTION_NOT_SPAM = 'not_spam';
	const ACTION_SPAM = 'spam';
	const ACTION_CLOSE = 'close';
	const ACTION_DELETE = 'delete';
	const ACTION_MOVE = 'move';
	const ACTION_TAKE = 'take';
	const ACTION_SURRENDER = 'surrender';
	const ACTION_WAITING = 'waiting';
	const ACTION_NOT_WAITING = 'not_waiting';

	public $ticket_ids = array(); // key = ticket id, value=old value
	public $action = ''; // spam/closed/move, etc.
	public $action_params = array(); // DAO Actions Taken
};

class CerberusTicketStatus {
	const OPEN = 0;
	const CLOSED = 1;
};

class CerberusTicketSpamTraining { // [TODO] Append 'Enum' to class name?
	const BLANK = '';
	const NOT_SPAM = 'N';
	const SPAM = 'S';
};

class CerberusTicketActionCode {
	const TICKET_OPENED = 'O';
	const TICKET_CUSTOMER_REPLY = 'R';
	const TICKET_WORKER_REPLY = 'W';
};

class Model_TeamMember {
	public $id;
	public $team_id;
	public $is_manager = 0;
}

class Model_Pop3Account {
	public $id;
	public $enabled=1;
	public $nickname;
	public $protocol='pop3';
	public $host;
	public $username;
	public $password;
	public $port=110;
};

class Model_CustomField {
	const TYPE_CHECKBOX = 'C';
	const TYPE_DROPDOWN = 'D';
	const TYPE_DATE = 'E';
	const TYPE_MULTI_PICKLIST = 'M';
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
			self::TYPE_MULTI_PICKLIST => 'Multi-Picklist',
			self::TYPE_CHECKBOX => 'Checkbox',
			self::TYPE_MULTI_CHECKBOX => 'Multi-Checkbox',
			self::TYPE_WORKER => 'Worker',
			self::TYPE_URL => 'URL',
//			self::TYPE_FILE => 'File',
		);
	}
};

