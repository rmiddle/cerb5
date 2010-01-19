<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmOppFields">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="saveOppProperties">
<input type="hidden" name="opp_id" value="{$opp->id}">

<blockquote style="margin:10px;">

	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{$translate->_('common.email')}: </td>
			<td width="100%">
				<div id="emailautocomplete" style="width:98%;" class="yui-ac">
					<input type="text" name="email" id="emailinput" value="{$address->email|escape}" style="border:1px solid rgb(180,180,180);padding:2px;" class="yui-ac-input">
					<div id="emailcontainer" class="yui-ac-container"></div>
					<br>
					<br>
				</div>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{$translate->_('crm.opportunity.name')|capitalize}: </td>
			<td width="100%"><input type="text" name="name" value="{$opp->name|escape}" style="width:98%;"></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('common.status')|capitalize}: </td>
			<td width="100%">
				<label><input type="radio" name="status" value="0" onclick="toggleDiv('oppPeekClosedDate','none');" {if empty($opp->id) || 0==$opp->is_closed}checked="checked"{/if}> {'crm.opp.status.open'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="status" value="1" onclick="toggleDiv('oppPeekClosedDate','');" {if $opp->is_closed && $opp->is_won}checked="checked"{/if}> {'crm.opp.status.closed.won'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="status" value="2" onclick="toggleDiv('oppPeekClosedDate','');" {if $opp->is_closed && !$opp->is_won}checked="checked"{/if}> {'crm.opp.status.closed.lost'|devblocks_translate|capitalize}</label>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('crm.opportunity.amount')|capitalize}: </td>
			<td width="100%">
				<input type="text" name="amount" size="10" maxlength="12" style="border:1px solid rgb(180,180,180);padding:2px;" value="{if empty($opp->amount)}0{else}{math equation="floor(x)" x=$opp->amount}{/if}" autocomplete="off">
				 . 
				<input type="text" name="amount_cents" size="3" maxlength="2" style="border:1px solid rgb(180,180,180);padding:2px;" value="{if empty($opp->amount)}00{else}{math equation="(x-floor(x))*100" x=$opp->amount}{/if}" autocomplete="off">
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('crm.opportunity.worker_id')|capitalize}:</td>
			<td width="100%"><select name="worker_id">
				<option value="0">- {'common.anybody'|devblocks_translate|lower} -</option>
				{foreach from=$workers item=worker key=worker_id name=workers}
					{if $worker_id==$active_worker->id}{math assign=me_worker_id equation="x" x=$smarty.foreach.workers.iteration}{/if}
					<option value="{$worker_id}" {if $opp->worker_id==$worker_id}selected{/if}>{$worker->getName()}</option>
				{/foreach}
			</select>
	      	{if !empty($me_worker_id)}
	      		<button type="button" onclick="this.form.worker_id.selectedIndex = {$me_worker_id};">{$translate->_('common.me')}</button>
	      	{/if}
      		<button type="button" onclick="this.form.worker_id.selectedIndex = 0;">{$translate->_('common.anybody')}</button>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('crm.opportunity.created_date')|capitalize}: </td>
			<td width="100%">
				<input type="text" name="created_date" size=35 value="{if !empty($opp->created_date)}{$opp->created_date|devblocks_date}{else}now{/if}"><button type="button" onclick="ajax.getDateChooser('dateOppCreated',this.form.created_date);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
				<div id="dateOppCreated" style="display:none;position:absolute;z-index:1;"></div>
			</td>
		</tr>
		<tr id="oppPeekClosedDate" {if !$opp->is_closed}style="display:none;"{/if}>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('crm.opportunity.closed_date')|capitalize}: </td>
			<td width="100%">
				<input type="text" name="closed_date" size="35" value="{if !empty($opp->closed_date)}{$opp->closed_date|devblocks_date}{/if}"><button type="button" onclick="ajax.getDateChooser('dateOppClosed',this.form.closed_date);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
				<div id="dateOppClosed" style="display:none;position:absolute;z-index:1;"></div>
			</td>
		</tr>
	</table>

	{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=false}
	<br>
	
{if ($active_worker->hasPriv('crm.opp.actions.create') && $active_worker->id==$opp->worker_id)
	|| ($active_worker->hasPriv('crm.opp.actions.update_nobody') && empty($opp->worker_id)) 
	|| $active_worker->hasPriv('crm.opp.actions.update_all')}
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
{else}
	<div class="error">You do not have permission to modify this record.</div>
{/if}
</blockquote>

</form>