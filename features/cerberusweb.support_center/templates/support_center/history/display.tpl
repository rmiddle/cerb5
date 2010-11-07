<div id="history">
	
<div class="header"><h1>{$ticket.t_subject|escape}</h1></div>

<form action="{devblocks_url}c=history{/devblocks_url}" method="post" name="">
<input type="hidden" name="a" value="saveTicketProperties">
<input type="hidden" name="mask" value="{$ticket.t_mask}">
<input type="hidden" name="closed" value="{if $ticket.t_is_closed}1{else}0{/if}">
	<b>{$translate->_('portal.sc.public.history.reference')}</b> {$ticket.t_mask}
	 &nbsp; 
	<b>{$translate->_('ticket.updated')|capitalize}:</b> <abbr title="{$ticket.t_updated_date|devblocks_date}">{$ticket.t_updated_date|devblocks_prettytime}</abbr>
	&nbsp;

	{*{if $display_assigned_to != 0}*}
		<b>{$translate->_('portal.sc.cfg.history.display_assigned_to.label')|capitalize}:</b> 
		{if !empty($context_workers)}{$translate->_('portal.sc.cfg.history.display_assigned_to.unassigned')|capitalize}{/if}
		{foreach from=$context_workers item=worker key=worker_id}
			{$worker->first_name}
			{$worker->getName()}
			{if $display_assigned_to == 1}{$worker->first_name}{else}{$worker->getName()}{/if}&nbsp; 	 
		{/foreach}
	{*{/if}*}

	<br>
	
	<div style="padding:5px;">
		{if $ticket.t_is_closed}
		<button type="button" onclick="this.form.closed.value='0';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/folder_out.gif{/devblocks_url}" align="top"> {$translate->_('common.reopen')|capitalize}</button>
		{else}
		<button type="button" onclick="this.form.closed.value='1';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/folder_ok.gif{/devblocks_url}" align="top"> {$translate->_('common.close')|capitalize}</button>
		{/if}
	</div>
</form>

<div class="reply">
	<div class="header"><h2>{$translate->_('portal.sc.public.history.reply')}</h2></div>
	<form action="{devblocks_url}c=history{/devblocks_url}" method="post" name="replyForm">
	<input type="hidden" name="a" value="doReply">
	<input type="hidden" name="mask" value="{$ticket.t_mask}">
	
	<b>{'message.header.from'|devblocks_translate|capitalize}:</b> 
	<select name="from">
		{$contact_addresses = $active_contact->getAddresses()}
		{foreach from=$contact_addresses item=address}
		<option value="{$address->email|escape}" {if 0==strcasecmp($address->id,$active_contact->email_id)}selected="selected"{/if}>{$address->email|escape}</option>
		{/foreach}
	</select>
	<br>
	
	<textarea name="content" rows="10" cols="80" style="width:98%;"></textarea><br>
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('portal.public.send_message')}</button>
	</form>
</div>

