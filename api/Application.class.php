<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
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
define("APP_BUILD", 2011051203);
define("APP_VERSION", '5.4.1');

define("APP_MAIL_PATH", APP_STORAGE_PATH . '/mail/');

require_once(APP_PATH . "/api/Model.class.php");
require_once(APP_PATH . "/api/Extension.class.php");

// App Scope ClassLoading
$path = APP_PATH . '/api/app/';

DevblocksPlatform::registerClasses($path . 'Mail.php', array(
	'CerberusMail',
));

DevblocksPlatform::registerClasses($path . 'Parser.php', array(
	'CerberusParser',
	'CerberusParserMessage',
	'ParserFile',
	'ParserFileBuffer',
));

DevblocksPlatform::registerClasses($path . 'Update.php', array(
	'ChUpdateController',
));

DevblocksPlatform::registerClasses($path . 'Utils.php', array(
	'CerberusUtils',
));

/**
 * Application-level Facade
 */
class CerberusApplication extends DevblocksApplication {
	const INDEX_TICKETS = 'tickets';
		
	const VIEW_SEARCH = 'search';
	const VIEW_MAIL_WORKFLOW = 'mail_workflow';
	const VIEW_MAIL_MESSAGES = 'mail_messages';
	
	/**
	 * @return CerberusVisit
	 */
	static function getVisit() {
		$session = DevblocksPlatform::getSessionService();
		return $session->getVisit();
	}
	
	/**
	 * @return Model_Worker
	 */
	static function getActiveWorker() {
		$visit = self::getVisit();
		return (null != $visit) 
			? $visit->getWorker()
			: null
			;
	}
	
	/**
	 * 
	 * @param string $uri
	 * @return DevblocksExtensionManifest or NULL
	 */
	static function getPageManifestByUri($uri) {
        $pages = DevblocksPlatform::getExtensions('cerberusweb.page', false);
        foreach($pages as $manifest) { /* @var $manifest DevblocksExtensionManifest */
            if(0 == strcasecmp($uri,$manifest->params['uri'])) {
                return $manifest;
            }
        }
        return NULL;
	}
	
	static function processRequest(DevblocksHttpRequest $request, $is_ajax=false) {
		/**
		 * Override the 'update' URI since we can't count on the database 
		 * being populated from XML beforehand when /update loads it.
		 */
		if(!$is_ajax && isset($request->path[0]) && 0 == strcasecmp($request->path[0],'update')) {
			if(null != ($update_controller = new ChUpdateController(null)))
				$update_controller->handleRequest($request);
			
		} else {
			// Hand it off to the platform
			DevblocksPlatform::processRequest($request, $is_ajax);
		}
	}
	
