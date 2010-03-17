<div id="account">
<div class="header"><h1>{$translate->_('portal.sc.public.my_account')}</h1></div>

{if !empty($account_error)}
<div class="error">{$account_error}</div>
{elseif !empty($account_success)}
<div class="success">{$translate->_('portal.sc.public.my_account.settings_saved')}</div>
{/if}

<form action="{devblocks_url}c=account{/devblocks_url}" method="post" id="myAccountForm">
<input type="hidden" name="a" value="saveAccount">

<b>{$translate->_('common.email')}:</b><br>
{$address->email}<br>
<br>

{if $display_address_full_name != 0}
<b>{$translate->_('contact_person.first_name')|capitalize}:</b><br>
<input type="text" name="first_name" size="35" value="{$address->first_name}"><br>
<br>
{/if}

{if $display_address_full_name == 2}
<b>{$translate->_('contact_person.last_name')|capitalize}:</b><br>
<input type="text" name="last_name" size="35" value="{$address->last_name}"><br>
<br>
{/if}

{if !empty($login_handler) && 0==strcasecmp($login_handler->manifest->id,'sc.login.auth.default')}
<b>{$translate->_('portal.sc.public.my_account.change_password')}</b><br>
<input type="password" id="change_password" name="change_password" size="35" value=""><br>
<br>

<b>{$translate->_('portal.sc.public.my_account.change_password_verify')}</b><br>
<input type="password" name="change_password2" size="35" value=""><br>
<br>
{/if}

{* Custom Fields *}
<div id="address_custom_fields_div">
	<table cellpadding="2" cellspacing="1" border="0">
		{foreach from=$address_fields item=a key=a_id}
			{if $cf_address_select.$a_id != 0}
					<tr>
						<td valign="top" width="1%" nowrap="nowrap">
							<b>{$a->name}:</b>
						</td>
						<td valign="top" width="99%">
							{if $cf_address_select.$a_id == 2} {* Read Write Version *}
								<input type="hidden" name="field_ids[]" value="{$a_id}">
								{assign var=display_submit value=1}
								{if $a->type=='S'}
									<input type="text" name="field_{$a_id}" size="45" maxlength="255" value="{$address_field_values.$a_id|escape}"><br>
								{elseif $a->type=='U'}
									<input type="text" name="field_{$a_id}" size="45" maxlength="255" value="{$address_field_values.$a_id|escape}">
									{if !empty($address_field_values.$a_id)}<a href="{$address_field_values.$a_id|escape}" target="_blank">URL</a>{else}<i>(URL)</i>{/if}
								{elseif $a->type=='N'}
									<input type="text" name="field_{$a_id}" size="45" maxlength="255" value="{$address_field_values.$a_id|escape}"><br>
								{elseif $a->type=='T'}
									<textarea name="field_{$a_id}" rows="4" cols="50" style="width:98%;">{$address_field_values.$a_id}</textarea><br>
								{elseif $a->type=='C'}
									<input type="checkbox" name="field_{$a_id}" value="1" {if $address_field_values.$a_id}checked{/if}><br>
								{elseif $a->type=='X'}
									{foreach from=$a->options item=opt}
										<label><input type="checkbox" name="field_{$a_id}[]" value="{$opt|escape}" {if isset($address_field_values.$a_id.$opt)}checked="checked"{/if}> {$opt}</label><br>
									{/foreach}
								{elseif $a->type=='D'}
									<select name="field_{$a_id}">{* [TODO] Fix selected *}
										<option value=""></option>
										{foreach from=$a->options item=opt}
											<option value="{$opt|escape}" {if $opt==$address_field_values.$a_id}selected{/if}>{$opt}</option>
										{/foreach}
									</select><br>
								{elseif $a->type=='M'}
									<select name="field_{$a_id}[]" size="5" multiple="multiple">
										{foreach from=$a->options item=opt}
											<option value="{$opt|escape}" {if isset($address_field_values.$a_id.$opt)}selected="selected"{/if}>{$opt}</option>
										{/foreach}
									</select><br>
									<i><small>{$translate->_('common.tips.multi_select')}</small></i>
								{elseif $a->type=='E'}
									<input type="text" name="field_{$a_id}" id="field_{$a_id}" size="45" maxlength="255" value="{if !empty($address_field_values.$a_id)}{$address_field_values.$a_id|devblocks_date}{/if}">
								{elseif $a->type=='W'}
									{if empty($workers)}
										{$workers = DAO_Worker::getAllActive()}
									{/if}
									<select name="field_{$a_id}">
										<option value=""></option>
										{foreach from=$workers item=worker}
											<option value="{$worker->id}" {if $worker->id==$address_field_values.$a_id}selected="selected"{/if}>{$worker->getName()}</option>
										{/foreach}
									</select>
								{/if}
							{else}  {* Read Only Version *}
								{if $a->type=='S'}
									{$address_field_values.$a_id|escape}<br>
								{elseif $a->type=='U'}
									{if !empty($address_field_values.$a_id)}<a href="{$address_field_values.$a_id|escape}" target="_blank">{$address_field_values.$a_id|escape}</a>{else}<i>(URL)</i>{/if}
								{elseif $a->type=='N'}
									{$address_field_values.$a_id|escape}<br>
								{elseif $a->type=='T'}
									{nl2br($address_field_values.$a_id)}<br>
								{elseif $a->type=='C'}
									<input type="checkbox" disabled="disabled" name="field_{$a_id}" value="1" {if $address_field_values.$a_id}checked{/if}><br>
								{elseif $a->type=='X'}
									{foreach from=$a->options item=opt}
										<label><input type="checkbox" disabled="disabled" name="field_{$a_id}[]" value="{$opt|escape}" {if isset($address_field_values.$a_id.$opt)}checked="checked"{/if}> {$opt}</label><br>
									{/foreach}
								{elseif $a->type=='D'}
									{foreach from=$a->options item=opt}
										{if $opt==$address_field_values.$a_id}{$opt}<br>{/if}
									{/foreach}
								{elseif $a->type=='M'}
									{foreach from=$a->options item=opt}
										{if isset($address_field_values.$a_id.$opt)}{$opt}<br>{/if}
									{/foreach}
								{elseif $a->type=='E'}
									{if !empty($address_field_values.$a_id)}{$address_field_values.$a_id|devblocks_date}{/if}<br>
								{elseif $a->type=='W'}
									{if $address_field_values.$a_id != 0}
										{$cust_worker = DAO_Worker::getAgent($address_field_values.$a_id)}
										{$cust_worker->getName()}
									{/if}
								{/if}
							{/if}  
						</td>
					</tr>
			{/if}
		{/foreach}
	</table>
</div>

<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button><br>
</form>
</div>

{if !empty($login_handler) && 0==strcasecmp($login_handler->manifest->id,'sc.login.auth.default')}
{literal}
<script language="JavaScript1.2" type="text/javascript">
  $(document).ready(function(){
    $("#myAccountForm").validate({
		rules: {
			change_password2: {
				equalTo: "#change_password"
			}
		},
		messages: {
			change_password2: {
				equalTo: "The passwords don't match."
			}
		}		
	});
  });
</script>
{/literal}
{/if}