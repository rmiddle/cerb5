<fieldset>
<legend style="{if $trigger->is_disabled}color:rgb(150,150,150);{/if}">{$trigger->title} {if $trigger->is_disabled}({'common.disabled'|devblocks_translate|capitalize}){/if}</legend>

{* [TODO] Use cache!! *}
{$tree_data = $trigger->getDecisionTreeData()}
{$tree_nodes = $tree_data.nodes}
{$tree_hier = $tree_data.tree}
{$tree_depths = $tree_data.depths}

<div class="node trigger">
	<input type="hidden" name="node_id" value="0">
	<div class="badge badge-lightgray">
		<a href="javascript:;" onclick="decisionNodeMenu(this,'0','{$trigger->id}');" style="font-weight:bold;color:rgb(0,0,0);text-decoration:none;">
			{$event->name} &#x25be;
		</a>
	</div>
	<div class="branch trigger" style="margin-left:10px;">
		{foreach from=$tree_hier[0] item=child_id}
			{include file="devblocks:cerberusweb.core::internal/decisions/branch.tpl" node_id=$child_id trigger_id=$trigger->id data=$tree_data nodes=$tree_nodes tree=$tree_hier depths=$tree_depths}
		{/foreach}
	</div>
</div>
</fieldset>

<script type="text/javascript">
$('#decisionTree{$trigger->id} DIV.node').draggable({
	revert:"invalid",
	revertDuration:250,
	cursor:'pointer',
	distance:15,
	opacity:0.50,
	cursorAt: { 
		cursor:"crosshair",
		top:-5,
		left:-5 
	},
	start:function(e,ui) {
		$(this).addClass('dragged');
	},
	stop:function(e,ui) {
		var $dragged = $(this);
		setTimeout(function() {
			$dragged.removeClass('dragged');
		}, 2000);
	}
});

$('#decisionTree{$trigger->id} DIV.node.trigger').droppable({
	greedy:true,
	tolerance:'pointer',
	accept: "#decisionTree{$trigger->id} DIV.node.switch, #decisionTree{$trigger->id} DIV.node.action",
	activate:function(e,ui) {
		$(this).find('> DIV.badge').addClass('selected');
	},
	deactivate:function(e,ui) {
		$(this).find('> DIV.badge').removeClass('selected');
	},
	drop:function(e,ui) {
		$(ui.draggable)
			.css('left','0')
			.css('top','0')
			;
		$(this).find('> DIV.branch').prepend(ui.draggable);
		
		child_id = $(ui.draggable).find('> input:hidden[name=node_id]').val();
		parent_id = $(this).find('> input:hidden[name=node_id]').val();
		genericAjaxGet('','c=internal&a=reparentNode&child_id=' + child_id + '&parent_id=' + parent_id);
	}
});

$('#decisionTree{$trigger->id} DIV.node.switch').droppable({
	greedy:true,
	tolerance:'pointer',
	accept: "#decisionTree{$trigger->id} DIV.node.outcome",
	activate:function(e,ui) {
		$(this).find('> DIV.badge').addClass('selected');
	},
	deactivate:function(e,ui) {
		$(this).find('> DIV.badge').removeClass('selected');
	},
	drop:function(e,ui) {
		$(ui.draggable)
			.css('left','0')
			.css('top','0')
			;
		$(this).find('> DIV.branch').prepend(ui.draggable);
		
		child_id = $(ui.draggable).find('> input:hidden[name=node_id]').val();
		parent_id = $(this).find('> input:hidden[name=node_id]').val();
		genericAjaxGet('','c=internal&a=reparentNode&child_id=' + child_id + '&parent_id=' + parent_id);
	}
});

$('#decisionTree{$trigger->id} DIV.node.outcome').droppable({
	greedy:true,
	tolerance:'pointer',
	accept: "#decisionTree{$trigger->id} DIV.node.switch, #decisionTree{$trigger->id} DIV.node.action",
	activate:function(e,ui) {
		$(this).find('> DIV.badge').addClass('selected');
	},
	deactivate:function(e,ui) {
		$(this).find('> DIV.badge').removeClass('selected');
	},
	drop:function(e,ui) {
		$(ui.draggable)
			.css('left','0')
			.css('top','0')
			;
		$(this).find('> DIV.branch').prepend(ui.draggable);

		child_id = $(ui.draggable).find('> input:hidden[name=node_id]').val();
		parent_id = $(this).find('> input:hidden[name=node_id]').val();
		genericAjaxGet('','c=internal&a=reparentNode&child_id=' + child_id + '&parent_id=' + parent_id);
	}
});
</script>