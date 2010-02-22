<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
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
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */

class CerberusParserMessage {
    public $encoding = '';
    public $headers = array();
    public $body = '';
    public $body_encoding = '';
    public $htmlbody = '';
    public $files = array();
	public $custom_fields = array();
};

class ParserFile {
	public $tmpname = null;
	public $mime_type = '';
	public $file_size = 0;

	function __destruct() {
		if(file_exists($this->tmpname)) {
			@unlink($this->tmpname);
		}
	}

	public function setTempFile($tmpname,$mimetype='application/octet-stream') {
		$this->mime_type = $mimetype;

		if(!empty($tmpname) && file_exists($tmpname)) {
			$this->tmpname = $tmpname;
		}
	}

	public function getTempFile() {
		return $this->tmpname;
	}

	static public function makeTempFilename() {
		$path = APP_TEMP_PATH . DIRECTORY_SEPARATOR;
		return tempnam($path,'mime');
	}
};

class CerberusParser {
    const ATTACHMENT_BUCKETS = 100; // hash

    /**
     * Enter description here...
     *
     * @param object $mime
     * @return CerberusParserMessage
     */
    static public function parseMime($mime, $full_filename) {
		$struct = mailparse_msg_get_structure($mime);
		$msginfo = mailparse_msg_get_part_data($mime);
		
		$message = new CerberusParserMessage();
		@$message->encoding = $msginfo['content-charset'];
		@$message->body_encoding = $message->encoding; // default

		// Decode headers
		@$message->headers = $msginfo['headers'];
		
		if(is_array($message->headers))
		foreach($message->headers as $header_name => $header_val) {
			if(is_array($header_val)) {
				foreach($header_val as $idx => $val) {
					$message->headers[$header_name][$idx] = self::fixQuotePrintableString($val);	
				}
			} else {
				$message->headers[$header_name] = self::fixQuotePrintableString($header_val);
			}
		}
		
		$settings = DevblocksPlatform::getPluginSettingsService();
		$is_attachments_enabled = $settings->get('cerberusweb.core',CerberusSettings::ATTACHMENTS_ENABLED,1);
		$attachments_max_size = $settings->get('cerberusweb.core',CerberusSettings::ATTACHMENTS_MAX_SIZE,10);
		
		foreach($struct as $st) {
//		    echo "PART $st...<br>\r\n";

		    $section = mailparse_msg_get_part($mime, $st);
		    $info = mailparse_msg_get_part_data($section);
		    
		    // handle parts that shouldn't have a content-name, don't handle twice
		    $handled = 0;
		    if(empty($info['content-name'])) {
		        if($info['content-type'] == 'text/plain') {
					$text = mailparse_msg_extract_part_file($section, $full_filename, NULL);
					
					if(isset($info['content-charset']) && !empty($info['content-charset'])) {
						$message->body_encoding = $info['content-charset'];
						
						if(@mb_check_encoding($text, $info['content-charset'])) {
							$text = mb_convert_encoding($text, LANG_CHARSET_CODE, $info['content-charset']);
						} else {
							$text = mb_convert_encoding($text, LANG_CHARSET_CODE);
						}
					}
					
	            	@$message->body .= $text;
	            	
	            	unset($text);
	            	$handled = 1;
		            
		        } elseif($info['content-type'] == 'text/html') {
	        		@$text = mailparse_msg_extract_part_file($section, $full_filename, NULL);

					if(isset($info['content-charset']) && !empty($info['content-charset'])) {
						if(@mb_check_encoding($text, $info['content-charset'])) {
							$text = mb_convert_encoding($text, LANG_CHARSET_CODE, $info['content-charset']);
						} else {
							$text = mb_convert_encoding($text, LANG_CHARSET_CODE);
						}
					}
	        		
					$message->htmlbody .= $text;
					unset($text);
					
		            // Add the html part as an attachment
		            // [TODO] Make attaching the HTML part an optional config option (off by default)
	                $tmpname = ParserFile::makeTempFilename();
	                $html_attach = new ParserFile();
	                $html_attach->setTempFile($tmpname,'text/html');
	                @file_put_contents($tmpname,$message->htmlbody);
	                $html_attach->file_size = filesize($tmpname);
	                $message->files["original_message.html"] = $html_attach;
	                unset($html_attach);
		            $handled = 1;
		            
		        } elseif($info['content-type'] == 'message/rfc822') {
					@$message_content = mailparse_msg_extract_part_file($section, $full_filename, NULL);
					
		        	$message_counter = empty($message_counter) ? 1 : $message_counter + 1;
	                $tmpname = ParserFile::makeTempFilename();
	                $html_attach = new ParserFile();
	                $html_attach->setTempFile($tmpname,'message/rfc822');
	                @file_put_contents($tmpname,$message_content);
	                $html_attach->file_size = filesize($tmpname);
	                $message->files['inline'.$message_counter.'.msg'] = $html_attach;
	                unset($html_attach);		        	 
		            $handled = 1;
		        }
		    }
		    
		    // whether or not it has a content-name, we need to add it as an attachment (if not already handled)
		    if ($handled == 0) {
		    	if (false === strpos(strtolower($info['content-type']),'multipart')) {
	                if(!$is_attachments_enabled) {
	                    break; // skip attachment
	                }
				    $attach = new ParseCronFileBuffer($section, $info, $full_filename);
	                
				    // [TODO] This could be more efficient by not even saving in the first place above:
                    // Make sure our attachment is under the max preferred size
				    if(filesize($attach->tmpname) > ($attachments_max_size * 1024000)) {
				        @unlink($attach->tmpname);
				        break;
				    }
				    
				    // if un-named, call it "unnamed message part"
				    if (!isset($info['content-name']) // if not set 
				    	|| (isset($info['content-name']) && empty($info['content-name']))) { // or blank 
				    	$info['content-name'] = 'unnamed_message_part';
				    }
				    
				    // filenames can be quoted-printable strings, too...
				    $info['content-name'] = self::fixQuotePrintableString($info['content-name']);

				    // content-name is not necessarily unique...
					if (isset($message->files[$info['content-name']])) {
						$j=1;
						while (isset($message->files[$info['content-name'] . '(' . $j . ')'])) {
							$j++;
						}
						$info['content-name'] = $info['content-name'] . '(' . $j . ')';
					}
					$message->files[$info['content-name']] = $attach;
		        }
		    }
		}
		
		// generate the plaintext part (if necessary)
		if(empty($message->body) && !empty($message->htmlbody)) {
			$message->body = DevblocksPlatform::stripHTML($message->htmlbody);
		}
		
		return $message;
    }
    
