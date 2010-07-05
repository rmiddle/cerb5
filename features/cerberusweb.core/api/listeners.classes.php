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
class ChCoreTour extends DevblocksHttpResponseListenerExtension implements IDevblocksTourListener {
	function __construct($manifest) {
		parent::__construct($manifest);
	}

	/**
	 * @return DevblocksTourCallout[]
	 */
	function registerCallouts() {
		return array(
        'tourHeaderMenu' => new DevblocksTourCallout('tourHeaderMenu','Helpdesk Menu','This is where you can change between major helpdesk sections.'),
        'tourHeaderMyTasks' => new DevblocksTourCallout('tourHeaderMyTasks','My Tasks','Here you can quickly jump to a summary of your current tasks.'),
        'tourHeaderTeamLoads' => new DevblocksTourCallout('tourHeaderTeamLoads','My Team Loads','Here you can quickly display the workload of any of your teams.  You can display a team\'s dashboard by clicking them.'),
        'tourHeaderGetTickets' => new DevblocksTourCallout('tourHeaderGetTickets','Get Tickets',"The 'Get Tickets' link will assign you available tickets from your desired teams."),
        'tourHeaderQuickLookup' => new DevblocksTourCallout('tourHeaderQuickLookup','Quick Lookup','Here you can quickly search for tickets from anywhere in the helpdesk.  This is generally most useful when someone calls up and you need to promptly locate their ticket.'),
        'tourOverviewSummaries' => new DevblocksTourCallout('tourOverviewSummaries','Groups &amp; Buckets','Tickets that need worker replies are organized into buckets and shared by groups.'),
        'tourOverviewWaiting' => new DevblocksTourCallout('tourOverviewWaiting','Waiting For Reply','Tickets that are waiting for requester replies are kept out of the way. After a requester replies, the appropriate ticket is moved back to the list of available work.'),
        'overview_all_actions' => new DevblocksTourCallout('overview_all_actions','List Actions','Each list of tickets provides a toolbar of possible actions. Actions may be applied to specific tickets or to the entire list. The "Move to:" shortcuts adapt to your most-used buckets and groups.  Bulk Update allows you to apply several actions at once to any tickets in a list that match your criteria.'),
        'viewoverview_all' => new DevblocksTourCallout('viewoverview_all','Peek',"You can preview the content of any ticket in a list by clicking the \"(peek)\" link next to its subject. Peek is especially helpful when confirming tickets are spam if they have an ambiguous subject. This saves you a lot of time that would otherwise be wasted clicking into each ticket and losing your place in the list."),
        'tourDashboardViews' => new DevblocksTourCallout('tourDashboardViews','Ticket Lists','This is where your customized lists of tickets are displayed.'),
        'tourDisplayConversation' => new DevblocksTourCallout('tourDisplayConversation','Conversation','This is where all e-mail replies will be displayed for this ticket.  Your responses will be sent to all requesters.'),
        'btnReplyFirst' => new DevblocksTourCallout('btnReplyFirst','Replying',"Clicking the Reply button while displaying a ticket will allow you to write a response, as you would in any e-mail client, without leaving the ticket's page. This allows you to reference the ticket's information and history as you write."),
        'tourDisplayPaging' => new DevblocksTourCallout('tourDisplayPaging','Paging',"If you clicked on a ticket from a list, the detailed ticket page will show your progress from that list in the top right. You can also use the keyboard shortcuts to advance through the list with the bracket keys: ' [ ' and ' ] '."),
        'displayOptions' => new DevblocksTourCallout('displayOptions','Pluggable Tabs',"With Cerberus Helpdesk's pluggable architecture, new capabilities can be added to your ticket management. For example, you could display all the CRM opportunities or billing invoices associated with the ticket's requesters."),
        'tourConfigMaintPurge' => new DevblocksTourCallout('tourConfigMaintPurge','Purge Deleted','Here you may purge any deleted tickets from the database.'),
        'tourDashboardSearchCriteria' => new DevblocksTourCallout('tourDashboardSearchCriteria','Search Criteria','Here you can change the criteria of the current search.'),
        'tourConfigMenu' => new DevblocksTourCallout('tourConfigMenu','Menu','This is where you may choose to configure various components of the helpdesk.'),
        'tourConfigMailRouting' => new DevblocksTourCallout('tourConfigMailRouting','Mail Routing','This is where you instruct the helpdesk how to deliver new messages.'),
        '' => new DevblocksTourCallout('',''),
		);
	}

