<?php
abstract class DevblocksApplication {
	
}

/**
 * The superclass of instanced extensions.
 *
 * @abstract 
 * @ingroup plugin
 */
class DevblocksExtension {
	public $manifest = null;
	public $id  = '';
	
	/**
	 * Constructor
	 *
	 * @private
	 * @param DevblocksExtensionManifest $manifest
	 * @return DevblocksExtension
	 */
	function DevblocksExtension($manifest) { /* @var $manifest DevblocksExtensionManifest */
        if(empty($manifest)) return;
        
		$this->manifest = $manifest;
		$this->id = $manifest->id;
	}
	
	function getParams() {
		return $this->manifest->getParams();
	}
	
	function setParam($key, $value) {
		return $this->manifest->setParam($key, $value);
	}
	
	function getParam($key,$default=null) {
		return $this->manifest->getParam($key, $default);
	}
};

abstract class Extension_DevblocksContext extends DevblocksExtension {
	public static function getAll($as_instances=false) {
		$contexts = DevblocksPlatform::getExtensions('devblocks.context', $as_instances);
		if($as_instances)
			uasort($contexts, create_function('$a, $b', "return strcasecmp(\$a->manifest->name,\$b->manifest->name);\n"));
		else
			uasort($contexts, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
		return $contexts;
	}
	
   	function authorize($context_id, Model_Worker $worker) {
		return true;
	}
    
    abstract function getMeta($context_id);
    abstract function getContext($object, &$token_labels, &$token_values, $prefix=null);
    abstract function getChooserView();
    function getViewClass() {
    	return @$this->manifest->params['view_class'];
    }
    abstract function getView($context=null, $context_id=null, $options=array());
};

abstract class Extension_DevblocksEvent extends DevblocksExtension {
	private $_labels = array();
	private $_values = array();
	