    /**
     * Enter description here...
     *
     * @param string $source
     * @return $filename
     */
    static public function saveMimeToFile($source, $path=null) {
    	if(empty($path))
    		$path = APP_TEMP_PATH . DIRECTORY_SEPARATOR;
    	else
    		$path = $path . DIRECTORY_SEPARATOR;
    	
		do {
			$unique = sprintf("%s.%04d.msg",
				time(),
				mt_rand(0,9999)
			);
			$filename = $path . $unique;
        } while(file_exists($filename));

          $fp = fopen($filename,'w');
          
          if($fp) {
              fwrite($fp,$source,strlen($source));
              @fclose($fp);
          }
          
		return $filename;
    }

	/**
	 * 
	 * @return Model_Address 
	 * @param array $headers
	 */    
	static public function getAddressFromHeaders($headers) {
		@$sReturnPath = $headers['return-path'];
		@$sReplyTo = $headers['reply-to'];
		@$sFrom = $headers['from'];
		
		$from = array();
		
		if(!empty($sReplyTo)) {
			$from = CerberusParser::parseRfcAddress($sReplyTo);
		} elseif(!empty($sFrom)) {
			$from = CerberusParser::parseRfcAddress($sFrom);
		} elseif(!empty($sReturnPath)) {
			$from = CerberusParser::parseRfcAddress($sReturnPath);
		}
		
		if(empty($from) || !is_array($from)) {
			return NULL;
		}
		
		foreach($from as $addy) {
			if(empty($addy->mailbox) || empty($addy->host))
				continue;
		
			@$fromAddress = $addy->mailbox.'@'.$addy->host;
			
			if(null != ($fromInst = CerberusApplication::hashLookupAddress($fromAddress, true))) {
				return $fromInst;
			}
		}
		
		return NULL;
	} 
	
