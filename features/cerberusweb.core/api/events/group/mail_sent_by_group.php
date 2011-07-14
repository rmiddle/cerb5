<?php
class Event_MailSentByGroup extends Extension_DevblocksEvent {
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
		 * Message
		 */
		
//		@$message = $event_model->params['message'];
//		$message_labels = array();
//		$message_values = array();
//		CerberusContexts::getContext(CerberusContexts::CONTEXT_MESSAGE, $message, $message_labels, $message_values, null, true);
//
//		// Fill in some custom values
//		//$values['sender_is_worker'] = (!empty($values['worker_id'])) ? 1 : 0;
//		
//			// Merge
//			CerberusContexts::merge(
//				'message_',
//				'',
//				$message_labels,
//				$message_values,
//				$labels,
//				$values
//			);
		
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
		
//		$labels['header'] = 'Message header';
//		$labels['sender_is_worker'] = 'Message sender is a worker';
//		$labels['ticket_has_owner'] = 'Ticket has owner';
//		$labels['ticket_watcher_count'] = 'Ticket watcher count';
		
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
		
//			'ticket_has_owner' => null,
//			'ticket_watcher_count' => null,
//		
//			'header' => null,
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
//			case 'ticket_has_owner':
//				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_bool.tpl');
//				break;
//			case 'ticket_watcher_count':
//				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_number.tpl');
//				break;
			case 'ticket_spam_score':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_score.tpl');
				break;
			case 'ticket_spam_training':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_training.tpl');
				break;
			case 'ticket_status':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_status.tpl');
				break;
//			// [TODO] Internalize
//			case 'header':
//				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_header.tpl');
//				break;
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
//				
//			case 'ticket_watcher_count':
//				$not = (substr($params['oper'],0,1) == '!');
//				$oper = ltrim($params['oper'],'!');
//				@$ticket_id = $values['ticket_id'];
//
//				$watchers = CerberusContexts::getWatchers(CerberusContexts::CONTEXT_TICKET, $ticket_id);
//				$value = count($watchers);
//				
//				switch($oper) {
//					case 'is':
//						$pass = intval($value)==intval($params['value']);
//						break;
//					case 'gt':
//						$pass = intval($value) > intval($params['value']);
//						break;
//					case 'lt':
//						$pass = intval($value) < intval($params['value']);
//						break;
//				}
//				
//				$pass = ($not) ? !$pass : $pass;
//				break;
			
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
				
//			case 'header':
//				$not = (substr($params['oper'],0,1) == '!');
//				$oper = ltrim($params['oper'],'!');
//				@$header = $params['header'];
//				@$param_value = $params['value'];
//				
//				// Lazy load
//				$value = DAO_MessageHeader::getOne($values['id'], $header);
//				
//				// Operators
//				switch($oper) {
//					case 'is':
//						$pass = (0==strcasecmp($value,$param_value));
//						break;
//					case 'like':
//						$regexp = DevblocksPlatform::strToRegExp($param_value);
//						$pass = @preg_match($regexp, $value);
//						break;
//					case 'contains':
//						$pass = (false !== stripos($value, $param_value)) ? true : false;
//						break;
//					case 'regexp':
//						$pass = @preg_match($param_value, $value);
//						break;
//					default:
//						$pass = false;
//						break;
//				}
//				
//				$pass = ($not) ? !$pass : $pass;
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
				'append_to_content' => array('label' =>'Append text to message content'),
				'prepend_to_content' => array('label' =>'Prepend text to message content'),
				'replace_content' => array('label' =>'Replace text in message content'),
//				'add_watchers' => array('label' =>'Add watchers'),
//				'create_comment' => array('label' =>'Create a comment'),
				'create_notification' => array('label' =>'Create a notification'),
//				'create_task' => array('label' =>'Create a task'),
//				'create_ticket' => array('label' =>'Create a ticket'),
//				'move_to_bucket' => array('label' => 'Move to bucket'),
//				'move_to_group' => array('label' => 'Move to group'),
//				'relay_email' => array('label' => 'Relay to external email'),
//				'send_email' => array('label' => 'Send email'),
//				'send_email_recipients' => array('label' => 'Reply to recipients'),
//				'set_owner' => array('label' =>'Set owner'),
//				'set_spam_training' => array('label' => 'Set spam training'),
//				'set_status' => array('label' => 'Set status'),
//				'set_subject' => array('label' => 'Set subject'),
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
				$tpl->display('devblocks:cerberusweb.core::events/mail_sent_by_group/action_add_content.tpl');
				break;
				
