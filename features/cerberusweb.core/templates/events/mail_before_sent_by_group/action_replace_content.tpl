<b>Replace:</b><br>
<textarea name="{$namePrefix}[replace]" rows="5" cols="45" style="width:100%;">{$params.replace}</textarea>
<br>

<b>With:</b><br>
<textarea name="{$namePrefix}[with]" rows="5" cols="45" style="width:100%;">{$params.with}</textarea>
<br>

<button type="button" onclick="genericAjaxPost($(this).closest('form').attr('id'),$(this).nextAll('div.tester').first(),'c=internal&a=testDecisionEventSnippets&prefix={$namePrefix}&field=with');">{'common.test'|devblocks_translate|capitalize}</button>
<select onchange="$field=$(this).siblings('textarea');$field.focus().insertAtCursor($(this).val());$(this).val('');">
	<option value="">-- insert at cursor --</option>
	{foreach from=$token_labels key=k item=v}
	<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
	{/foreach}
</select>
<div class="tester"></div>
<br>