	public static function getAll($as_instances=false) {
		$events = DevblocksPlatform::getExtensions('devblocks.event', $as_instances);
		if($as_instances)
			uasort($events, create_function('$a, $b', "return strcasecmp(\$a->manifest->name,\$b->manifest->name);\n"));
		else
			uasort($events, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
		return $events;
	}
	
	public static function getByContext($context, $as_instances=false) {
		$events = self::getAll(false);
		
		foreach($events as $event_id => $event) {
			if(isset($event->params['contexts'][0])) {
				$contexts = $event->params['contexts'][0]; // keys
				if(!isset($contexts[$context]))
					unset($events[$event_id]);
			}
		}
		
		if($as_instances) {
			foreach($events as $event_id => $event)
				$events[$event_id] = $event->createInstance();
		}
			
		return $events;
	}
	
	private function _importLabels($labels) {
		uasort($labels, create_function('$a, $b', "return strcasecmp(\$a,\$b);\n"));
		return $labels;
	}
	
	protected function _importLabelsTypesAsConditions($labels, $types) {
		$conditions = array();
		
		foreach($types as $token => $type) {
			if(!isset($labels[$token]))
				continue;
			
			$label = $labels[$token];
			
			// Strip any modifiers
			if(false !== ($pos = strpos($token,'|')))
				$token = substr($token,0,$pos);
				
				
			$conditions[$token] = array('label' => $label, 'type' => $type);
		}
		
		foreach($labels as $token => $label) {
			if(preg_match("#.*?_{0,1}custom_(\d+)#", $token, $matches)) {
				
				if(null == ($cfield = DAO_CustomField::get($matches[1])))
					continue;
					
				$conditions[$token] = array('label' => $label, 'type' => $cfield->type);
				
				switch($cfield->type) {
					case Model_CustomField::TYPE_DROPDOWN:
					case Model_CustomField::TYPE_MULTI_CHECKBOX:
						$conditions[$token]['options'] = $cfield->options;
						break;
				}
			}
		}
		
		return $conditions;
	}
	
	abstract function setEvent(Model_DevblocksEvent $event_model=null);
	
	function setLabels($labels) {
		$this->_labels = $this->_importLabels($labels);
	}
	
	function setValues($values) {
		$this->_values = $values;
	}
	
	function getLabels() {
		// Lazy load
		if(empty($this->_labels))
			$this->setEvent(null);
			
		return $this->_labels;
	}
	
	function getValues() {
		return $this->_values;
	}
	
	// [TODO] Cache results for this request
	function getConditions() {
		$conditions = array(
			'_month_of_year' => array('label' => 'Month of year', 'type' => ''),
			'_day_of_week' => array('label' => 'Day of week', 'type' => ''),
			'_time_of_day' => array('label' => 'Time of day', 'type' => ''),
		);
		$custom = $this->getConditionExtensions();
		
		if(!empty($custom) && is_array($custom))
			$conditions = array_merge($conditions, $custom);
		
		// Plugins
		// [TODO] Work in progress
		// [TODO] This should filter by event type
		$manifests = Extension_DevblocksEventCondition::getAll(false);
		//var_dump($manifests);
		foreach($manifests as $manifest) {
			$conditions[$manifest->id] = array('label' => $manifest->params['label']);
		}
			
		uasort($conditions, create_function('$a, $b', "return strcasecmp(\$a['label'],\$b['label']);\n"));
			
		return $conditions;
	}
	
	abstract function getConditionExtensions();
	abstract function renderConditionExtension($token, $trigger, $params=array(), $seq=null);
	abstract function runConditionExtension($token, $trigger, $params, $values);
	
	// [TODO] These templates should move to Devblocks
	function renderCondition($token, $trigger, $params=array(), $seq=null) {
		$conditions = $this->getConditionExtensions();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		// Is this an event-provided condition?
		if(null != (@$condition = $conditions[$token])) {
			// Automatic types
			switch($condition['type']) {
				case Model_CustomField::TYPE_CHECKBOX:
					$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_bool.tpl');
					break;
				case Model_CustomField::TYPE_DATE:
					$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_date.tpl');
					break;
				case Model_CustomField::TYPE_MULTI_LINE:
				case Model_CustomField::TYPE_SINGLE_LINE:
				case Model_CustomField::TYPE_URL:
					$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_string.tpl');
					break;
				case Model_CustomField::TYPE_NUMBER:
					$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_number.tpl');
					break;
				case Model_CustomField::TYPE_DROPDOWN:
				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					$tpl->assign('condition', $condition);
					$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_dropdown.tpl');
					break;
				case Model_CustomField::TYPE_WORKER:
					$tpl->assign('workers', DAO_Worker::getAll());
					$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_worker.tpl');
					break;
				default:
					$this->renderConditionExtension($token, $trigger, $params, $seq);
					break;
			}
			
		// Nope, it's a global condition
		} else {
			switch($token) {
				case '_month_of_year':
					$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_month_of_year.tpl');
					break;
	
				case '_day_of_week':
					$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_day_of_week.tpl');
					break;
	
				case '_time_of_day':
					$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_time_of_day.tpl');
					break;
					
				default:
					// Plugins
					if(null != ($ext = DevblocksPlatform::getExtension($token, true))
						&& $ext instanceof Extension_DevblocksEventCondition) { /* @var $ext Extension_DevblocksEventCondition */ 
						$ext->render($this, $trigger, $params, $seq);
					}
					break;
			}
		}
	}
	
	function runCondition($token, $trigger, $params, $values) {
		$logger = DevblocksPlatform::getConsoleLog('Assistant');
		$conditions = $this->getConditionExtensions();
		$not = false;
		$pass = true;
		
		// Operators
		if(null != (@$condition = $conditions[$token])) {
			if(null == (@$value = $values[$token])) {
				$value = '';
			}
			
			// Automatic types
			switch($condition['type']) {
				case Model_CustomField::TYPE_CHECKBOX:
					$bool = intval($params['bool']);
					$pass = !empty($value) == $bool;
					break;
				case Model_CustomField::TYPE_DATE:
					$not = (substr($params['oper'],0,1) == '!');
					$oper = ltrim($params['oper'],'!');
					switch($oper) {
						case 'between':
							$from = strtotime($params['from']);
							$to = strtotime($params['to']);
							if($to < $from)
								$to += 86400; // +1 day
							$pass = ($value >= $from && $value <= $to) ? true : false;
							break;
					}
					break;
				case Model_CustomField::TYPE_MULTI_LINE:
				case Model_CustomField::TYPE_SINGLE_LINE:
				case Model_CustomField::TYPE_URL:
					$not = (substr($params['oper'],0,1) == '!');
					$oper = ltrim($params['oper'],'!');
					switch($oper) {
						case 'is':
							$pass = (0==strcasecmp($value,$params['value']));
							break;
						case 'like':
							$regexp = DevblocksPlatform::strToRegExp($params['value']);
							$pass = @preg_match($regexp, $value);
							break;
						case 'contains':
							$pass = (false !== stripos($value, $params['value'])) ? true : false;
							break;
						case 'regexp':
							$pass = @preg_match($params['value'], $value);
							break;
						//case 'words_all':
						//	break;
						//case 'words_any':
						//	break;
					}
					
					// Handle operator negation
					break;
				case Model_CustomField::TYPE_NUMBER:
					$not = (substr($params['oper'],0,1) == '!');
					$oper = ltrim($params['oper'],'!');
					switch($oper) {
						case 'is':
							$pass = intval($value)==intval($params['value']);
							break;
						case 'gt':
							$pass = intval($value) > intval($params['value']);
							break;
						case 'lt':
							$pass = intval($value) < intval($params['value']);
							break;
					}
					break;
				case Model_CustomField::TYPE_DROPDOWN:
					$not = (substr($params['oper'],0,1) == '!');
					$oper = ltrim($params['oper'],'!');
					
					if(!isset($params['values']) || !is_array($params['values'])) {
						$pass = false;
						break;
					}
					
					switch($oper) {
						case 'in':
							$pass = false;
							foreach($params['values'] as $v) {
								if(isset($value[$v])) {
									$pass = true;
									break;
								}
							}
							break;
					}
					break;
				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					$not = (substr($params['oper'],0,1) == '!');
					$oper = ltrim($params['oper'],'!');
					
					if(preg_match("#(.*?_custom)_(\d+)#", $token, $matches) && 3 == count($matches)) {
						@$value = $values[$matches[1]][$matches[2]]; 
					}
					
					if(!is_array($value) || !isset($params['values']) || !is_array($params['values'])) {
						$pass = false;
						break;
					}
					
					switch($oper) {
						case 'is':
							$pass = true;
							foreach($params['values'] as $v) {
								if(!isset($value[$v])) {
									$pass = false;
									break;
								}
							}
							break;
						case 'in':
							$pass = false;
							foreach($params['values'] as $v) {
								if(isset($value[$v])) {
									$pass = true;
									break;
								}
							}
							break;
					}
					break;
				case Model_CustomField::TYPE_WORKER:
					$not = (substr($params['oper'],0,1) == '!');
					$oper = ltrim($params['oper'],'!');
					
					if(!is_array($value))
						$value = empty($value) ? array() : array($value);
					
					if(!is_array($params['worker_id']))
						return false;
					
					switch($oper) {
						case 'in':
							$pass = false;
							foreach($params['worker_id'] as $v) {
								if(in_array($v, $value)) {
									$pass = true;
									break;
								}
							}
							break;
					}
					break;
				default:
					$pass = $this->runConditionExtension($token, $trigger, $params, $values);
					break;
			}
			
		} else {
			switch($token) {
				case '_month_of_year':
					$not = (substr($params['oper'],0,1) == '!');
					$oper = ltrim($params['oper'],'!');
					switch($oper) {
						case 'is':
							$month = date('n');
							$pass = in_array($month, $params['month']);
							break;
					}
					break;
				case '_day_of_week':
					$not = (substr($params['oper'],0,1) == '!');
					$oper = ltrim($params['oper'],'!');
					switch($oper) {
						case 'is':
							$today = date('N');
							$pass = in_array($today, $params['day']);
							break;
					}
					break;
				case '_time_of_day':
					$not = (substr($params['oper'],0,1) == '!');
					$oper = ltrim($params['oper'],'!');
					switch($oper) {
						case 'between':
							$now = strtotime('now');
							$from = strtotime($params['from']);
							$to = strtotime($params['to']);
							if($to < $from)
								$to += 86400; // +1 day
							$pass = ($now >= $from && $now <= $to) ? true : false;
							break;
					}
					break;
				default:
					// Plugins
					if(null != ($ext = DevblocksPlatform::getExtension($token, true))
						&& $ext instanceof Extension_DevblocksEventCondition) { /* @var $ext Extension_DevblocksEventCondition */ 
						$pass = $ext->run($token, $trigger, $params, $values);
					}
					break;
			}
		}
		
		// Inverse operator?
		if($not)
			$pass = !$pass;
			
		$logger->info(sprintf("Checking condition '%s'... %s", $token, ($pass ? 'PASS' : 'FAIL')));
		
		return $pass;
	}
	
	// [TODO] Cache results for this request
	function getActions() {
		$actions = array();
		$custom = $this->getActionExtensions();
		
		if(!empty($custom) && is_array($custom))
			$actions = array_merge($actions, $custom);
		
		// Add plugin extensions
		// [TODO] This should be filtered by event type?
		$manifests = Extension_DevblocksEventAction::getAll(false);
		//var_dump($manifests);
		foreach($manifests as $manifest) {
			$actions[$manifest->id] = array('label' => $manifest->params['label']);
		}
			
		uasort($actions, create_function('$a, $b', "return strcasecmp(\$a['label'],\$b['label']);\n"));
			
		return $actions;
	}
	
	abstract function getActionExtensions();
	abstract function renderActionExtension($token, $trigger, $params=array(), $seq=null);
	abstract function runActionExtension($token, $trigger, $params, &$values);
	
	function renderAction($token, $trigger, $params=array(), $seq=null) {
		$actions = $this->getActionExtensions();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);
		
		// Is this an event-provided action?
		if(null != (@$action = $actions[$token])) {
			$this->renderActionExtension($token, $trigger, $params, $seq);
			
		// Nope, it's a global action
		} else {
			switch($token) {
				default:
					// Plugins
					if(null != ($ext = DevblocksPlatform::getExtension($token, true))
						&& $ext instanceof Extension_DevblocksEventAction) { /* @var $ext Extension_DevblocksEventAction */ 
						$ext->render($this, $trigger, $params, $seq);
					}
					break;
			}
		}		
	}
	