	/**
	 * Enter description here...
	 *
	 * @param CerberusParserMessage $message
	 * @return integer
	 */
	static public function parseMessage(CerberusParserMessage $message, $options=array()) {
		/*
		 * options:
		 * 'no_autoreply'
		 */
		$logger = DevblocksPlatform::getConsoleLog();
		$settings = DevblocksPlatform::getPluginSettingsService();
		$helpdesk_senders = CerberusApplication::getHelpdeskSenders();
		
        // Pre-parse mail filters
		$pre_filters = Model_PreParseRule::getMatches($message);
		if(is_array($pre_filters) && !empty($pre_filters)) {
			// Load filter action manifests for reuse
			$ext_action_mfts = DevblocksPlatform::getExtensions('cerberusweb.mail_filter.action', false);

			// Loop through all matching filters
			foreach($pre_filters as $pre_filter) {
				
	        	// Do something with matching filter's actions
	        	foreach($pre_filter->actions as $action_key => $action) {
	        		
	        		switch($action_key) {
	        			case 'blackhole':
	        				return NULL;
	        				break;
	        				
	        			case 'redirect':
	        				@$to = $action['to'];
	        				CerberusMail::reflect($message, $to);
	        				return NULL;
	        				break;
	        				
	        			case 'bounce':
	        				@$msg = $action['message'];
							@$subject = 'Delivery failed: ' . self::fixQuotePrintableString($message->headers['subject']);
							
	        				// [TODO] Follow the RFC spec on a true bounce
							if(null != ($fromAddressInst = CerberusParser::getAddressFromHeaders($message->headers))) {
	        					CerberusMail::quickSend($fromAddressInst->email,$subject,$msg);
							}
	        				return NULL;
	        				break;
						
						default:
							// Plugin pre-parser filter actions
							if(isset($ext_action_mfts[$action_key])) {
								if(null != (@$ext_action = $ext_action_mfts[$action_key]->createInstance())) {
									try { 
										/* @var $ext_action Extension_MailFilterAction */
										$ext_action->run($pre_filter, $message);
									} catch(Exception $e) {	}
								}
							}
							break;
	        		}
	        	}
	        }
		}
		
		$headers =& $message->headers;

		// From
		if(null == ($fromAddressInst = CerberusParser::getAddressFromHeaders($headers))) {
			$logger->error("[Parser] 'From' address could not be created.");
			return NULL;
		}

		// To/Cc/Bcc
		$to = array();
		$sTo = @$headers['to'];
		$bIsNew = true;

		if(!empty($sTo)) {
		    // [TODO] Do we still need this RFC address parser?
			$to = CerberusParser::parseRfcAddress($sTo);
		}
		
		// Subject
		// Fix quote printable subject (quoted blocks can appear anywhere in subject)
		$sSubject = "";
		if(isset($headers['subject']) && !empty($headers['subject'])) {
			$sSubject = $headers['subject'];
			if(is_array($sSubject))
				$sSubject = array_shift($sSubject);
		}
		// The subject can still end up empty after QP decode
		if(empty($sSubject))
			$sSubject = "(no subject)";
			
		// Date
		$iDate = @strtotime($headers['date']);
		// If blank, or in the future, set to the current date
		if(empty($iDate) || $iDate > time())
			$iDate = time();
		
		// Is banned?
		if(1==$fromAddressInst->is_banned) {
			$logger->info("[Parser] Ignoring ticket from banned address: " . $fromAddressInst->email);
			return NULL;
		}
		
		// Overloadable
		$enumSpamTraining = '';
		
		// Message Id / References / In-Reply-To
		@$sMessageId = $headers['message-id'];
        
        $body_append_text = array();
        $body_append_html = array();
        // [mdf]Check attached files before creating the ticket because we may need to overwrite the message-id
		// also store any contents of rfc822 files so we can include them after the body
		foreach ($message->files as $filename => $file) { /* @var $file ParserFile */
			
			switch($file->mime_type) {
				case 'message/rfc822':
					$full_filename = $file->tmpname;
					$mail = mailparse_msg_parse_file($full_filename);
					$struct = mailparse_msg_get_structure($mail);
					$msginfo = mailparse_msg_get_part_data($mail);
					
					$inline_headers = $msginfo['headers'];
					if(isset($headers['from']) && (strtolower(substr($headers['from'], 0, 11))=='postmaster@' || strtolower(substr($headers['from'], 0, 14))=='mailer-daemon@')) {
						$headers['in-reply-to'] = $inline_headers['message-id'];
					}
				break;
			}
		}
        
		// [JAS] [TODO] References header may contain multiple message-ids to find
		if(null != ($ids = self::findParentMessage($headers))) {
        	$bIsNew = false;
        	$id = $ids['ticket_id'];
        	$msgid = $ids['message_id'];

        	// Is it a worker reply from an external client?  If so, proxy
        	if(null != ($worker_address = DAO_AddressToWorker::getByAddress($fromAddressInst->email))) {
        		$logger->info("[Parser] Handling an external worker response from " . $fromAddressInst->email);

        		if(!DAO_Ticket::isTicketRequester($worker_address->address, $id)) {
					// Watcher Commands [TODO] Document on wiki/etc
					if(0 != ($matches = preg_match_all("/\[(.*?)\]/i", $message->headers['subject'], $commands))) {
						@$command = strtolower(array_pop($commands[1]));
						$logger->info("[Parser] Worker command: " . $command);
						
						switch($command) {
							case 'close':
								DAO_Ticket::updateTicket($id,array(
									DAO_Ticket::IS_CLOSED => CerberusTicketStatus::CLOSED
								));
								break;
								
							case 'take':
								DAO_Ticket::updateTicket($id,array(
									DAO_Ticket::NEXT_WORKER_ID => $worker_address->worker_id
								));
								break;
								
							case 'comment':
								$comment_id = DAO_TicketComment::create(array(
									DAO_TicketComment::ADDRESS_ID => $fromAddressInst->id,
									DAO_TicketComment::CREATED => time(),
									DAO_TicketComment::TICKET_ID => $id,
									DAO_TicketComment::COMMENT => $message->body,
								));
								return $id;
								break;
								
							default:
								// Typo?
								break;
						}
					}
	
					$attachment_files = array();
					$attachment_files['name'] = array();
					$attachment_files['type'] = array();
					$attachment_files['tmp_name'] = array();
					$attachment_files['size'] = array();
					
					$i=0;
					foreach($message->files as $filename => $file) {
						$attachment_files['name'][$i] = $filename;
						$attachment_files['type'][$i] = $file->mime_type;
						$attachment_files['tmp_name'][$i] = $file->tmpname;
						$attachment_files['size'][$i] = $file->file_size;
						$i++;
					} 				
					
	        		CerberusMail::sendTicketMessage(array(
						'message_id' => $msgid,
						'content' => $message->body,
						'files' => $attachment_files,
						'agent_id' => $worker_address->worker_id,
					));
					
	        		return $id;
	        		
        		} else {
        			// ... worker is a requester, treat as normal
        			$logger->info("[Parser] The external worker was a ticket requester, so we're not treating them as a watcher.");
        		}
        		
        	} else { // Reply: Not sent by a worker
	        	/*
	        	 * [TODO] check that this sender is a requester on the matched ticket
	        	 * Otherwise blank out the $id
	        	 */
        	}
        }
        
		$group_id = 0;
		
		if(empty($id)) { // New Ticket
			$sMask = CerberusApplication::generateTicketMask();
			$groups = DAO_Group::getAll();
		
			// Routing new tickets
			if(null != ($routing_rules = Model_MailToGroupRule::getMatches(
				$fromAddressInst,
				$message
			))) {
				if(is_array($routing_rules))
				foreach($routing_rules as $rule) {
					// Only end up with the last 'move' action (ignore the previous)
					if(isset($rule->actions['move'])) {
						$group_id = intval($rule->actions['move']['group_id']);
						
						
						// We don't need to move again when running rule actions
						unset($rule->actions['move']);
					}
				}
			}
			
			// Make sure the group exists
			if(!isset($groups[$group_id]))
				$group_id = null;
				
			// Last ditch effort to check for a default group to deliver to
			if(empty($group_id)) {
				if(null != ($default_team = DAO_Group::getDefaultGroup())) {
					$group_id = $default_team->id;
				} else {
					// Bounce
					return null;
				}
			}
			
			
			// [JAS] It's important to not set the group_id on the ticket until the messages exist
			// or inbox filters will just abort.
			$fields = array(
				DAO_Ticket::MASK => $sMask,
				DAO_Ticket::SUBJECT => $sSubject,
				DAO_Ticket::IS_CLOSED => 0,
				DAO_Ticket::FIRST_WROTE_ID => intval($fromAddressInst->id),
				DAO_Ticket::LAST_WROTE_ID => intval($fromAddressInst->id),
				DAO_Ticket::CREATED_DATE => $iDate,
				DAO_Ticket::UPDATED_DATE => $iDate,
				DAO_Ticket::LAST_ACTION_CODE => CerberusTicketActionCode::TICKET_OPENED,
			);
			$id = DAO_Ticket::createTicket($fields);
			
			// Apply routing actions to our new ticket ID
			if(isset($routing_rules) && is_array($routing_rules))
			foreach($routing_rules as $rule) {
				$rule->run($id);
			}
		}

		// [JAS]: Add requesters to the ticket
		if(!empty($fromAddressInst->id) && !empty($id)) {
			// Don't add a requester if the sender is a helpdesk address
			if(isset($helpdesk_senders[$fromAddressInst->email])) {
				$logger->info("[Parser] Not adding ourselves as a requester: " . $fromAddressInst->email);
			} else {
				DAO_Ticket::createRequester($fromAddressInst->id,$id);
			}
		}
	    
		// Add the other TO/CC addresses to the ticket
		// [TODO] This should be cleaned up and optimized
		if($settings->get('cerberusweb.core',CerberusSettings::PARSER_AUTO_REQ,0)) {
			@$autoreq_exclude_list = $settings->get('cerberusweb.core',CerberusSettings::PARSER_AUTO_REQ_EXCLUDE,'');
			$destinations = self::getDestinations($headers);
			
			if(is_array($destinations) && !empty($destinations)) {
				
				// Filter out any excluded requesters
				if(!empty($autoreq_exclude_list)) {
					@$autoreq_exclude = DevblocksPlatform::parseCrlfString($autoreq_exclude_list);
					
					if(is_array($autoreq_exclude) && !empty($autoreq_exclude))
					foreach($autoreq_exclude as $excl_pattern) {
						$excl_regexp = DevblocksPlatform::parseStringAsRegExp($excl_pattern);
						
						// Check all destinations for this pattern
						foreach($destinations as $idx => $dest) {
							if(@preg_match($excl_regexp, $dest)) {
								unset($destinations[$idx]);
							}
						}
					}
				}
				
				foreach($destinations as $dest) {
					if(null != ($destInst = CerberusApplication::hashLookupAddress($dest, true))) {
						// Skip if the destination is one of our senders or the matching TO
						if(isset($helpdesk_senders[$destInst->email]))
							continue;
					 	
						DAO_Ticket::createRequester($destInst->id,$id);
					}
				}
			}
		}
		
		$attachment_path = APP_STORAGE_PATH . '/attachments/'; // [TODO] This should allow external attachments (S3)
		
        $fields = array(
            DAO_Message::TICKET_ID => $id,
            DAO_Message::CREATED_DATE => $iDate,
            DAO_Message::ADDRESS_ID => $fromAddressInst->id
        );
		$email_id = DAO_Message::create($fields);
		
		// Content
		DAO_MessageContent::create($email_id, $message->body);
		
		// Headers
		foreach($headers as $hk => $hv) {
		    DAO_MessageHeader::create($email_id, $hk, $hv);
		}
		
		// [mdf] Loop through files to insert attachment records in the db, and move temporary files
		if(!empty($email_id)) {
			foreach ($message->files as $filename => $file) { /* @var $file ParserFile */
				//[mdf] skip rfc822 messages since we extracted their content above
				if($file->mime_type == 'message/rfc822') {
					continue;
				}
				
			    $fields = array(
			        DAO_Attachment::MESSAGE_ID => $email_id,
			        DAO_Attachment::DISPLAY_NAME => $filename,
			        DAO_Attachment::MIME_TYPE => $file->mime_type,
			        DAO_Attachment::FILE_SIZE => intval($file->file_size),
			    );
			    $file_id = DAO_Attachment::create($fields);
				
			    if(empty($file_id)) {
			        @unlink($file->tmpname); // remove our temp file
				    continue;
				}
				
			    // Make file attachments use buckets so we have a max per directory
	            $attachment_bucket = sprintf("%03d/",
	                mt_rand(1,100)
	            );
	            $attachment_file = $file_id;
	            
	            if(!file_exists($attachment_path.$attachment_bucket)) {
	                @mkdir($attachment_path.$attachment_bucket, 0770, true);
	                // [TODO] Needs error checking
	            }

	            rename($file->getTempFile(), $attachment_path.$attachment_bucket.$attachment_file);
			    
			    // [TODO] Split off attachments into its own DAO
			    DAO_Attachment::update($file_id, array(
			        DAO_Attachment::FILEPATH => $attachment_bucket.$attachment_file
			    ));
			}
		}
		
		// Pre-load custom fields
		if(isset($message->custom_fields) && !empty($message->custom_fields))
		foreach($message->custom_fields as $cf_id => $cf_val) {
			if((is_array($cf_val) && !empty($cf_val))
				|| (!is_array($cf_val) && 0 != strlen($cf_val)))
				DAO_CustomFieldValue::setFieldValue('cerberusweb.fields.source.ticket',$id,$cf_id,$cf_val);
		}

		// Finalize our new ticket details (post-message creation)
		if($bIsNew && !empty($id) && !empty($email_id)) {
			// First thread (needed for anti-spam)
			DAO_Ticket::updateTicket($id, array(
				 DAO_Ticket::FIRST_MESSAGE_ID => $email_id
			));
			
			// Prime the change fields (which a few things like anti-spam might change before we commit)
			$change_fields = array(
			    DAO_Ticket::TEAM_ID => $group_id, // this triggers move rules
			);
			
		    $out = CerberusBayes::calculateTicketSpamProbability($id);

		    if(!empty($group_id)) {
		    	@$spam_threshold = DAO_GroupSettings::get($group_id, DAO_GroupSettings::SETTING_SPAM_THRESHOLD, 80);
		        @$spam_action = DAO_GroupSettings::get($group_id, DAO_GroupSettings::SETTING_SPAM_ACTION, '');
		        @$spam_action_param = DAO_GroupSettings::get($group_id, DAO_GroupSettings::SETTING_SPAM_ACTION_PARAM,'');
		        
			    if($out['probability']*100 >= $spam_threshold) {
			    	$enumSpamTraining = CerberusTicketSpamTraining::SPAM;
			    	
			        switch($spam_action) {
			            default:
			            case 0: // do nothing
                            break;
							
			            case 1: // delete
							$change_fields[DAO_Ticket::IS_CLOSED] = 1;
							$change_fields[DAO_Ticket::IS_DELETED] = 1;
                            break;
							
			            case 2: // move
							$buckets = DAO_Bucket::getAll();
							
							// Verify bucket exists
                            if(!empty($spam_action_param) && isset($buckets[$spam_action_param])) {
                            	$change_fields[DAO_Ticket::TEAM_ID] = $group_id;
								$change_fields[DAO_Ticket::CATEGORY_ID] = $spam_action_param;
                            }	                            
			                break;
			        }
			    }
			} // end spam training		
		
			// Save properties
			if(!empty($change_fields))
				DAO_Ticket::updateTicket($id, $change_fields);
		}

		// Reply notifications (new messages are handled by 'move' listener)
		if(!$bIsNew) {
			// Inbound Reply Event
		    $eventMgr = DevblocksPlatform::getEventService();
		    $eventMgr->trigger(
		        new Model_DevblocksEvent(
		            'ticket.reply.inbound',
	                array(
	                    'ticket_id' => $id,
	                )
	            )
		    );
		}

		// New ticket processing
		if($bIsNew) {
			
			// Auto reply
			@$autoreply_enabled = DAO_GroupSettings::get($group_id, DAO_GroupSettings::SETTING_AUTO_REPLY_ENABLED, 0);
			@$autoreply = DAO_GroupSettings::get($group_id, DAO_GroupSettings::SETTING_AUTO_REPLY, '');
			
			/*
			 * Send the group's autoreply if one exists, as long as this ticket isn't spam
			 */
			if(!isset($options['no_autoreply'])
				&& $autoreply_enabled 
				&& !empty($autoreply) 
				&& $enumSpamTraining != CerberusTicketSpamTraining::SPAM
				) {
					CerberusMail::sendTicketMessage(array(
						'ticket_id' => $id,
						'message_id' => $email_id,
						'content' => str_replace(
				        	array('#ticket_id#','#mask#','#subject#','#timestamp#', '#sender#','#sender_first#','#orig_body#'),
				        	array($id, $sMask, $sSubject, date('r'), $fromAddressInst->email, $fromAddressInst->first_name, ltrim($message->body)),
				        	$autoreply
						),
						'is_autoreply' => true,
						'dont_keep_copy' => true
					));
			}
			
		} // end bIsNew
		
		unset($message);
		
		// Re-open and update our date on new replies
		if(!$bIsNew) {
			DAO_Ticket::updateTicket($id,array(
			    DAO_Ticket::UPDATED_DATE => time(),
			    DAO_Ticket::IS_WAITING => 0,
			    DAO_Ticket::IS_CLOSED => 0,
			    DAO_Ticket::IS_DELETED => 0,
			    DAO_Ticket::LAST_WROTE_ID => $fromAddressInst->id,
			    DAO_Ticket::LAST_ACTION_CODE => CerberusTicketActionCode::TICKET_CUSTOMER_REPLY,
			));
			
			// [TODO] The TICKET_CUSTOMER_REPLY should be sure of this message address not being a worker
		}
		
	    @imap_errors(); // Prevent errors from spilling out into STDOUT
	    
		return $id;
	}

