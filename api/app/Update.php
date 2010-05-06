<?php
class ChUpdateController extends DevblocksControllerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
	    @set_time_limit(0); // no timelimit (when possible)

	    $translate = DevblocksPlatform::getTranslationService();
	    
	    $stack = $request->path;
	    array_shift($stack); // update

	    $cache = DevblocksPlatform::getCacheService(); /* @var $cache _DevblocksCacheManager */
    	$url = DevblocksPlatform::getUrlService();
	    
	    switch(array_shift($stack)) {
	    	case 'unlicense':
	    		DevblocksPlatform::setPluginSetting('cerberusweb.core',CerberusSettings::LICENSE, '');
	    		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('update')));
	    		break;
	    		
	    	case 'locked':
	    		if(!DevblocksPlatform::versionConsistencyCheck()) {
	    			echo "<h1>Cerberus Helpdesk 5.x</h1>";
	    			echo "The helpdesk is currently waiting for an administrator to finish upgrading. ".
	    				"Please wait a few minutes and then ". 
		    			sprintf("<a href='%s'>try again</a>.<br><br>",
							$url->write('c=update&a=locked')
		    			);
	    			echo sprintf("If you're an admin you may <a href='%s'>finish the upgrade</a>.",
	    				$url->write('c=update')
	    			);
	    		} else {
	    			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
	    		}
	    		break;
	    		
	    	default:
			    $path = APP_TEMP_PATH . DIRECTORY_SEPARATOR;
				$file = $path . 'c4update_lock';	    		
				
				$settings = DevblocksPlatform::getPluginSettingsService();
				
			    $authorized_ips_str = $settings->get('cerberusweb.core',CerberusSettings::AUTHORIZED_IPS,CerberusSettingsDefaults::AUTHORIZED_IPS);
			    $authorized_ips = DevblocksPlatform::parseCrlfString($authorized_ips_str);
			    
		   	    $authorized_ip_defaults = DevblocksPlatform::parseCsvString(AUTHORIZED_IPS_DEFAULTS);
			    $authorized_ips = array_merge($authorized_ips, $authorized_ip_defaults);
			    
			    // Is this IP authorized?
			    $pass = false;
				foreach ($authorized_ips as $ip)
				{
					if(substr($ip,0,strlen($ip)) == substr($_SERVER['REMOTE_ADDR'],0,strlen($ip)))
				 	{ $pass=true; break; }
				}
			    if(!$pass) {
				    echo vsprintf($translate->_('update.ip_unauthorized'), $_SERVER['REMOTE_ADDR']);
				    return;
			    }
				
			    // Potential errors
			    $errors = array();

			    // Release dates
			    $r = array(
			    	'5.0' => gmmktime(0,0,0,4,22,2010),
			    );
			    
			    /*																																																																																																																																																																																																																			*/$r = array('5.0'=>1271894400,);/*
			     * This well-designed software is the result of over 8 years of R&D.
			     * We're sharing every resulting byte of that hard work with you.
			     * You're free to make changes for your own use, but we ask that you 
			     * please respect our licensing and help support commerical open source.
			     */
			    $remuneration = CerberusLicense::getInstance();
				@$u = $remuneration->upgrades;
				
			    $version = null;
				foreach(array_keys($r) as $v) {
					if($u>=$r[$v])
						$version = array($v => $r[$v]);
				}
				
				end($r);
				
			    if(!is_null($u) && $u < end($r)) {
			    	$errors[] = sprintf("Your Cerb5 license is valid for %s software updates.  Your coverage for major software updates expired on %s, and %s is not included.  Please <a href='%s' target='_blank'>renew your license</a>%s, <a href='%s'>remove your license</a> and enter Evaluation Mode (1 simultaneous worker), or <a href='%s' target='_blank'>download</a> an earlier version.",
			    		is_array($version)?(key($version).'.x'):('earlier'),
			    		gmdate("F d, Y",$u),
			    		APP_VERSION,
			    		'http://www.cerberusweb.com/buy',
			    		!is_null($remuneration->key) ? sprintf(" (%s)",$remuneration->key) : '',
			    		$url->write('c=update&a=unlicense'),
			    		'http://www.cerberusweb.com/download'
			    	);
			    }
			    
			    // Check requirements
			    $errors += CerberusApplication::checkRequirements();
			    
			    if(!empty($errors)) {
				    echo "
				    <style>
				    a { color: red; font-weight:bold; }
				    ul { color:red; }
				    </style>
				    ";
			    	
				    echo "<h1>Cerberus Helpdesk 5.x</h1>";
				    
			    	echo $translate->_('update.correct_errors');
			    	echo "<ul>";
			    	foreach($errors as $error) {
			    		echo "<li>".$error."</li>";
			    	}
			    	echo "</ul>";
			    	exit;
			    }
			    
			    try {
				    // If authorized, lock and attempt update
					if(!file_exists($file) || @filectime($file)+600 < time()) { // 10 min lock
						// Log everybody out since we're touching the database
						$session = DevblocksPlatform::getSessionService();
						$session->clearAll();

						// Lock file
						touch($file);
						
						// Recursive patch
						CerberusApplication::update();
						
						// Clean up
						@unlink($file);

						$cache = DevblocksPlatform::getCacheService();
						$cache->save(APP_BUILD, "devblocks_app_build");

						// Clear all caches
						$cache->clean();
						DevblocksPlatform::getClassLoaderService()->destroy();
						
						// Clear compiled templates
						$tpl = DevblocksPlatform::getTemplateService();
						$tpl->utility->clearCompiledTemplate();
						$tpl->cache->clearAll();

						// Reload plugin translations
						DAO_Translation::reloadPluginStrings();

						// Redirect
				    	DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
	
					} else {
						echo $translate->_('update.locked_another');
					}
					
	    	} catch(Exception $e) {
	    		unlink($file);
	    		die($e->getMessage());
	    	}
	    }
	    
		exit;
	}
}