			case 'replace_content':
				$tpl->display('devblocks:cerberusweb.core::events/mail_sent_by_group/action_replace_content.tpl');
				break;

//			case 'set_owner':
//				DevblocksEventHelper::renderActionSetTicketOwner();
//				break;
//				
//			case 'add_watchers':
//				DevblocksEventHelper::renderActionAddWatchers();
//				break;
//				
//			case 'relay_email':
//				// [TODO] Filter to group members
//				$group = DAO_Group::get($trigger->owner_context_id);
//				DevblocksEventHelper::renderActionRelayEmail(array_keys($group->getMembers()));
//				break;
//				
//			case 'send_email':
//				DevblocksEventHelper::renderActionSendEmail();
//				break;
//				
//			case 'send_email_recipients':
//				// [TODO] Share
//				$tpl->assign('workers', DAO_Worker::getAll());
//				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_owner/action_send_email_recipients.tpl');
//				break;
//				
//			case 'create_comment':
//				DevblocksEventHelper::renderActionCreateComment();
//				break;
				
			case 'create_notification':
				DevblocksEventHelper::renderActionCreateNotification();
				break;
				
//			case 'create_task':
//				DevblocksEventHelper::renderActionCreateTask();
//				break;
//				
//			case 'create_ticket':
//				DevblocksEventHelper::renderActionCreateTicket();
//				break;
//				
//			case 'set_spam_training':
//				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/action_set_spam_training.tpl');
//				break;
//				
//			case 'set_status':
//				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/action_set_status.tpl');
//				break;
//				
//			case 'set_subject':
//				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_string.tpl');
//				break;
//				
//			case 'move_to_bucket':
//				// [TODO] Share
//				$buckets = DAO_Bucket::getByTeam($trigger->owner_context_id);
//				$tpl->assign('buckets', $buckets);
//				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/action_move_to_bucket.tpl');
//				break;
//				
//			case 'move_to_group':
//				// [TODO] Use trigger cache
//				$groups = DAO_Group::getAll();
//				$tpl->assign('groups', $groups);
//				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/action_move_to_group.tpl');
//				break;

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
			
//			case 'set_owner':
//				DevblocksEventHelper::runActionSetTicketOwner($params, $values, $ticket_id);
//				break;
//				
//			case 'add_watchers':
//				DevblocksEventHelper::runActionAddWatchers($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
//				break;
//			
//			case 'send_email':
//				DevblocksEventHelper::runActionSendEmail($params, $values);
//				break;
//				
//			case 'relay_email':
//				DevblocksEventHelper::runActionRelayEmail($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
//				break;
//				
//			case 'send_email_recipients':
//				// Translate message tokens
//				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
//				$content = $tpl_builder->build($params['content'], $values);
//				
//				$properties = array(
//					'ticket_id' => $ticket_id,
//					'message_id' => $message_id,
//					'content' => $content,
//					'agent_id' => 0, //$worker_id,
//				);
//				
//				if(isset($params['is_autoreply']) && !empty($params['is_autoreply']))
//					$properties['is_autoreply'] = true;
//				
//				CerberusMail::sendTicketMessage($properties);
//				break;
//				
//			case 'create_comment':
//				DevblocksEventHelper::runActionCreateComment($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
//				break;

			case 'create_notification':
				$url_writer = DevblocksPlatform::getUrlService();
				$url = $url_writer->writeNoProxy('c=display&id='.$values['ticket_mask'], true);
				
