<form action="{devblocks_url}{/devblocks_url}" method="post" id="formConfigCommunityTool">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="portal">
<input type="hidden" name="action" value="saveTabSettings">
<input type="hidden" name="portal" value="{$instance->code}">
<input type="hidden" name="do_delete" value="0">

<b>Portal Name:</b> ("Support Portal", "Contact Form", "ProductX FAQ")<br>
<input type="text" name="portal_name" value="{if !empty($instance->name)}{$instance->name}{else}{$instance->manifest->name}{/if}" size="65"><br>
<br> 

{if !empty($instance) && !empty($tool)}
{$tool->configure($instance)}
{/if}

<div>
	<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
	{if !empty($instance)}<button type="button" class="red" onclick="if(confirm('{$translate->_('portal.cfg.confirm_delete')}')){literal}{this.form.do_delete.value='1';this.form.submit();}{/literal}"><span class="cerb-sprite2 sprite-cross-circle"></span> {$translate->_('common.delete')|capitalize}</button>{/if}
</div>

</form>