	static function checkRequirements() {
		$errors = array();
		
		// Privileges
		
		// Make sure the temporary directories of Devblocks are writeable.
		if(!is_writeable(APP_TEMP_PATH)) {
			$errors[] = APP_TEMP_PATH ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!file_exists(APP_TEMP_PATH . "/templates_c")) {
			@mkdir(APP_TEMP_PATH . "/templates_c");
		}
		
		if(!is_writeable(APP_TEMP_PATH . "/templates_c/")) {
			$errors[] = APP_TEMP_PATH . "/templates_c/" . " is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}

		if(!file_exists(APP_TEMP_PATH . "/cache")) {
			@mkdir(APP_TEMP_PATH . "/cache");
		}
		
		if(!is_writeable(APP_TEMP_PATH . "/cache/")) {
			$errors[] = APP_TEMP_PATH . "/cache/" . " is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(APP_STORAGE_PATH)) {
			$errors[] = APP_STORAGE_PATH ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(APP_STORAGE_PATH . "/import/fail")) {
			$errors[] = APP_STORAGE_PATH . "/import/fail/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(APP_STORAGE_PATH . "/import/new")) {
			$errors[] = APP_STORAGE_PATH . "/import/new/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(APP_STORAGE_PATH . "/mail/new/")) {
			$errors[] = APP_STORAGE_PATH . "/mail/new/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(APP_STORAGE_PATH . "/mail/fail/")) {
			$errors[] = APP_STORAGE_PATH . "/mail/fail/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		// Requirements
		
		// PHP Version
		if(version_compare(PHP_VERSION,"5.2") >=0) {
		} else {
			$errors[] = sprintf("Cerberus Helpdesk %s requires PHP 5.2 or later. Your server PHP version is %s",
				APP_VERSION,
				PHP_VERSION 
			);
		}
		
		// File Uploads
		$ini_file_uploads = ini_get("file_uploads");
		if($ini_file_uploads == 1 || strcasecmp($ini_file_uploads,"on")==0) {
		} else {
			$errors[] = 'file_uploads is disabled in your php.ini file. Please enable it.';
		}
		
		// File Upload Temporary Directory
		// [TODO] This isn't fatal
//		$ini_upload_tmp_dir = ini_get("upload_tmp_dir");
//		if(!empty($ini_upload_tmp_dir)) {
//		} else {
//			$errors[] = 'upload_tmp_dir is empty in your php.ini file.	Please set it.';
//		}
		
		// Memory Limit
		$memory_limit = ini_get("memory_limit");
		if ($memory_limit == '') { // empty string means failure or not defined, assume no compiled memory limits
		} else {
			$ini_memory_limit = DevblocksPlatform::parseBytesString($memory_limit);
			if($ini_memory_limit < 16777216) {
				$errors[] = 'memory_limit must be 16M or larger (32M recommended) in your php.ini file.  Please increase it.';
			}
		}
		
		// Extension: MySQL
		if(extension_loaded("mysql")) {
		} else {
			$errors[] = "The 'MySQL' PHP extension is required.  Please enable it.";
		}
		
		// Extension: Sessions
		if(extension_loaded("session")) {
		} else {
			$errors[] = "The 'Session' PHP extension is required.  Please enable it.";
		}
		
		// Extension: PCRE
		if(extension_loaded("pcre")) {
		} else {
			$errors[] = "The 'PCRE' PHP extension is required.  Please enable it.";
		}
		
		// Extension: GD
		if(extension_loaded("gd") && function_exists('imagettfbbox')) {
		} else {
			$errors[] = "The 'GD' PHP extension (with FreeType library support) is required.  Please enable them.";
		}
		
		// Extension: IMAP
		if(extension_loaded("imap")) {
		} else {
			$errors[] = "The 'IMAP' PHP extension is required.  Please enable it.";
		}
		
		// Extension: MailParse
		if(extension_loaded("mailparse")) {
		} else {
			$errors[] = "The 'MailParse' PHP extension is required.  Please enable it.";
		}
		
		// Extension: mbstring
		if(extension_loaded("mbstring")) {
		} else {
			$errors[] = "The 'MbString' PHP extension is required.  Please	enable it.";
		}
		
		// Extension: XML
		if(extension_loaded("xml")) {
		} else {
			$errors[] = "The 'XML' PHP extension is required.  Please enable it.";
		}
		
		// Extension: SimpleXML
		if(extension_loaded("simplexml")) {
		} else {
			$errors[] = "The 'SimpleXML' PHP extension is required.  Please enable it.";
		}
		
		// Extension: DOM
		if(extension_loaded("dom")) {
		} else {
			$errors[] = "The 'DOM' PHP extension is required.  Please enable it.";
		}
		
		// Extension: SPL
		if(extension_loaded("spl")) {
		} else {
			$errors[] = "The 'SPL' PHP extension is required.  Please enable it.";
		}
		
		// Extension: JSON
		if(extension_loaded("json")) {
		} else {
			$errors[] = "The 'JSON' PHP extension is required.  Please enable it.";
		}
		
		return $errors;
	}
	
	static function update() {
		// Update the platform
		if(!DevblocksPlatform::update())
			throw new Exception("Couldn't update Devblocks.");
			
		// Read in plugin information from the filesystem to the database
		DevblocksPlatform::readPlugins();
		
		// Clean up missing plugins
		DAO_Platform::cleanupPluginTables();
		DAO_Platform::maint();
		
		// Registry
		$plugins = DevblocksPlatform::getPluginRegistry();
		
		// Update the application core (version by version)
		if(!isset($plugins['cerberusweb.core']))
			throw new Exception("Couldn't read application manifest.");
	
		$plugin_patches = array();

		// Load patches
		foreach($plugins as $p) { /* @var $p DevblocksPluginManifest */
			if('devblocks.core'==$p->id)
				continue;
			
			// Don't patch disabled plugins
			if($p->enabled)
				$plugin_patches[$p->id] = $p->getPatches();
		}
		
		$core_patches = $plugin_patches['cerberusweb.core'];
		unset($plugin_patches['cerberusweb.core']);
		
		/*
		 * For each core release, patch plugins in dependency order
		 */
		foreach($core_patches as $patch) { /* @var $patch DevblocksPatch */
			if(!file_exists($patch->getFilename()))
				throw new Exception("Missing application patch: ".$path);
			
			$version = $patch->getVersion();
			
			if(!$patch->run())
				throw new Exception("Application patch failed to apply: ".$path);
			
			// Patch this version and then patch plugins up to this version
			foreach($plugin_patches as $plugin_id => $patches) {
				$pass = true;
				foreach($patches as $k => $plugin_patch) {
					// Recursive patch up to _version_
					if($pass && version_compare($plugin_patch->getVersion(), $version, "<=")) {
						if($plugin_patch->run()) {
							unset($plugin_patches[$plugin_id][$k]);
						} else {
							$plugins[$plugin_id]->setEnabled(false);
							$pass = false;
						}
					}
				}
			}
		}
		
		return TRUE;
	}	
	
	static function generatePassword($length=8) {
		$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ123456789';
		$len = strlen($chars)-1;
		$password = '';
		
		for($x=0;$x<$length;$x++) {
			$chars = str_shuffle($chars);
			$password .= substr($chars,mt_rand(0,$len),1);
		}
		
		return $password;		
	}
	
	/**
	 * Enter description here...
	 *
	 * @return a unique ticket mask as a string
	 */
	static function generateTicketMask($pattern = "LLL-NNNNN-NNN") {
		$letters = "ABCDEFGHIJKLMNPQRSTUVWXYZ";
		$numbers = "123456789";

		do {		
			$mask = "";
			$bytes = preg_split('//', $pattern, -1, PREG_SPLIT_NO_EMPTY);
			
			if(is_array($bytes))
			foreach($bytes as $byte) {
				switch(strtoupper($byte)) {
					case 'L':
						$mask .= substr($letters,mt_rand(0,strlen($letters)-1),1);
						break;
					case 'N':
						$mask .= substr($numbers,mt_rand(0,strlen($numbers)-1),1);
						break;
					case 'C': // L or N
						if(mt_rand(0,100) >= 50) { // L
							$mask .= substr($letters,mt_rand(0,strlen($letters)-1),1);	
						} else { // N
							$mask .= substr($numbers,mt_rand(0,strlen($numbers)-1),1);	
						}
						break;
					case 'Y':
						$mask .= date('Y');
						break;
					case 'M':
						$mask .= date('m');
						break;
					case 'D':
						$mask .= date('d');
						break;
					default:
						$mask .= $byte;
						break;
				}
			}
		} while(null != DAO_Ticket::getTicketIdByMask($mask));
		
//		echo "Generated unique mask: ",$mask,"<BR>";
		
		return $mask;
	}
	
	/**
	 * Generate an RFC-compliant Message-ID
	 */
	static function generateMessageId() {
		$message_id = sprintf('<%s.%s@%s>', base_convert(time(), 10, 36), base_convert(mt_rand(), 10, 36), !empty($_SERVER['HTTP_HOST']) ?  $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
		return $message_id;
	}
	
	/**
	 * Translates the string version of a group/bucket combo into their 
	 * respective IDs.
	 * 
	 * @todo This needs a better name and home
	 */
	static function translateTeamCategoryCode($code) {
		$t_or_c = substr($code,0,1);
		$t_or_c_id = intval(substr($code,1));
		
		if($t_or_c=='c') {
			$categories = DAO_Bucket::getAll();
			$team_id = $categories[$t_or_c_id]->team_id;
			$category_id = $t_or_c_id; 
		} else {
			$team_id = $t_or_c_id;
                        if($t_or_c=='a') {
                                $category_id = -1;
                        } else {
                                $category_id = 0;
                        }
		}
		
		return array($team_id, $category_id);
	}
	
	/**
	 * Looks up an e-mail address using a revolving cache.  This is helpful 
	 * in situations where you may look up the same e-mail address multiple 
	 * times (reports, audit log, views) and you don't want to waste code 
	 * filtering out dupes.
	 * 
	 * @param string $address The e-mail address to look up
	 * @param bool $create Should the address be created if not found?
	 * @return Model_Address The address object or NULL 
	 * 
	 * @todo [JAS]: Move this to a global cache/hash registry
	 */
	static public function hashLookupAddress($email, $create=false) {
	    static $hash_address_to_id = array();
	    static $hash_hits = array();
	    static $hash_size = 0;
	    
	    if(isset($hash_address_to_id[$email])) {
	    	$return = $hash_address_to_id[$email];
	    	
	        @$hash_hits[$email] = intval($hash_hits[$email]) + 1;
	        $hash_size++;
	        
	        // [JAS]: if our hash grows past our limit, crop hits array + intersect keys
	        if($hash_size > 250) {
	            arsort($hash_hits);
	            $hash_hits = array_slice($hash_hits,0,100,true);
	            $hash_address_to_id = array_intersect_key($hash_address_to_id,$hash_hits);
	            $hash_size = count($hash_address_to_id);
	        }
	        
	        return $return;
	    }
	    
	    $address = DAO_Address::lookupAddress($email, $create);
	    if(!empty($address)) {
	        $hash_address_to_id[$email] = $address;
	    }
	    return $address;
	}

	/**
	 * Looks up a ticket ID by the provided mask using a revolving cache.
	 * This is useful if you need to translate several ticket masks into 
	 * IDs where there may be a lot of redundancy (batches in the e-mail 
	 * parser, etc.)
	 * 
	 * @param string $mask The ticket mask to look up
	 * @return integer The ticket id, or NULL if not found
	 *  
	 * @todo [JAS]: Move this to a global cache/hash registry 
	 */
	static public function hashLookupTicketIdByMask($mask) {
	    static $hash_mask_to_id = array();
	    static $hash_hits = array();
	    static $hash_size = 0;
	    
	    if(isset($hash_mask_to_id[$mask])) {
	    	$return = $hash_mask_to_id[$mask];
	    	
	        @$hash_hits[$mask] = intval($hash_hits[$mask]) + 1;
	        $hash_size++;

	        // [JAS]: if our hash grows past our limit, crop hits array + intersect keys
	        if($hash_size > 250) {
	            arsort($hash_hits);
	            $hash_hits = array_slice($hash_hits,0,100,true);
	            $hash_mask_to_id = array_intersect_key($hash_mask_to_id,$hash_hits);
	            $hash_size = count($hash_mask_to_id);
	        }
	        
	        return $return;
	    }
	    
	    $ticket_id = DAO_Ticket::getTicketIdByMask($mask);
	    if(!empty($ticket_id)) {
	        $hash_mask_to_id[$mask] = $ticket_id;
	    }
	    return $ticket_id;
	}
};

interface IContextToken {
	static function getValue($context, $context_values);
};

class CerberusContexts {
	private static $_default_actor_context = null;
	private static $_default_actor_context_id = null;
	
	const CONTEXT_ADDRESS = 'cerberusweb.contexts.address';
	const CONTEXT_ATTACHMENT = 'cerberusweb.contexts.attachment';
	const CONTEXT_BUCKET = 'cerberusweb.contexts.bucket';
	const CONTEXT_CALL = 'cerberusweb.contexts.call';
	const CONTEXT_COMMENT = 'cerberusweb.contexts.comment';
	const CONTEXT_CONTACT_PERSON = 'cerberusweb.contexts.contact_person';
	const CONTEXT_FEEDBACK = 'cerberusweb.contexts.feedback';
	const CONTEXT_GROUP = 'cerberusweb.contexts.group';
	const CONTEXT_KB_ARTICLE = 'cerberusweb.contexts.kb_article';
	const CONTEXT_KB_CATEGORY = 'cerberusweb.contexts.kb_category';
	const CONTEXT_MESSAGE = 'cerberusweb.contexts.message';
	const CONTEXT_NOTIFICATION= 'cerberusweb.contexts.notification';
	const CONTEXT_OPPORTUNITY = 'cerberusweb.contexts.opportunity';
	const CONTEXT_ORG = 'cerberusweb.contexts.org';
	const CONTEXT_PORTAL = 'cerberusweb.contexts.portal';
	const CONTEXT_SNIPPET = 'cerberusweb.contexts.snippet';
	const CONTEXT_TASK = 'cerberusweb.contexts.task';
	const CONTEXT_TICKET = 'cerberusweb.contexts.ticket';
	const CONTEXT_TIMETRACKING = 'cerberusweb.contexts.timetracking';
	const CONTEXT_WORKER = 'cerberusweb.contexts.worker';
	
	public static function getContext($context, $context_object, &$labels, &$values, $prefix=null, $nested=false) {
		switch($context) {
			case 'cerberusweb.contexts.attachment':
				self::_getAttachmentContext($context_object, $labels, $values, $prefix);
				break;
			case 'cerberusweb.contexts.bucket':
				self::_getBucketContext($context_object, $labels, $values, $prefix);
				break;
			default:
				// Migrated
				if(null != ($ctx = DevblocksPlatform::getExtension($context, true)) 
					&& $ctx instanceof Extension_DevblocksContext) {
						$ctx->getContext($context_object, $labels, $values, $prefix);
				}
				break;
		}

		if(!$nested) {
			// Globals
			CerberusContexts::merge(
				'global_',
				'(Global) ',
				array(
					'timestamp|date' => 'Current Date+Time',
				),
				array(
					'timestamp' => time(),
				),
				$labels,
				$values
			);
			
			// Current worker (Don't add to worker context)
			if($context != CerberusContexts::CONTEXT_WORKER) {
				$active_worker = CerberusApplication::getActiveWorker();
				$merge_token_labels = array();
				$merge_token_values = array();
				self::getContext(self::CONTEXT_WORKER, $active_worker, $merge_token_labels, $merge_token_values, '', true);
		
				CerberusContexts::merge(
					'worker_',
					'Current:Worker:',
					$merge_token_labels,
					$merge_token_values,
					$labels,
					$values
				);
			}
			
			// Plugin-provided tokens
			$token_extension_mfts = DevblocksPlatform::getExtensions('cerberusweb.snippet.token', false);
			foreach($token_extension_mfts as $mft) { /* @var $mft DevblocksExtensionManifest */
				@$token = $mft->params['token'];
				@$label = $mft->params['label'];
				@$contexts = $mft->params['contexts'][0];
				
				if(empty($token) || empty($label) || !is_array($contexts))
					continue;
	
				if(!isset($contexts[$context]))
					continue;
					
				if(null != ($ext = $mft->createInstance()) && $ext instanceof IContextToken) {
					/* @var $ext IContextToken */
					$value = $ext->getValue($context, $values);
					
					if(!empty($value)) {
						$labels['plugin_'.$token] = '(Plugin) '.$label;
						$values['plugin_'.$token] = $value;
					}
				}
			}
		}
		
		
		// Rename labels
		foreach($labels as $idx => $label) {
			// [TODO] mb_*
			$labels[$idx] = ucfirst(strtolower(strtr($label,':',' ')));
		}
		
		// Alphabetize
		asort($labels);
		
		return null;
	}
	
	public static function scrubTokensWithRegexp(&$labels, &$values, $patterns=array()) {
		foreach($patterns as $pattern) {
			foreach(array_keys($labels) as $token) {
				if(preg_match($pattern, $token)) {
					unset($labels[$token]);
				}
			}
			foreach(array_keys($values) as $token) {
				if(false !== ($pos = strpos($token,'|')))
					$token = substr($token,0,$pos);
				
				if(preg_match($pattern, $token)) {
					unset($values[$token]);
				}
			}
		}

		return TRUE;
	}
	
	/**
	 * 
	 * @param string $token_prefix
	 * @param array $label_replace
	 * @param array $src_labels
	 * @param array $dst_labels
	 * @param array $src_values
	 * @param array $dst_values
	 * @return void
	 */
	public static function merge($token_prefix, $label_prefix, $src_labels, $src_values, &$dst_labels, &$dst_values) {
		foreach($src_labels as $token => $label) {
			$dst_labels[$token_prefix.$token] = $label_prefix.$label; 
		}
		
		foreach($src_values as $token => $value) {
			$dst_values[$token_prefix.$token] = $src_values[$token];
		}

		return true;
	}
	
	static public function getWatchers($context, $context_id) {
		list($results, $null) = DAO_Worker::search(
			array(
				SearchFields_Worker::ID,
			),
			array(
				new DevblocksSearchCriteria(SearchFields_Worker::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Worker::CONTEXT_LINK_ID,'=',$context_id),
			),
			0,
			0,
			null,
			null,
			false
		);
		
		$workers = array();
		
		if(!empty($results)) {
			$workers = DAO_Worker::getWhere(sprintf("%s IN (%s)",
				DAO_Worker::ID,
				implode(',', array_keys($results))
			));
		}
		
		return $workers;
	}
	
//	static public function setWatchers($context, $context_id, $worker_ids) {
//		if(!is_array($worker_ids))
//			$worker_ids = array($worker_ids);
//		
//		$current_workers = self::getWatchers($context, $context_id);
//		
//		// Remove
//		if(is_array($current_workers))
//		foreach($current_workers as $current_worker_id => $current_worker) {
//			if(false === array_search($current_worker_id, $worker_ids))
//				DAO_ContextLink::deleteLink($context, $context_id, CerberusContexts::CONTEXT_WORKER, $current_worker_id);
//		}
//		
//		// Add
//		if(is_array($worker_ids))
//		foreach($worker_ids as $worker_id) {
//			DAO_ContextLink::setLink($context, $context_id, CerberusContexts::CONTEXT_WORKER, $worker_id);
//		}
//	}

	static public function addWatchers($context, $context_id, $worker_ids) {
		if(!is_array($worker_ids))
			$worker_ids = array($worker_ids);
		
		foreach($worker_ids as $worker_id)
			DAO_ContextLink::setLink($context, $context_id, CerberusContexts::CONTEXT_WORKER, $worker_id);
	}
	
	static public function removeWatchers($context, $context_id, $worker_ids) {
		if(!is_array($worker_ids))
			$worker_ids = array($worker_ids);
			
		foreach($worker_ids as $worker_id)
			DAO_ContextLink::deleteLink($context, $context_id, CerberusContexts::CONTEXT_WORKER, $worker_id);
	}
	
	static public function formatActivityLogEntry($entry, $format=null, $scrub_tokens=array()) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$url_writer = DevblocksPlatform::getUrlService();
		$translate = DevblocksPlatform::getTranslationService();
		
		// Load the translated version of the message

		$entry['message'] = $translate->_($entry['message']);
		
		// Scrub desired tokens
		
		if(is_array($scrub_tokens) && !empty($scrub_tokens)) {
			foreach($scrub_tokens as $token) {
				// Scrub tokens and only preserve trailing whitespace
				$entry['message'] = preg_replace('#\s*\{\{'.$token.'\}\}(\s*)#', '\1', $entry['message']);
			}
		}
		
		// Variables
		
		$vars = $entry['variables']; 
		
		switch($format) {
			case 'html':
				// HTML formatting and incorporating URLs
				if(is_array($vars))
				foreach($vars as $k => $v) {
					$vars[$k] = htmlentities($v, ENT_QUOTES, LANG_CHARSET_CODE);
				}
				
				if(isset($entry['urls']))
				foreach($entry['urls'] as $token => $url) {
					if(0 != strcasecmp('http',substr($url,0,4)))
						$url = $url_writer->write($url, true);
					
					$vars[$token] = '<a href="'.$url.'" style="font-weight:bold;">'.$vars[$token].'</a>';
				}
				break;
				
			case 'markdown':
				if(isset($entry['urls']))
				foreach($entry['urls'] as $token => $url) {
					if(0 != strcasecmp('http',substr($url,0,4)))
						$url = $url_writer->write($url, true);
					
					$vars[$token] = '['.$vars[$token].']('.$url.')';
				}
				break;
				
			case 'email':
				@$url = reset($entry['urls']);
				
				if(empty($url))
					break;
					
				if(0 != strcasecmp('http',substr($url,0,4)))
					$url = $url_writer->write($url, true);
					
				$entry['message'] .= ' <' . $url . '>'; 
				break;
				
			default:
				break;
		}
		
		if(!is_array($vars))
			$vars = array();
		
		return $tpl_builder->build($entry['message'], $vars);
	}
	
	static public function setActivityDefaultActor($context, $context_id=null) {
		if(empty($context) || empty($context_id)) {
			self::$_default_actor_context = null;
			self::$_default_actor_context_id = null;
		} else {
			self::$_default_actor_context = $context;
			self::$_default_actor_context_id = $context_id;
		}
	}
	
	static public function logActivity($activity_point, $target_context, $target_context_id, $entry_array, $actor_context=null, $actor_context_id=null, $also_notify_worker_ids=array()) {
		// Forced actor
		if(!empty($actor_context) && !empty($actor_context_id)) {
			if(null != ($ctx = DevblocksPlatform::getExtension($actor_context, true))
				&& $ctx instanceof Extension_DevblocksContext) {
				$meta = $ctx->getMeta($actor_context_id);
				$actor_name = $meta['name'];
				$actor_url = $meta['permalink'];
			}
		}
		
		// Auto-detect the actor
		if(empty($actor_context)) {
			$actor_name = null;
			$actor_context = null;
			$actor_context_id = 0;
			$actor_url = null;
			
			// See if we're inside of an attendant's running decision tree
			if(EventListener_Triggers::getDepth() > 0
				&& null != ($trigger_id = end(EventListener_Triggers::getTriggerLog())) 
				&& !empty($trigger_id) 
				&& null != ($trigger = DAO_TriggerEvent::get($trigger_id)) 
			) {
				/* @var $trigger Model_TriggerEvent */
				
				switch($trigger->owner_context) {
					case CerberusContexts::CONTEXT_GROUP:
						$group = DAO_Group::get($trigger->owner_context_id);
						$actor_name = $group->name . ' [' . $trigger->title . ']';
						$actor_context = $trigger->owner_context;
						$actor_context_id = $trigger->owner_context_id;
						$actor_url = sprintf("c=profiles&type=group&who=%d", $actor_context_id);
						break;
						
					case CerberusContexts::CONTEXT_WORKER:
						$worker = DAO_Worker::get($trigger->owner_context_id);
						$actor_name = $worker->getName() . ' [' . $trigger->title . ']';
						$actor_context = $trigger->owner_context;
						$actor_context_id = $trigger->owner_context_id;
						$actor_url = sprintf("c=profiles&type=worker&who=%d", $actor_context_id);
						break;
				}
				
			// Otherwise see if we have an active session
			} else {
				// If we have a default, use it instead of the current session
				if(empty($actor_context) && !empty(self::$_default_actor_context)) {
					$actor_context = self::$_default_actor_context;
					$actor_context_id = self::$_default_actor_context_id;
					if(null != ($ctx = DevblocksPlatform::getExtension($actor_context, true))
						&& $ctx instanceof Extension_DevblocksContext) {
						$meta = $ctx->getMeta($actor_context_id);
						$actor_name = $meta['name'];
						$actor_url = $meta['permalink'];
					}
				}

				// Try using current session 
				if(empty($actor_context) && null != ($active_worker = CerberusApplication::getActiveWorker())) {
					$actor_name = $active_worker->getName();
					$actor_context = CerberusContexts::CONTEXT_WORKER;
					$actor_context_id = $active_worker->id;
					$actor_url = sprintf("c=profiles&type=worker&who=%d", $actor_context_id);
				}				
				
			} 
		}
		
		if(empty($actor_context)) {
			$actor_name = 'The system';
		}
		
		$entry_array['variables']['actor'] = $actor_name;
		
		if(!empty($actor_url))
			$entry_array['urls']['actor'] = $actor_url;
		
		// Activity Log
		DAO_ContextActivityLog::create(array(
			DAO_ContextActivityLog::ACTIVITY_POINT => $activity_point,
			DAO_ContextActivityLog::CREATED => time(),
			DAO_ContextActivityLog::ACTOR_CONTEXT => $actor_context,
			DAO_ContextActivityLog::ACTOR_CONTEXT_ID =>$actor_context_id,
			DAO_ContextActivityLog::TARGET_CONTEXT => $target_context,
			DAO_ContextActivityLog::TARGET_CONTEXT_ID => $target_context_id,
			DAO_ContextActivityLog::ENTRY_JSON => json_encode($entry_array),
		));
		
		// Tell target watchers about the activity
		
		$watchers = array();
		
		// Merge in watchers of the actor (if not a worker)
		if(CerberusContexts::CONTEXT_WORKER != $actor_context) {
			$watchers = array_merge(
				$watchers,
				array_keys(CerberusContexts::getWatchers($actor_context, $actor_context_id))
			);
		}
		
		// And watchers of the target (if not a worker)
		if(CerberusContexts::CONTEXT_WORKER != $target_context) {
			$watchers = array_merge(
				$watchers,
				array_keys(CerberusContexts::getWatchers($target_context, $target_context_id))
			);
		}
		
		// Include the 'also notify' list
		$watchers = array_merge(
			$watchers,
			$also_notify_worker_ids
		);
		
		// Remove dupe watchers
		$watcher_ids = array_unique($watchers);
		
		$url_writer = DevblocksPlatform::getUrlService();
		
		// Fire off notifications
		if(is_array($watcher_ids)) {
			$message = CerberusContexts::formatActivityLogEntry($entry_array, 'plaintext');
			@$url = reset($entry_array['urls']); 
			
			if(0 != strcasecmp('http',substr($url,0,4)))
				$url = $url_writer->write($url, true);
			
			foreach($watcher_ids as $watcher_id) {
				// Skip a watcher if they are the actor
				if($actor_context == CerberusContexts::CONTEXT_WORKER
					&& $actor_context_id == $watcher_id)
						continue;
				
				// Does the worker want this kind of notification?
				$dont_notify_on_activities = WorkerPrefs::getDontNotifyOnActivities($watcher_id);
				if(in_array($activity_point, $dont_notify_on_activities))
					continue;
					
				// If yes, send it						
				DAO_Notification::create(array(
					DAO_Notification::CREATED_DATE => time(),
					DAO_Notification::IS_READ => 0,
					DAO_Notification::WORKER_ID => $watcher_id,
					DAO_Notification::MESSAGE => $message,
					DAO_Notification::URL => $url,
				));
			}
		}
		
	}
	
	private static function _getAttachmentContext($attachment, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Attachment:';
		
		$translate = DevblocksPlatform::getTranslationService();
		
		// Polymorph
		if(is_numeric($attachment)) {
			$attachment = DAO_Attachment::get($attachment);
		} elseif($attachment instanceof Model_Attachment) {
			// It's what we want already.
		} else {
			$attachment = null;
		}
			
		// Token labels
		$token_labels = array(
			'id' => $prefix.$translate->_('common.id'),
			'mime_type' => $prefix.$translate->_('attachment.mime_type'),
			'name' => $prefix.$translate->_('attachment.display_name'),
			'size' => $prefix.$translate->_('attachment.storage_size'),
			'updated|date' => $prefix.$translate->_('common.updated'),
		);
		
		// Token values
		$token_values = array();
		
		if(null != $attachment) {
			$token_values['id'] = $attachment->id;
			$token_values['mime_type'] = $attachment->mime_type;
			$token_values['name'] = $attachment->display_name;
			$token_values['size'] = $attachment->storage_size;
			$token_values['updated'] = $attachment->updated;
		}
		
		return true;
	}
	
	/**
	 * 
	 * @param mixed $bucket
	 * @param array $token_labels
	 * @param array $token_values
	 */
	private static function _getBucketContext($bucket, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Bucket:';
		
		$translate = DevblocksPlatform::getTranslationService();
		//$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG);

		// Polymorph
		if(is_numeric($bucket)) {
			$bucket = DAO_Bucket::get($bucket); 
		} elseif($bucket instanceof Model_Bucket) {
			// It's what we want already.
		} else {
			$bucket = null;
		}
		/* @var $bucket Model_Bucket */
		
		// Token labels
		$token_labels = array(
			'id' => $prefix.$translate->_('common.id'),
			'name|default(\'Inbox\')' => $prefix.$translate->_('common.name'),
		);
		
//		if(is_array($fields))
//		foreach($fields as $cf_id => $field) {
//			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
//		}

		// Token values
		$token_values = array();
		
		// Bucket token values
		if($bucket) {
			$token_values['id'] = $bucket->id;
			$token_values['name'] = $bucket->name;
			//$token_values['custom'] = array();
			
//			$field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ORG, $org->id));
//			if(is_array($field_values) && !empty($field_values)) {
//				foreach($field_values as $cf_id => $cf_val) {
//					if(!isset($fields[$cf_id]))
//						continue;
//					
//					// The literal value
//					if(null != $org)
//						$token_values['custom'][$cf_id] = $cf_val;
//					
//					// Stringify
//					if(is_array($cf_val))
//						$cf_val = implode(', ', $cf_val);
//						
//					if(is_string($cf_val)) {
//						if(null != $org)
//							$token_values['custom_'.$cf_id] = $cf_val;
//					}
//				}
//			}
		}

		return true;
	}
};

class CerberusLicense {
	private static $instance = null;
	private $data = array();

	/**
	 * Commercial open source is the most constructive business model for our project.
	 * We trust you.
	 */
	private function __construct() {
		/*																																																																																																																															*/try{$l=(null!=(@$data=json_decode(DevblocksPlatform::getPluginSetting('cerberusweb.core',CerberusSettings::LICENSE,''),true)))?$data:array();self::validate(base64_decode(@$l[base64_decode('ZGF0YQ==')]),@$l['company'],@$l['email'])?@$this->data=$l:@$this->data=array();}catch(Exception $e){@$this->data=array();}return;/*
		 * We've spent many years working on this software.
		 * Please be honest!
		 * http://www.cerberusweb.com/buy
		 */
		$this->data = (null != ($data = json_decode(DevblocksPlatform::getPluginSetting('devblocks.core','license_info',array()),true)))?$data:array();
	}
	
	/**																																																																																																																																*/public function __get($property) {switch($property){case 'w':return(max(1,@intval($this->seats)));break;default:return(isset($this->data[$property]))?$this->data[$property]:null;break;}}/*
	 * @return CerberusLicense
	 */
	public static function getInstance() {
		if(is_null(self::$instance)) {
			self::$instance = new CerberusLicense();
		}
		return self::$instance;
	}
	
	/*
	 * Please purchase a legitimate license and help support the project.
	 * http://www.cerberusweb.com/buy
	 */
	public static function validate($key, $company, $email) {
		/*  																																																																																																																														*/try{foreach(array('L0tleTogKC4qKS8='=>'s','L0NyZWF0ZWQ6ICguKikv'=>'c','L1VwZGF0ZWQ6ICguKikv'=>'u','L1VwZ3JhZGVzOiAoLiopLw=='=>'e','L1NlYXRzOiAoLiopLw=='=>'w') as $k=>$v)@preg_match(base64_decode($k),$key,$matches)?@$$v=trim($matches[1]):null;$r=array();@$w=intval($w);@$cp=base64_decode('Y29tcGFueQ==');@$em=base64_decode('ZW1haWw=');@$cd=preg_replace('/[^A-Z0-9]/','',$s);@$l=explode('-',$e);@$e=gmmktime(0,0,0,$l[1],$l[2],$l[0]);@$l=explode('-',$c);@$c=gmmktime(0,0,0,$l[1],$l[2],$l[0]);@$l=explode('-',$u);@$u=gmmktime(0,0,0,$l[1],$l[2],$l[0]);@$h=str_split(strtoupper(sha1(sha1('cerb5').sha1($$cp).sha1($$em).sha1(intval($w)).sha1(gmdate('Y-m-d',$c)).sha1(gmdate('Y-m-d',$e)))),1);if(0==@strcasecmp(sprintf("%02X",strlen($$cp)+intval($w)),substr($cd,3,2))&&@intval(hexdec(substr($cd,5,1))==@intval(bindec(sprintf("%d%d%d%d",(182<=gmdate('z',$e))?1:0,(5==gmdate('w',$e))?1:0,('th'==gmdate('S',$e))?1:0,(1==gmdate('w',$e))?1:0))))&&0==@strcasecmp($h[hexdec(substr($cd,1,2))-@hexdec(substr($cd,0,1))],substr($cd,0,1)))@$r=array(base64_decode('a2V5')=>$s,base64_decode('Y3JlYXRlZA==')=>$c,base64_decode('dXBkYXRlZA==')=>$u,base64_decode('dXBncmFkZXM=')=>$e,@$cp=>$$cp,@$em=>$$em,base64_decode('c2VhdHM=')=>intval($w),base64_decode('ZGF0YQ==')=>base64_encode($key));return $r;}catch(Exception $e){return array();}/*
		 * Simple, huh?
		 */
		$lines = explode("\n", $key);
		
		/*
		 * Remember that our cache can return stale data here. Be sure to
		 * clear caches.  The config area does already.
		 */
		return (!empty($key) && !empty($lines)) 
			? array(
				'company' => $company,
				'email' => $email,
				'key'     => (list($k,$v)=explode(":",$lines[1]))?trim($v):null,
				'created' => (list($k,$v)=explode(":",$lines[2]))?trim($v):null,
				'updated' => (list($k,$v)=explode(":",$lines[3]))?trim($v):null,
				'upgrades' => (list($k,$v)=explode(":",$lines[4]))?trim($v):null,
				'seats' => (list($k,$v)=explode(":",$lines[5]))?trim($v):null
			)
			: array();
	}
	
	public static function getReleases() {
		/**																																																																																																																														*/return array('5.0.0'=>1271894400,'5.1.0'=>1281830400,'5.2.0'=>1288569600,'5.3.0'=>1295049600,'5.4.0'=>1303862400);/*
		 * Major releases by date in GMT
		 */
		return array(
			'5.0.0' => gmmktime(0,0,0,4,22,2010),
			'5.1.0' => gmmktime(0,0,0,8,15,2010),
			'5.2.0' => gmmktime(0,0,0,11,1,2010),
			'5.3.0' => gmmktime(0,0,0,1,15,2011),
			'5.4.0' => gmmktime(0,0,0,4,27,2011),
		);
	}
	
	public static function getReleaseDate($version) {
		$latest_licensed = 0;
		$version = array_shift(explode("-",$version,2));
		foreach(self::getReleases() as $release => $release_date) {
			if(version_compare($release, $version) <= 0)
				$latest_licensed = $release_date;
		}
		return $latest_licensed;
	}
};

class CerberusSettings {
	const DEFAULT_TICKET_REPLY_STATUS = 'default_ticket_reply_status'; 
	const DEFAULT_TICKET_OPEN_STATUS = 'default_ticket_open_status'; 
	const DEFAULT_TICKET_SEND_STATUS = 'default_ticket_send_status'; 
	const DEFAULT_AUTOREPLY_LIMITER = 'default_autoreply_limiter'; 
	const HELPDESK_TITLE = 'helpdesk_title'; 
	const HELPDESK_LOGO_URL = 'helpdesk_logo_url'; 
	const DISABLE_WORKER_EMAIL_TO_CUSTOMER = 'disable_worker_email_to_customer'; 
 	const SMTP_HOST = 'smtp_host'; 
	const SMTP_AUTH_ENABLED = 'smtp_auth_enabled'; 
	const SMTP_AUTH_USER = 'smtp_auth_user'; 
	const SMTP_AUTH_PASS = 'smtp_auth_pass'; 
	const SMTP_PORT = 'smtp_port'; 
	const SMTP_ENCRYPTION_TYPE = 'smtp_enc';
	const SMTP_MAX_SENDS = 'smtp_max_sends';
	const SMTP_TIMEOUT = 'smtp_timeout';
	const ATTACHMENTS_ENABLED = 'attachments_enabled'; 
	const ATTACHMENTS_MAX_SIZE = 'attachments_max_size'; 
	const PARSER_AUTO_REQ = 'parser_autoreq'; 
	const PARSER_AUTO_REQ_EXCLUDE = 'parser_autoreq_exclude'; 
	const AUTHORIZED_IPS = 'authorized_ips';
	const LICENSE = 'license_json';
	const ACL_ENABLED = 'acl_enabled';
};

class CerberusSettingsDefaults {
	const DEFAULT_AUTOREPLY_LIMITER = 300; 
	const HELPDESK_TITLE = 'Cerberus Helpdesk :: Team-based E-mail Management'; 
	const SMTP_HOST = 'localhost'; 
	const SMTP_AUTH_ENABLED = 0; 
	const SMTP_AUTH_USER = ''; 
	const SMTP_AUTH_PASS = ''; 
	const SMTP_PORT = 25; 
	const SMTP_ENCRYPTION_TYPE = 'None';
	const SMTP_MAX_SENDS = 20;
	const SMTP_TIMEOUT = 30;
	const ATTACHMENTS_ENABLED = 1; 
	const ATTACHMENTS_MAX_SIZE = 10; 
	const PARSER_AUTO_REQ = 0; 
	const PARSER_AUTO_REQ_EXCLUDE = ''; 
	const AUTHORIZED_IPS = "127.0.0.1\n::1\n";
	const ACL_ENABLED = 0;
};

// [TODO] This gets called a lot when it happens after the registry cache
class C4_DevblocksExtensionDelegate implements DevblocksExtensionDelegate {
	static $_worker = null;
	
	static function shouldLoadExtension(DevblocksExtensionManifest $extension_manifest) {
		// Always allow core
		if("devblocks.core" == $extension_manifest->plugin_id)
			return true;
		if("cerberusweb.core" == $extension_manifest->plugin_id)
			return true;
			
		// [TODO] This should limit to just things we can run with no session
		// Community Tools, Cron/Update.  They are still limited by their own
		// isVisible() otherwise.
		if(null == self::$_worker) {
			if(null == (self::$_worker = CerberusApplication::getActiveWorker()))
				return true;
		}
		
		return self::$_worker->hasPriv('plugin.'.$extension_manifest->plugin_id);
	}
};

class CerberusVisit extends DevblocksVisit {
	private $worker_id;

	const KEY_VIEW_LAST_ACTION = 'view_last_action';
	const KEY_MY_WORKSPACE = 'view_my_workspace';
	const KEY_WORKFLOW_FILTER = 'workflow_filter';

	public function __construct() {
		$this->worker_id = null;
	}

	/**
	 * @return Model_Worker
	 */
	public function getWorker() {
		if(empty($this->worker_id))
			return null;
			
		return DAO_Worker::get($this->worker_id);
	}
	
	public function setWorker(Model_Worker $worker=null) {
		if(is_null($worker)) {
			$this->worker_id = null;
		} else {
			$this->worker_id = $worker->id;
		}
	}
};

class C4_ORMHelper extends DevblocksORMHelper {
	static public function qstr($str) {
		$db = DevblocksPlatform::getDatabaseService();
		return $db->qstr($str);	
	}
	
	static protected function _appendSelectJoinSqlForCustomFieldTables($tables, $params, $key, $select_sql, $join_sql) {
		$custom_fields = DAO_CustomField::getAll();
		$field_ids = array();

		$return_multiple_values = false; // can our CF return more than one hit? (GROUP BY)
		
		if(is_array($tables))
		foreach($tables as $tbl_name => $null) {
			// Filter and sanitize
			if(substr($tbl_name,0,3) != "cf_" // not a custom field 
				|| 0 == ($field_id = intval(substr($tbl_name,3)))) // not a field_id
				continue;

			// Make sure the field exists for this context
			if(!isset($custom_fields[$field_id]))
				continue; 

			$field_table = sprintf("cf_%d", $field_id);
			$value_table = '';
			$field_key = $key;
			
			if(is_array($key)) {
				if(isset($key[$custom_fields[$field_id]->context]))
					$field_key = $key[$custom_fields[$field_id]->context];
				else
					continue;
			}
			
			// Join value by field data type
			switch($custom_fields[$field_id]->type) {
				case Model_CustomField::TYPE_MULTI_LINE:
					$value_table = 'custom_field_clobvalue';
					break;
				case Model_CustomField::TYPE_CHECKBOX:
				case Model_CustomField::TYPE_DATE:
				case Model_CustomField::TYPE_NUMBER:
				case Model_CustomField::TYPE_WORKER:
					$value_table = 'custom_field_numbervalue';
					break;
				default:
				case Model_CustomField::TYPE_SINGLE_LINE:
				case Model_CustomField::TYPE_DROPDOWN:
				case Model_CustomField::TYPE_URL:
					$value_table = 'custom_field_stringvalue';
					break;
			}

			$has_multiple_values = false;
			switch($custom_fields[$field_id]->type) {
				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					$has_multiple_values = true;
					break;
			}
			
			// If we have multiple values but we don't need to WHERE the JOIN, be efficient and don't GROUP BY
			if(!isset($params['cf_'.$field_id])) {
				$select_sql .= sprintf(",(SELECT field_value FROM %s WHERE %s=context_id AND field_id=%d LIMIT 0,1) AS %s ",
					$value_table,
					$field_key,
					$field_id,
					$field_table
				);
				
			} else {
				$select_sql .= sprintf(", %s.field_value as %s ",
					$field_table,
					$field_table
				);
				
				$join_sql .= sprintf("LEFT JOIN %s %s ON (%s=%s.context_id AND %s.field_id=%d) ",
					$value_table,
					$field_table,
					$field_key,
					$field_table,
					$field_table,
					$field_id
				);
				
				// If we do need to WHERE this JOIN, make sure we GROUP BY
				if($has_multiple_values)
					$return_multiple_values = true;
			}
		}
		
		return array($select_sql, $join_sql, $return_multiple_values);
	}
};