	function run(DevblocksHttpResponse $response, Smarty $tpl) {
		$path = $response->path;

		$callouts = CerberusApplication::getTourCallouts();

		switch(array_shift($path)) {
			case 'welcome':
				$tour = array(
	                'title' => 'Welcome!',
	                'body' => "This assistant will help you become familiar with the helpdesk by following along and providing information about the current page.  You may follow the 'Points of Interest' links highlighted below to read tips about nearby functionality.",
	                'callouts' => array(
					$callouts['tourHeaderMenu'],
					)
				);
				break;

			case "display":
				$tour = array(
	                'title' => 'Display Ticket',
	                'body' => "This screen displays the currently selected ticket.  Here you can modify the ticket or send a new reply to all requesters.<br><br>Clicking the Requester History tab will show all the past and present tickets from the ticket's requesters. This is an easy way to find and merge duplicate tickets from the same requester, or from several requesters from the same organization.<br><br>Often, a ticket may require action from several workers before it's complete. You can create tasks for each worker to track the progress of these actions. In Cerberus Helpdesk, workers don't \"own\" tickets. Each ticket has a \"next worker\" who is responsible for moving the ticket forward.<br><br>A detailed walkthrough of the display ticket page is available here: <a href=\"http://www.cerberusweb.com/tour/display\" target=\"_blank\">http://www.cerberusweb.com/tour/display</a>",
	                'callouts' => array(
						$callouts['tourDisplayConversation'],
						$callouts['btnReplyFirst'],
						$callouts['tourDisplayPaging'],
						$callouts['displayOptions'],
					)
				);
				break;

			case "preferences":
				$tour = array(
             	   'title' => 'Preferences',
            	    'body' => 'This screen allows you to change the personal preferences on your helpdesk account.',
				);
				break;

			case "groups":
				$tour = array(
             	   'title' => 'My Groups',
              	  'body' => 'This screen allows you to administer and configure groups for which you are a manager.  This includes members, buckets, mail routing rules, and other group-specific preferences.',
				);
				break;

			case "config":
				switch(array_shift($path)) {
					default:
					case NULL:
					case "general":
						$tour = array(
	                        'title' => 'General Settings',
    	                    'body' => 'These settings control the overall behavior of the helpdesk.',
						);
						break;

					case "workflow":
						$tour = array(
	                        'title' => 'Team Configuration',
    	                    'body' => "Here you may create new helpdesk workers and organize them into teams.  Common teams often include departments (such as: Support, Sales, Development, Marketing, Billing, etc.) or various projects that warrant their own workloads.",
						);
						break;

					case "fnr":
						$tour = array(
	                        'title' => 'Fetch & Retrieve',
	                        'body' => "The Fetch & Retrieve config allows you to define a wide variety of sources for pulling support data from (wikis, blogs, kbs, faqs, etc).  Any source that returns RSS-style XML results to a search can be used.",
						);
						break;

					case "mail":
						$tour = array(
	                        'title' => 'Mail Configuration',
	                        'body' => "This section controls the heart of your helpdesk: e-mail.  Here you may define the routing rules that determine what to do with new messages.  This is also where you set your preferences for sending mail out of the helpdesk.  To configure the POP3 downloader, click 'helpdesk config'->'scheduler'->'POP3 Mail Checker'",
	                        'callouts' => array(
							$callouts['tourConfigMailRouting']
						)
						);
						break;

					case "maintenance":
						$tour = array(
	                        'title' => 'Maintenance',
	                        'body' => 'This section is dedicated to ensuring your helpdesk continues to operate lightly and quickly.',
	                        'callouts' => array(
							$callouts['tourConfigMaintPurge'],
						)
						);
						break;

					case "extensions":
						$tour = array(
	                        'title' => 'Extensions',
	                        'body' => "This is where you may extend Cerberus Helpdesk by installing new functionality through plug-ins.",
	                        'callouts' => array(
							)
						);
						break;
					case "jobs":
						$tour = array(
	                        'title' => 'Scheduler',
	                        'body' => "The scheduler is where you can set up tasks that will periodically run behind-the-scenes.",
	                        'callouts' => array(
							)
						);
						break;
				}
				break;

			case NULL:
			case "tickets":
				switch(array_shift($path)) {
					default:
					case NULL:
					case 'overview':
						$tour = array(
	                        'title' => 'Mail Overview',
	                        'body' => "The Mail tab provides the ability to compose outgoing email as well as view lists of tickets, either here in the general overview, in specific search result lists, or in your personalized ticket lists in 'my workspaces'.  A detailed walkthrough of the mail page is available here: <a href=\"http://www.cerberusweb.com/tour/overview\" target=\"_blank\">http://www.cerberusweb.com/tour/overview</a>",
	                        'callouts' => array(
							$callouts['tourOverviewSummaries'],
							$callouts['tourOverviewWaiting'],
							$callouts['overview_all_actions'],
							$callouts['viewoverview_all'],
							)
						);
						break;
						
					case 'lists':
						$tour = array(
	                        'title' => 'My Workspaces',
	                        'body' => 'Here is where you set up personalized lists of tickets.  Any Overview or Search results list can be copied here by clicking the "copy" link in the list title bar.',
	                        'callouts' => array(
							$callouts['tourDashboardViews'],
							)
						);
						break;
						
					case 'search':
						$tour = array(
	                        'title' => 'Searching Tickets',
	                        'body' => '',
	                        'callouts' => array(
							$callouts['tourDashboardSearchCriteria']
							)
						);
						break;

					case 'compose':
						$tour = array(
	                        'title' => 'Compose Mail',
    	                    'body' => '',
						);
						break;
						
					case 'create':
						$tour = array(
	                        'title' => 'Log Ticket',
    	                    'body' => '',
						);
						break;
				}
				break;
				
			case 'contacts':
				switch(array_shift($path)) {
					default:
					case NULL:
					case 'orgs':
						$tour = array(
	                        'title' => 'Organizations',
	                        'body' => '',
	                        'callouts' => array(
							)
						);
						break;
						
					case 'addresses':
						$tour = array(
	                        'title' => 'Addresses',
	                        'body' => '',
	                        'callouts' => array(
							)
						);
						break;
						
					case 'import':
						$tour = array(
	                        'title' => 'Importing Orgs and Addresses',
	                        'body' => 'Use this screen to import Organizational and Address info.  The import allows comparison checking to do incremental imports and not duplicate data.',
	                        'callouts' => array(
							)
						);
						break;
				}
				break;
				
			case 'kb':
				$tour = array(
	                'title' => 'Knowledgebase',
	                'body' => "",
	                'callouts' => array(
					)
				);
				break;
				
			case 'tasks':
				$tour = array(
	                'title' => 'Tasks',
	                'body' => "Often, a ticket may require action from several workers before it's complete. You can create tasks for each worker to track the progress of these actions. In Cerberus Helpdesk, workers don't \"own\" tickets. Each ticket has a \"next worker\" who is responsible for moving the ticket forward.",
	                'callouts' => array(
					)
				);
				break;
				
			case 'community':
				$tour = array(
	                'title' => 'Communities',
	                'body' => 'Here you can create Public Community interfaces to Cerberus, including Knowledgebases, Contact Forms, and Support Centers.',
	                'callouts' => array(
					)
				);
				break;
				
		}

		if(!empty($tour))
		$tpl->assign('tour', $tour);
	}
};

