{include file="file:$core_tpl/tickets/submenu.tpl"}

{if !empty($last_ticket_mask)}
<div class="ui-widget">
	<div class="ui-state-highlight ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
		<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span> 
		<strong>Message created!</strong> 
		(<a href="{devblocks_url}c=display&mask={$last_ticket_mask}{/devblocks_url}">view</a>)
		</p>
	</div>
</div>
{/if}

<div class="block">
<h2>{$translate->_('mail.log_message')|capitalize}</h2>
<form id="frmLogTicket" name="frmLogTicket" enctype="multipart/form-data" method="post" action="{devblocks_url}{/devblocks_url}">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="logTicket">
<input type="hidden" name="draft_id" value="{$draft->id}">

<table cellpadding="2" cellspacing="0" border="0" width="100%">
  <tbody>
	<tr>
		<td>
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>{'message.header.to'|devblocks_translate}:</b>&nbsp;</td>
					<td width="100%">
						<select name="to" id="to" class="required" style="border:1px solid rgb(180,180,180);padding:2px;">
							{foreach from=$destinations item=destination}
							<option value="{$destination}" {if 0==strcasecmp($destination,$draft->params.to)}selected="selected"{/if}>{$destination}</option>
							{/foreach}
						</select>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>{'mail.log_message.requesters'|devblocks_translate}:</b>&nbsp;</td>
					<td width="100%">
						<input type="text" name="reqs" value="{$draft->params.requesters|escape}" class="required" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;">
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>{'message.header.subject'|devblocks_translate}:</b>&nbsp;</td>
					<td width="100%"><input type="text" size="100" name="subject" value="{$draft->subject|escape}" class="required" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;"></td>
				</tr>
			</table>
		</td>
	</tr>
	
	<tr>
		<td>
		<button id="btnSaveDraft" type="button" onclick="genericAjaxPost('frmLogTicket',null,'c=tickets&a=saveDraft&type=create',function(json) { var obj = $.parseJSON(json); if(!obj || !obj.html || !obj.draft_id) return; $('#divDraftStatus').html(obj.html); $('#frmLogTicket input[name=draft_id]').val(obj.draft_id); } );"><span class="cerb-sprite sprite-check"></span> Save Draft</button>
		<button type="button" onclick="genericAjaxGet('','c=tickets&a=getLogTicketSignature&email='+escape(selectValue(this.form.to)),function(text) { insertAtCursor(document.getElementById('content'), text); } );"><span class="cerb-sprite sprite-document_edit"></span> Insert Signature</button>
		<button type="button" onclick="genericAjaxPopup('peek','c=display&a=showSnippets&text=content&contexts=cerberusweb.contexts.worker',null,false,'550');"><span class="cerb-sprite sprite-text_rich"></span> {$translate->_('common.snippets')|capitalize}</button>
		{* Plugin Toolbar *}
		{if !empty($logmail_toolbaritems)}
			{foreach from=$logmail_toolbaritems item=renderer}
				{if !empty($renderer)}{$renderer->render($message)}{/if}
			{/foreach}
		{/if}
		<br>
		
		<div id="logTicketToolbarOptions"></div>
		<div id="divDraftStatus"></div>
		
		<textarea name="content" id="content" rows="15" cols="80" class="reply required" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;">{$draft->body}</textarea><br>
		<label><input type="checkbox" name="send_to_requesters" value="1" {if $draft->params.send_to_reqs}checked="checked"{/if}> {'mail.log_message.send_to_requesters'|devblocks_translate}</label>
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
			<div id="replyAttachments{$message->id}" style="display:block;margin:5px;padding:5px;background-color:rgb(240,240,240);">
			<table cellpadding="0" cellspacing="0" border="0" width="100%">
			<tr>
				<td style="background-color:rgb(184,0,4);width:10px;"></td>
				<td style="padding-left:5px;">
					{* [TODO] Display by Group *}
					<table cellpadding="2" cellspacing="1" border="0">
					{assign var=last_group_id value=-1}
					{foreach from=$ticket_fields item=f key=f_id}
					{assign var=field_group_id value=$f->group_id}
					{if $field_group_id == 0 || $field_group_id == $ticket->team_id}
						{assign var=show_submit value=1}
						{if $field_group_id != $last_group_id}
							<tr>
								<td colspan="2"><H2>{if $f->group_id==0}Global{else}{$groups.$field_group_id->name}{/if} Fields</H2></td>
							</tr>
						{/if}
							<tr>
								<td valign="top" width="1%" nowrap="nowrap">
									<input type="hidden" name="field_ids[]" value="{$f_id}">
									<b>{$f->name}:</b>
								</td>
								<td valign="top" width="99%">
									{if $f->type=='S'}
										<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{$ticket_field_values.$f_id|escape}"><br>
									{elseif $f->type=='U'}
										<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{$ticket_field_values.$f_id|escape}">
										{if !empty($ticket_field_values.$f_id)}<a href="{$ticket_field_values.$f_id|escape}" target="_blank">URL</a>{else}<i>(URL)</i>{/if}
									{elseif $f->type=='N'}
										<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{$ticket_field_values.$f_id|escape}"><br>
									{elseif $f->type=='T'}
										<textarea name="field_{$f_id}" rows="4" cols="50" style="width:98%;">{$ticket_field_values.$f_id}</textarea><br>
									{elseif $f->type=='C'}
										<input type="checkbox" name="field_{$f_id}" value="1" {if $ticket_field_values.$f_id}checked{/if}><br>
									{elseif $f->type=='X'}
										{foreach from=$f->options item=opt}
										<label><input type="checkbox" name="field_{$f_id}[]" value="{$opt|escape}" {if isset($ticket_field_values.$f_id.$opt)}checked="checked"{/if}> {$opt}</label><br>
										{/foreach}
									{elseif $f->type=='D'}
										<select name="field_{$f_id}">{* [TODO] Fix selected *}
											<option value=""></option>
											{foreach from=$f->options item=opt}
											<option value="{$opt|escape}" {if $opt==$ticket_field_values.$f_id}selected{/if}>{$opt}</option>
											{/foreach}
										</select><br>
									{elseif $f->type=='M'}
										<select name="field_{$f_id}[]" size="5" multiple="multiple">
											{foreach from=$f->options item=opt}
											<option value="{$opt|escape}" {if isset($ticket_field_values.$f_id.$opt)}selected="selected"{/if}>{$opt}</option>
											{/foreach}
										</select><br>
										<i><small>{$translate->_('common.tips.multi_select')}</small></i>
									{elseif $f->type=='E'}
										<input type="text" id="field_{$f_id}" name="field_{$f_id}" size="45" maxlength="255" value="{if !empty($ticket_field_values.$f_id)}{$ticket_field_values.$f_id|devblocks_date}{/if}">
										<script type="text/javascript" language="JavaScript1.2">
											devblocksAjaxDateChooser('#field_{$f_id}');
										</script>
									{elseif $f->type=='W'}
										{if empty($workers)}
											{$workers = DAO_Worker::getAllActive()}
										{/if}
										<select name="field_{$f_id}">
											<option value=""></option>
											{foreach from=$workers item=worker}
											<option value="{$worker->id}" {if $worker->id==$ticket_field_values.$f_id}selected="selected"{/if}>{$worker->getName()}</option>
											{/foreach}
										</select>
									{/if}	
								</td>
							</tr>
						{assign var=last_group_id value=$f->group_id}
					{/if}
					{/foreach}
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
		
								{if $active_worker->hasPriv('core.ticket.actions.assign')}
									<b>{$translate->_('display.reply.next.handle_reply')}</b><br>
									<button type="button" class="chooser_worker"><span class="cerb-sprite sprite-add"></span></button>
							      	<br>
							      	<br>
								{/if}
						      	
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
			<br>
			<button type="submit" onclick="$('#btnSaveDraft').click();"><span class="cerb-sprite sprite-check"></span> Send Message</button>
			<button type="button" onclick="$('#btnSaveDraft').click();document.location='{devblocks_url}c=tickets{/devblocks_url}';"><span class="cerb-sprite sprite-media_pause"></span> {$translate->_('display.ui.continue_later')|capitalize}</button>
			<button type="button" onclick="if(confirm('Are you sure you want to discard this message?')) { if(0!==this.form.draft_id.value.length) { genericAjaxGet('', 'c=tickets&a=deleteDraft&draft_id='+escape(this.form.draft_id.value)); } document.location='{devblocks_url}c=tickets{/devblocks_url}'; } "><span class="cerb-sprite sprite-delete"></span> {$translate->_('display.ui.discard')|capitalize}</button>
		</td>
	</tr>
  </tbody>
</table>
</form>
</div>

<script type="text/javascript">
	$(function() {
		ajax.emailAutoComplete('#frmLogTicket input[name=reqs]', { multiple: true } );
		
		$('#frmLogTicket').validate();
		
		setInterval("$('#btnSaveDraft').click();", 30000);
		
		$('#frmLogTicket button.chooser_worker').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','worker_id');
		});		
	});
</script>
