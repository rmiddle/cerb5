<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmStorageSchemaPeek" name="frmStorageSchemaPeek" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveStorageSchemaPeek">
<input type="hidden" name="ext_id" value="{$schema->manifest->id}">

{$schema->renderConfig()}

<button type="button" onclick="genericAjaxPost('frmStorageSchemaPeek','schema_{$schema->manifest->id|md5}');genericPanel.dialog('close');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>

</form>

<script type="text/javascript" language="JavaScript1.2">
	genericPanel.one('dialogopen', function(event,ui) {
		genericPanel.dialog('option','title',"{$schema->manifest->name|escape}");
	} );
</script>
