
<select name="next_assigned_to">
	<option {if $next_assigned_to == 0}selected{/if} value="0">{$translate->_('portal.sc.cfg.history.next_assigned_to.hide')|capitalize}</option>
	<option {if $next_assigned_to == 1}selected{/if} value="1">{$translate->_('portal.sc.cfg.history.next_assigned_to.firstname')|capitalize}</option>
	<option {if $next_assigned_to == 2}selected{/if} value="2">{$translate->_('portal.sc.cfg.history.next_assigned_to.fullname')|capitalize}</option>
</select>
<b>{$translate->_('portal.sc.cfg.history.next_assigned_to')|capitalize}</b>
<br>

<table cellpadding="2" cellspacing="1" border="0">
{assign var=last_group_id value=0}
<tr>
	<td colspan="2"><H2>Global Fields</H2></td>
</tr>
{foreach from=$ticket_fields item=f key=f_id}
	{assign var=field_group_id value=$f->group_id}
	{if $field_group_id != $last_group_id}
		<tr>
			<td colspan="2"><H2>{$groups.$field_group_id->name} {$translate->_('portal.sc.cfg.history.fields')}</H2></td>
		</tr>
	{/if}
	<tr>
		<td valign="top" width="1%" nowrap="nowrap">
			<select name="cf_select_{$f_id}">
				<option {if $cf_select.$f_id == 0}selected{/if} value="0">{$translate->_('portal.sc.cfg.history.hidden')|capitalize}</option>
				<option {if $cf_select.$f_id == 1}selected{/if} value="1">{$translate->_('portal.sc.cfg.history.read_only')|capitalize}</option>
				<option {if $cf_select.$f_id == 2}selected{/if} value="2">{$translate->_('portal.sc.cfg.history.read_write')|capitalize}</option>
			</select>
			<b>{$f->name}</b>
			<br>
		</td>
	</tr>
	{assign var=last_group_id value=$f->group_id}
{/foreach}
</table>
