<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		
		<td width="0%" nowrap="nowrap" valign="top">
		
			<div class="block">
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td><h2>Sources</h2></td>
				</tr>
				<tr>
					<td nowrap="nowrap">
						{if !empty($context_manifests)}
							{foreach from=$context_manifests item=manifest key=manifest_id}
								&#187; <a href="javascript:;" onclick="genericAjaxGet('frmConfigFieldSource','c=config&a=getFieldSource&ext_id={$manifest_id}');">{$manifest->name}</a><br>
							{/foreach}
						{/if}
					</td>
				</tr>
			</table>
			</div>
			
		</td>
		
		<td width="100%" valign="top">
			<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmConfigFieldSource" onsubmit="return false;">
				{if !empty($ext_id)}
					{assign var=context_manifest value=$context_manifests.$ext_id}
					{include file="devblocks:cerberusweb.core::configuration/tabs/fields/edit_source.tpl" object=$context_manifest}
				{/if}
			</form>
		</td>
		
	</tr>
</table>


