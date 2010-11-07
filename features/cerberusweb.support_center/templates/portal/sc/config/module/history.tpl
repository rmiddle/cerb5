<table cellpadding="2" cellspacing="1" border="0">
<tr>
	<td colspan="2"><H2>Ticket History Misc</H2></td>
</tr>

<tr>
	<td>
		<select name="display_assigned_to">
			<option {if $display_assigned_to == 0}selected{/if} value="0">{$translate->_('portal.sc.cfg.history.display_assigned_to.hide')|capitalize}</option>
			<option {if $display_assigned_to == 1}selected{/if} value="1">{$translate->_('portal.sc.cfg.history.display_assigned_to.firstname')|capitalize}</option>
			<option {if $display_assigned_to == 2}selected{/if} value="2">{$translate->_('portal.sc.cfg.history.display_assigned_to.fullname')|capitalize}</option>
		</select>
	</td>
	<td>
		<b>{$translate->_('portal.sc.cfg.history.display_assigned_to')|capitalize}</b>
	</td>
</tr>
</table>
<br>

{if !empty($ticket_custom_fields)}
{foreach from=$ticket_custom_fields item=field key=field_id}
	{$ticket_fields[] = 'ticket_custom_'|cat:$field_id}
	{$ticket_labels[] = ''|cat:$field->name|cat:' ('|cat:$field_types.{$field->type}|cat:')'}
{/foreach}
{/if}

<table cellpadding="2" cellspacing="1" border="0">
<tr>
	<td colspan="2"><H2>Ticket Fields</H2></td>
</tr>

{foreach from=$ticket_fields item=field name=fields}
	<tr>
		<td>
{*			<input type="hidden" name="fields[]" value="{$field}">
*}			<select name="fields_visible[]">
				<option value="0">{$translate->_('portal.sc.cfg.history.hidden')|capitalize}</option>
				<option value="1" {if 1==$show_fields.{$field}}selected="selected"{/if}>{$translate->_('portal.sc.cfg.history.read_only')|capitalize}</option>
				<option value="2" {if 2==$show_fields.{$field}}selected="selected"{/if}>{$translate->_('portal.sc.cfg.history.editable')|capitalize}</option>
			</select>
			<b>{'$field->name|cat:' ('|cat:$field_types.{$field->type}|cat:')'|capitalize}</b>
{*			{if $field->group_id != 0}
				({$groups.$field->group_id->name} {$translate->_('portal.sc.cfg.history.fields')})
			{/if}
*}
			<br>
		</td>
	</tr>
{/foreach}


</table>
<br>