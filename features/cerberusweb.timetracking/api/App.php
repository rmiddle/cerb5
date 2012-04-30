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
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */

if (class_exists('Extension_AppPreBodyRenderer',true)):
	class ChTimeTrackingPreBodyRenderer extends Extension_AppPreBodyRenderer {
		function render() {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('current_timestamp', time());
			$tpl->display('devblocks:cerberusweb.timetracking::timetracking/renderers/prebody.tpl');
		}
	};
endif;

if (class_exists('Extension_CrmOpportunityToolbarItem',true)):
	class ChCrmOppToolbarTimer extends Extension_CrmOpportunityToolbarItem {
		function render(Model_CrmOpportunity $opp) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('opp', $opp); /* @var $opp Model_CrmOpportunity */
			$tpl->display('devblocks:cerberusweb.timetracking::timetracking/renderers/opps/opp_toolbar_timer.tpl');
		}
	};
endif;

if (class_exists('Extension_TaskToolbarItem',true)):
	class ChTimeTrackingTaskToolbarTimer extends Extension_TaskToolbarItem {
		function render(Model_Task $task) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('task', $task); /* @var $task Model_Task */
			$tpl->display('devblocks:cerberusweb.timetracking::timetracking/renderers/tasks/task_toolbar_timer.tpl');
		}
	};
endif;

if (class_exists('Extension_TicketToolbarItem',true)):
	class ChTimeTrackingTicketToolbarTimer extends Extension_TicketToolbarItem {
		function render(Model_Ticket $ticket) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('ticket', $ticket); /* @var $ticket Model_Ticket */
			$tpl->display('devblocks:cerberusweb.timetracking::timetracking/renderers/tickets/ticket_toolbar_timer.tpl');
		}
	};
endif;

if (class_exists('Extension_ReplyToolbarItem',true)):
	class ChTimeTrackingReplyToolbarTimer extends Extension_ReplyToolbarItem {
		function render(Model_Message $message) { 
			$tpl = DevblocksPlatform::getTemplateService();
			
			$tpl->assign('message', $message); /* @var $message Model_Message */
			
//			if(null != ($first_wrote_address_id = $ticket->first_wrote_address_id)
//				&& null != ($first_wrote_address = DAO_Address::get($first_wrote_address_id))) {
//				$tpl->assign('tt_first_wrote', $first_wrote_address);
//			}
			
			$tpl->display('devblocks:cerberusweb.timetracking::timetracking/renderers/tickets/reply_toolbar_timer.tpl');
		}
	};
endif;

if (class_exists('Extension_LogMailToolbarItem',true)):
	class ChTimeTrackingLogMailToolbarTimer extends Extension_LogMailToolbarItem {
		function render() { 
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->display('devblocks:cerberusweb.timetracking::timetracking/renderers/tickets/logmail_toolbar_timer.tpl');
		}
	};
endif;

if (class_exists('Extension_SendMailToolbarItem',true)):
	class ChTimeTrackingSendMailToolbarTimer extends Extension_SendMailToolbarItem {
		function render() { 
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->display('devblocks:cerberusweb.timetracking::timetracking/renderers/tickets/sendmail_toolbar_timer.tpl');
		}
	};
endif;

class ChTimeTrackingEventListener extends DevblocksEventListenerExtension {
    /**
     * @param Model_DevblocksEvent $event
     */
    function handleEvent(Model_DevblocksEvent $event) {
        switch($event->id) {
            case 'cron.maint':
				//DAO_TimeTrackingActivity::maint();            	
				DAO_TimeTrackingEntry::maint();            	
            	break;
            	
            case 'ticket.action.merge':
            	$new_ticket_id = $event->params['new_ticket_id'];
            	$old_ticket_ids = $event->params['old_ticket_ids'];
            	
            	// [TODO]
            	break;
        }
    }
};

