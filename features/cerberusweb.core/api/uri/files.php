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
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class ChFilesController extends DevblocksControllerExtension {
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$translate = DevblocksPlatform::getTranslationService();
		
		$stack = $request->path;				// URLS like: /files/10000/plaintext.txt
		array_shift($stack);					// files	
		$file_guid = array_shift($stack); 		// GUID
		$file_name = array_shift($stack); 		// plaintext.txt

		// Security
		if(null == ($active_worker = CerberusApplication::getActiveWorker()))
			die($translate->_('common.access_denied'));
		
		if(empty($file_guid) || empty($file_name))
			die($translate->_('files.not_found'));
		
		$link = DAO_AttachmentLink::getByGUID($file_guid);
		
		if(null == ($context = $link->getContext()))
			die($translate->_('common.access_denied'));
		
		// Security
		if(!$context->authorize($link->context_id, $active_worker))
			die($translate->_('common.access_denied'));
			
		$file = $link->getAttachment();
		
		
		if(false === ($fp = DevblocksPlatform::getTempFile()))
			die("Could not open a temporary file.");
		if(false === $file->getFileContents($fp))
			die("Error reading resource.");
			
		$file_stats = fstat($fp);

		// Set headers
		header("Expires: Mon, 26 Nov 1962 00:00:00 GMT\n");
		header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT\n");
		header("Cache-control: private\n");
		header("Pragma: no-cache\n");
		header("Content-Type: " . $file->mime_type . "\n");
		header("Content-Transfer-Encoding: binary\n"); 
		header("Content-Length: " . $file_stats['size'] . "\n");
		
		fpassthru($fp);
		fclose($fp);
		
		exit;
	}
};
