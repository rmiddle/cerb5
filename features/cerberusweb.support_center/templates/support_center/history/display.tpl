<div id="history">
	
<div class="header"><h1>{$ticket.t_subject}</h1></div>

<form action="{devblocks_url}c=history{/devblocks_url}" method="post" name="">
<input type="hidden" name="a" value="saveTicketProperties">
<input type="hidden" name="mask" value="{$ticket.t_mask}">
<input type="hidden" name="closed" value="{if $ticket.t_is_closed}1{else}0{/if}">
	<b>{$translate->_('portal.sc.public.history.reference')}</b> {$ticket.t_mask}
	 &nbsp; 
	<b>{$translate->_('ticket.updated')|capitalize}:</b> <abbr title="{$ticket.t_updated_date|devblocks_date}">{$ticket.t_updated_date|devblocks_prettytime}</abbr>
	&nbsp;

	{if $display_assigned_to != 0}
		<b>{$translate->_('portal.sc.cfg.history.display_assigned_to.label')|capitalize}:</b> 
		{if empty($context_workers)}{$translate->_('portal.sc.cfg.history.display_assigned_to.unassigned')|capitalize}{/if}
		{foreach from=$context_workers item=worker key=worker_id}
			{if $display_assigned_to == 1}{$worker->first_name}{else}{$worker->getName()}{/if}&nbsp; 	 
		{/foreach}
	{/if}

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
		<option value="{$address->email}" {if 0==strcasecmp($address->id,$active_contact->email_id)}selected="selected"{/if}>{$address->email}</option>
		{/foreach}
	</select>
	<br>
	
	<textarea name="content" rows="10" cols="80" style="width:98%;"></textarea><br>
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('portal.public.send_message')}</button>
	</form>
</div>

{* Custom Fields *}
<div id="custom_fields_div">
<form action="{devblocks_url}c=history{/devblocks_url}" method="post" name="">
<input type="hidden" name="a" value="saveTicketCustomProperties">
<input type="hidden" name="mask" value="{$ticket.t_mask}">
	<table cellpadding="2" cellspacing="1" border="0">
		{foreach from=$ticket_custom_fields item=field key=field_id}
			{if $show_fields.{$field_id}}
				<tr>
					<td width="1%" nowrap="nowrap" valign="top"><b>{$field->name|escape}:</b></td>
					<td width="99%">
						{if 1==$show_fields.{$field_id}}
							{include file="devblocks:cerberusweb.support_center:portal_{$portal_code}:support_center/history/customfields_readonly.tpl" values=$ticket_custom_field_values}
						{elseif 2==$show_fields.{$field_id}}
							{include file="devblocks:cerberusweb.support_center:portal_{$portal_code}:support_center/history/customfields_writeable.tpl" values=$ticket_custom_field_values field_prefix="ticket_custom"}
						{else}
						{/if}
					</td>
				</tr>
			{/if}
		{/foreach}
	</table>
	{if $display_submit}
		<button type="submit" id="btnSubmit">{$translate->_('common.save')|capitalize}</button>
	{/if}
</form>
</div>

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
		{if !empty($sender_name)}&quot;{$sender_name}&quot; {/if}&lt;{$sender->email}&gt; 
	</span><br>
	<span class="header"><b>{$translate->_('message.header.to')|capitalize}:</b> {$headers.to}</span><br>
	{if !empty($headers.cc)}<span class="header"><b>{$translate->_('message.header.cc')|capitalize}:</b> {$headers.cc}</span><br>{/if}
	{if !empty($headers.date)}<span class="header"><b>{$translate->_('message.header.date')|capitalize}:</b> {$headers.date}</span><br>{/if}
	<br>
	
	<div style="clear:both;">
	<pre>{$message->getContent()|trim|escape|devblocks_hyperlinks|devblocks_hideemailquotes nofilter}</pre>
	</div>
	
	{if isset($attachments.$message_id)}
		<div style="margin-top:10px;">
		<b>Attachments:</b><br>
		<ul style="margin-top:0px;">
		{foreach from=$attachments.$message_id item=map}
			{$links = $map.links}
			{$files = $map.attachments}
			
			{foreach from=$links item=link}
			{$attachment = $files.{$link->attachment_id}}
			<li>
				<a href="{devblocks_url}c=ajax&a=downloadFile&guid={$link->guid}&name={$attachment->display_name}{/devblocks_url}" target="_blank">{$attachment->display_name}</a>
				( 
					{$attachment->storage_size|devblocks_prettybytes}
					- 
					{if !empty($attachment->mime_type)}{$attachment->mime_type}{else}{$translate->_('display.convo.unknown_format')|capitalize}{/if}
				 )
			</li>
			{/foreach}
		{/foreach}
		</ul>
		</div>
	{/if}
	
	</div>
{/foreach}

</div><!--#history-->