class ChTimeTrackingPage extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		
		array_shift($stack); // timetracking
		
		$module = array_shift($stack); // display
		
		switch($module) {
			default:
			case 'display':
				@$time_id = intval(array_shift($stack));
				if(null == ($time_entry = DAO_TimeTrackingEntry::get($time_id))) {
					break; // [TODO] Not found
				}
				$tpl->assign('time_entry', $time_entry);						

				// Custom fields
				
				$custom_fields = DAO_CustomField::getAll();
				$tpl->assign('custom_fields', $custom_fields);
				
				// Properties
				
				$properties = array();
				
				$properties['status'] = array(
					'label' => ucfirst($translate->_('common.status')),
					'type' => Model_CustomField::TYPE_SINGLE_LINE,
					'value' => ($time_entry->is_closed) ? $translate->_('status.closed') : $translate->_('status.open'),
				);
				
				$properties['log_date'] = array(
					'label' => ucfirst($translate->_('timetracking_entry.log_date')),
					'type' => Model_CustomField::TYPE_DATE,
					'value' => $time_entry->log_date,
				);
				
				$properties['time_spent'] = array(
					'label' => 'Time spent',
					'type' => null,
					'value' => $time_entry->time_actual_mins * 60,
				);
				
				// [TODO] Worker?
				
				@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_TIMETRACKING, $time_entry->id)) or array();
		
				foreach($custom_fields as $cf_id => $cfield) {
					if(!isset($values[$cf_id]))
						continue;
						
					$properties['cf_' . $cf_id] = array(
						'label' => $cfield->name,
						'type' => $cfield->type,
						'value' => $values[$cf_id],
					);
				}
				
				$tpl->assign('properties', $properties);
				
				// Macros
				$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.timetracking');
				$tpl->assign('macros', $macros);
				
				$tpl->display('devblocks:cerberusweb.timetracking::timetracking/display/index.tpl');
				break;
		}
	}	
	
	/**
	 * @return Model_Activity
	 */
	public function getActivity() {
        return new Model_Activity('activity.default');
	}
	
	private function _startTimer() {
		if(!isset($_SESSION['timetracking_started'])) {
			$_SESSION['timetracking_started'] = time();	
		}
	}
	
	private function _stopTimer() {
		@$time = intval($_SESSION['timetracking_started']);
		
		// If a timer was running
		if(!empty($time)) {
			$elapsed = time() - $time;
			unset($_SESSION['timetracking_started']);
			@$_SESSION['timetracking_total'] = intval($_SESSION['timetracking_total']) + $elapsed;
		}

		@$total = $_SESSION['timetracking_total'];
		if(empty($total))
			return false;
		
		return $total;
	}
	
	private function _destroyTimer() {
		unset($_SESSION['timetracking_context']);
		unset($_SESSION['timetracking_context_id']);
		unset($_SESSION['timetracking_started']);
		unset($_SESSION['timetracking_total']);
		unset($_SESSION['timetracking_link']);
	}
	
	function startTimerAction() {
		@$context = urldecode(DevblocksPlatform::importGPC($_REQUEST['context'],'string',''));
		@$context_id = intval(DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0));
		
		if(!empty($context) && !isset($_SESSION['timetracking_context'])) {
			$_SESSION['timetracking_context'] = $context;
			$_SESSION['timetracking_context_id'] = $context_id;
		}
		
		$this->_startTimer();
	}
	
	function pauseTimerJsonAction() {
		header("Content-Type: application/json");
		
		$total_secs = $this->_stopTimer();
		
		echo json_encode(array(
			'status' => true,
			'total_mins' => ceil($total_secs/60),
		));
		exit;
	}
	
	function saveEntryAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Make sure we're an active worker
		if(empty($active_worker) || empty($active_worker->id))
			return;
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
			
		@$activity_id = DevblocksPlatform::importGPC($_POST['activity_id'],'integer',0);
		@$time_actual_mins = DevblocksPlatform::importGPC($_POST['time_actual_mins'],'integer',0);
		@$is_closed = DevblocksPlatform::importGPC($_POST['is_closed'],'integer',0);
		
		// Date
		@$log_date = DevblocksPlatform::importGPC($_REQUEST['log_date'],'string','now');
		if(false == (@$log_date = strtotime($log_date)))
			$log_date = time();
		
		// Comment
		@$comment = DevblocksPlatform::importGPC($_POST['comment'],'string','');
		
		// Delete entries
		if(!empty($id) && !empty($do_delete)) {
			if(null != ($entry = DAO_TimeTrackingEntry::get($id))) {
				// Check privs
				if(($active_worker->hasPriv('timetracking.actions.create') && $active_worker->id==$entry->worker_id)
					|| $active_worker->hasPriv('timetracking.actions.update_all'))
						DAO_TimeTrackingEntry::delete($id);
			}
			
			return;
		}
		
		// New or modify
		$fields = array(
			DAO_TimeTrackingEntry::ACTIVITY_ID => intval($activity_id),
			DAO_TimeTrackingEntry::TIME_ACTUAL_MINS => intval($time_actual_mins),
			DAO_TimeTrackingEntry::LOG_DATE => intval($log_date),
			DAO_TimeTrackingEntry::IS_CLOSED => intval($is_closed),
		);

		// Only on new
		if(empty($id)) {
			$fields[DAO_TimeTrackingEntry::WORKER_ID] = intval($active_worker->id);
		}
		
		if(empty($id)) { // create
			$id = DAO_TimeTrackingEntry::create($fields);
			
			@$is_watcher = DevblocksPlatform::importGPC($_REQUEST['is_watcher'],'integer',0);
			if($is_watcher)
				CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TIMETRACKING, $id, $active_worker->id);
			
			$translate = DevblocksPlatform::getTranslationService();
			$url_writer = DevblocksPlatform::getUrlService();
			
			// Context Link (if given)
			@$link_context = DevblocksPlatform::importGPC($_REQUEST['link_context'],'string','');
			@$link_context_id = DevblocksPlatform::importGPC($_REQUEST['link_context_id'],'integer','');
			
			// Procedurally create a comment
			// [TODO] Check context for 'comment' option
			switch($link_context) {
				// If ticket, add a comment about the timeslip to the ticket
				case CerberusContexts::CONTEXT_OPPORTUNITY:
				case CerberusContexts::CONTEXT_TICKET:
				case CerberusContexts::CONTEXT_TASK:
					if(null != ($worker_address = DAO_Address::lookupAddress($active_worker->email, false))) {
						if(!empty($activity_id)) {
							$activity = DAO_TimeTrackingActivity::get($activity_id);
						}
						
						// [TODO] This comment could be added to anything context now using DAO_Comment + Context_*
						$context_comment = sprintf(
							"== %s ==\n".
							"%s %s\n".
							"%s %d\n".
							"%s %s (%s)\n".
							(!empty($comment) ? sprintf("%s: %s\n", $translate->_('common.comment'), $comment) : '').
							"\n".
							"%s\n",
							$translate->_('timetracking.ui.timetracking'),
							$translate->_('timetracking.ui.worker'),
							$active_worker->getName(),
							$translate->_('timetracking.ui.comment.time_spent'),
							$time_actual_mins,
							$translate->_('timetracking.ui.comment.activity'),
							(!empty($activity) ? $activity->name : ''),
							((!empty($activity) && $activity->rate > 0.00) ? $translate->_('timetracking.ui.billable') : $translate->_('timetracking.ui.non_billable')),
							$url_writer->writeNoProxy(sprintf("c=timetracking&a=display&id=%d", $id), true)
						);
						$fields = array(
							DAO_Comment::ADDRESS_ID => intval($worker_address->id),
							DAO_Comment::COMMENT => $context_comment,
							DAO_Comment::CREATED => time(),
							DAO_Comment::CONTEXT => $link_context,
							DAO_Comment::CONTEXT_ID => intval($link_context_id),
						);
						DAO_Comment::create($fields);
					}
					break;
			}
			
			// Establishing a context link?
			if(isset($link_context) && isset($link_context_id)) {
				// Primary context
				DAO_ContextLink::setLink(CerberusContexts::CONTEXT_TIMETRACKING, $id, $link_context, $link_context_id);
				
				// Associated contexts
				switch($link_context) {
					case CerberusContexts::CONTEXT_OPPORTUNITY:
						if(!class_exists('DAO_CrmOpportunity', true))
							break;
							
						$labels = null;
						$values = null;
						CerberusContexts::getContext($link_context, $link_context_id, $labels, $values);
						
						if(is_array($values)) {
							// Is there an org associated with this context?
							if(isset($values['email_org_id']) && !empty($values['email_org_id'])) {
								DAO_ContextLink::setLink(CerberusContexts::CONTEXT_TIMETRACKING, $id, CerberusContexts::CONTEXT_ORG, $values['email_org_id']);
							}
						}
						break;
						
					case CerberusContexts::CONTEXT_TICKET:
						$labels = null;
						$values = null;
						CerberusContexts::getContext($link_context, $link_context_id, $labels, $values);
						
						if(is_array($values)) {
							// Try the ticket's org
							@$org_id = $values['org_id'];
							
							// Fallback to the initial sender's org
							if(empty($org_id))
								@$org_id = $values['initial_message_sender_org_id'];
							
							// Is there an org associated with this context?
							if(!empty($org_id)) {
								DAO_ContextLink::setLink(CerberusContexts::CONTEXT_TIMETRACKING, $id, CerberusContexts::CONTEXT_ORG, $org_id);
							}
						}
						break;
				}
			}			
			
		} else { // modify
			DAO_TimeTrackingEntry::update($id, $fields);
		}
		
		// Custom field saves
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
		DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_TIMETRACKING, $id, $field_ids);
		
		// Comments
		if(!empty($comment)) {
			@$also_notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
			
			$fields = array(
				DAO_Comment::ADDRESS_ID => $active_worker->getAddress()->id,
				DAO_Comment::COMMENT => $comment,
				DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TIMETRACKING,
				DAO_Comment::CONTEXT_ID => $id,
				DAO_Comment::CREATED => time(),
			);		
			$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);
		}
	}
	
	function viewTimeExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time()); 
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}

		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		
		do {
			$models = array();
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
//					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=activity&tab=timetracking', true),
//					'toolbar_extension_id' => 'cerberusweb.explorer.toolbar.',
				);
				$models[] = $model; 
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_TimeTrackingEntry::ID],
					'url' => $url_writer->writeNoProxy(sprintf("c=timetracking&a=display&id=%d", $row[SearchFields_TimeTrackingEntry::ID]), true),
				);
				$models[] = $model; 
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	} 
	
	function clearEntryAction() {
		$this->_destroyTimer();
	}
	
	function showBulkPanelAction() {
		@$id_csv = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

	    if(!empty($id_csv)) {
	        $ids = DevblocksPlatform::parseCsvString($id_csv);
	        $tpl->assign('ids', implode(',', $ids));
	    }
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TIMETRACKING);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Macros
		$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.timetracking');
		$tpl->assign('macros', $macros);
		
		$tpl->display('devblocks:cerberusweb.timetracking::timetracking/time/bulk.tpl');
	}
	
	function doBulkUpdateAction() {
		@set_time_limit(600); // 10m
		
		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    $ids = array();
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Time Tracking fields
		@$is_closed = DevblocksPlatform::importGPC($_POST['is_closed'],'string','');

		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
		@$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'],'array',array());
		
		$do = array();
		
		// Do: ...
		if(0 != strlen($is_closed))
			$do['is_closed'] = !empty($is_closed) ? 1 : 0;

		// Do: Scheduled Behavior
		if(0 != strlen($behavior_id)) {
			$do['behavior'] = array(
				'id' => $behavior_id,
				'when' => $behavior_when,
				'params' => $behavior_params,
			);
		}
		
		// Watchers
		$watcher_params = array();
		
		@$watcher_add_ids = DevblocksPlatform::importGPC($_REQUEST['do_watcher_add_ids'],'array',array());
		if(!empty($watcher_add_ids))
			$watcher_params['add'] = $watcher_add_ids;
			
		@$watcher_remove_ids = DevblocksPlatform::importGPC($_REQUEST['do_watcher_remove_ids'],'array',array());
		if(!empty($watcher_remove_ids))
			$watcher_params['remove'] = $watcher_remove_ids;
		
		if(!empty($watcher_params))
			$do['watchers'] = $watcher_params;
		
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);

		switch($filter) {
			// Checked rows
			case 'checks':
			    @$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
			default:
				break;
		}
		
		$view->doBulkUpdate($filter, $do, $ids);
		
		$view->render();
		return;
	}
	
};

