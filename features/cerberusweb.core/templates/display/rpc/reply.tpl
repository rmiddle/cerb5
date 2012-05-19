<div class="block" style="width:98%;margin:10px;">

<form id="reply{$message->id}_part1">
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td><h2>{if $is_forward}{$translate->_('display.ui.forward')|capitalize}{else}{$translate->_('display.ui.reply')|capitalize}{/if}</h2></td>
	</tr>
	<tr>
		<td width="100%">
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				{if isset($groups.{$ticket->group_id})}
				<tr>
					<td width="1%" nowrap="nowrap" valign="top">{$translate->_('message.header.from')|capitalize}: </td>
					<td width="99%" align="left">
						{$groups.{$ticket->group_id}->name}
					</td>
				</tr>
				{/if}
				
				<tr>
					<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('message.header.to')|capitalize}:</b> </td>
					<td width="99%" align="left">
						<input type="text" size="45" name="to" value="{if !empty($draft)}{$draft->params.to}{else}{if $is_forward}{else}{foreach from=$requesters item=req_addy name=reqs}{$fullname=$req_addy->getName()}{if !empty($fullname)}{$fullname} &lt;{$req_addy->email}&gt;{else}{$req_addy->email}{/if}{if !$smarty.foreach.reqs.last}, {/if}{/foreach}{/if}{/if}" class="required" style="width:100%;border:1px solid rgb(180,180,180);padding:2px;">
						<div class="instructions" style="display:none;">
							These recipients will automatically be included in all future correspondence
						</div>
						
						{if !$is_forward}
							{if !empty($suggested_recipients)}
								<div id="reply{$message->id}_suggested">
									<a href="javascript:;" onclick="$(this).closest('div').remove();">x</a>
									<b>Consider adding these recipients:</b>
									<ul class="bubbles">
									{foreach from=$suggested_recipients item=sug name=sugs}
										<li><a href="javascript:;" class="suggested">{$sug.full_email}</a></li>
									{/foreach}
									</ul> 
								</div>
							{/if}
						{/if}
					</td>
				</tr>
				
				<tr>
					<td width="1%" nowrap="nowrap" valign="top">{$translate->_('message.header.cc')|capitalize}: </td>
					<td width="99%" align="left">
						<input type="text" size="45" name="cc" value="{$draft->params.cc}" style="width:100%;border:1px solid rgb(180,180,180);padding:2px;">
						<div class="instructions" style="display:none;">
							These recipients will publicly receive a copy of this message	
						</div>
					</td>
				</tr>

				<tr>
					<td width="1%" nowrap="nowrap" valign="top">{$translate->_('message.header.bcc')|capitalize}: </td>
					<td width="99%" align="left">
						<input type="text" size="45" name="bcc" value="{$draft->params.bcc}" style="width:100%;border:1px solid rgb(180,180,180);padding:2px;">					
						<div class="instructions" style="display:none;">
							These recipients will secretly receive a copy of this message			
						</div>
					</td>
				</tr>
				
				<tr>
					<td width="1%" nowrap="nowrap" valign="top">{$translate->_('message.header.subject')|capitalize}: </td>
					<td width="99%" align="left">
						<input type="text" size="45" name="subject" value="{if !empty($draft)}{$draft->params.subject}{else}{if $is_forward}Fwd: {/if}{$ticket->subject}{/if}" style="width:100%;border:1px solid rgb(180,180,180);padding:2px;" class="required">					
					</td>
				</tr>
				
			</table>

			<div id="divDraftStatus{$message->id}"></div>
			
			<div>
				<fieldset style="display:inline-block;">
					<legend>Actions</legend>
					{assign var=headers value=$message->getHeaders()}
					<button name="saveDraft" type="button" onclick="if($(this).attr('disabled'))return;$(this).attr('disabled','disabled');genericAjaxPost('reply{$message->id}_part2',null,'c=display&a=saveDraftReply&is_ajax=1',function(json, ui) { var obj = $.parseJSON(json); $('#divDraftStatus{$message->id}').html(obj.html); $('#reply{$message->id}_part2 input[name=draft_id]').val(obj.draft_id); $('#reply{$message->id}_part1 button[name=saveDraft]').removeAttr('disabled'); } );"><span class="cerb-sprite2 sprite-tick-circle"></span> Save Draft</button>
					<button id="btnInsertReplySig{$message->id}" type="button" title="(Ctrl+Shift+G)" onclick="genericAjaxGet('','c=tickets&a=getComposeSignature&group_id={$ticket->group_id}&bucket_id={$ticket->bucket_id}',function(txt) { $('#reply_{$message->id}').insertAtCursor(txt); } );"><span class="cerb-sprite sprite-document_edit"></span> {$translate->_('display.reply.insert_sig')|capitalize}</button>
					{* Plugin Toolbar *}
					{if !empty($reply_toolbaritems)}
						{foreach from=$reply_toolbaritems item=renderer}
							{if !empty($renderer)}{$renderer->render($message)}{/if}
						{/foreach}
					{/if}
				</fieldset>		
				
				<fieldset style="display:inline-block;">
					<legend>{'common.snippets'|devblocks_translate|capitalize}</legend>
					<div>
						Insert: 
						<input type="text" size="25" class="context-snippet autocomplete">
						<button type="button" onclick="ajax.chooserSnippet('chooser{$message->id}',$('#reply_{$message->id}'), { '{CerberusContexts::CONTEXT_TICKET}':'{$ticket->id}', '{CerberusContexts::CONTEXT_WORKER}':'{$active_worker->id}' });"><span class="cerb-sprite sprite-view"></span></button>
						<button type="button" onclick="genericAjaxPopup('peek','c=internal&a=showSnippetsPeek&id=0&owner_context={CerberusContexts::CONTEXT_WORKER}&owner_context_id={$active_worker->id}&context={CerberusContexts::CONTEXT_TICKET}&context_id={$ticket->id}',null,false,'550');"><span class="cerb-sprite2 sprite-plus-circle"></span></button>
					</div>
				</fieldset>
			</div>
			
		</td>
	</tr>
