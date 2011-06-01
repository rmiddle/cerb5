<b>Relay to:</b><br>
<ul style="margin:0px 0px 10px 15px;padding:0;list-style:none;max-height:150px;overflow:auto;">
{foreach from=$addresses item=address key=address_key}
<li>
	<label>
	<input type="checkbox" name="{$namePrefix}[to][]" value="{$address_key}" {if in_array($address_key,$params.to)}checked="checked"{/if}>
	{$address->address} ({$workers.{$address->worker_id}->getName()})
	</label>
</li>
{/foreach}
</ul>

<b>{'message.header.subject'|devblocks_translate|capitalize}:</b> (use {literal}{{ticket_subject}}{/literal} for default)<br>
<input type="text" name="{$namePrefix}[subject]" value="{if empty($params.subject)}{literal}{{ticket_subject}}{/literal}{else}{$params.subject}{/if}" size="45" style="width:100%;"><br>
<br>

<b>{'common.content'|devblocks_translate|capitalize}:</b><br>
<textarea name="{$namePrefix}[content]" rows="10" cols="45" style="width:100%;">{if isset($params.content)}{$params.content}{else}{$default_content}{/if}</textarea>

<button type="button" onclick="genericAjaxPost($(this).closest('form').attr('id'),$(this).nextAll('div.tester').first(),'c=internal&a=testDecisionEventSnippets&prefix={$namePrefix}&field[]=subject&field[]=content');">{'common.test'|devblocks_translate|capitalize}</button>
<select onchange="$field=$(this).siblings('textarea');$field.focus().insertAtCursor($(this).val());$(this).val('');">
	<option value="">-- insert at cursor --</option>
	{foreach from=$token_labels key=k item=v}
	<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
	{/foreach}
</select>
<div>
Lines that begin with <code>##</code> will be ignored on reply.
</div>
<div class="tester"></div>
<br>