	/**
	 * First we check the references and in-reply-to headers to find a 
	 * historical match in the database. If those don't match we check 
	 * the subject line for a mask (if one exists). If none of those
	 * options match we return null.
	 *
	 * @param array $headers
	 * @return array
	 */
	static private function findParentMessage($headers) {
		@$aSubject = $headers['subject'];
		@$sMessageId = trim($headers['message-id']);
		@$sInReplyTo = trim($headers['in-reply-to']);
		@$sReferences = trim($headers['references']);
		@$sThreadTopic = trim($headers['thread-topic']);

		// [TODO] Could turn string comparisons into hashes here for simple equality checks
		
		$aReferences = array();
		
		// Add all References
		if(!empty($sReferences)) {
			if(preg_match("/(\<.*?\@.*?\>)/", $sReferences, $matches)) {
				unset($matches[0]); // who cares about the pattern
				foreach($matches as $ref) {
					$ref = trim($ref);
					if(!empty($ref) && 0 != strcasecmp($ref,$sMessageId))
						$aReferences[$ref] = 1;
				}
			}
		}

		unset($matches);
		
		// Append first <*> from In-Reply-To
		if(!empty($sInReplyTo)) {
			if(preg_match("/(\<.*?\@.*?\>)/", $sInReplyTo, $matches)) {
				if(isset($matches[1])) { // only use the first In-Reply-To
					$ref = trim($matches[1]);
					if(!empty($ref) && 0 != strcasecmp($ref,$sMessageId))
						$aReferences[$ref] = 1;
				}
			}
		}
		
		// Try matching our references or in-reply-to
		if(is_array($aReferences) && !empty($aReferences)) {
			foreach(array_keys($aReferences) as $ref) {
				if(empty($ref)) continue;
				if(null != ($ids = DAO_Ticket::getTicketByMessageId($ref))) {
				    return $ids;
				}
			}
		}
		
		// Try matching the subject line
		// [TODO] This should only happen if the destination has subject masks enabled
		if(!is_array($aSubject))
			$aSubject = array($aSubject);
			
		foreach($aSubject as $sSubject) {
			if(preg_match("/.*\[.*?\#(.*?)\].*/", $sSubject, $matches)) {
				if(isset($matches[1])) {
					$mask = $matches[1];
					if(null != ($ticket = DAO_Ticket::getTicketByMask($mask))) {
						return array(
							'ticket_id' => intval($ticket->id),
							'message_id' => intval($ticket->first_message_id)
						);
					}
				}
			}
		}
		
		return NULL;
	}
	
