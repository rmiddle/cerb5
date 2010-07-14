<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div>

<h2>{$translate->_('timetracking.ui.reports.time_spent_worker')}</h2>

<form action="{devblocks_url}c=reports&report=report.timetracking.timespentworker{/devblocks_url}" method="POST" id="frmRange" name="frmRange">
<input type="hidden" name="c" value="reports">
{$translate->_('timetracking.ui.reports.from')} <input type="text" name="start" id="start" size="24" value="{$start}"><button type="button" onclick="devblocksAjaxDateChooser('#start','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
{$translate->_('timetracking.ui.reports.to')} <input type="text" name="end" id="end" size="24" value="{$end}"><button type="button" onclick="devblocksAjaxDateChooser('#end','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
<button type="submit" id="btnSubmit">{$translate->_('common.refresh')|capitalize}</button>
<div id="divCal"></div>

{$translate->_('timetracking.ui.reports.past')} <a href="javascript:;" onclick="document.getElementById('start').value='-1 year';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('timetracking.ui.reports.filters.1_year')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-6 months';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{'timetracking.ui.reports.filters.n_months'|devblocks_translate:6}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-3 months';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{'timetracking.ui.reports.filters.n_months'|devblocks_translate:3}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 month';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('timetracking.ui.reports.filters.1_month')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 week';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('timetracking.ui.reports.filters.1_week')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 day';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('timetracking.ui.reports.filters.1_day')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='today';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('common.today')|lower}</a>
<br>
<br>
{$translate->_('timetracking.ui.worker')} <select name="worker_id" onchange="document.getElementById('btnSubmit').click();">
	<option value="0" {if empty($sel_worker_id)}selected="selected"{/if}>{$translate->_('timetracking.ui.reports.time_spent_org.all_workers')}</option>
{foreach from=$workers item=worker key=worker_id name=workers}
	<option value="{$worker_id}" {if $sel_worker_id==$worker_id}selected="selected"{/if}>{$worker->getName()}</option>
{/foreach}
</select>
</form>

<!-- Chart -->

{if !empty($data)}
<div id="placeholder" style="margin:1em;width:650px;height:{20+(32*count($data))}px;"></div>

<script type="text/javascript">
	$(function() {
		var d = [
			{foreach from=$data item=row key=iter name=iters}
			[{$row.mins}, {$iter}]{if !$smarty.foreach.iters.last},{/if}
			{/foreach}
		];
		
		var options = {
			lines: { show: false, fill: false },
			bars: { show: true, fill: true, horizontal: true, align: "center", barWidth: 1 },
			points: { show: false, fill: false },
			grid: {
				borderWidth: 0,
				horizontalLines: false,
				hoverable: false
			},
			xaxis: {
				min: 0,
				minTickSize: 1,
				tickFormatter: function(val, axis) {
					return Math.floor(val).toString();
				}
			},
			yaxis: {
				ticks: [
					{foreach from=$data item=row key=iter name=iters}
					[{$iter},"<b>{$row.value|escape}</b>"]{if !$smarty.foreach.iters.last},{/if}
					{/foreach}
				]
			}
		} ;
		
		$.plot($("#placeholder"), [d], options);
	} );
</script>
{/if}

<!-- Table -->

{if $invalidDate}<div><font color="red"><b>{$translate->_('timetracking.ui.reports.invalid_date')}</b></font></div>{/if}

{if !empty($time_entries)}
	{foreach from=$time_entries item=worker_entry key=worker_id}
	{assign var=worker_name value=$workers.$worker_id->getName()}

		<div class="block">
		<table cellspacing="0" cellpadding="3" border="0">
			<tr>
				<td colspan="2">
				<h2>
				{if !empty($worker_name)}
				{$worker_name}
				{/if}
				</h2>
				<span style="margin-bottom:10px;"><b>{$worker_entry.total_mins} {$translate->_('common.minutes')|lower}</b></span>
				</td>
			</tr>	
		
			{foreach from=$worker_entry.entries item=time_entry key=time_entry_id}
				{if is_numeric($time_entry_id)}
					{assign var=generic_worker value='timetracking.ui.generic_worker'|devblocks_translate}
					
					{if isset($worker_name)}
						{assign var=worker_name value=$worker_name}
					{else}
						{assign var=worker_name value=$generic_worker}
					{/if}
					<tr>
						<td>{$time_entry.log_date|date_format:"%Y-%m-%d"}</td>
						<td>
							{assign var=tagged_worker_name value="<B>"|cat:$worker_name|cat:"</B>"}
							{assign var=tagged_mins value="<B>"|cat:$time_entry.mins|cat:"</B>"}
							{assign var=tagged_activity value="<B>"|cat:$time_entry.activity_name|cat:"</B>"}
														
							{if !empty($time_entry.activity_name)}
								{'timetracking.ui.tracked_desc'|devblocks_translate:$tagged_worker_name:$tagged_mins:$tagged_activity}
							{else}
								{'%s tracked %s mins'|devblocks_translate:$tagged_worker_name:$tagged_mins}
							{/if}					
						
							<a href="javascript:;" onclick="genericAjaxPopup('peek','c=timetracking&a=showEntry&id={$time_entry.id}',null,false,'500');"><span class="ui-icon ui-icon-newwin" style="display:inline-block;vertical-align:middle;" title="{$translate->_('views.peek')}"></span></a>
						</td>
					</tr>
				{/if}
			{/foreach}
		</table>
		</div>
		<br/>
	{/foreach}
{/if}

