
<select name="address_full_name">
	<option {if $address_full_name == 0}selected{/if} value="0">{$translate->_('portal.sc.cfg.history.next_assigned_to.hide')|capitalize}</option>
	<option {if $address_full_name == 1}selected{/if} value="1">{$translate->_('portal.sc.cfg.history.next_assigned_to.firstname')|capitalize}</option>
	<option {if $address_full_name == 2}selected{/if} value="2">{$translate->_('portal.sc.cfg.history.next_assigned_to.fullname')|capitalize}</option>
</select>
<b>{$translate->_('portal.sc.cfg.account.full_name')|capitalize}</b>
<br>

<table cellpadding="2" cellspacing="1" border="0">
<tr>
	<td colspan="2"><H2>Custom Fields</H2></td>
</tr>
{foreach from=$address_fields item=a key=a_id}
	<tr>
		<td valign="top" width="1%" nowrap="nowrap">
			<select name="cf_address_select_{$a_id}">
				<option {if $cf_address_select.$a_id == 0}selected{/if} value="0">{$translate->_('portal.sc.cfg.history.hidden')|capitalize}</option>
				<option {if $cf_address_select.$a_id == 1}selected{/if} value="1">{$translate->_('portal.sc.cfg.history.read_only')|capitalize}</option>
				<option {if $cf_address_select.$a_id == 2}selected{/if} value="2">{$translate->_('portal.sc.cfg.history.read_write')|capitalize}</option>
			</select>
			<b>{$a->name}</b>
			<br>
		</td>
	</tr>
{/foreach}
</table>
