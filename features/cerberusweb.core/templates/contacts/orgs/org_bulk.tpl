<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="doOrgBulkUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="org_ids" value="{$org_ids}">

<h2>{$translate->_('common.bulk_update.with')|capitalize}:</h2>

<label><input type="radio" name="filter" value="" {if empty($org_ids)}checked{/if}> {$translate->_('common.bulk_update.filter.all')}</label> 
<label><input type="radio" name="filter" value="checks" {if !empty($org_ids)}checked{/if}> {$translate->_('common.bulk_update.filter.checked')}</label> 
<br>
<br>

<H2>{$translate->_('common.bulk_update.do')|capitalize}:</H2>
<table cellspacing="0" cellpadding="2" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right">{$translate->_('contact_org.country')|capitalize}:</td>
		<td width="100%">
			<input type="text" name="country" value="" size="35">
		</td>
	</tr>
</table>

{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=true}	

<br>

{if $active_worker->hasPriv('core.addybook.org.actions.update')}<button type="button" onclick="genericPanel.dialog('close');genericAjaxPost('formBatchUpdate','view{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>{/if}
<br>
</form>

<script type="text/javascript" language="JavaScript1.2">
	genericPanel.one('dialogopen', function(event,ui) {
		genericPanel.dialog('option','title',"{'common.bulk_update'|devblocks_translate|escape:'quotes'}");
	} );
</script>
