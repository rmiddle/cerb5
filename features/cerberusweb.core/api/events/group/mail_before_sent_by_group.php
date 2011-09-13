<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
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

class Event_MailBeforeSentByGroup extends Extension_DevblocksEvent {
	const ID = 'event.mail.sent.group';
	
	static function trigger(&$properties, Model_Message $message, Model_Ticket $ticket, Model_Group $group) {
		$events = DevblocksPlatform::getEventService();
		$events->trigger(
	        new Model_DevblocksEvent(
	            self::ID,
                array(
                    'properties' => &$properties,
                    'message' => $message,
                    'ticket' => $ticket,
                    'group' => $group,
                	'_whisper' => array(
                		CerberusContexts::CONTEXT_GROUP => array($group->id),
                	),
                )
            )
		);
	} 
	
	/**
	 * 
	 * @param array $properties
	 * @param Model_Message $message
	 * @param Model_Ticket $ticket
	 * @param Model_Group $group
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($properties=null, Model_Message $message=null, Model_Ticket $ticket=null, Model_Group $group=null) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($message)) {
			// Pull the latest ticket
			list($results) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0),
				),
				10,
				0,
				SearchFields_Ticket::TICKET_ID,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$message = DAO_Message::get($result[SearchFields_Ticket::TICKET_LAST_MESSAGE_ID]);
			$ticket = DAO_Ticket::get($result[SearchFields_Ticket::TICKET_ID]);
			$group = DAO_Group::get($result[SearchFields_Ticket::TICKET_TEAM_ID]);
		}
		
		$properties = array(
			'to' => 'customer@example.com',
			'cc' => 'boss@example.com',
			'bcc' => 'secret@example.com',
			'subject' => 'This is the subject',
			'ticket_reopen' => "+2 hours",
			'closed' => 2,
			'content' => "This is the message body\r\nOn more than one line.\r\n",
			'agent_id' => $active_worker->id,
		);
		
		$values['content'] =& $properties['content'];
		$values['to'] =& $properties['to'];
		$values['cc'] =& $properties['cc'];
		$values['bcc'] =& $properties['bcc'];
		$values['subject'] =& $properties['subject'];
		$values['waiting_until'] =& $properties['ticket_reopen'];
		$values['closed'] =& $properties['closed'];
		$values['worker_id'] =& $properties['agent_id'];
		
		return new Model_DevblocksEvent(
			self::ID,
			array(
				'properties' => $properties,
				'message' => $message,
				'ticket' => $ticket,
				'group' => $group,
			)
		);
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		$labels = array();
		$values = array();
		
		/**
		 * Properties
		 */
		@$properties =& $event_model->params['properties'];
		$prefix = 'Sent message ';
		
		$labels['content'] = $prefix.'content';
		$values['content'] =& $properties['content'];
		
		$labels['to'] = $prefix.'to';
		$values['to'] =& $properties['to'];
		
		$labels['cc'] = $prefix.'cc';
		$values['cc'] =& $properties['cc'];
		
		$labels['bcc'] = $prefix.'bcc';
		$values['bcc'] =& $properties['bcc'];

		$labels['subject'] = $prefix.'subject';
		$values['subject'] =& $properties['subject'];
		
		//$labels['waiting_until'] = $prefix.'waiting until';
		$values['waiting_until'] =& $properties['ticket_reopen'];
		
		//$labels['closed'] = $prefix.'is closed';
		$values['closed'] =& $properties['closed'];
		
		//$labels['worker_id'] = $prefix.'worker id';
		$values['worker_id'] =& $properties['agent_id'];
		
		/**
		 * Ticket
		 */
		
		@$ticket = $event_model->params['ticket']; /* @var $ticket Model_Ticket */
		@$ticket_id = $ticket->id;
		
