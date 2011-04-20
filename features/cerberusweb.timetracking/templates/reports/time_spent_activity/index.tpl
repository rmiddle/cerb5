<ul class="submenu">
</ul>
<div style="clear:both;"></div>

<div class="block">
<h2>{$translate->_('timetracking.ui.reports.time_spent_activity')}</h2>

<form action="{devblocks_url}c=reports&report=report.timetracking.timespentactivity{/devblocks_url}" method="POST" id="frmRange" name="frmRange">
<input type="hidden" name="c" value="reports">
<b>{$translate->_('timetracking.ui.reports.from')}</b> <input type="text" name="start" id="start" size="24" value="{$start}"><button type="button" onclick="devblocksAjaxDateChooser('#start','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
<b>{$translate->_('timetracking.ui.reports.to')}</b> <input type="text" name="end" id="end" size="24" value="{$end}"><button type="button" onclick="devblocksAjaxDateChooser('#end','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
<b>Grouping:</b> <select name="report_date_grouping">
	<option value="">-auto-</option>
	<option value="year" {if 'year'==$report_date_grouping}selected="selected"{/if}>Years</option>
	<option value="month" {if 'month'==$report_date_grouping}selected="selected"{/if}>Months</option>
	<option value="day" {if 'day'==$report_date_grouping}selected="selected"{/if}>Days</option>
</select>
<div id="divCal"></div>

