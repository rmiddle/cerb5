<form action="{devblocks_url}{/devblocks_url}" method="post" name="replyTemplateEditForm" id="replyTemplateEditForm">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveReplyTemplate">
<input type="hidden" name="id" value="{$template->id}">
<input type="hidden" name="type" value="{$type}">
<input type="hidden" name="do_delete" value="0">

<b>Title:</b><br>
<input type="text" name="title" size="35" value="{$template->title|escape}" style="width:100%;"><br>

<b>Description:</b><br>
<input type="text" name="description" size="35" value="{$template->description|escape}" style="width:100%;"><br>

<b>Folder:</b><br>
<select name="folder" onchange="toggleDiv('replyTemplateFolderNew',(selectValue(this)==''?'inline':'none'));">
	{foreach from=$folders item=folder}
	<option value="{$folder|escape}" {if $template->folder==$folder}selected{/if}>{$folder}</option>
	{/foreach}
	<option value="">-- new folder: --</option>
</select>
<span id="replyTemplateFolderNew" style="display:{if empty($folders)}inline{else}none{/if};">
<b>Folder Name:</b> 
<input type="text" name="folder_new" value="" size="24" maxlength="64">
</span>
<br>

<b>Text:</b><br>
<textarea name="template" rows="10" cols="45" style="width:100%;">{$template->content}</textarea><br>

<b>Insert Placeholder:</b> <select name="token" onchange="insertAtCursor(this.form.template,selectValue(this.form.token));this.form.token.selectedIndex=0;this.form.template.focus();">
	<option value="">-- choose --</option>
	<optgroup label="General">
		<option value="#timestamp#">Current Time</option>
	</optgroup>
	{if 2==$type}
		<optgroup label="Sender">
			<option value="#sender_first_name#">First Name</option>
			<option value="#sender_last_name#">Last Name</option>
			<option value="#sender_org#">Organization</option>
		</optgroup>
		<optgroup label="Ticket">
			<option value="#ticket_id#">Internal ID</option>
			<option value="#ticket_mask#">Reference ID (Mask)</option>
			<option value="#ticket_subject#">Subject</option>
		</optgroup>
	{/if}
	<optgroup label="Worker">
		<option value="#worker_first_name#">First Name</option>
		<option value="#worker_last_name#">Last Name</option>
		<option value="#worker_title#">Title</option>
	</optgroup>
</select>
<br>
<br>

<button type="button" onclick="genericPanel.dialog('close');genericAjaxPost('replyTemplateEditForm', '', 'c=display&a=saveReplyTemplate');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
{if $template->id}
<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this template?')) { this.form.do_delete.value='1'; genericPanel.dialog('close'); genericAjaxPost('replyTemplateEditForm', '', 'c=display&a=saveReplyTemplate'); } "><span class="cerb-sprite sprite-forbidden"></span> {$translate->_('common.delete')|capitalize}</button>
{/if}
<button type="button" onclick="genericPanel.dialog('close');"><span class="cerb-sprite sprite-delete"></span> {$translate->_('common.cancel')|capitalize}</button>

</form>

<script type="text/javascript" language="JavaScript1.2">
	genericPanel.one('dialogopen', function(event,ui) {
		genericPanel.dialog('option','title',"Edit E-mail Template");
	} );
</script>
