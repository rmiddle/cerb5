{include file="file:$core_tpl/tickets/submenu.tpl"}

{if !empty($last_ticket_mask)}
<div class="ui-widget">
	<div class="ui-state-highlight ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
		<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span> 
		<strong>Message sent!</strong> 
		(<a href="{devblocks_url}c=display&mask={$last_ticket_mask}{/devblocks_url}">view</a>)
		</p>
	</div>
</div>
{/if}

<div class="block">
<h2>Outgoing Message</h2>
<form id="frmCompose" name="compose" enctype="multipart/form-data" method="post" action="{devblocks_url}{/devblocks_url}" onsubmit="return ('1' == this.do_submit.value);">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="composeMail">
<input type="hidden" name="draft_id" value="{$draft->id}">
<input type="hidden" name="do_submit" value="0">

<table cellpadding="2" cellspacing="0" border="0" width="100%">
  <tbody>
	<tr>
		<td>
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>From:</b>&nbsp;</td>
					<td width="100%">
						<select name="team_id" id="team_id" style="border:1px solid rgb(180,180,180);padding:2px;">
							{foreach from=$active_worker_memberships item=membership key=group_id}
							<option value="{$group_id}" {if $group_id==$draft->params.group_id}selected{/if}>{$teams.$group_id->name}</option>
							{/foreach}
						</select>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>To:</b>&nbsp;</td>
					<td width="100%">
						<input type="text" name="to" value="{$draft->params.to|escape}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;">
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right">Cc:&nbsp;</td>
					<td width="100%">
						<input type="text" size="100" name="cc" value="{$draft->params.cc|escape}" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;">
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right">Bcc:&nbsp;</td>
					<td width="100%">
						<input type="text" size="100" name="bcc" value="{$draft->params.bcc|escape}" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;">
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>Subject:</b>&nbsp;</td>
					<td width="100%"><input type="text" size="100" name="subject" value="{$draft->subject|escape}" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;"></td>
				</tr>

			</table>
		</td>
	</tr>
	
	<tr>
		<td>
			<button id="btnSaveDraft" type="button" onclick="genericAjaxPost('frmCompose',null,'c=tickets&a=saveDraft&type=compose',function(json) { var obj = $.parseJSON(json); if(!obj || !obj.html || !obj.draft_id) return; $('#divDraftStatus').html(obj.html); $('#frmCompose input[name=draft_id]').val(obj.draft_id); } );"><span class="cerb-sprite sprite-check"></span> Save Draft</button>
			<button type="button" onclick="genericAjaxGet('','c=tickets&a=getComposeSignature&group_id='+selectValue(this.form.team_id),function(text) { insertAtCursor(document.getElementById('content'),text); } );"><span class="cerb-sprite sprite-document_edit"></span> Insert Signature</button>
			<button type="button" onclick="genericAjaxPanel('c=display&a=showSnippets&text=content&contexts=cerberusweb.snippets.worker',null,false,'550');"><span class="cerb-sprite sprite-text_rich"></span> {$translate->_('common.snippets')|capitalize}</button>
			{* Plugin Toolbar *}
			{if !empty($sendmail_toolbaritems)}
				{foreach from=$sendmail_toolbaritems item=renderer}
					{if !empty($renderer)}{$renderer->render($message)}{/if}
				{/foreach}
			{/if}
			<br>
			
			<div id="sendMailToolbarOptions"></div>
			<div id="divDraftStatus"></div>
			
			<textarea name="content" id="content" rows="15" cols="80" class="reply" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;">{$draft->body|escape}</textarea>
		</td>
	</tr>

	<tr>
		<td>
			<div id="replyAttachments{$message->id}" style="display:block;margin:5px;padding:5px;background-color:rgb(240,240,240);">
			<table cellpadding="0" cellspacing="0" border="0" width="100%">
			<tr>
				<td style="background-color:rgb(0,184,4);width:10px;"></td>
				<td style="padding-left:5px;">
					<H2>{$translate->_('display.convo.attachments_label')|capitalize}</H2>
					{'display.reply.attachments_limit'|devblocks_translate:$upload_max_filesize}<br>
					
					<b>{$translate->_('display.reply.attachments_add')}</b> 
					(<a href="javascript:;" onclick="appendFileInput('displayReplyAttachments','attachment[]');">{$translate->_('display.reply.attachments_more')|lower}</a>)
					(<a href="javascript:;" onclick="$('#displayReplyAttachments').html('');appendFileInput('displayReplyAttachments','attachment[]');">{$translate->_('common.clear')|lower}</a>)
					<br>
					<table cellpadding="2" cellspacing="0" border="0" width="100%">
						<tr>
							<td width="100%" valign="top">
								<div id="displayReplyAttachments">
									<input type="file" name="attachment[]" size="45"></input><br> 
								</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			</table>
			</div>
		</td>
	</tr>
				
	<tr>
		<td>
		<div style="background-color:rgb(240,240,240);margin:5px;padding:5px;">
			<table cellpadding="0" cellspacing="0" border="0" width="100%">
			<tr>
				<td style="background-color:rgb(18,147,195);width:10px;"></td>
				<td style="padding-left:5px;">
				<H2>{$translate->_('display.reply.next_label')|capitalize}</H2>
					<table cellpadding="2" cellspacing="0" border="0">
						<tr>
							<td nowrap="nowrap" valign="top" colspan="2">
								<label><input type="radio" name="closed" value="0" onclick="toggleDiv('ticketClosed','none');">{$translate->_('status.open')|capitalize}</label>
								<label><input type="radio" name="closed" value="2" onclick="toggleDiv('ticketClosed','block');" checked>{$translate->_('status.waiting')|capitalize}</label>
								{if $active_worker->hasPriv('core.ticket.actions.close')}<label><input type="radio" name="closed" value="1" onclick="toggleDiv('ticketClosed','block');">{$translate->_('status.closed')|capitalize}</label>{/if}
								<br>
								<br>

								<div id="ticketClosed" style="display:block;margin-left:10px;">
								<b>{$translate->_('display.reply.next.resume')}</b> {$translate->_('display.reply.next.resume_eg')}<br> 
								<input type="text" name="ticket_reopen" size="55" value=""><br>
								{$translate->_('display.reply.next.resume_blank')}<br>
								<br>
								</div>
		
								<b>{$translate->_('display.reply.next.handle_reply')}</b><br>
						      	<select name="next_worker_id" onchange="toggleDiv('replySurrender{$message->id}',this.selectedIndex?'block':'none');">
						      		<option value="0" selected="selected">{$translate->_('common.anybody')|capitalize}
						      		{foreach from=$workers item=worker key=worker_id name=workers}
										{if $worker_id==$active_worker->id || $active_worker->hasPriv('core.ticket.actions.assign')}
							      			{if $worker_id==$active_worker->id}{assign var=next_worker_id_sel value=$smarty.foreach.workers.iteration}{/if}
							      			<option value="{$worker_id}">{$worker->getName()}
										{/if}
						      		{/foreach}
						      	</select>&nbsp;
						      	{if $active_worker->hasPriv('core.ticket.actions.assign') && !empty($next_worker_id_sel)}
						      		<button type="button" onclick="this.form.next_worker_id.selectedIndex = {$next_worker_id_sel};toggleDiv('replySurrender{$message->id}','block');">{$translate->_('common.me')|lower}</button>
						      		<button type="button" onclick="this.form.next_worker_id.selectedIndex = 0;toggleDiv('replySurrender{$message->id}','none');">{$translate->_('common.anybody')|lower}</button>
						      	{/if}
						      	<br>
						      	<br>
						      	
						      	<div id="replySurrender{$message->id}" style="display:none;margin-left:10px;">
									<b>{$translate->_('display.reply.next.handle_reply_after')}</b> {$translate->_('display.reply.next.handle_reply_after_eg')}<br>  
							      	<input type="text" name="unlock_date" size="32" maxlength="255" value="">
							      	<button type="button" onclick="this.form.unlock_date.value='+2 hours';">{$translate->_('display.reply.next.handle_reply_after_2hrs')}</button>
							      	<br>
							      	<br>
							    </div>
		
								{if $active_worker->hasPriv('core.ticket.actions.move')}
								<b>{$translate->_('display.reply.next.move')}</b><br>  
						      	<select name="bucket_id">
						      		<option value="">-- {$translate->_('display.reply.next.move.no_thanks')|lower} --</option>
						      		<optgroup label="{$translate->_('common.inboxes')|capitalize}">
						      		{foreach from=$teams item=team}
						      			<option value="t{$team->id}">{$team->name}</option>
						      		{/foreach}
						      		</optgroup>
						      		{foreach from=$team_categories item=categories key=teamId}
										{if !empty($active_worker_memberships.$teamId)}
							      			{assign var=team value=$teams.$teamId}
							      			<optgroup label="-- {$team->name} --">
							      			{foreach from=$categories item=category}
							    				<option value="c{$category->id}">{$category->name}</option>
							    			{/foreach}
							    			</optgroup>
										{/if}
						     		{/foreach}
						      	</select><br>
						      	<br>
								{/if}
						      	
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</div>
		</td>
	</tr>
		
	<tr>
		<td>
			<button type="button" onclick="this.form.do_submit.value='1';this.form.submit();"><span class="cerb-sprite sprite-check"></span> Send Message</button>
			<button type="button" onclick="$('#btnSaveDraft').click();document.location='{devblocks_url}c=tickets{/devblocks_url}';"><span class="cerb-sprite sprite-media_pause"></span> {$translate->_('display.ui.continue_later')|capitalize}</button>
			<button type="button" onclick="if(confirm('Are you sure you want to discard this message?')) { if(0!==this.form.draft_id.value.length) { genericAjaxGet('', 'c=tickets&a=deleteDraft&draft_id='+escape(this.form.draft_id.value)); } document.location='{devblocks_url}c=tickets{/devblocks_url}'; } "><span class="cerb-sprite sprite-delete"></span> {$translate->_('display.ui.discard')|capitalize}</button>
		</td>
	</tr>
  </tbody>
</table>
</form>
</div>

<script language="JavaScript1.2" type="text/javascript">
	$(function() {
		ajax.emailAutoComplete('#frmCompose input[name=to]', { multiple: true } );
		ajax.emailAutoComplete('#frmCompose input[name=cc]', { multiple: true } );
		ajax.emailAutoComplete('#frmCompose input[name=bcc]', { multiple: true } );
		
		setInterval("$('#btnSaveDraft').click();", 30000);
	} );
</script>
