<b>{'message.header.subject'|devblocks_translate|capitalize}:</b>
<input type="text" name="{$namePrefix}[subject]" value="{$params.subject}" size="45" style="width:100%;"><br>
<br>

<b>{'common.content'|devblocks_translate|capitalize}:</b><br>
<textarea name="{$namePrefix}[content]" rows="10" cols="45" style="width:100%;">{$params.content}</textarea>
<br>

{*<button type="button" onclick="genericAjaxPost('formSnippetsPeek','peekTemplateTest','c=internal&a=snippetTest&snippet_context={$snippet->context}&snippet_field=content');"><span class="cerb-sprite sprite-gear"></span> Test</button>*}
<select onchange="$field=$(this).siblings('textarea');$field.focus().insertAtCursor($(this).val());$(this).val('');">
	<option value="">-- insert at cursor --</option>
	{foreach from=$token_labels key=k item=v}
	<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
	{/foreach}
</select>
<br>
