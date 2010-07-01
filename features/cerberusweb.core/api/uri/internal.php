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
class ChInternalController extends DevblocksControllerExtension {
	const ID = 'core.controller.internal';
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$worker = CerberusApplication::getActiveWorker();
		if(empty($worker)) return;
		
		$stack = $request->path;
		array_shift($stack); // internal
		
	    @$action = array_shift($stack) . 'Action';

	    switch($action) {
	        case NULL:
	            // [TODO] Index/page render
	            break;
	            
	        default:
			    // Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action));
				}
	            break;
	    }
	}
	
	// Ajax
	function showCalloutAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'string');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$callouts = CerberusApplication::getTourCallouts();
		
	    $callout = array();
	    if(isset($callouts[$id]))
	        $callout = $callouts[$id];
		
	    $tpl->assign('callout',$callout);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'internal/tour/callout.tpl');
	}

	// Post
	function doStopTourAction() {
		$worker = CerberusApplication::getActiveWorker();
		DAO_WorkerPref::set($worker->id, 'assist_mode', 0);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('home')));
	}
	
	// Contexts
	
	function showTabContextLinksAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(__FILE__))) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		// Contexts
		
		$context_extensions = DevblocksPlatform::getExtensions('devblocks.context', false);
		$tpl->assign('context_extensions', $context_extensions);
		
		// Context Links
		
		$views = array();
		$contexts = array();

		$context_links = DAO_ContextLink::getLinks($context, $context_id);
		if(is_array($context_links))
		foreach($context_links as $link) {
			if(!isset($contexts[$link->context]))
				$contexts[$link->context] = array();
		}
		unset($context_links);
		
		foreach($contexts as $ctx => $ids) {
			if(null == ($ext_context = DevblocksPlatform::getExtension($ctx, true)))
				continue;
				
			if(!$ext_context instanceof Extension_DevblocksContext)
				continue;
				
			$view = $ext_context->getView($context, $context_id);
			
			if(!empty($view))
				$views[$view->id] = $view;
		}
		
		ksort($views);
		
		$tpl->assign('views', $views);
		
		$tpl->display('file:'.$this->_TPL_PATH.'context_links/tab.tpl');
	}	
	
	function chooserOpenAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		
		if(null != ($context_extension = DevblocksPlatform::getExtension($context, true))) {
			$tpl = DevblocksPlatform::getTemplateService();
			$path = dirname(dirname(dirname(__FILE__))) . '/templates/';
			$tpl->assign('context', $context_extension);
			$tpl->assign('view', $context_extension->getChooserView());
			$tpl->display('file:'.$path.'context_links/choosers/__generic.tpl');
		}
	}
	
	function contextAddLinksAction() {
		@$from_context = DevblocksPlatform::importGPC($_REQUEST['from_context'],'string','');
		@$from_context_id = DevblocksPlatform::importGPC($_REQUEST['from_context_id'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_ids = DevblocksPlatform::importGPC($_REQUEST['context_id'],'array',array());
		
		if(is_array($context_ids))
		foreach($context_ids as $context_id)
			DAO_ContextLink::setLink($context, $context_id, $from_context, $from_context_id, true);
	}
	
	function contextDeleteLinksAction() {
		@$from_context = DevblocksPlatform::importGPC($_REQUEST['from_context'],'string','');
		@$from_context_id = DevblocksPlatform::importGPC($_REQUEST['from_context_id'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_ids = DevblocksPlatform::importGPC($_REQUEST['context_id'],'array',array());
		
		if(is_array($context_ids))
		foreach($context_ids as $context_id)
			DAO_ContextLink::deleteLink($context, $context_id, $from_context, $from_context_id, true);
	}
	
	// Snippets
	
	function snippetPasteAction() {
		@$snippet_id = DevblocksPlatform::importGPC($_REQUEST['snippet_id'],'integer',0);
		
		if(null != ($snippet = DAO_Snippet::get($snippet_id))) {
			echo $snippet->content;
		}
	}
	
	function snippetTestAction() {
		@$snippet_context = DevblocksPlatform::importGPC($_REQUEST['snippet_context'],'string','');
		//@$snippet_context_id = DevblocksPlatform::importGPC($_REQUEST['snippet_context_id'],'integer',0);
		@$snippet_field = DevblocksPlatform::importGPC($_REQUEST['snippet_field'],'string','');

		$content = '';
		if(isset($_REQUEST[$snippet_field]))
			$content = DevblocksPlatform::importGPC($_REQUEST[$snippet_field]);
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$token_labels = array();
		$token_value = array();
		
		switch($snippet_context) {
			case 'cerberusweb.contexts.plaintext':
				break;
				
			case 'cerberusweb.contexts.ticket':
				// [TODO] Randomize
				list($result, $count) = DAO_Ticket::search(
					array(),
					array(
					),
					10,
					0,
					SearchFields_Ticket::TICKET_UPDATED_DATE,
					false,
					false
				);
				
				shuffle($result);
				
				CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, array_shift($result), $token_labels, $token_values);
				break;
				
			case 'cerberusweb.contexts.worker':
				$active_worker = CerberusApplication::getActiveWorker();
				CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $active_worker, $token_labels, $token_values);
				break;
		}

		$success = false;
		$output = '';
		
		if(!empty($token_values)) {
			// Try to build the template
			if(false === ($out = $tpl_builder->build($content, $token_values))) {
				// If we failed, show the compile errors
				$errors = $tpl_builder->getErrors();
				$success= false;
				$output = @array_shift($errors);
			} else {
				// If successful, return the parsed template
				$success = true;
				$output = $out;
			}
		}
		
		$tpl->assign('success', $success);
		$tpl->assign('output', htmlentities($output, null, LANG_CHARSET_CODE));
		$tpl->display('file:'.$this->_TPL_PATH.'internal/renderers/test_results.tpl');
	}
	
	// Ajax
	function viewRefreshAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		$view = C4_AbstractViewLoader::getView($id);
		$view->render();
	}
	
	function viewSortByAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$sortBy = DevblocksPlatform::importGPC($_REQUEST['sortBy']);
		
		$view = C4_AbstractViewLoader::getView($id);
		$view->doSortBy($sortBy);
		C4_AbstractViewLoader::setView($id, $view);
		
		$view->render();
	}
	
	function viewPageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$page = DevblocksPlatform::importGPC(DevblocksPlatform::importGPC($_REQUEST['page']));
		
		$view = C4_AbstractViewLoader::getView($id);
		$view->doPage($page);
		C4_AbstractViewLoader::setView($id, $view);
		
		$view->render();
	}
	
	function viewGetCriteriaAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);
		
		$view = C4_AbstractViewLoader::getView($id);
		$view->renderCriteria($field);
	}
	
	private function _viewRenderInlineFilters($view) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view', $view);
		$tpl->display('file:' . $this->_TPL_PATH . 'internal/views/customize_view_criteria.tpl');
	}
	
	// Ajax
	function viewAddFilterAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);
		@$oper = DevblocksPlatform::importGPC($_REQUEST['oper']);
		@$value = DevblocksPlatform::importGPC($_REQUEST['value']);
		@$field_deletes = DevblocksPlatform::importGPC($_REQUEST['field_deletes'],'array',array());
		
		$view = C4_AbstractViewLoader::getView($id);

		// [TODO] Nuke criteria
		if(is_array($field_deletes) && !empty($field_deletes)) {
			foreach($field_deletes as $field_delete) {
				$view->doRemoveCriteria($field_delete);
			}
		}
		
		if(!empty($field)) {
			$view->doSetCriteria($field, $oper, $value);
		}
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$this->_viewRenderInlineFilters($view);
	}
	
	function viewResetFiltersAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$view = C4_AbstractViewLoader::getView($id);

		$view->doResetCriteria();
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$this->_viewRenderInlineFilters($view);		
	}
	
	function viewLoadPresetAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$preset_id = DevblocksPlatform::importGPC($_REQUEST['_preset'],'integer',0);

		$view = C4_AbstractViewLoader::getView($id);

		$view->removeAllParams();
		
		if(null != ($preset = DAO_ViewFiltersPreset::get($preset_id))) {
			if(is_array($preset->params))
			foreach($preset->params as $data) {
				$view->addParam(new DevblocksSearchCriteria($data['field'], $data['operator'], $data['value']));
			}
		}
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$this->_viewRenderInlineFilters($view);
	}
	
	function viewAddPresetAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$preset_name = DevblocksPlatform::importGPC($_REQUEST['_preset_name'],'string','');

		$active_worker = CerberusApplication::getActiveWorker();
		

		$view = C4_AbstractViewLoader::getView($id);

		$fields = array(
			DAO_ViewFiltersPreset::NAME => !empty($preset_name) ? $preset_name : 'New Preset',
			DAO_ViewFiltersPreset::VIEW_CLASS => get_class($view),
			DAO_ViewFiltersPreset::WORKER_ID => $active_worker->id,
			DAO_ViewFiltersPreset::PARAMS_JSON => json_encode($view->getEditableParams()),
		);
		
		DAO_ViewFiltersPreset::create($fields);
		
		$this->_viewRenderInlineFilters($view);
	}
	
	function viewEditPresetsAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$preset_dels = DevblocksPlatform::importGPC($_REQUEST['_preset_del'],'array',array());

		$view = C4_AbstractViewLoader::getView($id);

		DAO_ViewFiltersPreset::delete($preset_dels);
		
		$this->_viewRenderInlineFilters($view);		
	}
	
	
	// Ajax
	function viewCustomizeAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH . '');
		$tpl->assign('id', $id);

		$view = C4_AbstractViewLoader::getView($id);
		$tpl->assign('view', $view);

		$tpl->display('file:' . $this->_TPL_PATH . 'internal/views/customize_view.tpl');
	}
	
	function viewShowCopyAction() {
        @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');

		$active_worker = CerberusApplication::getActiveWorker();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH . '';
		$tpl->assign('path', $tpl_path);
        
        $view = C4_AbstractViewLoader::getView($view_id);

		$workspaces = DAO_WorkerWorkspaceList::getWorkspaces($active_worker->id);
		$tpl->assign('workspaces', $workspaces);
        
        $tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);

        $tpl->display($tpl_path.'internal/views/copy.tpl');
	}
	
	function viewDoCopyAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
	    @$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
	    
		@$list_title = DevblocksPlatform::importGPC($_POST['list_title'],'string', '');
		@$workspace = DevblocksPlatform::importGPC($_POST['workspace'],'string', '');
		@$new_workspace = DevblocksPlatform::importGPC($_POST['new_workspace'],'string', '');
		
		if(empty($workspace) && empty($new_workspace))
			$new_workspace = $translate->_('mail.workspaces.new');
			
		if(empty($list_title))
			$list_title = $translate->_('mail.workspaces.new_list');
		
		$workspace_name = (!empty($new_workspace) ? $new_workspace : $workspace);
		
        // Find the proper workspace source based on the class of the view
        $source_manifests = DevblocksPlatform::getExtensions(Extension_WorkspaceSource::EXTENSION_POINT, false);
        $source_manifest = null;
        if(is_array($source_manifests))
        foreach($source_manifests as $mft) {
        	if(is_a($view, $mft->params['view_class'])) {
				$source_manifest = $mft;       		
        		break;
        	}
        }
		
        if(!is_null($source_manifest)) {
			// View params inside the list for quick render overload
			$list_view = new Model_WorkerWorkspaceListView();
			$list_view->title = $list_title;
			$list_view->num_rows = $view->renderLimit;
			$list_view->columns = $view->view_columns;
			$list_view->params = $view->getEditableParams();
			$list_view->sort_by = $view->renderSortBy;
			$list_view->sort_asc = $view->renderSortAsc;
			
			// Save the new worklist
			$fields = array(
				DAO_WorkerWorkspaceList::WORKER_ID => $active_worker->id,
				DAO_WorkerWorkspaceList::WORKSPACE => $workspace_name,
				DAO_WorkerWorkspaceList::SOURCE_EXTENSION => $source_manifest->id,
				DAO_WorkerWorkspaceList::LIST_VIEW => serialize($list_view),
				DAO_WorkerWorkspaceList::LIST_POS => 99,
			);
			$list_id = DAO_WorkerWorkspaceList::create($fields);
        }
		
		// Select the workspace tab
		$visit->set(CerberusVisit::KEY_HOME_SELECTED_TAB, 'w_'.$workspace_name);
        
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('home')));
	}

	// Ajax
	function viewShowExportAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH . '');
		$tpl->assign('view_id', $view_id);

		$view = C4_AbstractViewLoader::getView($view_id);
		$tpl->assign('view', $view);
		
		$model_columns = $view->getColumnsAvailable();
		$tpl->assign('model_columns', $model_columns);
		
		$view_columns = $view->view_columns;
		$tpl->assign('view_columns', $view_columns);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'internal/views/view_export.tpl');
	}
	
	function viewDoExportAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);
		@$columns = DevblocksPlatform::importGPC($_REQUEST['columns'],'array',array());
		@$export_as = DevblocksPlatform::importGPC($_REQUEST['export_as'],'string','csv');

		// Scan through the columns and remove any blanks
		if(is_array($columns))
		foreach($columns as $idx => $col) {
			if(empty($col))
				unset($columns[$idx]);
		}
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$column_manifests = $view->getColumnsAvailable();

		// Override display
		$view->view_columns = $columns;
		$view->renderPage = 0;
		$view->renderLimit = -1;

		if('csv' == $export_as) {
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: text/plain; charset=".LANG_CHARSET_CODE);
			
			// Column headers
			if(is_array($columns)) {
				$cols = array();
				foreach($columns as $col) {
					$cols[] = sprintf("\"%s\"",
						str_replace('"','\"',mb_convert_case($column_manifests[$col]->db_label,MB_CASE_TITLE))
					);
				}
				echo implode(',', $cols) . "\r\n";
			}
			
			// Get data
			list($results, $null) = $view->getData();
			if(is_array($results))
			foreach($results as $row) {
				if(is_array($row)) {
					$cols = array();
					if(is_array($columns))
					foreach($columns as $col) {
						$cols[] = sprintf("\"%s\"",
							str_replace('"','\"',$row[$col])
						);
					}
					echo implode(',', $cols) . "\r\n";
				}
			}
			
		} elseif('xml' == $export_as) {
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: text/plain; charset=".LANG_CHARSET_CODE);
			
			$xml = simplexml_load_string("<results/>"); /* @var $xml SimpleXMLElement */
			
			// Get data
			list($results, $null) = $view->getData();
			if(is_array($results))
			foreach($results as $row) {
				$result =& $xml->addChild("result");
				if(is_array($columns))
				foreach($columns as $col) {
					$field =& $result->addChild("field",htmlspecialchars($row[$col],null,LANG_CHARSET_CODE));
					$field->addAttribute("id", $col);
				}
			}
		
			// Pretty format and output
			$doc = new DOMDocument('1.0');
			$doc->preserveWhiteSpace = false;
			$doc->loadXML($xml->asXML());
			$doc->formatOutput = true;
			echo $doc->saveXML();			
		}
		
		exit;
	}
	
	// Post?
	function viewSaveCustomizeAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$columns = DevblocksPlatform::importGPC($_REQUEST['columns'],'array', array());
		@$num_rows = DevblocksPlatform::importGPC($_REQUEST['num_rows'],'integer',10);
		
		$num_rows = max($num_rows, 1); // make 1 the minimum
		
		$view = C4_AbstractViewLoader::getView($id);
		$view->doCustomize($columns, $num_rows);

		$active_worker = CerberusApplication::getActiveWorker();
		
		// Conditional Persist
		if(substr($id,0,5)=="cust_") { // custom workspace
			$list_view_id = intval(substr($id,5));
			
			// Special custom view fields
			@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string', $translate->_('views.new_list'));
			
			$view->name = $title;

			// Persist Object
			$list_view = new Model_WorkerWorkspaceListView();
			$list_view->title = $title;
			$list_view->columns = $view->view_columns;
			$list_view->num_rows = $view->renderLimit;
			$list_view->params = $view->getEditableParams();
			$list_view->sort_by = $view->renderSortBy;
			$list_view->sort_asc = $view->renderSortAsc;
			
			DAO_WorkerWorkspaceList::update($list_view_id, array(
				DAO_WorkerWorkspaceList::LIST_VIEW => serialize($list_view)
			));
			
		} else {
			$prefs = new C4_AbstractViewModel();
			$prefs->class_name = get_class($view);
			$prefs->view_columns = $view->view_columns;
			$prefs->renderLimit = $view->renderLimit;
			$prefs->renderSortBy = $view->renderSortBy;
			$prefs->renderSortAsc = $view->renderSortAsc;
			
			DAO_WorkerPref::set($active_worker->id, 'view'.$view->id, serialize($prefs));
		}
		
		C4_AbstractViewLoader::setView($id, $view);
		
		$view->render();
	}
	
	function startAutoRefreshAction() {
		$url = DevblocksPlatform::importGPC($_REQUEST['url'],'string', '');
		$secs = DevblocksPlatform::importGPC($_REQUEST['secs'],'integer', 300);
		
		$_SESSION['autorefresh'] = array(
			'url' => $url,
			'started' => time(),
			'secs' => $secs,
		);
	}
	
	function stopAutoRefreshAction() {
		unset($_SESSION['autorefresh']);
	}
	
	function transformMarkupToHTMLAction() {
		$format = DevblocksPlatform::importGPC($_REQUEST['format'],'string', '');
		$data = DevblocksPlatform::importGPC($_REQUEST['data'],'string', '');
		
		switch($format) {
			case 'markdown':
				echo DevblocksPlatform::parseMarkdown($data);
				break;
			case 'html':
			default:
				echo $data;
				break;
		}
	}

	// Comments
	
	function showTabContextCommentsAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(__FILE__))) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);

		$comments = DAO_Comment::getByContext($context, $context_id);
		$tpl->assign('comments', $comments);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'internal/comments/tab.tpl');
	}
	
	function commentShowPopupAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH . '');
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'internal/comments/peek.tpl');
	}
	
	function commentSavePopupAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
		
		// Worker is logged in
		if(null === ($active_worker = CerberusApplication::getActiveWorker()))
			return;
		
		
		// Form was filled in
		if(empty($context) || empty($context_id) || empty($comment))
			return;
			
		$fields = array(
			DAO_Comment::CONTEXT => $context,
			DAO_Comment::CONTEXT_ID => $context_id,
			DAO_Comment::ADDRESS_ID => $active_worker->getAddress()->id,
			DAO_Comment::COMMENT => $comment,
			DAO_Comment::CREATED => time(),
		);
		$comment_id = DAO_Comment::create($fields);
		
//		// Worker address exists
//		if(null === ($address = CerberusApplication::hashLookupAddress($active_worker->email,true)))
//			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket_id)));
			
//		@$ticket = DAO_Ticket::get($ticket_id);
		
		
		// Notifications
		$url_writer = DevblocksPlatform::getUrlService();
		@$notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
		if(is_array($notify_worker_ids) && !empty($notify_worker_ids))
		foreach($notify_worker_ids as $notify_worker_id) {
			$fields = array(
				DAO_WorkerEvent::CREATED_DATE => time(),
				DAO_WorkerEvent::WORKER_ID => $notify_worker_id,
				//DAO_WorkerEvent::URL => $url_writer->write('c=display&id='.$ticket->mask,true),
				DAO_WorkerEvent::TITLE => 'New Comment', // [TODO] Translate
				DAO_WorkerEvent::CONTENT => sprintf("%s comments: %s", $active_worker->getName(), $comment), // [TODO] Translate
			);
			DAO_WorkerEvent::create($fields);
		}
		
//		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket->mask,'comments')));		
		
	}
	
	function commentDeleteAction() {
		@$comment_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		DAO_Comment::delete($comment_id);
	}
};
