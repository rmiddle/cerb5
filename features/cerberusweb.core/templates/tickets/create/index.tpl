{include file="file:$core_tpl/tickets/submenu.tpl"}

{if $smarty.const.DEMO_MODE}
<div style="color:red;padding:2px;font-weight:bold;">NOTE: This helpdesk is in Demo Mode and mail will not be sent.</div>
{/if}
{if !empty($last_ticket_mask)}
<div class="success">Message created! &nbsp; &nbsp; <a href="{devblocks_url}c=display&mask={$last_ticket_mask}{/devblocks_url}" style="font-weight:normal;color:rgb(80,80,80);">View the message</a></div>
{/if}
{if !empty($no_to_in_ticket)}
<div class="error">{$translate->_('display.error.create')}</div>
{/if}

<div class="block">
<h2>{$translate->_('mail.log_message')|capitalize}</h2>
<form name="compose" enctype="multipart/form-data" method="post" action="{devblocks_url}{/devblocks_url}" onsubmit="return ('1' == this.do_submit.value);">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="logTicket">
<input type="hidden" name="do_submit" value="0">

<table cellpadding="2" cellspacing="0" border="0" width="100%">
  <tbody>
	<tr>
		<td>
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>{'message.header.to'|devblocks_translate}:</b>&nbsp;</td>
					<td width="100%">
						<select name="to" id="to" style="border:1px solid rgb(180,180,180);padding:2px;">
							{foreach from=$destinations item=destination}
							<option value="{$destination}" {if 0==strcasecmp($destination,$to)}selected{/if}>{$destination}</option>
							{/foreach}
						</select>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>{'mail.log_message.requesters'|devblocks_translate}:</b>&nbsp;</td>
					<td width="100%">
						<div id="emailautocomplete" style="width:98%;padding-bottom:2em;z-index:1;">
							<input type="text" name="reqs" id="emailinput" value="{$reqs}" style="_position:absolute;border:1px solid rgb(180,180,180);padding:2px;" autocomplete="off">
							<div id="emailcontainer"></div>
						</div>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right"><b>{'message.header.subject'|devblocks_translate}:</b>&nbsp;</td>
					<td width="100%"><input type="text" size="100" name="subject" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;"></td>
				</tr>
			</table>
		</td>
	</tr>
	
	<tr>
		<td>
		<button type="button" onclick="genericAjaxGet('','c=tickets&a=getComposeSignature&group_id='+selectValue(this.form.to),{literal}function(o){insertAtCursor(document.getElementById('content'),o.responseText);}{/literal});"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_edit.gif{/devblocks_url}" align="top"> Insert Signature</button>
		<button type="button" onclick="genericAjaxPanel('c=display&a=showTemplatesPanel&type=3&txt_name=content',this,false,'550px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/text_rich.gif{/devblocks_url}" align="top"> E-mail Templates</button>
		{* Plugin Toolbar *}
		{if !empty($logmail_toolbaritems)}
			{foreach from=$logmail_toolbaritems item=renderer}
				{if !empty($renderer)}{$renderer->render($message)}{/if}
			{/foreach}
		{/if}
		<br>
		
		<div id="logTicketToolbarOptions"></div>
		
		<textarea name="content" id="content" rows="15" cols="80" class="reply" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;"></textarea><br>
		<label><input type="checkbox" name="send_to_requesters" value="1"> {'mail.log_message.send_to_requesters'|devblocks_translate}</label>
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
					(<a href="javascript:;" onclick="clearDiv('displayReplyAttachments');appendFileInput('displayReplyAttachments','attachment[]');">{$translate->_('common.clear')|lower}</a>)
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
										<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{if !empty($ticket_field_values.$f_id)}{$ticket_field_values.$f_id|devblocks_date}{/if}"><button type="button" onclick="ajax.getDateChooser('dateCustom{$f_id}',this.form.field_{$f_id});">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
										<div id="dateCustom{$f_id}" style="display:none;position:absolute;z-index:1;"></div>
									{elseif $f->type=='W'}
										{if empty($workers)}
											{php}$this->assign('workers', DAO_Worker::getAllActive());{/php}
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
								<label><input type="radio" name="closed" value="0" onclick="toggleDiv('ticketClosed','none');" {if $default_ticket_open_status eq 0}checked{/if}>{$translate->_('status.open')|capitalize}</label>
								<label><input type="radio" name="closed" value="2" onclick="toggleDiv('ticketClosed','block');" {if $default_ticket_open_status eq 1}checked{/if}>{$translate->_('status.waiting')|capitalize}</label>
								{if $active_worker->hasPriv('core.ticket.actions.close')}<label><input type="radio" name="closed" value="1" onclick="toggleDiv('ticketClosed','block');"{if $default_ticket_open_status eq 2}checked{/if}>{$translate->_('status.closed')|capitalize}</label>{/if}
								<br>
								<br>
		
								<div id="ticketClosed" style="display:{if $default_ticket_open_status==0}none{else}block{/if};margin-left:10px;">
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
			<br>
			<button type="button" onclick="this.form.do_submit.value='1';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> Send Message</button>
			<button type="button" onclick="document.location='{devblocks_url}c=tickets{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> Discard</button>
		</td>
	</tr>
  </tbody>
</table>
</form>
</div>

<script type="text/javascript">
{literal}
YAHOO.util.Event.addListener(window,'load',function(e) {
	ajax.cbEmailMultiplePeek(null);
});
{/literal}
</script>
