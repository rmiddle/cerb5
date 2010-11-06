<select name="display_assigned_to">
	<option {if $next_assigned_to == 0}selected{/if} value="0">{$translate->_('portal.sc.cfg.history.display_assigned_to.hide')|capitalize}</option>
	<option {if $next_assigned_to == 1}selected{/if} value="1">{$translate->_('portal.sc.cfg.history.display_assigned_to.firstname')|capitalize}</option>
	<option {if $next_assigned_to == 2}selected{/if} value="2">{$translate->_('portal.sc.cfg.history.display_assigned_to.fullname')|capitalize}</option>
</select>
<b>{$translate->_('portal.sc.cfg.history.display_assigned_to')|capitalize}</b>
<br>

<table cellpadding="2" cellspacing="1" border="0">
{assign var=last_group_id value=0}
<tr>
	<td colspan="2"><H2>Global Fields</H2></td>
</tr>
//{foreach from=$ticket_fields item=f key=f_id}
{foreach from=$ticket_fields item=field name=fields}
	{assign var=field_group_id value=$field->group_id}
	{if $field_group_id != $last_group_id}
		<tr>
			<td colspan="2"><H2>{$groups.$field_group_id->name} {$translate->_('portal.sc.cfg.history.fields')}</H2></td>
		</tr>
	{/if}
	<tr>
		<td valign="top" width="1%" nowrap="nowrap">
			<input type="hidden" name="fields[]" value="{$field}">
			<select name="fields_visible[]">
				<option value="0">{$translate->_('portal.sc.cfg.history.hidden')|capitalize}</option>
				<option value="1" {if 1==$show_fields.{$field}}selected="selected"{/if}>{$translate->_('portal.sc.cfg.history.read_only')|capitalize}</option>
				<option value="2" {if 2==$show_fields.{$field}}selected="selected"{/if}>{$translate->_('portal.sc.cfg.history.editable')|capitalize}</option>
			</select>
			<b>{$field->name|capitalize}</b>
			<br>
		</td>
	</tr>
	{assign var=last_group_id value=$field->group_id}
{/foreach}

</table>