<b>{$translate->_('timetracking.ui.reports.past')}</b> <a href="javascript:;" onclick="$('#start').val('-1 year');$('#end').val('now');">{$translate->_('timetracking.ui.reports.filters.1_year')|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-6 months');$('#end').val('now');">{'timetracking.ui.reports.filters.n_months'|devblocks_translate:6}</a>
| <a href="javascript:;" onclick="$('#start').val('-3 months');$('#end').val('now');">{'timetracking.ui.reports.filters.n_months'|devblocks_translate:3}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 month');$('#end').val('now');">{$translate->_('timetracking.ui.reports.filters.1_month')|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 week');$('#end').val('now');">{$translate->_('timetracking.ui.reports.filters.1_week')|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 day');$('#end').val('now');">{$translate->_('timetracking.ui.reports.filters.1_day')|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('today');$('#end').val('now');">{$translate->_('common.today')|lower}</a>
<br>

<b>{$translate->_('reports.ui.filters.worker')}</b> 
<button type="button" class="chooser_worker"><span class="cerb-sprite sprite-view"></span></button>
<ul class="chooser-container bubbles">
{if is_array($filter_worker_ids) && !empty($filter_worker_ids)}
	{foreach from=$filter_worker_ids item=filter_worker_id}
	{$filter_worker = $workers.{$filter_worker_id}}
	{if !empty($filter_worker)}
	<li>{$filter_worker->getName()}<input type="hidden" name="worker_id[]" value="{$filter_worker->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
	{/if}
	{/foreach}
{/if}
</ul>
<br>
<br>

<button type="submit" id="btnSubmit">{$translate->_('reports.common.run_report')|capitalize}</button>
</form>
</div>

<!-- Chart -->

{if !empty($data)}

<!--[if IE]><script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/excanvas.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script><![endif]-->
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/jquery.jqplot.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.barRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.canvasTextRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<link rel="stylesheet" type="text/css" href="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=css/jqplot/jquery.jqplot.min.css{/devblocks_url}?v={$smarty.const.APP_BUILD}" />

<div id="reportLegend" style="margin:5px;"></div>
<div id="reportChart" style="width:98%;height:350px;"></div>

<script type="text/javascript">
{foreach from=$data item=plots key=activity_id}
line{$activity_id} = [{foreach from=$plots key=plot item=freq name=plots}
{$freq}{if !$smarty.foreach.plots.last},{/if}
{/foreach}
];
{/foreach}

chartData = [
{foreach from=$data item=null key=activity_id name=activities}line{$activity_id}{if !$smarty.foreach.activities.last},{/if}{/foreach}
];

var cerbChartStyle = {
	seriesColors: [
		'rgba(115,168,0,0.8)',
		'rgba(207,218,30,0.8)',
		'rgba(249,190,49,0.8)',
		'rgba(244,89,9,0.8)',
		'rgba(238,24,49,0.8)',
		'rgba(189,19,79,0.8)',
		'rgba(50,37,238,0.8)',
		'rgba(87,109,243,0.8)',
		'rgba(116,87,229,0.8)',
		'rgba(143,46,137,0.8)',
		'rgba(241,124,242,0.8)',
		'rgba(180,117,198,0.8)',
		'rgba(196,191,210,0.8)',
		'rgba(18,134,49,0.8)',
		'rgba(44,187,105,0.8)',
		'rgba(184,197,146,0.8)',
		'rgba(46,124,180,0.8)',
		'rgba(84,189,199,0.8)',
		'rgba(24,200,252,0.8)',
		'rgba(254,194,153,0.8)',
		'rgba(213,153,160,0.8)',
		'rgba(244,237,86,0.8)',
		'rgba(204,137,59,0.8)',
		'rgba(157,88,44,0.8)',
		'rgba(108,46,45,0.8)'
	]
};

chartOptions = {
    stackSeries: true,
	legend:{ 
		show:false
	},
	title:{
		show: false 
	},
	grid:{
		shadow: false,
		background:'rgb(255,255,255)',
		borderWidth:0
	},
	seriesColors: cerbChartStyle.seriesColors,	
    seriesDefaults:{
		renderer:$.jqplot.BarRenderer,
        rendererOptions:{ 
			highlightMouseOver: true,
			barPadding:0,
			barMargin:0
		},
		shadow: false,
		fill:true,
		fillAndStroke:false,
		showLine:true,
		showMarker:false,
		markerOptions: {
			size:8,
			style:'filledCircle',
			shadow:false
		}
	},
    series:[
		{foreach from=$data key=activity_id item=null name=activities}{ label:'{$activities.$activity_id}' }{if !$smarty.foreach.activities.last},{/if}{/foreach}
    ],
    axes:{
        xaxis:{
		  renderer:$.jqplot.CategoryAxisRenderer,
	      tickRenderer: $.jqplot.CanvasAxisTickRenderer,
	      tickOptions: {
		  	{if count($xaxis_ticks) > 94}show:false,{/if}
		  	showGridline:false,
	        {if count($xaxis_ticks) < 94 && count($xaxis_ticks) > 13}
			angle: 90,
			{/if}
	        fontSize: '8pt'
	      },
		  ticks:['{implode("','",$xaxis_ticks) nofilter}']
		}, 
        yaxis:{
		  labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
		  label:'(mins)',
		  min:0,
		  autoscale:true,
		  tickRenderer: $.jqplot.CanvasAxisTickRenderer,
		  tickOptions:{
		  	formatString:'%d',
			fontSize: '8pt'
		  }
		}
    }
};

$('#reportChart').bind('jqplotPostDraw',function(event, plot) {
	$legend = $('#reportLegend');
	$legend.html('');
	len = plot.series.length;
	for(series in plot.series) {
		if(navigator.appName == 'Microsoft Internet Explorer') {
			$cell = $('<span style="margin-right:5px;display:inline-block;"><span style="background-color:'+plot.series[series].color.replace('rgba','rgb').replace(',0.8','')+';display:inline-block;padding:0px;margin:2px;width:16px;height:16px;">&nbsp;</span>'+plot.series[series].label+'</span>');
		} else {
			$cell = $('<span style="margin-right:5px;display:inline-block;"><span style="background-color:'+plot.series[series].color+';display:inline-block;padding:0px;margin:2px;width:16px;height:16px;">&nbsp;</span>'+plot.series[series].label+'</span>');
		}
		$legend.append($cell);
	}
});

var reportTooltip = $('#reportChart').qtip({
	show: {
		when:{ event:'jqplotDataHighlight' }
	},
	hide:{
		when:{ event:'jqplotDataUnhighlight' }
	},
	style:{
		name:'cream',
		tip:{ corner:'bottomLeft' },
		border:{
			radius:3,
			width:5
		}
	},
	position:{
		target:'mouse',
		corner:{
			tooltip:'bottomLeft',
			target:'topMiddle'
		},
		adjust:{
			x:-5
		}
	}
});

$('#reportChart').bind('jqplotDataHighlight',function(event, seriesIndex, pointIndex, data) {
	tooltip = $('#reportChart').qtip("api");
	
	str = "";
	
	if(!plot1 || !plot1.series || !plot1.series[seriesIndex])
		return;
	
	if(plot1.series[seriesIndex].label)
		str += plot1.series[seriesIndex].label;
	
	if(plot1.axes.xaxis.ticks[pointIndex])
		str += "<br>(" + plot1.axes.xaxis.ticks[pointIndex] + ")";
	
	tooltip.updateContent(str, true);
});

plot1 = $.jqplot('reportChart', chartData, chartOptions);	
</script>

{include file="devblocks:cerberusweb.reports::reports/_shared/chart_selector.tpl"}
{/if}

<br>

<!-- Table -->

{if $invalidDate}
	<div><font color="red"><b>{$translate->_('reports.ui.invalid_date')}</b></font></div>
{/if}

{if !empty($view)}
	<div id="view{$view->id}">{$view->render()}</div>
	<br>
{/if}

{if !empty($data)}
	{foreach from=$data key=activity_id item=plots name=activities}
		<div class="block" style="display:inline-block;">
		{$sum = 0}
		<h2>{$activities.{$activity_id}->name}</h2>
		{foreach from=$plots key=plot item=data name=plots}
			{$plot}: {$data}<br>
			{$sum = $sum + $data}
		{/foreach}
		<b>Sum: {$sum}</b><br>
		<b>Mean: {($sum/count($plots))|string_format:"%0.2f"}</b><br>
		</div>
	{/foreach}
{else}
	<div><b>No data.</b></div>
{/if}

<script type="text/javascript">
	$('#frmRange button.chooser_worker').each(function(event) {
		ajax.chooser(this,'cerberusweb.contexts.worker','worker_id', { autocomplete:true });
	});
</script>

<br>