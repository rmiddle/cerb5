{counter assign=num_groups_displayed name=num_groups_displayed start=0}
{if !empty($report_groups)}
	{foreach from=$report_groups item=report_group key=group_extid}
		{assign var=report_group_mft value=$report_group.manifest}
		
		{if !isset($report_group_mft->params.acl) || $active_worker->hasPriv($report_group_mft->params.acl)}
		{counter name=num_groups_displayed print=false}
		<fieldset class="peek">
			<legend>{$translate->_($report_group_mft->params.group_name)}</legend>
			
			{if !empty($report_group.reports)}
				<ul style="margin:0px;">
				{foreach from=$report_group.reports item=reportMft}
					<li><a href="{devblocks_url}c=pages&page={$page->id}-{$page->name|devblocks_permalink}&report={$reportMft->id}{/devblocks_url}">{$translate->_($reportMft->params.report_name)}</a></li>
				{/foreach}
				</ul>
			{/if}
		</fieldset>
		{/if}
	{/foreach}
{/if}

{if empty($num_groups_displayed)}
	<fieldset class="peek">
		<legend>No Report Groups</legend>
		You do not have access to any report groups.
	</fieldset>
{/if}