	function runAction($token, $trigger, $params, &$values) {
		$actions = $this->getActionExtensions();
		
		if(null != (@$action = $actions[$token])) {
			$this->runActionExtension($token, $trigger, $params, $values);
			
		} else {
			switch($token) {
				default:
					// Plugins
					if(null != ($ext = DevblocksPlatform::getExtension($token, true))
						&& $ext instanceof Extension_DevblocksEventAction) { /* @var $ext Extension_DevblocksEventAction */ 
						$ext->run($token, $trigger, $params, $values);
					}
					break;
			}
		}
			
	}
	
};

class DevblocksEventHelper {
	/*
	 * Action: Custom Fields
	 */
	static function getActionCustomFields($context) {
		$actions = array();
		
		// Set custom fields
		$custom_fields = DAO_CustomField::getByContext($context);
		foreach($custom_fields as $field_id => $field) {
			$actions['set_cf_' . $field_id] = array(
				'label' => 'Set ' . mb_convert_case($field->name, MB_CASE_LOWER),
				'type' => $field->type,
			);
		}
		
		return $actions;
	}
	
	static function renderActionSetCustomField(Model_CustomField $custom_field) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		switch($custom_field->type) {
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_URL:
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_string.tpl');
				break;
				
			case Model_CustomField::TYPE_MULTI_LINE:
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_clob.tpl');
				break;
				
			case Model_CustomField::TYPE_NUMBER:
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_number.tpl');
				break;
				
			case Model_CustomField::TYPE_CHECKBOX:
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_bool.tpl');
				break;
				
			case Model_CustomField::TYPE_DATE:
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_date.tpl');
				break;
				
			case Model_CustomField::TYPE_DROPDOWN:
				$tpl->assign('options', $custom_field->options);
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_dropdown.tpl');
				$tpl->clearAssign('options');
				break;
				
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
				$tpl->assign('options', $custom_field->options);
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_multi_checkbox.tpl');
				$tpl->clearAssign('options');
				break;
				
			case Model_CustomField::TYPE_WORKER:
				$workers = DAO_Worker::getAllActive();
				$tpl->assign('workers', $workers);
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_worker.tpl');
				break;
		}		
	}	
	