{* Custom Fields *}
{*
<div id="custom_fields_div">
<form action="{devblocks_url}c=history{/devblocks_url}" method="post" name="">
<input type="hidden" name="a" value="saveTicketCustomProperties">
<input type="hidden" name="mask" value="{$ticket.t_mask}">
	<table cellpadding="2" cellspacing="1" border="0">
		{foreach from=$ticket_fields item=f key=f_id}
			{assign var=field_group_id value=$f->group_id}
			{if $cf_select.$f_id != 0}
				<script type="text/javascript">
					$("#custom_fields_div").addClass("custom_fields");
				</script>
				{if $field_group_id == 0 || $field_group_id == $ticket.t_team_id}
					<tr>
						<td valign="top" width="1%" nowrap="nowrap">
							<b>{$f->name}:</b>
						</td>
						<td valign="top" width="99%">
							{if $cf_select.$f_id == 2} *}{* Read Write Version *} {*
								<input type="hidden" name="field_ids[]" value="{$f_id}">
								{assign var=display_submit value=1}
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
									<select name="field_{$f_id}"> *} {* [TODO] Fix selected *} {*
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
									<input type="text" name="field_{$f_id}" id="field_{$f_id}" size="45" maxlength="255" value="{if !empty($ticket_field_values.$f_id)}{$ticket_field_values.$f_id|devblocks_date}{/if}">
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
							{else}  *}{* Read Only Version *}{*
								{if $f->type=='S'}
									{$ticket_field_values.$f_id|escape}<br>
								{elseif $f->type=='U'}
									{if !empty($ticket_field_values.$f_id)}<a href="{$ticket_field_values.$f_id|escape}" target="_blank">{$ticket_field_values.$f_id|escape}</a>{else}<i>(URL)</i>{/if}
								{elseif $f->type=='N'}
									{$ticket_field_values.$f_id|escape}<br>
								{elseif $f->type=='T'}
									{nl2br($ticket_field_values.$f_id)}<br>
								{elseif $f->type=='C'}
									<input type="checkbox" disabled="disabled" name="field_{$f_id}" value="1" {if $ticket_field_values.$f_id}checked{/if}><br>
								{elseif $f->type=='X'}
									{foreach from=$f->options item=opt}
										<label><input type="checkbox" disabled="disabled" name="field_{$f_id}[]" value="{$opt|escape}" {if isset($ticket_field_values.$f_id.$opt)}checked="checked"{/if}> {$opt}</label><br>
									{/foreach}
								{elseif $f->type=='D'}
									{foreach from=$f->options item=opt}
										{if $opt==$ticket_field_values.$f_id}{$opt}<br>{/if}
									{/foreach}
								{elseif $f->type=='M'}
									{foreach from=$f->options item=opt}
										{if isset($ticket_field_values.$f_id.$opt)}{$opt}<br>{/if}
									{/foreach}
								{elseif $f->type=='E'}
									{if !empty($ticket_field_values.$f_id)}{$ticket_field_values.$f_id|devblocks_date}{/if}<br>
								{elseif $f->type=='W'}
									{if $ticket_field_values.$f_id != 0}
										{$cust_worker = DAO_Worker::get($ticket_field_values.$f_id)}
										{$cust_worker->getName()}
									{/if}
								{/if}
							{/if}  
						</td>
					</tr>
				{/if}
			{/if}
		{/foreach}
	</table>
	{if $display_submit}
		<button type="submit" id="btnSubmit">{$translate->_('common.save')|capitalize}</button>
	{/if}
</form>
</div>
*}
{* Message History *}
{$badge_extensions = DevblocksPlatform::getExtensions('cerberusweb.support_center.message.badge', true)}
{foreach from=$messages item=message key=message_id}
	{assign var=headers value=$message->getHeaders()}
	{assign var=sender value=$message->getSender()}
	<div class="message {if $message->is_outgoing}outbound_message{else}inbound_message{/if}" style="overflow:auto;">

	{foreach from=$badge_extensions item=extension}
		{$extension->render($message)}
	{/foreach}
		
	<span class="header"><b>{$translate->_('message.header.from')|capitalize}:</b>
		{$sender_name = $sender->getName()}
		{if !empty($sender_name)}&quot;{$sender_name|escape}&quot; {/if}&lt;{$sender->email|escape}&gt; 
	</span><br>
	<span class="header"><b>{$translate->_('message.header.to')|capitalize}:</b> {$headers.to|escape}</span><br>
	{if !empty($headers.cc)}<span class="header"><b>{$translate->_('message.header.cc')|capitalize}:</b> {$headers.cc|escape}</span><br>{/if}
	{if !empty($headers.date)}<span class="header"><b>{$translate->_('message.header.date')|capitalize}:</b> {$headers.date|escape}</span><br>{/if}
	<br>
	
	<div style="clear:both;">
	{$message->getContent()|trim|escape|nl2br}
	</div>
	
	{if isset($attachments.$message_id)}
		<div style="margin-top:10px;">
		<b>Attachments:</b><br>
		<ul style="margin-top:0px;">
		{foreach from=$attachments.$message_id item=attachment key=attachment_id}
			<li>
				<a href="{devblocks_url}c=ajax&a=downloadFile&mask={$ticket.t_mask}&md5={$attachment_id|cat:$message->id|cat:$attachment.a_display_name|md5}&name={$attachment.a_display_name|escape}{/devblocks_url}" target="_blank">{$attachment.a_display_name|escape}</a>
				( 
					{$attachment.a_storage_size|devblocks_prettybytes}
					- 
					{if !empty($attachment.a_mime_type)}{$attachment.a_mime_type}{else}{$translate->_('display.convo.unknown_format')|capitalize}{/if}
				 )
			</li>
		{/foreach}
		</ul>
		</div>
	{/if}
	
	</div>
{/foreach}

</div><!--#history-->