		$ticket_labels = array();
		$ticket_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, $ticket_id, $ticket_labels, $ticket_values, null, true);
		
			// Fill some custom values

			// Clear dupe content
			CerberusContexts::scrubTokensWithRegexp(
				$ticket_labels,
				$ticket_values,
				array(
					"#^initial_message_#",
					"#^latest_message_#",
					"#^group_#",
					//"#^id$#",
				)
			);
			
			// Merge
			CerberusContexts::merge(
				'ticket_',
				'',
				$ticket_labels,
				$ticket_values,
				$labels,
				$values
			);
			
		/**
		 * Group
		 */
		@$group = $event_model->params['group']; /* @var $group Model_Group */
		$group_labels = array();
		$group_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_GROUP, $group, $group_labels, $group_values, null, true);
				
			// Merge
			CerberusContexts::merge(
				'group_',
				'',
				$group_labels,
				$group_values,
				$labels,
				$values
			);
		
		/**
		 * Worker
		 */
		@$worker_id = $values['worker_id'];
		$worker_labels = array();
		$worker_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_id, $worker_labels, $worker_values, '', true);
				
			// Clear dupe content
			CerberusContexts::scrubTokensWithRegexp(
				$worker_labels,
				$worker_values,
				array(
					"#^address_org_#",
				)
			);
		
			// Merge
			CerberusContexts::merge(
				'worker_',
				'Worker ',
				$worker_labels,
				$worker_values,
				$labels,
				$values
			);

		/**
		 * Signature
		 */
		$labels['group_sig'] = 'Group signature';
		if(!empty($group) && !empty($ticket)) {
			if(null != ($worker = DAO_Worker::get($worker_id)))
				$values['group_sig'] = $group->getReplySignature($ticket->category_id, $worker);
		}
			
		/**
		 * Return
		 */

		$this->setLabels($labels);
		$this->setValues($values);		
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		$types = array(
			'bcc' => Model_CustomField::TYPE_SINGLE_LINE,
			'cc' => Model_CustomField::TYPE_SINGLE_LINE,
			'content' => Model_CustomField::TYPE_MULTI_LINE,
			'subject' => Model_CustomField::TYPE_SINGLE_LINE,
			'to' => Model_CustomField::TYPE_SINGLE_LINE,
		
			'group_name' => Model_CustomField::TYPE_SINGLE_LINE,
		
			'ticket_owner_address_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_title' => Model_CustomField::TYPE_SINGLE_LINE,
		
			"ticket_bucket_name|default('Inbox')" => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_created|date' => Model_CustomField::TYPE_DATE,
			'ticket_mask' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_spam_score' => null,
			'ticket_spam_training' => null,
			'ticket_status' => null,
			'ticket_subject' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_updated|date' => Model_CustomField::TYPE_DATE,
			'ticket_url' => Model_CustomField::TYPE_URL,
		
			'worker_address_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'worker_address_num_nonspam' => Model_CustomField::TYPE_NUMBER,
			'worker_address_num_spam' => Model_CustomField::TYPE_NUMBER,
			'worker_address_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'worker_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'worker_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'worker_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'worker_title' => Model_CustomField::TYPE_SINGLE_LINE,
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
			case 'ticket_spam_score':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_score.tpl');
				break;
			case 'ticket_spam_training':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_training.tpl');
				break;
			case 'ticket_status':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_status.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $trigger, $params, $values) {
		$pass = true;
		
		switch($token) {
			case 'ticket_spam_score':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$value = intval($values[$token] * 100);

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
				
				$pass = ($not) ? !$pass : $pass;
				break;
				
			case 'ticket_spam_training':
			case 'ticket_status':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$value = $values[$token];
				
				if(!isset($params['values']) || !is_array($params['values'])) {
					$pass = false;
					break;
				}
				
				switch($oper) {
					case 'in':
						$pass = false;
						foreach($params['values'] as $v) {
							if($v == $value) {
								$pass = true;
								break;
							}
						}
						break;
				}
				$pass = ($not) ? !$pass : $pass;
				break;
				
			default:
				$pass = false;
				break;
		}
		
		return $pass;
	}
	
	function getActionExtensions() {
		$actions = 
			array(
				'append_to_content' => array('label' =>'Append text to message content'),
				'prepend_to_content' => array('label' =>'Prepend text to message content'),
				'replace_content' => array('label' =>'Replace text in message content'),
				'create_notification' => array('label' =>'Create a notification'),
			)
			+ DevblocksEventHelper::getActionCustomFields(CerberusContexts::CONTEXT_TICKET)
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
			case 'append_to_content':
			case 'prepend_to_content':
				$tpl->display('devblocks:cerberusweb.core::events/mail_before_sent_by_group/action_add_content.tpl');
				break;
				
			case 'replace_content':
				$tpl->display('devblocks:cerberusweb.core::events/mail_before_sent_by_group/action_replace_content.tpl');
				break;

			case 'create_notification':
				DevblocksEventHelper::renderActionCreateNotification();
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
		@$ticket_id = $values['ticket_id'];

		if(empty($ticket_id))
			return;
		
		switch($token) {
			case 'append_to_content':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$values['content'] .= "\r\n" . $tpl_builder->build($params['content'], $values);
				break;
				
			case 'prepend_to_content':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$values['content'] = $tpl_builder->build($params['content'], $values) . "\r\n" . $values['content'];
				break;
				
			case 'replace_content':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$with = $tpl_builder->build($params['with'], $values);
				$values['content'] = str_replace($params['replace'], $with, $values['content']);
				break;
			
			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;

			default:
				if('set_cf_' == substr($token,0,7)) {
					$field_id = substr($token,7);
					$custom_field = DAO_CustomField::get($field_id);
					$context = null;
					$context_id = null;
					
					// If different types of custom fields, need to find the proper context_id
					switch($custom_field->context) {
						case CerberusContexts::CONTEXT_TICKET:
							$context = $custom_field->context;
							$context_id = $ticket_id;
							break;
					}
					
					if(!empty($context) && !empty($context_id))
						DevblocksEventHelper::runActionSetCustomField($custom_field, 'ticket_custom', $params, $values, $context, $context_id);
				}
				break;				
		}
	}
};