				DevblocksEventHelper::runActionCreateNotification($params, $values, $url);
				break;

//			case 'create_task':
//				DevblocksEventHelper::runActionCreateTask($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
//				break;
//
//			case 'create_ticket':
//				DevblocksEventHelper::runActionCreateTicket($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
//				break;
//
//			case 'set_spam_training':
//				@$to_training = $params['value'];
//				@$current_training = $values['ticket_spam_training'];
//
//				if($to_training == $current_training)
//					break;
//					
//				switch($to_training) {
//					case 'S':
//						CerberusBayes::markTicketAsSpam($ticket_id);
//						$values['ticket_spam_training'] = $to_training;
//						break;
//					case 'N':
//						CerberusBayes::markTicketAsNotSpam($ticket_id);
//						$values['ticket_spam_training'] = $to_training;
//						break;
//				}
//				break;
//				
//			case 'set_status':
//				@$to_status = $params['status'];
//				@$current_status = $values['ticket_status'];
//				
//				if($to_status == $current_status)
//					break;
//					
//				// Status
//				switch($to_status) {
//					case 'open':
//						$fields = array(
//							DAO_Ticket::IS_WAITING => 0,
//							DAO_Ticket::IS_CLOSED => 0,
//							DAO_Ticket::IS_DELETED => 0,
//						);
//						break;
//					case 'waiting':
//						$fields = array(
//							DAO_Ticket::IS_WAITING => 1,
//							DAO_Ticket::IS_CLOSED => 0,
//							DAO_Ticket::IS_DELETED => 0,
//						);
//						break;
//					case 'closed':
//						$fields = array(
//							DAO_Ticket::IS_WAITING => 0,
//							DAO_Ticket::IS_CLOSED => 1,
//							DAO_Ticket::IS_DELETED => 0,
//						);
//						break;
//					case 'deleted':
//						$fields = array(
//							DAO_Ticket::IS_WAITING => 0,
//							DAO_Ticket::IS_CLOSED => 1,
//							DAO_Ticket::IS_DELETED => 1,
//						);
//						break;
//					default:
//						$fields = array();
//						break;
//				}
//				if(!empty($fields)) {
//					DAO_Ticket::update($ticket_id, $fields);
//					$values['ticket_status'] = $to_status;
//				}
//				break;
//				
//			case 'set_subject':
//				DAO_Ticket::update($ticket_id,array(
//					DAO_Ticket::SUBJECT => $params['value'],
//				));
//				$values['ticket_subject'] = $params['value'];
//				break;
//				
//			case 'move_to_group':
//				@$to_group_id = intval($params['group_id']);
//				@$current_group_id = intval($values['group_id']);
//				$groups = DAO_Group::getAll();
//				
//				// Don't trigger a move event into the same bucket.
//				if($to_group_id == $current_group_id)
//					break;
//				
//				if(!empty($to_group_id) && !isset($groups[$to_group_id]))
//					break;
//					
//				// Move
//				DAO_Ticket::update($ticket_id, array(
//					DAO_Ticket::TEAM_ID => $to_group_id, 
//					DAO_Ticket::CATEGORY_ID => 0, 
//				));
//				
//				// Pull group context + merge
//				$merge_token_labels = array();
//				$merge_token_values = array();
//				$labels = $this->getLabels();
//				CerberusContexts::getContext(CerberusContexts::CONTEXT_GROUP, $to_group_id, $merge_token_labels, $merge_token_values, '', true);
//		
//				CerberusContexts::merge(
//					'group_',
//					'Group:',
//					$merge_token_labels,
//					$merge_token_values,
//					$labels,
//					$values
//				);
//				break;				
//				
//			case 'move_to_bucket':
//				@$to_bucket_id = intval($params['bucket_id']);
//				@$current_bucket_id = intval($values['ticket_bucket_id']);
//				$buckets = DAO_Bucket::getAll();
//				
//				// Don't trigger a move event into the same bucket.
//				if($to_bucket_id == $current_bucket_id)
//					break;
//				
//				if(!empty($to_bucket_id) && !isset($buckets[$to_bucket_id]))
//					break;
//					
//				// Move
//				DAO_Ticket::update($ticket_id, array(
//					DAO_Ticket::CATEGORY_ID => $to_bucket_id, 
//				));
//				$values['ticket_bucket_id'] = $to_bucket_id;
//				break;

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