	static function runActionSetCustomField(Model_CustomField $custom_field, $value_key, $params, &$values, $context, $context_id) {
		@$field_id = $custom_field->id;
		
		// [TODO] Log
		if(empty($field_id) || empty($context) || empty($context_id))
			return;
		
		switch($custom_field->type) {
			case Model_CustomField::TYPE_CHECKBOX:
			case Model_CustomField::TYPE_DROPDOWN:
			case Model_CustomField::TYPE_MULTI_LINE:
			case Model_CustomField::TYPE_NUMBER:
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_URL:
				@$value = $params['value'];
				
				$builder = DevblocksPlatform::getTemplateBuilder();
				$value = $builder->build($value, $values);
				
				DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, $value);

				if(!empty($value_key)) {
					$values[$value_key.'_'.$field_id] = $value;
					$values[$value_key][$field_id] = $value;
				}
				break;
			
			case Model_CustomField::TYPE_DATE:
				$value = $params['value'];
				$value = strtotime($value);
				
				DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, $value);
				
				if(!empty($value_key)) {
					$values[$value_key.'_'.$field_id] = $value;
					$values[$value_key][$field_id] = $value;
				}
				break;
				
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
				@$opts = $params['values'];
				
				DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, $opts, true);

				if(!empty($value_key)) {
					$values[$value_key.'_'.$field_id] = implode(',',$opts);
					$values[$value_key][$field_id] = $opts;
				}
				
				break;
				
			case Model_CustomField::TYPE_WORKER:
				@$worker_id = $params['worker_id'];
				
				DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, $worker_id);
				
				if(!empty($value_key)) {
					$values[$value_key.'_'.$field_id] = $worker_id;
					$values[$value_key][$field_id] = $worker_id;
				}
				break;
				
			default:
				$this->runActionExtension($token, $trigger, $params, $values);
				break;	
		}		
	}
	
	/*
	 * Action: Create Comment
	 */
	
	static function renderActionCreateComment() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_create_comment.tpl');
	}
	
	static function runActionCreateComment($params, $values, $context, $context_id) {
		// Translate message tokens
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$content = $tpl_builder->build($params['content'], $values);
		
		$fields = array(
			DAO_Comment::ADDRESS_ID => 0,
			DAO_Comment::CONTEXT => $context,
			DAO_Comment::CONTEXT_ID => $context_id,
			DAO_Comment::CREATED => time(),
			DAO_Comment::COMMENT => $content,
		);
		$comment_id = DAO_Comment::create($fields);
		
		return $comment_id;
	}
	
	static function renderActionScheduleTicketReply() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.core::events/model/ticket/action_schedule_email_recipients.tpl');
	}
	
	static function runActionScheduleTicketReply($params, $values, $ticket_id, $message_id) {
		@$delivery_date_relative = $params['delivery_date'];
		
		if(false == ($delivery_date = strtotime($delivery_date_relative)))
			$delivery_date = time();
		
		// Translate message tokens
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$content = $tpl_builder->build($params['content'], $values);
		
		$fields = array(
			DAO_MailQueue::TYPE => Model_MailQueue::TYPE_TICKET_REPLY,
			DAO_MailQueue::IS_QUEUED => 1,
			//DAO_MailQueue::HINT_TO => implode($values['recipients']),
			DAO_MailQueue::HINT_TO => '(recipients)',
			DAO_MailQueue::SUBJECT => $values['ticket_subject'],
			DAO_MailQueue::BODY => $content,
			DAO_MailQueue::PARAMS_JSON => json_encode(array(
				'in_reply_message_id' => $message_id,				
			)),
			DAO_MailQueue::TICKET_ID => $ticket_id,
			DAO_MailQueue::WORKER_ID => 0,
			DAO_MailQueue::UPDATED => time(),
			DAO_MailQueue::QUEUE_DELIVERY_DATE => $delivery_date,
		);
		$queue_id = DAO_MailQueue::create($fields);
	}
	
	static function renderActionSetTicketOwner() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('workers', DAO_Worker::getAllActive());
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_worker.tpl');
	}
	
	static function runActionSetTicketOwner($params, $values, $ticket_id) {
		@$owner_id = intval($params['worker_id']);
		$fields = array(
			DAO_Ticket::OWNER_ID => $owner_id,
		);
		DAO_Ticket::update($ticket_id, $fields);
	}
	
	static function renderActionAddWatchers() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('workers', DAO_Worker::getAll());
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_add_watchers.tpl');
	}
	
	static function runActionAddWatchers($params, $values, $context, $context_id) {
		@$worker_ids = $params['worker_id'];
		
		if(!is_array($worker_ids) || empty($worker_ids))
			return;

		CerberusContexts::addWatchers($context, $context_id, $worker_ids);
	}
	
	/*
	 * Action: Create Notification
	 */
	
	static function renderActionCreateNotification() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('workers', DAO_Worker::getAll());
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_create_notification.tpl');
	}
	
	static function runActionCreateNotification($params, $values, $url) {
		@$notify_worker_ids = $params['notify_worker_id'];
		
		if(!is_array($notify_worker_ids) || empty($notify_worker_ids))
			return;
		
		// Translate message tokens
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$content = $tpl_builder->build($params['content'], $values);
		
		foreach($notify_worker_ids as $notify_worker_id) {
			$fields = array(
				DAO_Notification::WORKER_ID => $notify_worker_id,
				DAO_Notification::CREATED_DATE => time(),
				DAO_Notification::MESSAGE => $content,
				DAO_Notification::URL => $url,
			);
			$notification_id = DAO_Notification::create($fields);
			
			DAO_Notification::clearCountCache($notify_worker_id);
		}
		
		return $notification_id;
	}
	
	/*
	 * Action: Create Task
	 */
	
	static function renderActionCreateTask() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('workers', DAO_Worker::getAll());
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_create_task.tpl');
	}
	
	static function runActionCreateTask($params, $values, $context=null, $context_id=null) {
		$due_date = intval(@strtotime($params['due_date']));
	
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$title = $tpl_builder->build($params['title'], $values);
		$comment = $tpl_builder->build($params['comment'], $values);
		
		$fields = array(
			DAO_Task::TITLE => $title,
			DAO_Task::UPDATED_DATE => time(),
			DAO_Task::DUE_DATE => $due_date,
		);
		$task_id = DAO_Task::create($fields);
		
		// Comment content
		if(!empty($comment)) {
			$fields = array(
				DAO_Comment::ADDRESS_ID => 0, // [TODO] ???
				DAO_Comment::COMMENT => $comment,
				DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TASK,
				DAO_Comment::CONTEXT_ID => $task_id,
				DAO_Comment::CREATED => time(),
			);
			DAO_Comment::create($fields);
		}
		
		// Watchers
		if(isset($params['worker_id']) && !empty($params['worker_id']))
			CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TASK, $task_id, $params['worker_id']);
		
		// [TODO] Notify

		// Connection
		if(!empty($context) && !empty($context_id))
			DAO_ContextLink::setLink(CerberusContexts::CONTEXT_TASK, $task_id, $context, $context_id);

		return $task_id;
	}
	
	/*
	 * Action: Create Ticket
	 */
	
	static function renderActionCreateTicket() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('groups', DAO_Group::getAll());
		$tpl->assign('workers', DAO_Worker::getAll());
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_create_ticket.tpl');
	}
	
	static function runActionCreateTicket($params, $values) {
		@$group_id = $params['group_id'];
		
		if(null == ($group = DAO_Group::get($group_id)))
			return;
		
		$group_replyto = $group->getReplyTo();
			
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$requesters = $tpl_builder->build($params['requesters'], $values);
		$subject = $tpl_builder->build($params['subject'], $values);
		$content = $tpl_builder->build($params['content'], $values);
				
//		@$dont_send = $properties['dont_send'];
//		@$closed = $properties['closed'];
//		@$move_bucket = $properties['move_bucket'];
//		@$ticket_reopen = $properties['ticket_reopen'];

		$message = new CerberusParserMessage();
		$message->headers['date'] = date('r'); 
		$message->headers['to'] = $group_replyto->email;
		$message->headers['subject'] = $subject;
		$message->headers['message-id'] = CerberusApplication::generateMessageId();
		
		// Sender
		$fromList = imap_rfc822_parse_adrlist(rtrim($requesters,', '),'');
		
		if(empty($fromList) || !is_array($fromList)) {
			return; // abort with message
		}
		$from = array_shift($fromList);
		$from_address = $from->mailbox . '@' . $from->host;
		$message->headers['from'] = $from_address;

		$message->body = sprintf(
			"(... This message was manually created by a virtual assistant on behalf of the requesters ...)\r\n"
		);

		// [TODO] Custom fields
		
		// Parse
		$ticket_id = CerberusParser::parseMessage($message);
		$ticket = DAO_Ticket::get($ticket_id);
		
		// Add additional requesters to ticket
		if(is_array($fromList) && !empty($fromList))
		foreach($fromList as $requester) {
			if(empty($requester))
				continue;
			$host = empty($requester->host) ? 'localhost' : $requester->host;
			DAO_Ticket::createRequester($requester->mailbox . '@' . $host, $ticket_id);
		}
		
		// Worker reply
		$properties = array(
		    'message_id' => $ticket->first_message_id,
		    'ticket_id' => $ticket_id,
		    'subject' => $subject,
		    'content' => $content,
		    //'closed' => $closed,
		    //'bucket_id' => $move_bucket,
		    //'ticket_reopen' => $ticket_reopen,
		    'agent_id' => 0, //$active_worker->id,
			//'dont_send' => (false==$send_to_requesters),
		);
		
		CerberusMail::sendTicketMessage($properties);
		
		return $ticket_id;
	}
	
	/*
	 * Action: Send Email
	 */
	
	function renderActionSendEmail() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_send_email.tpl');
	}
	
	function runActionSendEmail($params, $values) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();

		$to = $params['to'];
		$subject = $tpl_builder->build($params['subject'], $values);
		$content = $tpl_builder->build($params['content'], $values);

		CerberusMail::quickSend(
			$to,
			$subject,
			$content
		);
	}
	
	/*
	 * Action: Relay Email
	 */
	
	function renderActionRelayEmail($filter_to_worker_ids=array()) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$addresses = DAO_AddressToWorker::getAll();

		// Filter?
		if(!empty($filter_to_worker_ids)) {
			foreach($addresses as $k => $v) {
				if(!in_array($v->worker_id, $filter_to_worker_ids))
					unset($addresses[$k]);
			}
		}
		$tpl->assign('addresses', $addresses);
		
		$tpl->assign('default_content',
<<< EOL
## Relayed from {{ticket_url}}
## Your reply to this message will be broadcast to the requesters. 
## Instructions: http://wiki.cerb5.com/wiki/Email_Relay
##
{{content}}
EOL
		);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_relay_email.tpl');
	}
	
	// [TODO] Eventually for reuse we'll need to change this
	function runActionRelayEmail($params, $values, $context, $context_id) {
		$logger = DevblocksPlatform::getConsoleLog('Attendant');
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();

		$mail_service = DevblocksPlatform::getMailService();
		$mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
		
		$bucket_id = intval(@$values['ticket_bucket_id']);
		
		if(!isset($values['group_id']) || null == ($group = DAO_Group::get($values['group_id']))) {
			$logger->error("Can't load the ticket's group. Aborting action.");
			return;
		}
		
		$replyto = $group->getReplyTo($bucket_id);
		$relay_list = $params['to'];
		
		// Attachments
		$attachment_data = array();
		if(isset($values['id']) && !empty($values['id'])) {
			if(isset($params['include_attachments']) && !empty($params['include_attachments'])) {
				$attachment_data = DAO_AttachmentLink::getLinksAndAttachments(CerberusContexts::CONTEXT_MESSAGE, $values['id']);
			}
		}
		
		if(is_array($relay_list))
		foreach($relay_list as $to) {
			try {
				// [TODO] Cache
				if(null == ($worker_address = DAO_AddressToWorker::getByAddress($to)))
					continue;
					
				if(null == ($worker = DAO_Worker::get($worker_address->worker_id)))
					continue;
				
				$mail = $mail_service->createMessage();
				
				$mail->setTo(array($worker_address->address));
	
				$headers = $mail->getHeaders(); /* @var $headers Swift_Mime_Header */

				if(isset($values['sender_full_name']) && !empty($values['sender_full_name'])) {
					$mail->setFrom($values['sender_address'], $values['sender_full_name']);
				} else {
					$mail->setFrom($values['sender_address']);
				}
				
				$replyto_personal = $replyto->getReplyPersonal($worker);
				if(!empty($replyto_personal)) {
					$mail->setReplyTo($replyto->email, $replyto_personal);
				} else {
					$mail->setReplyTo($replyto->email);
				}
				
				if(!isset($params['subject']) || empty($params['subject'])) {
					$mail->setSubject($values['ticket_subject']);
				} else {
					$subject = $tpl_builder->build($params['subject'], $values);
					$mail->setSubject($subject);
				}
	
				// Find the owner of this address and sign it.
				$sign = substr(md5($context.$context_id.$worker->pass),8,8);
				
				$headers->removeAll('message-id');
				$headers->addTextHeader('Message-Id', sprintf("<%s_%d_%d_%s@cerb5>", $context, $context_id, time(), $sign));
				$headers->addTextHeader('X-CerberusRedirect','1');
	
				$content = $tpl_builder->build($params['content'], $values);
				
				$mail->setBody($content);
				
				// Files
				if(!empty($attachment_data) && isset($attachment_data['attachments']) && !empty($attachment_data['attachments'])) {
					foreach($attachment_data['attachments'] as $file_id => $file) { /* @var $file Model_Attachment */
						if(false !== ($fp = DevblocksPlatform::getTempFile())) {
							if(false !== $file->getFileContents($fp)) {
								$attach = Swift_Attachment::fromPath(DevblocksPlatform::getTempFileInfo($fp), $file->mime_type);
								$attach->setFilename($file->display_name);
								$mail->attach($attach);
								fclose($fp);
							}
						}
					}
					
				}
				
				$result = $mailer->send($mail);
				unset($mail);
				
				if(!$result) {
					return false;
				}			
				
			} catch (Exception $e) {
				
			}
		}
	}
};