	static public function getDestinations($headers) {
		$sources = array();
		
		if(isset($headers['to']))
			$sources = array_merge($sources, is_array($headers['to']) ? $headers['to'] : array($headers['to']));

		if(isset($headers['cc']))
			$sources = array_merge($sources, is_array($headers['cc']) ? $headers['cc'] : array($headers['cc'])); 
		
		if(isset($headers['envelope-to']))
			$sources = array_merge($sources, is_array($headers['envelope-to']) ? $headers['envelope-to'] : array($headers['envelope-to'])); 
		
		if(isset($headers['x-envelope-to']))
			$sources = array_merge($sources, is_array($headers['x-envelope-to']) ? $headers['x-envelope-to'] : array($headers['x-envelope-to']));
		
		if(isset($headers['delivered-to']))
			$sources = array_merge($sources, is_array($headers['delivered-to']) ? $headers['delivered-to'] : array($headers['delivered-to']));
		
		$destinations = array();
		foreach($sources as $source) {
			@$parsed = imap_rfc822_parse_adrlist($source,'localhost');
			$destinations = array_merge($destinations, is_array($parsed) ? $parsed : array($parsed));
		}
		
		$addresses = array();
		foreach($destinations as $destination) {
			if(empty($destination->mailbox) || empty($destination->host))
				continue;
			
			$addresses[] = $destination->mailbox.'@'.$destination->host;
		}
		
		@imap_errors(); // Prevent errors from spilling out into STDOUT

		return $addresses;
	} 		
	
	// [TODO] Phase out in favor of the CerberusUtils class
	static function parseRfcAddress($address_string) {
		return CerberusUtils::parseRfcAddressList($address_string);
	}
	
	static function fixQuotePrintableString($input) {
		$out = '';

		// Make a single element array from any !array input
		if(!is_array($input))
			$input = array($input);

		foreach($input as $str) {
			$out .= !empty($out) ? ' ' : '';
			
			$parts = imap_mime_header_decode($str);
			if(is_array($parts))
			foreach($parts as $part) {
				try {
					$charset = ($part->charset != 'default') ? $part->charset : 'auto';
					@$out .= mb_convert_encoding($part->text,LANG_CHARSET_CODE,$charset);
				} catch(Exception $e) {}
			}
		}

		// Strip invalid characters in our encoding
		if(!mb_check_encoding($out, LANG_CHARSET_CODE))
			$out = mb_convert_encoding($out, LANG_CHARSET_CODE, LANG_CHARSET_CODE);
		
		return $out;
	}
	
};
