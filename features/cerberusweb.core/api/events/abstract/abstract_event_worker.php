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

abstract class AbstractEvent_Worker extends Extension_DevblocksEvent {
	protected $_event_id = null; // override

	/**
	 * 
	 * @param integer $worker_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($worker_id=null) {
		
		if(empty($worker_id)) {
			// Pull the latest record
			list($results) = DAO_Worker::search(
				array(),
				array(
					//new DevblocksSearchCriteria(SearchFields_Task::IS_CLOSED,'=',0),
				),
				10,
				0,
				SearchFields_Worker::ID,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$worker_id = $result[SearchFields_Worker::ID];
		}
		
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'worker_id' => $worker_id,
			)
		);
	}	
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		$labels = array();
		$values = array();

		/**
		 * Worker
		 */
		
		@$worker_id = $event_model->params['worker_id']; 
		$merge_labels = array();
		$merge_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_id, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'worker_',
				'',
				$merge_labels,
				$merge_values,
				$labels,
				$values
			);

		/**
		 * Return
		 */

		$this->setLabels($labels);
		$this->setValues($values);		
	}
	
	function getValuesContexts($trigger) {
		$vals = array(
			'worker_id' => array(
				'label' => 'Worker',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
			'worker_address_id' => array(
				'label' => 'Worker email',
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			),
			'worker_address_org_id' => array(
				'label' => 'Worker org',
				'context' => CerberusContexts::CONTEXT_ORG,
			),
		);
		
		$vars = parent::getValuesContexts($trigger);
		
		$vals_to_ctx = array_merge($vals, $vars);
		asort($vals_to_ctx);
		
		return $vals_to_ctx;
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		$types = array(
			'worker_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'worker_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'worker_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'worker_title' => Model_CustomField::TYPE_SINGLE_LINE,
			
			'worker_address_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'worker_address_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'worker_address_num_nonspam' => Model_CustomField::TYPE_NUMBER,
			'worker_address_num_spam' => Model_CustomField::TYPE_NUMBER,
			
			'worker_address_org_city' => Model_CustomField::TYPE_SINGLE_LINE,
			'worker_address_org_country' => Model_CustomField::TYPE_SINGLE_LINE,
			'worker_address_org_created|date' => Model_CustomField::TYPE_DATE,
			'worker_address_org_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'worker_address_org_phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'worker_address_org_postal' => Model_CustomField::TYPE_SINGLE_LINE,
			'worker_address_org_province' => Model_CustomField::TYPE_SINGLE_LINE,
			'worker_address_org_street' => Model_CustomField::TYPE_SINGLE_LINE,
			'worker_address_org_website' => Model_CustomField::TYPE_URL,
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
	
	function runConditionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($token) {
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
				'schedule_behavior' => array('label' => 'Schedule behavior'),
				'send_email' => array('label' => 'Send email'),
				'set_email_links' => array('label' => 'Set links on email'),
				'set_email_org_links' => array('label' => 'Set links on organization'),
				'unschedule_behavior' => array('label' => 'Unschedule behavior'),
			)
			+ DevblocksEventHelper::getActionCustomFields(CerberusContexts::CONTEXT_WORKER)
			;
			
		return $actions;
	}
	
	function renderActionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels($trigger);
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'add_watchers':
				DevblocksEventHelper::renderActionAddWatchers($trigger);
				break;
			
			case 'create_comment':
				DevblocksEventHelper::renderActionCreateComment($trigger);
				break;
				
			case 'create_notification':
				DevblocksEventHelper::renderActionCreateNotification($trigger);
				break;
				
			case 'create_task':
				DevblocksEventHelper::renderActionCreateTask($trigger);
				break;
				
			case 'create_ticket':
				DevblocksEventHelper::renderActionCreateTicket($trigger);
				break;
				
			case 'schedule_behavior':
				$dates = array();
				$conditions = $this->getConditions($trigger);
				foreach($conditions as $key => $data) {
					if($data['type'] == Model_CustomField::TYPE_DATE)
					$dates[$key] = $data['label'];
				}
				$tpl->assign('dates', $dates);
			
				DevblocksEventHelper::renderActionScheduleBehavior($trigger);
				break;

			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail($trigger);
				break;

			case 'unschedule_behavior':
				DevblocksEventHelper::renderActionUnscheduleBehavior($trigger);
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
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$worker_id = $dict->worker_id;

		if(empty($worker_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				return DevblocksEventHelper::simulateActionAddWatchers($params, $dict, 'worker_id');
				break;
			case 'create_comment':
				return DevblocksEventHelper::simulateActionCreateComment($params, $dict, 'worker_id');
				break;
			case 'create_notification':
				return DevblocksEventHelper::simulateActionCreateNotification($params, $dict, 'worker_id');
				break;
			case 'create_task':
				return DevblocksEventHelper::simulateActionCreateTask($params, $dict, 'worker_id');
				break;
			case 'create_ticket':
				return DevblocksEventHelper::simulateActionCreateTicket($params, $dict);
				break;
			case 'schedule_behavior':
				return DevblocksEventHelper::simulateActionScheduleBehavior($params, $dict);
				break;
			case 'send_email':
				return DevblocksEventHelper::simulateActionSendEmail($params, $dict);
				break;
			case 'unschedule_behavior':
				return DevblocksEventHelper::simulateActionUnscheduleBehavior($params, $dict);
				break;
			default:
				if('set_cf_' == substr($token,0,7)) {
					$field_id = substr($token,7);
					$custom_field = DAO_CustomField::get($field_id);
					$context = null;
					$context_id = null;
					
					// If different types of custom fields, need to find the proper context_id
					switch($custom_field->context) {
						case CerberusContexts::CONTEXT_WORKER:
							$context = $custom_field->context;
							$context_id = $worker_id;
							break;
					}
					
					if(!empty($context) && !empty($context_id))
						return DevblocksEventHelper::simulateActionSetCustomField($custom_field, 'worker_custom', $params, $dict, $context, $context_id);
				}
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$worker_id = $dict->worker_id;

		if(empty($worker_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				DevblocksEventHelper::runActionAddWatchers($params, $dict, 'worker_id');
				break;
			
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $dict, 'worker_id');
				break;
				
			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $dict, 'worker_id');
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $dict, 'worker_id');
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $dict);
				break;
				
			case 'schedule_behavior':
				DevblocksEventHelper::runActionScheduleBehavior($params, $dict);
				break;

			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $dict);
				break;
				
			case 'unschedule_behavior':
				DevblocksEventHelper::runActionUnscheduleBehavior($params, $dict);
				break;
				
			default:
				if('set_cf_' == substr($token,0,7)) {
					$field_id = substr($token,7);
					$custom_field = DAO_CustomField::get($field_id);
					$context = null;
					$context_id = null;
					
					// If different types of custom fields, need to find the proper context_id
					switch($custom_field->context) {
						case CerberusContexts::CONTEXT_WORKER:
							$context = $custom_field->context;
							$context_id = $worker_id;
							break;
					}
					
					if(!empty($context) && !empty($context_id))
						DevblocksEventHelper::runActionSetCustomField($custom_field, 'worker_custom', $params, $dict, $context, $context_id);
				}
				break;	
		}
	}
	
};