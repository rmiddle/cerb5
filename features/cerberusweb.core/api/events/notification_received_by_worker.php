<?php
class Event_NotificationReceivedByWorker extends Extension_DevblocksEvent {
	const ID = 'event.notification.received.worker';
	
	static function trigger($notification_id, $worker_id) {
		$events = DevblocksPlatform::getEventService();
		$events->trigger(
	        new Model_DevblocksEvent(
	            self::ID,
                array(
                    'notification_id' => $notification_id,
                	'_whisper' => array(
                		CerberusContexts::CONTEXT_WORKER => array($worker_id),
                	),
                )
            )
		);
	} 
	
	/**
	 * 
	 * @param integer $notification_id
	 * @param integer $worker_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($notification_id=null, $worker_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($notification_id)) {
			// Pull the latest ticket
			list($results) = DAO_Notification::search(
				//array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Notification::WORKER_ID,'=',$active_worker->id),
				),
				10,
				0,
				SearchFields_Notification::CREATED_DATE,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$notification_id = $result[SearchFields_Notification::ID];
			$worker_id = $active_worker->id;
		}
		
		return new Model_DevblocksEvent(
			self::ID,
			array(
				'notification_id' => $notification_id,
				'worker_id' => $worker_id,
			)
		);
	}	
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		@$notification_id = $event_model->params['notification_id']; 
		
		$labels = array();
		$values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_NOTIFICATION, $notification_id, $labels, $values, null, true);
		
		$this->setLabels($labels);
		$this->setValues($values);
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		// [TODO] Move this into snippets somehow
		$types = array(
			'created|date' => Model_CustomField::TYPE_DATE,
			'message' => Model_CustomField::TYPE_SINGLE_LINE,
			'is_read' => Model_CustomField::TYPE_CHECKBOX,
			'url' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_title' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_num_nonspam' => Model_CustomField::TYPE_NUMBER,
			'assignee_address_num_spam' => Model_CustomField::TYPE_NUMBER,
			'assignee_address_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'assignee_address_org_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_created' => Model_CustomField::TYPE_DATE,
			'assignee_address_org_city' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_country' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_province' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_postal' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_street' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_website' => Model_CustomField::TYPE_SINGLE_LINE,
		);

		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;		
	}
	
	function renderConditionExtension($token, $trigger, $params=array(), $seq=null) {
		$conditions = $this->getConditions();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		//$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_bool.tpl');
	}
	
	function runConditionExtension($token, $trigger, $params, $values) {
		$pass = true;
		
		switch($token) {
			default:
				$pass = false;
				//var_dump('unimplemented');
				break;
		}
		
		return $pass;
	}
	
	function getActionExtensions() { // $id
		$actions = array(
			'send_email_owner' => array('label' => 'Send email to me'),
			'create_task' => array('label' =>'Create a task'),
		);
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
			case 'send_email_owner':
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$addresses = DAO_AddressToWorker::getByWorker($trigger->owner_context_id);
				$tpl->assign('addresses', $addresses);
				
				$tpl->display('devblocks:cerberusweb.core::events/notification_received_by_owner/action_send_email_owner.tpl');
				break;
				
			case 'create_task':
				DevblocksEventHelper::renderActionCreateTask();
				break;
		}
			
		//$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_string.tpl');
	}
	
	function runActionExtension($token, $trigger, $params, &$values) {
		@$notification_id = $values['id'];

		if(empty($notification_id))
			return;
		
		switch($token) {
			case 'send_email_owner':
				$to = array();
				
				if(isset($params['to'])) {
					$to = $params['to'];
					
				} else {
					// Default to worker email address
					@$to = array($values['assignee_address_address']);
				}
				
				if(
					empty($to)
					|| !isset($params['subject'])
					|| !isset($params['content'])
				)
					break;
				
				// Translate message tokens
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$subject = strtr($tpl_builder->build($params['subject'], $values), "\r\n", ' '); // no CRLF
				$content = $tpl_builder->build($params['content'], $values);

				if(is_array($to))
				foreach($to as $to_addy) {
					CerberusMail::quickSend(
						$to_addy,
						$subject,
						$content
					);
				}
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $values); //, CerberusContexts::CONTEXT_NOTIFICATION, $values['id']
				break;				
		}
	}
	
};