abstract class Extension_DevblocksEventCondition extends DevblocksExtension {
	public static function getAll($as_instances=false) {
		$extensions = DevblocksPlatform::getExtensions('devblocks.event.condition', $as_instances);
		if($as_instances)
			uasort($extensions, create_function('$a, $b', "return strcasecmp(\$a->manifest->params['label'],\$b->manifest->params['label']);\n"));
		else
			uasort($extensions, create_function('$a, $b', "return strcasecmp(\$a->params['label'],\$b->params['label']);\n"));
		return $extensions;
	}
	
	abstract function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null);
	abstract function run($token, Model_TriggerEvent $trigger, $params, $values);
}

abstract class Extension_DevblocksEventAction extends DevblocksExtension {
	public static function getAll($as_instances=false) {
		$extensions = DevblocksPlatform::getExtensions('devblocks.event.action', $as_instances);
		if($as_instances)
			uasort($extensions, create_function('$a, $b', "return strcasecmp(\$a->manifest->params['label'],\$b->manifest->params['label']);\n"));
		else
			uasort($extensions, create_function('$a, $b', "return strcasecmp(\$a->params['label'],\$b->params['label']);\n"));
		return $extensions;
	}
	
	abstract function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null);
	abstract function run($token, Model_TriggerEvent $trigger, $params, &$values);
}