class ChCoreEventListener extends DevblocksEventListenerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}

	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {
		// Cerberus Helpdesk Workflow
		switch($event->id) {
			case 'dao.ticket.update':
				$this->_handleDaoTicketUpdate($event);
				break;
				
			case 'ticket.action.assigned':
				$this->_handleTicketAssigned($event);
				break;
				
			case 'ticket.action.closed':
				$this->_handleTicketClosed($event);
				break;
				
			case 'ticket.action.moved':
				$this->_handleTicketMoved($event);
				break;

			case 'cron.heartbeat':
				$this->_handleCronHeartbeat($event);
				break;
				
			case 'cron.maint':
				$this->_handleCronMaint($event);
				break;
		}
	}

	private function _handleDaoTicketUpdate($event) {
    	@$objects = $event->params['objects'];

		$eventMgr = DevblocksPlatform::getEventService();
		
    	if(is_array($objects))
    	foreach($objects as $object_id => $object) {
    		@$model = $object['model'];
    		@$changes = $object['changes'];
    		
    		if(empty($model) || empty($changes))
    			continue;
    		
        	/*
        	 * Ticket assigned to worker/nobody
        	 */
			@$next_worker_id = $changes[DAO_Ticket::NEXT_WORKER_ID];
			
			if(!is_null($next_worker_id)) {
			    $eventMgr->trigger(
			        new Model_DevblocksEvent(
			            'ticket.action.assigned',
		                array(
		                    'ticket_id' => $object_id,
		                	'worker_id' => $model[DAO_Ticket::NEXT_WORKER_ID],
		                    'model' => $model,
		                )
		            )
			    );
			}
	    	
			/*
			 * Ticket moved
			 */
			@$group_id = $changes[DAO_Ticket::TEAM_ID];
			@$bucket_id = $changes[DAO_Ticket::CATEGORY_ID];
			
			if(!is_null($group_id) || !is_null($bucket_id)) {
			    $eventMgr->trigger(
			        new Model_DevblocksEvent(
			            'ticket.action.moved',
		                array(
		                    'ticket_id' => $object_id,
		                	'group_id' => $model[DAO_Ticket::TEAM_ID],
		                	'bucket_id' => $model[DAO_Ticket::CATEGORY_ID],
		                    'model' => $model,
		                )
		            )
			    );
			}
			
			/*
			 * Ticket closed
			 */
			@$closed = $changes[DAO_Ticket::IS_CLOSED];
			
			if(!is_null($closed) && !empty($model[DAO_Ticket::IS_CLOSED])) {
			    $eventMgr->trigger(
			        new Model_DevblocksEvent(
			            'ticket.action.closed',
		                array(
		                    'ticket_id' => $object_id,
		                    'model' => $model,
		                )
		            )
			    );
			}	    	
    	}
	}
	
	private function _handleTicketAssigned($event) {
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		@$ticket_id = $event->params['ticket_id'];
		@$worker_id = $event->params['worker_id'];
		@$model = $event->params['model'];
		
		if(empty($worker_id))
			return;
		
    	// If this is headless, or we're assigning a different worker
    	if(empty($active_worker) || $active_worker->id != $worker_id) {
	    	// Send a notification
			$fields = array(
				DAO_WorkerEvent::CREATED_DATE => time(),
				DAO_WorkerEvent::WORKER_ID => $worker_id,
				DAO_WorkerEvent::URL => $url_writer->write('c=display&id='.$model[DAO_Ticket::MASK],true),
				DAO_WorkerEvent::TITLE => 'New Ticket Assignment', // [TODO] Translate
				DAO_WorkerEvent::CONTENT => sprintf("#%s: %s", $model[DAO_Ticket::MASK], $model[DAO_Ticket::SUBJECT]),
				DAO_WorkerEvent::IS_READ => 0,
			);
			DAO_WorkerEvent::create($fields);
    	}
	}
	
	private function _handleTicketMoved($event) {
		@$ticket_id = $event->params['ticket_id'];
		@$group_id = $event->params['group_id'];
		@$bucket_id = $event->params['bucket_id'];
		
		// If we're landing in an inbox we need to check its filters
		if(!empty($group_id) && empty($bucket_id)) { // moving to an inbox
			// Run the new inbox filters
			$matches = CerberusApplication::runGroupRouting($group_id, $ticket_id);
			
			// If we matched no rules, we're stuck in the destination inbox.
			if(!empty($matches)) {
				// If more inbox rules want to move this ticket don't consider this finished
				if(is_array($matches))
				foreach($matches as $match) {
	                if(isset($match->actions['move'])) // any moves
						return;
				}
			}
		}

		// Trigger an inbound event			
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'ticket.reply.inbound',
                array(
                    'ticket_id' => $ticket_id,
                )
            )
	    );
	}
	
	private function _handleTicketClosed($event) {
		@$ticket_id = $event->params['ticket_id'];
		@$model = $event->params['model'];

		// If we're closing *and* deleting, abort.
		@$is_deleted = $model[DAO_Ticket::IS_DELETED];
		if(!is_null($is_deleted) && $is_deleted)
			return;
			
		$group_settings = DAO_GroupSettings::getSettings();
		@$group_id = $model[DAO_Ticket::TEAM_ID];

		// Make sure the current group has an auto-close reply
		if(!isset($group_settings[$group_id][DAO_GroupSettings::SETTING_CLOSE_REPLY_ENABLED]))
			return;

		// If the template doesn't exist or is empty
		if(!isset($group_settings[$group_id][DAO_GroupSettings::SETTING_CLOSE_REPLY])
			|| empty($group_settings[$group_id][DAO_GroupSettings::SETTING_CLOSE_REPLY]))
			return;
			
		try {
			$token_labels = array();
			$token_values = array();
			CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, $ticket_id, $token_labels, $token_values);
			
			$tpl_builder = DevblocksPlatform::getTemplateBuilder();
			if(false === ($closereply_content = $tpl_builder->build($group_settings[$group_id][DAO_GroupSettings::SETTING_CLOSE_REPLY], $token_values)))
				throw new Exception('Failed parsing close auto-reply snippet.');
			
			$result = CerberusMail::sendTicketMessage(array(
				'ticket_id' => $ticket_id,
				'message_id' => $model[DAO_Ticket::FIRST_MESSAGE_ID],
				'content' => $closereply_content,
				'is_autoreply' => false,
				'dont_keep_copy' => true
			));
			
		} catch (Exception $e) {
			// [TODO] Error report
		}
	}
	
	private function _handleCronMaint($event) {
		DAO_Address::maint();
		DAO_ExplorerSet::maint();
		DAO_Group::maint();
		DAO_Ticket::maint();
		DAO_Message::maint();
		DAO_Worker::maint();
		DAO_WorkerEvent::maint();
		DAO_Snippet::maint();
	}
	
	private function _handleCronHeartbeat($event) {
		// Re-open any conversations past their reopen date
		$fields = array(
			DAO_Ticket::IS_CLOSED => 0,
			DAO_Ticket::IS_WAITING => 0,
			DAO_Ticket::DUE_DATE => 0
		);
		$where = sprintf("(%s = %d OR %s = %d) AND %s > 0 AND %s < %d",
			DAO_Ticket::IS_WAITING,
			1,
			DAO_Ticket::IS_CLOSED,
			1,
			DAO_Ticket::DUE_DATE,
			DAO_Ticket::DUE_DATE,
			time()
		);
		DAO_Ticket::updateWhere($fields, $where);

		// Close any 'waiting' tickets past their group max wait time 
		// [TODO]
		
		// Surrender any tickets past their unlock date
		$fields = array(
			DAO_Ticket::NEXT_WORKER_ID => 0,
			DAO_Ticket::UNLOCK_DATE => 0
		);
		$where = sprintf("%s > 0 AND %s > 0 AND %s < %d",
			DAO_Ticket::NEXT_WORKER_ID,
			DAO_Ticket::UNLOCK_DATE,
			DAO_Ticket::UNLOCK_DATE,
			time()
		);
		DAO_Ticket::updateWhere($fields, $where);
	}
};
