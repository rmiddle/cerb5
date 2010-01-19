<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/mail_write.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>Compose</h1></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formComposePeek" name="formComposePeek" onsubmit="return false;">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveComposePeek">
<input type="hidden" name="view_id" value="{$view_id}">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right">From:</td>
		<td width="100%">
			<select name="team_id" style="border:1px solid rgb(180,180,180);padding:2px;">
				{foreach from=$active_worker_memberships item=membership key=group_id}
				<option value="{$group_id}" {if $default_group_id==$group_id}selected="selected"{/if}>{$teams.$group_id->name}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right">To: </td>
		<td width="100%">
			<div id="emailautocomplete" style="width:98%;" class="yui-ac">
				<input type="text" name="to" id="emailinput" value="{$to}" style="border:1px solid rgb(180,180,180);padding:2px;" class="yui-ac-input">
				<div id="emailcontainer" class="yui-ac-container"></div>
				<br>
				<br>
			</div>			
		</td>
	</tr>
	{*
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Cc: </td>
		<td width="100%">
			<input type="text" name="cc" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="" autocomplete="off">
		</td>
	</tr>
	*}
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Subject: </td>
		<td width="100%">
			<input type="text" name="subject" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$default_subject|escape}" autocomplete="off">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">Body: </td>
		<td width="100%">
			<textarea id="divComposeContent" name="content" style="width:98%;height:150px;border:1px solid rgb(180,180,180);padding:2px;"></textarea><br>
			<button type="button" onclick="genericAjaxGet('','c=tickets&a=getComposeSignature&group_id='+selectValue(this.form.team_id),{literal}function(o){insertAtCursor(document.getElementById('divComposeContent'),o.responseText);}{/literal});"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_edit.gif{/devblocks_url}" align="top"> Insert Signature</button>
			{*<button type="button" onclick="genericAjaxPanel('c=display&a=showTemplatesPanel&type=1&reply_id=0&txt_name=content',this,false,'550px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/text_rich.gif{/devblocks_url}" align="top"> E-mail Templates</button>*}
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<b>Next:</b> 
			<label><input type="radio" name="closed" value="0" {if 0==$default_closed}checked="checked"{/if}>Open</label>
			<label><input type="radio" name="closed" value="2" {if 2==$default_closed}checked="checked"{/if}>Waiting for reply</label>
			{if $active_worker->hasPriv('core.ticket.actions.close')}<label><input type="radio" name="closed" value="1" {if 1==$default_closed}checked="checked"{/if}>Closed</label>{/if}
			<br>
			<br>

			<b>Who should handle the follow-up?</b><br>
	      	<select name="next_worker_id">
	      		<option value="0" {if 0==$default_next_worker_id}selected="selected"{/if}>Anybody
	      		{foreach from=$workers item=worker key=worker_id name=workers}
					{if $worker_id==$active_worker->id || $active_worker->hasPriv('core.ticket.actions.assign')}
		      			{if $worker_id==$active_worker->id}{assign var=next_worker_id_sel value=$smarty.foreach.workers.iteration}{/if}
		      			<option value="{$worker_id}" {if $worker_id==$default_next_worker_id}selected="selected"{/if}>{$worker->getName()}
					{/if}
	      		{/foreach}
	      	</select>&nbsp;
	      	{if $active_worker->hasPriv('core.ticket.actions.assign') && !empty($next_worker_id_sel)}
	      		<button type="button" onclick="this.form.next_worker_id.selectedIndex = {$next_worker_id_sel};">me</button>
	      		<button type="button" onclick="this.form.next_worker_id.selectedIndex = 0;">anybody</button>
	      	{/if}
	      	<br>
	      	<br>			
		</td>
	</tr>
</table>

<button type="button" onclick="genericPanel.hide();genericAjaxPost('formComposePeek', 'view{$view_id}')"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
<br>
</form>