abstract class DevblocksHttpResponseListenerExtension extends DevblocksExtension {
	function run(DevblocksHttpResponse $request, Smarty $tpl) {
	}
};

abstract class Extension_DevblocksStorageEngine extends DevblocksExtension {
	protected $_options = array();

	abstract function renderConfig(Model_DevblocksStorageProfile $profile);
	abstract function saveConfig(Model_DevblocksStorageProfile $profile);
	abstract function testConfig();
	
	abstract function exists($namespace, $key);
	abstract function put($namespace, $id, $data);
	abstract function get($namespace, $key, &$fp=null);
	abstract function delete($namespace, $key);
	
	public function setOptions($options=array()) {
		if(is_array($options))
			$this->_options = $options;
	}

	protected function escapeNamespace($namespace) {
		return strtolower(DevblocksPlatform::strAlphaNum($namespace, '\_'));
	}
};

abstract class Extension_DevblocksStorageSchema extends DevblocksExtension {
	abstract function render();
	abstract function renderConfig();
	abstract function saveConfig();
	
	public static function getActiveStorageProfile() {}

	public static function get($object, &$fp=null) {}
	public static function put($id, $contents, $profile=null) {}
	public static function delete($ids) {}
	public static function archive($stop_time=null) {}
	public static function unarchive($stop_time=null) {}
	
