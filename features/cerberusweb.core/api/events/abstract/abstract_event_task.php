<?php
abstract class AbstractEvent_Task extends Extension_DevblocksEvent {
	protected $_event_id = null; // override

	/**
	 * 
	 * @param integer $task_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($task_id=null) {
		
		if(empty($task_id)) {
			// Pull the latest record
			list($results) = DAO_Task::search(
				array(),
				array(
					//new DevblocksSearchCriteria(SearchFields_Task::IS_CLOSED,'=',0),
				),
				10,
				0,
				SearchFields_Task::ID,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$opp_id = $result[SearchFields_Task::ID];
		}
		
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'task_id' => $task_id,
			)
		);
	}	
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		$labels = array();
		$values = array();

		/**
		 * Task
		 */
		
		@$task_id = $event_model->params['task_id']; 
		$task_labels = array();
		$task_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_TASK, $task_id, $task_labels, $task_values, null, true);

			// Merge
			CerberusContexts::merge(
				'task_',
				'',
				$task_labels,
				$task_values,
				$labels,
				$values
			);

		/**
		 * Return
		 */

		$this->setLabels($labels);
		$this->setValues($values);		
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
//		$labels['ticket_has_owner'] = 'Ticket has owner';
		
		$types = array(
			'task_is_completed' => Model_CustomField::TYPE_CHECKBOX,
			'task_completed|date' => Model_CustomField::TYPE_DATE,
			'task_due|date' => Model_CustomField::TYPE_DATE,
			'task_updated|date' => Model_CustomField::TYPE_DATE,
			'task_status' => Model_CustomField::TYPE_SINGLE_LINE,
			'task_title' => Model_CustomField::TYPE_SINGLE_LINE,
		
//			'ticket_has_owner' => null,
		);

		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;		
	}
	
	function renderConditionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($token) {
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $trigger, $params, $values) {
		$pass = true;
		
		switch($token) {
//			case 'ticket_has_owner':
//				$bool = $params['bool'];
//				@$value = $values['ticket_owner_id'];
//				$pass = ($bool == !empty($value));
//				break;
				
			default:
				$pass = false;
				break;
		}
		
		return $pass;
	}
	
	function getActionExtensions() {
		$actions = 
			array(
				'add_watchers' => array('label' =>'Add watchers'),
				'create_comment' => array('label' =>'Create a comment'),
				'create_notification' => array('label' =>'Create a notification'),
				'create_task' => array('label' =>'Create a task'),
				'create_ticket' => array('label' =>'Create a ticket'),
				'send_email' => array('label' => 'Send email'),
				'set_status' => array('label' => 'Set status'),
			)
			+ DevblocksEventHelper::getActionCustomFields(CerberusContexts::CONTEXT_TASK)
			;
			
		return $actions;
	}
	
	function renderActionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels();
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'add_watchers':
				DevblocksEventHelper::renderActionAddWatchers();
				break;
			
			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail();
				break;
				
			case 'create_comment':
				DevblocksEventHelper::renderActionCreateComment();
				break;
				
			case 'create_notification':
				DevblocksEventHelper::renderActionCreateNotification();
				break;
				
			case 'create_task':
				DevblocksEventHelper::renderActionCreateTask();
				break;
				
			case 'create_ticket':
				DevblocksEventHelper::renderActionCreateTicket();
				break;
				
			case 'set_status':
				$tpl->display('devblocks:cerberusweb.core::events/model/task/action_set_status.tpl');
				break;
				
			default:
				if('set_cf_' == substr($token,0,7)) {
					$field_id = substr($token,7);
					$custom_field = DAO_CustomField::get($field_id);
					DevblocksEventHelper::renderActionSetCustomField($custom_field);
				}
				break;
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');		
	}
	
	function runActionExtension($token, $trigger, $params, &$values) {
		@$task_id = $values['task_id'];

		if(empty($task_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				DevblocksEventHelper::runActionAddWatchers($params, $values, CerberusContexts::CONTEXT_TASK, $task_id);
				break;
			
			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $values);
				break;
				
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $values, CerberusContexts::CONTEXT_TASK, $task_id);
				break;
				
			case 'create_notification':
				$url_writer = DevblocksPlatform::getUrlService();
				$url = $url_writer->writeNoProxy('c=tasks&tab=display&id='.$values['task_id'], true);
				
				DevblocksEventHelper::runActionCreateNotification($params, $values, $url);
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $values, CerberusContexts::CONTEXT_TASK, $task_id);
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $values, CerberusContexts::CONTEXT_TASK, $task_id);
				break;
				
			case 'set_status':
				@$to_status = $params['status'];
				@$current_status = $values['task_status'];
				
				if($to_status == $current_status)
					break;
				
				$fields = array();
					
				switch($to_status) {
					case 'active':
						$fields = array(
							DAO_Task::IS_COMPLETED => 0,
							DAO_Task::COMPLETED_DATE => 0,
						);
						break;
					case 'completed':
						$fields = array(
							DAO_Task::IS_COMPLETED => 1,
							DAO_Task::COMPLETED_DATE => time(),
						);
						break;
				}
				
				if(!empty($fields)) {
					$values['status'] = $to_status;
					DAO_Task::update($task_id, $fields);
				}
				
				break;
				
			default:
				if('set_cf_' == substr($token,0,7)) {
					$field_id = substr($token,7);
					$custom_field = DAO_CustomField::get($field_id);
					$context = null;
					$context_id = null;
					
					// If different types of custom fields, need to find the proper context_id
					switch($custom_field->context) {
						case CerberusContexts::CONTEXT_TASK:
							$context = $custom_field->context;
							$context_id = $task_id;
							break;
					}
					
					if(!empty($context) && !empty($context_id))
						DevblocksEventHelper::runActionSetCustomField($custom_field, 'task_custom', $params, $values, $context, $context_id);
				}
				break;	
		}
	}
	
};