</table>
</form>

<div id="replyToolbarOptions{$message->id}"></div>

<form id="reply{$message->id}_part2" action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data">
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td>
<!-- {* [TODO] This is ugly but gets the job done for now, giving toolbar plugins above their own <form> scope *} -->
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="sendReply">
<input type="hidden" name="id" value="{$message->id}">
<input type="hidden" name="ticket_id" value="{$ticket->id}">
<input type="hidden" name="ticket_mask" value="{$ticket->mask}">
<input type="hidden" name="draft_id" value="{$draft->id}">
<input type="hidden" name="reply_mode" value="">
{if $is_forward}<input type="hidden" name="is_forward" value="1">{/if}

<!-- {* Copy these dynamically so a plugin dev doesn't need to conflict with the reply <form> *} -->
<input type="hidden" name="to" value="{if !empty($draft)}{$draft->params.to}{else}{if $is_forward}{else}{foreach from=$requesters item=req_addy name=reqs}{$req_addy->email}{if !$smarty.foreach.reqs.last}, {/if}{/foreach}{/if}{/if}">
<input type="hidden" name="cc" value="{$draft->params.cc}">
<input type="hidden" name="bcc" value="{$draft->params.bcc}">
<input type="hidden" name="subject" value="{if !empty($draft)}{$draft->params.subject}{else}{if $is_forward}Fwd: {/if}{$ticket->subject}{/if}">

{if $is_forward}
<textarea name="content" rows="20" cols="80" id="reply_{$message->id}" class="reply" style="width:98%;border:1px solid rgb(180,180,180);padding:5px;">
{if !empty($draft)}{$draft->body}{else}
{if !empty($signature)}


{$signature}
{/if}

{$translate->_('display.reply.forward.banner')}
{if isset($headers.subject)}{$translate->_('message.header.subject')|capitalize}: {$headers.subject|cat:"\n"}{/if}
{if isset($headers.from)}{$translate->_('message.header.from')|capitalize}: {$headers.from|cat:"\n"}{/if}
{if isset($headers.date)}{$translate->_('message.header.date')|capitalize}: {$headers.date|cat:"\n"}{/if}
{if isset($headers.to)}{$translate->_('message.header.to')|capitalize}: {$headers.to|cat:"\n"}{/if}

{$message->getContent()|trim}
{/if}
</textarea>
{else}
<textarea name="content" rows="20" cols="80" id="reply_{$message->id}" class="reply" style="width:98%;border:1px solid rgb(180,180,180);padding:5px;">
{if !empty($draft)}{$draft->body}{else}
{if !empty($signature) && 1==$signature_pos}


{$signature}{if $is_quoted}{*Sig above*}


{/if}
{/if}{if $is_quoted}{$quote_sender=$message->getSender()}{$quote_sender_personal=$quote_sender->getName()}{if !empty($quote_sender_personal)}{$reply_personal=$quote_sender_personal}{else}{$reply_personal=$quote_sender->email}{/if}{$reply_date=$message->created_date|devblocks_date:'D, d M Y'}{'display.reply.reply_banner'|devblocks_translate:$reply_date:$reply_personal}
{/if}{if $is_quoted}{$message->getContent()|trim|indent:1:'> '}
{/if}{if !empty($signature) && 2==$signature_pos}


{$signature}
{/if}{*Sig below*}{/if}
</textarea>
{/if}
		</td>
	</tr>
	<tr>
		<td>
			<fieldset class="peek">
				<legend>{$translate->_('common.attachments')|capitalize}</legend>

				<button type="button" class="chooser_file"><span class="cerb-sprite2 sprite-plus-circle"></span></button>
				<ul class="bubbles chooser-container">
				{if $draft->params.file_ids}
					{foreach from=$draft->params.file_ids item=file_id}
						{$file = DAO_Attachment::get($file_id)}
						{if !empty($file)}
						<li><input type="hidden" name="file_ids[]" value="{$file_id}">{$file->display_name} ({$file->storage_size} bytes) <a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
						{/if} 
					{/foreach}
				{elseif $is_forward && !empty($forward_attachments)}
					{foreach from=$forward_attachments item=attach}
						<li><input type="hidden" name="file_ids[]" value="{$attach->id}">{$attach->display_name} ({$attach->storage_size} bytes) <a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
					{/foreach}
				{/if}
				</ul>
				
			</fieldset>
		</td>
	</tr>
	<tr>
		<td>
			<fieldset class="peek">
				<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
				
				<table cellpadding="2" cellspacing="0" border="0">
					<tr>
						<td nowrap="nowrap" valign="top" colspan="2">
							<div style="margin-bottom:10px;">
								{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" object_watchers=$object_watchers context=CerberusContexts::CONTEXT_TICKET context_id=$ticket->id full=true}
							</div>

							<label><input type="radio" name="closed" value="0" onclick="toggleDiv('replyOpen{$message->id}','block');toggleDiv('replyClosed{$message->id}','none');" {if (empty($draft) && 'open'==$mail_status_reply) || $draft->params.closed==0}checked="checked"{/if}>{$translate->_('status.open')|capitalize}</label>
							<label><input type="radio" name="closed" value="2" onclick="toggleDiv('replyOpen{$message->id}','block');toggleDiv('replyClosed{$message->id}','block');" {if (empty($draft) && 'waiting'==$mail_status_reply) || $draft->params.closed==2}checked="checked"{/if}>{$translate->_('status.waiting')|capitalize}</label>
							{if $active_worker->hasPriv('core.ticket.actions.close') || ($ticket->is_closed && !$ticket->is_deleted)}<label><input type="radio" name="closed" value="1" onclick="toggleDiv('replyOpen{$message->id}','none');toggleDiv('replyClosed{$message->id}','block');" {if (empty($draft) && 'closed'==$mail_status_reply) || $draft->params.closed==1}checked="checked"{/if}>{$translate->_('status.closed')|capitalize}</label>{/if}
							<br>
							<br>
							
					      	<div id="replyClosed{$message->id}" style="display:{if (empty($draft) && 'open'==$mail_status_reply) || $draft->params.closed==0}none{else}block{/if};margin-left:10px;margin-bottom:10px;">
					      	<b>{$translate->_('display.reply.next.resume')}</b> {$translate->_('display.reply.next.resume_eg')}<br> 
					      	<input type="text" name="ticket_reopen" size="55" value="{if !empty($draft)}{$draft->params.ticket_reopen}{elseif !empty($ticket->due_date)}{$ticket->due_date|devblocks_date}{/if}"><br>
					      	{$translate->_('display.reply.next.resume_blank')}<br>
					      	</div>
	
							{if $active_worker->hasPriv('core.ticket.actions.move')}
							<b>{$translate->_('display.reply.next.move')}</b><br>  
					      	<select name="bucket_id">
					      		<option value="">-- {$translate->_('display.reply.next.move.no_thanks')|lower} --</option>
					      		{if empty($ticket->bucket_id)}{assign var=t_or_c value="t"}{else}{assign var=t_or_c value="c"}{/if}
					      		<optgroup label="{$translate->_('common.inboxes')|capitalize}">
					      		{foreach from=$groups item=group}
					      			<option value="t{$group->id}" {if $draft->params.bucket_id=="t{$group->id}"}selected="selected"{/if}>{$group->name}{if $t_or_c=='t' && $ticket->group_id==$group->id} {$translate->_('display.reply.next.move.current')}{/if}</option>
					      		{/foreach}
					      		</optgroup>
					      		{foreach from=$group_buckets item=buckets key=groupId}
					      			{assign var=group value=$groups.$groupId}
					      			{if !empty($active_worker_memberships.$groupId)}
						      			<optgroup label="-- {$group->name} --">
						      			{foreach from=$buckets item=bucket}
						    				<option value="c{$bucket->id}" {if $draft->params.bucket_id=="c{$bucket->id}"}selected="selected"{/if}>{$bucket->name}{if $t_or_c=='c' && $ticket->bucket_id==$bucket->id} {$translate->_('display.reply.next.move.current')}{/if}</option>
						    			{/foreach}
						    			</optgroup>
						    		{/if}
					     		{/foreach}
					      	</select><br>
					      	<br>
					      	{/if}
					      	
					      	<b>{'display.reply.next.owner'|devblocks_translate}</b><br>
					      	<select name="owner_id">
					      		<option value="">-- {'common.nobody'|devblocks_translate|lower} --</option>
					      		{foreach from=$workers item=owner key=owner_id}
					      		<option value="{$owner_id}" {if !empty($draft) && $draft->params.owner_id==$owner_id}selected="selected"{elseif $ticket->owner_id==$owner_id}selected="selected"{/if}>{$owner->getName()}</option>
					      		{/foreach}
					      	</select>
					      	<button type="button" onclick="$(this).prev('select[name=owner_id]').val('{$active_worker->id}');">{'common.me'|devblocks_translate|lower}</button>
					      	<button type="button" onclick="$(this).prevAll('select[name=owner_id]').first().val('');">{'common.nobody'|devblocks_translate|lower}</button>
					      	<br>
					      	<br>
						</td>
					</tr>
				</table>
			</fieldset>
			
			<fieldset class="peek">
				<legend>{'common.custom_fields'|devblocks_translate|capitalize}</legend>
				
				{if !empty($custom_fields) || !empty($group_fields)}
				
				{if !empty($draft) && !empty($draft->params.custom_fields)}
					{$custom_field_values = $draft->params.custom_fields}
				{/if}
				
				<div id="compose_cfields" style="margin:5px 0px 0px 10px;">
					<div class="global">
						{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
					</div>
					<div class="group">
						{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" custom_fields=$group_fields bulk=false}
					</div>
				</div>
				{/if}
			</fieldset>
		</td>
	</tr>
	<tr>
		<td>
			<button type="button" class="send split-left" onclick="$(this).closest('td').find('ul li:first a').click();"><span class="cerb-sprite2 sprite-tick-circle"></span> {if $is_forward}{$translate->_('display.ui.forward')|capitalize}{else}{$translate->_('display.ui.send_message')}{/if}</button><!--
      		--><button type="button" class="split-right" onclick="$(this).next('ul').toggle();"><span class="cerb-sprite sprite-arrow-down-white"></span></button>
      		<ul class="cerb-popupmenu cerb-float" style="margin-top:-5px;">
      			<li><a href="javascript:;" class="send" onclick="if($('#reply{$message->id}_part1').validate().form()) { if(null != draftAutoSaveInterval) { clearTimeout(draftAutoSaveInterval); draftAutoSaveInterval = null; } $frm = $(this).closest('form'); $frm.find('input:hidden[name=reply_mode]').val(''); $(this).closest('td').hide(); $frm.submit(); }">{if $is_forward}{$translate->_('display.ui.forward')}{else}{$translate->_('display.ui.send_message')}{/if}</a></li>
      			<li><a href="javascript:;" class="save" onclick="if($('#reply{$message->id}_part1').validate().form()) { if(null != draftAutoSaveInterval) { clearTimeout(draftAutoSaveInterval); draftAutoSaveInterval = null; } $frm = $(this).closest('form'); $frm.find('input:hidden[name=reply_mode]').val('save'); $(this).closest('td').hide(); $frm.submit(); }">{'display.ui.save_nosend'|devblocks_translate}</a></li>
      			<li><a href="javascript:;" class="draft" onclick="if($('#reply{$message->id}_part1').validate().form()) { if(null != draftAutoSaveInterval) { clearTimeout(draftAutoSaveInterval); draftAutoSaveInterval = null; } $frm = $(this).closest('form'); $frm.find('input:hidden[name=a]').val('saveDraftReply'); $(this).closest('td').hide(); $frm.submit(); } ">{$translate->_('display.ui.continue_later')}</a></li>
      		</ul>
			<button type="button" class="discard" onclick="window.onbeforeunload=null;if(confirm('Are you sure you want to discard this reply?')) { if(null != draftAutoSaveInterval) { clearTimeout(draftAutoSaveInterval); draftAutoSaveInterval = null; } $frm = $(this).closest('form'); genericAjaxGet('', 'c=mail&a=handleSectionAction&section=drafts&action=deleteDraft&draft_id='+escape($frm.find('input:hidden[name=draft_id]').val()), function(o) { $frm = $('#reply{$message->id}_part2'); $('#draft'+escape($frm.find('input:hidden[name=draft_id]').val())).remove(); $('#reply{$message->id}').html('');  } ); }"><span class="cerb-sprite2 sprite-cross-circle"></span> {$translate->_('display.ui.discard')|capitalize}</button>
		</td>
	</tr>
</table>
</form>

</div>

<script type="text/javascript">
	if(draftAutoSaveInterval == undefined)
		var draftAutoSaveInterval = null;
	
	$(function() {
		$frm = $('#reply{$message->id}_part1');
		$frm2 = $('#reply{$message->id}_part2');
		
		// Autocompletes
		ajax.emailAutoComplete('#reply{$message->id}_part1 input[name=to]', { multiple: true } );
		ajax.emailAutoComplete('#reply{$message->id}_part1 input[name=cc]', { multiple: true } );
		ajax.emailAutoComplete('#reply{$message->id}_part1 input[name=bcc]', { multiple: true } );
		
		$frm.find('input:text').focus(function(event) {
			$(this).nextAll('div.instructions').fadeIn();
		});
		
		$frm.find('input:text').blur(function(event) {
			$(this).nextAll('div.instructions').fadeOut();
			name = event.target.name;
			$('#reply{$message->id}_part2 input:hidden[name='+name+']').val(event.target.value);
		} );
		
		$frm.find('input:text[name=to], #reply{$message->id}_part1 input:text[name=cc], #reply{$message->id}_part1 input:text[name=bcc]').focus(function(event) {
			$('#reply{$message->id}_suggested').appendTo($(this).closest('td'));
		});
		
		// Insert suggested on click
		$('#reply{$message->id}_suggested').find('a.suggested').click(function(e) {
			$this = $(this);
			$sug = $this.text();
			
			$to=$this.closest('td').find('input:text:first');
			$val=$to.val();
			$len=$val.length;
			
			$last = null;
			if($len>0)
				$last=$val.substring($len-1);
			
			if(0==$len || $last==' ')
				$to.val($val+$sug);
			else if($last==',')
				$to.val($val + ' '+$sug);
			else $to.val($val + ', '+$sug);
				$to.focus();
			
			$ul=$this.closest('ul');
			$this.closest('li').remove();
			if(0==$ul.find('li').length)
				$ul.closest('div').remove();
		});
		
		$frm.validate();
		
		$frm.find('button[name=saveDraft]').click(); // save now
		if(null != draftAutoSaveInterval) {
			clearTimeout(draftAutoSaveInterval);
			draftAutoSaveInterval = null;
		}
		draftAutoSaveInterval = setInterval("$('#reply{$message->id}_part1 button[name=saveDraft]').click();", 30000); // and every 30 sec

		$frm.find('input:text.context-snippet').autocomplete({
			source: DevblocksAppPath+'ajax.php?c=internal&a=autocomplete&context=cerberusweb.contexts.snippet&contexts[]=cerberusweb.contexts.ticket&contexts[]=cerberusweb.contexts.worker',
			minLength: 1,
			focus:function(event, ui) {
				return false;
			},
			autoFocus:true,
			select:function(event, ui) {
				$this = $(this);
				$textarea = $('#reply_{$message->id}');
				
				$label = ui.item.label.replace("<","&lt;").replace(">","&gt;");
				$value = ui.item.value;
				
				// Now we need to read in each snippet as either 'raw' or 'parsed' via Ajax
				url = 'c=internal&a=snippetPaste&id=' + $value;

				// Context-dependent arguments
				if('cerberusweb.contexts.ticket'==ui.item.context) {
					url += "&context_id={$ticket->id}";
				} else if ('cerberusweb.contexts.worker'==ui.item.context) {
					url += "&context_id={$active_worker->id}";
				}

				genericAjaxGet('',url,function(txt) {
					$textarea.insertAtCursor(txt);
				}, { async: false });

				$this.val('');
				return false;
			}
		});

		// Files
		$frm2.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});
		
		// Menu
		$frm2.find('button.send')
			.siblings('ul.cerb-popupmenu')
			.hover(
				function(e) { }, 
				function(e) { $(this).hide(); }
			)
			.find('> li')
			.click(function(e) {
				$(this).closest('ul.cerb-popupmenu').hide();

				e.stopPropagation();
				if(!$(e.target).is('li'))
				return;

				$(this).find('a').trigger('click');
			})
		;

		// Shortcuts
		
		{if $pref_keyboard_shortcuts}
		
		// Reply textbox
		$('#reply_{$message->id}').keypress(function(event) {
			if(!$(this).is(':focus'))
				return;
			
			if(!event.ctrlKey) //!event.altKey && !event.ctrlKey && !event.metaKey
				return;

			if(event.ctrlKey && event.shiftKey) {
				switch(event.which) {
					case 7:  
					case 71: // (G) Insert Signature
						try {
							event.preventDefault();
							$('#btnInsertReplySig{$message->id}').click();
						} catch(ex) { } 
						break;
					case 9:  
					case 73: // (I) Insert Snippet
						try {
							event.preventDefault();
							$('#reply{$message->id}_part1').find('.context-snippet').focus();
						} catch(ex) { } 
						break;
				}
			}
		});
		
		{/if}
	});
</script>
