<?php
class UmScHistoryController extends Extension_UmScController {
	const PARAM_NEXT_ASSIGNED_TO = 'history.next_assigned_to';
	const PARAM_CF_SELECT = 'history.cf_select';
	
	function isVisible() {
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		return !empty($active_user);
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/templates/';
		
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		
		$stack = $response->path;
		array_shift($stack); // history
		$mask = array_shift($stack);
		
		if(empty($mask)) {
			
			// Open Tickets
			
			if(null == ($open_view = UmScAbstractViewLoader::getView('', 'sc_history_open'))) {
				$open_view = new UmSc_TicketHistoryView();
				$open_view->id = 'sc_history_open';
			}
			
			// Lock to current visitor and open tickets
			$open_view->params = array(
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
			);

			$open_view->name = "";
			$open_view->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
			$open_view->renderSortAsc = false;
			$open_view->renderLimit = 10;

			UmScAbstractViewLoader::setView($open_view->id, $open_view);
			$tpl->assign('open_view', $open_view);
			
			// Closed Tickets
			
			if(null == ($closed_view = UmScAbstractViewLoader::getView('', 'sc_history_closed'))) {
				$closed_view = new UmSc_TicketHistoryView();
				$closed_view->id = 'sc_history_closed';
			}
			
			// Lock to current visitor and closed tickets
			$closed_view->params = array(
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',1),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
			);

			$closed_view->name = "";
			$closed_view->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
			$closed_view->renderSortAsc = false;
			$closed_view->renderLimit = 10;

			UmScAbstractViewLoader::setView($closed_view->id, $closed_view);
			$tpl->assign('closed_view', $closed_view);

			$tpl->display("devblocks:usermeet.core:support_center/history/index.tpl:portal_".UmPortalHelper::getCode());
			
		} elseif ('search'==$mask) {
			@$q = DevblocksPlatform::importGPC($_REQUEST['q'],'string','');
			$tpl->assign('q', $q);

			if(null == ($view = UmScAbstractViewLoader::getView('', 'sc_history_search'))) {
				$view = new UmSc_TicketHistoryView();
				$view->id = 'sc_history_search';
			}
			
			$view->name = "";
			$view->view_columns = array(
				SearchFields_Ticket::TICKET_MASK,
				SearchFields_Ticket::TICKET_SUBJECT,
				SearchFields_Ticket::TICKET_UPDATED_DATE,
				SearchFields_Ticket::TICKET_CLOSED,
			);
			$view->params = array(
				array(
					DevblocksSearchCriteria::GROUP_OR,
					new DevblocksSearchCriteria(SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT,DevblocksSearchCriteria::OPER_FULLTEXT,array($q,'all')),
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,DevblocksSearchCriteria::OPER_LIKE,$q.'%'),
				),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
			);
			
			UmScAbstractViewLoader::setView($view->id, $view);
			$tpl->assign('view', $view);
			
			$tpl->display("devblocks:usermeet.core:support_center/history/search_results.tpl:portal_".UmPortalHelper::getCode());
			
		} else {
			// Secure retrieval (address + mask)
			list($tickets) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,'=',$mask),
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
				),
				1,
				0,
				null,
				null,
				false
			);
			$ticket = array_shift($tickets);
			
			// Security check (mask compare)
			if(0 == strcasecmp($ticket[SearchFields_Ticket::TICKET_MASK],$mask)) {
				$messages = DAO_Message::getMessagesByTicket($ticket[SearchFields_Ticket::TICKET_ID]);
				$messages = array_reverse($messages, true);
				$attachments = array();						
				
				// Attachments
				if(is_array($messages) && !empty($messages)) {
					list($msg_attachments) = DAO_Attachment::search(
						array(
							SearchFields_Attachment::MESSAGE_ID => new DevblocksSearchCriteria(SearchFields_Attachment::MESSAGE_ID,'in',array_keys($messages))
						),
						-1,
						0,
						null,
						null,
						false
					);
					
					if(is_array($msg_attachments))
					foreach($msg_attachments as $attach_id => $attach) {
						if(null == ($msg_id = intval($attach[SearchFields_Attachment::MESSAGE_ID])))
							continue;
							
						if(0 == strcasecmp('original_message.html',$attach[SearchFields_Attachment::DISPLAY_NAME]))
							continue;
							
						if(!isset($attachments[$msg_id]))
							$attachments[$msg_id] = array();
						
						$attachments[$msg_id][$attach_id] = $attach;
						
						unset($attach);
					}
				}
				// Groups (for custom fields)
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);

				// Custom fields
				$ticket_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
				$tpl->assign('ticket_fields', $ticket_fields);

				$ticket_field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Ticket::ID, $ticket[SearchFields_Ticket::TICKET_ID]));
				$tpl->assign('ticket_field_values', $ticket_field_values);
				
				$tpl->assign('ticket', $ticket);
				$tpl->assign('messages', $messages);
				$tpl->assign('attachments', $attachments);

				$display_next_assigned_to = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_NEXT_ASSIGNED_TO, 0);
				$tpl->assign('display_next_assigned_to', $display_next_assigned_to);
						
				$cf_select_serial = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_CF_SELECT, '');
				$cf_select = !empty($cf_select_serial) ? unserialize($cf_select_serial) : array();
				$tpl->assign('cf_select', $cf_select);
        
				$tpl->display("devblocks:usermeet.core:support_center/history/display.tpl:portal_".UmPortalHelper::getCode());
			}
		}
				
	}
	
	function saveTicketPropertiesAction() {
		@$mask = DevblocksPlatform::importGPC($_REQUEST['mask'],'string','');
		@$closed = DevblocksPlatform::importGPC($_REQUEST['closed'],'integer','0');
		
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);

		// Secure retrieval (address + mask)
		list($tickets) = DAO_Ticket::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,'=',$mask),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
			),
			1,
			0,
			null,
			null,
			false
		);
		$ticket = array_shift($tickets);
		$ticket_id = $ticket[SearchFields_Ticket::TICKET_ID];

		$fields = array(
			DAO_Ticket::IS_CLOSED => ($closed) ? 1 : 0
		);
		DAO_Ticket::update($ticket_id,$fields);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'history',$ticket[SearchFields_Ticket::TICKET_MASK])));		
	}
	
	function saveTicketCustomPropertiesAction() {
		@$mask = DevblocksPlatform::importGPC($_REQUEST['mask'],'string','');
		
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		
		// Secure retrieval (address + mask)
		list($tickets) = DAO_Ticket::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,'=',$mask),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
			),
			1,
			0,
			null,
			null,
			false
		);
		$ticket = array_shift($tickets);
		$ticket_id = $ticket[SearchFields_Ticket::TICKET_ID];
		
		// Custom field saves
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
		DAO_CustomFieldValue::handleFormPost(ChCustomFieldSource_Ticket::ID, $ticket_id, $field_ids);

		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'history',$ticket[SearchFields_Ticket::TICKET_MASK])));		
	}
	
	function doReplyAction() {
		@$mask = DevblocksPlatform::importGPC($_REQUEST['mask'],'string','');
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);

		// Secure retrieval (address + mask)
		list($tickets) = DAO_Ticket::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,'=',$mask),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
			),
			1,
			0,
			null,
			null,
			false
		);
		$ticket = array_shift($tickets);
		
		$messages = DAO_Message::getMessagesByTicket($ticket[SearchFields_Ticket::TICKET_ID]);
		$last_message = array_pop($messages); /* @var $last_message Model_Message */
		$last_message_headers = $last_message->getHeaders();
		unset($messages);

		// Helpdesk settings
		$settings = DevblocksPlatform::getPluginSettingsService();
		$global_from = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_FROM,CerberusSettingsDefaults::DEFAULT_REPLY_FROM);
		
		// Ticket group settings
		$group_id = $ticket[SearchFields_Ticket::TICKET_TEAM_ID];
		@$group_from = DAO_GroupSettings::get($group_id, DAO_GroupSettings::SETTING_REPLY_FROM, '');
		
		// Headers
		$to = !empty($group_from) ? $group_from : $global_from;
		@$in_reply_to = $last_message_headers['message-id'];
		@$message_id = CerberusApplication::generateMessageId();
		
		$message = new CerberusParserMessage();
		$message->headers['from'] = $active_user->email;
		$message->headers['to'] = $to;
		$message->headers['date'] = date('r');
		$message->headers['subject'] = 'Re: ' . $ticket[SearchFields_Ticket::TICKET_SUBJECT];
		$message->headers['message-id'] = $message_id;
		$message->headers['in-reply-to'] = $in_reply_to;
		
		$message->body = sprintf(
			"%s",
			$content
		);
   
		CerberusParser::parseMessage($message,array('no_autoreply'=>true));
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'history',$ticket[SearchFields_Ticket::TICKET_MASK])));
	}

	function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/templates/';

		$settings = DevblocksPlatform::getPluginSettingsService();
        
		$next_assigned_to = DAO_CommunityToolProperty::get($instance->code, self::PARAM_NEXT_ASSIGNED_TO, 0);
		$tpl->assign('next_assigned_to', $next_assigned_to);

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Contact: Fields
		$ticket_fields = DAO_CustomField::getBySource('cerberusweb.fields.source.ticket');
		$tpl->assign('ticket_fields', $ticket_fields);

		$cf_select_serial = DAO_CommunityToolProperty::get($instance->code,self::PARAM_CF_SELECT, '');
		$cf_select = !empty($cf_select_serial) ? unserialize($cf_select_serial) : array();
		$tpl->assign('cf_select', $cf_select);
		
		$tpl->display("file:${tpl_path}portal/sc/config/module/history.tpl");
	}
	
	function saveConfiguration(Model_CommunityTool $instance) {
		@$iNextAssignedTo = DevblocksPlatform::importGPC($_POST['next_assigned_to'],'integer',0);
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_NEXT_ASSIGNED_TO, $iNextAssignedTo);

		$ticket_fields = DAO_CustomField::getBySource('cerberusweb.fields.source.ticket');
		foreach ($ticket_fields as $id => $value) {
			@$cf_select[$id] = DevblocksPlatform::importGPC($_POST['cf_select_'.$id],'integer',0);
		}
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_CF_SELECT, serialize($cf_select));
	}
};

class UmSc_TicketHistoryView extends C4_AbstractView {
	const DEFAULT_ID = 'sc_history';
	
	private $_TPL_PATH = '';

	function __construct() {
		$this->_TPL_PATH = dirname(dirname(dirname(dirname(__FILE__)))) . '/templates/';
		
		$this->id = self::DEFAULT_ID;
		$this->name = 'Tickets';
		$this->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_Ticket::TICKET_MASK,
			SearchFields_Ticket::TICKET_SUBJECT,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
		);
	}

	function getData() {
		$objects = DAO_Ticket::search(
			array(),
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}

	function render() {
		//$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$tpl->assign('view_fields', $this->getColumns());
		
		$tpl->display("devblocks:usermeet.core:support_center/history/view.tpl:portal_".UmPortalHelper::getCode());
	}

	static function getFields() {
		return SearchFields_Ticket::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_Ticket::ID]);
		return $fields;
	}

	static function getColumns() {
		return self::getFields();
	}
};