if(class_exists('Extension_PageSection')):
class ChTimeTracking_SetupPageSection extends Extension_PageSection {
	const ID = 'timetracking.setup.section.timetracking';
	
	function render() {
		$settings = DevblocksPlatform::getPluginSettingsService();
		$tpl = DevblocksPlatform::getTemplateService();

		$billable_activities = DAO_TimeTrackingActivity::getWhere(sprintf("%s!=0",DAO_TimeTrackingActivity::RATE));
		$tpl->assign('billable_activities', $billable_activities);
		
		$nonbillable_activities = DAO_TimeTrackingActivity::getWhere(sprintf("%s=0",DAO_TimeTrackingActivity::RATE));
		$tpl->assign('nonbillable_activities', $nonbillable_activities);
		
		$tpl->display('devblocks:cerberusweb.timetracking::config/activities/index.tpl');
	}

	function saveAction() {
		$settings = DevblocksPlatform::getPluginSettingsService();
		@$plugin_id = DevblocksPlatform::importGPC($_REQUEST['plugin_id'],'string');

		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$rate = floatval(DevblocksPlatform::importGPC($_REQUEST['rate'],'string',''));
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);

		if(empty($name)) {
			$name = "(no name)";
		}
		
		if(empty($id)) { // Add
			$fields = array(
				DAO_TimeTrackingActivity::NAME => $name,
				DAO_TimeTrackingActivity::RATE => $rate,
			);
			$activity_id = DAO_TimeTrackingActivity::create($fields);
			
		} else { // Edit
			if($do_delete) { // Delete
				DAO_TimeTrackingActivity::delete($id);
				
			} else { // Modify
				$fields = array(
					DAO_TimeTrackingActivity::NAME => $name,
					DAO_TimeTrackingActivity::RATE => $rate,
				);
				DAO_TimeTrackingActivity::update($id, $fields);
			}
			
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','timetracking')));
		exit;		
	}
	
	function getActivityAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(!empty($id) && null != ($activity = DAO_TimeTrackingActivity::get($id)))
			$tpl->assign('activity', $activity);
		
		$tpl->display('devblocks:cerberusweb.timetracking::config/activities/edit_activity.tpl');
	}
}
endif;

if(class_exists('Extension_PageMenuItem')):
class ChTimeTracking_SetupPluginsMenuItem extends Extension_PageMenuItem {
	const ID = 'timetracking.setup.menu.plugins.timetracking';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.timetracking::config/menu_item.tpl');
	}
}
endif;

if (class_exists('Extension_ReportGroup',true)):
class ChReportGroupTimeTracking extends Extension_ReportGroup {
	// [TODO] This stub is pointless and should be refactored out.
};
endif;