	protected function _stats($table_name) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$stats = array();
		
		$results = $db->GetArray(sprintf("SELECT storage_extension, storage_profile_id, count(id) as hits, sum(storage_size) as bytes FROM %s GROUP BY storage_extension, storage_profile_id ORDER BY storage_extension",
			$table_name
		));
		foreach($results as $result) {
			$stats[$result['storage_extension'].':'.intval($result['storage_profile_id'])] = array(
				'storage_extension' => $result['storage_extension'],
				'storage_profile_id' => $result['storage_profile_id'],
				'count' => intval($result['hits']),
				'bytes' => intval($result['bytes']),
			);
		}
		
		return $stats;
	}
	
};

abstract class DevblocksControllerExtension extends DevblocksExtension implements DevblocksHttpRequestHandler {
	public function handleRequest(DevblocksHttpRequest $request) {}
	public function writeResponse(DevblocksHttpResponse $response) {}
};

abstract class DevblocksEventListenerExtension extends DevblocksExtension {
    /**
     * @param Model_DevblocksEvent $event
     */
    function handleEvent(Model_DevblocksEvent $event) {}
};

interface DevblocksHttpRequestHandler {
	/**
	 * @param DevblocksHttpRequest
	 * @return DevblocksHttpResponse
	 */
	public function handleRequest(DevblocksHttpRequest $request);
	public function writeResponse(DevblocksHttpResponse $response);
}

class DevblocksHttpRequest extends DevblocksHttpIO {
	/**
	 * @param array $path
	 */
	function __construct($path, $query=array()) {
		parent::__construct($path, $query);
	}
}

class DevblocksHttpResponse extends DevblocksHttpIO {
	/**
	 * @param array $path
	 */
	function __construct($path, $query=array()) {
		parent::__construct($path, $query);
	}
}

abstract class DevblocksHttpIO {
	public $path = array();
	public $query = array();
	
	/**
	 * Enter description here...
	 *
	 * @param array $path
	 */
	function __construct($path,$query=array()) {
		$this->path = $path;
		$this->query = $query;
	}
}
