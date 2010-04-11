<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmGroupEdit">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveTabMail">
<input type="hidden" name="team_id" value="{$team->id}">

<div class="block">
<h2>Preferences</h2>
<br>
	<div style="margin-left:20px">
	<h3>Anti-Spam</h3>
	
	When new messages have spam probability 
	<select name="spam_threshold">
		<option value="80" {if $group_spam_threshold=="80"}selected{/if}>80%</option>
		<option value="85" {if $group_spam_threshold=="85"}selected{/if}>85%</option>
		<option value="90" {if $group_spam_threshold=="90"}selected{/if}>90%</option>
		<option value="95" {if $group_spam_threshold=="95"}selected{/if}>95%</option>
		<option value="99" {if $group_spam_threshold=="99"}selected{/if}>99%</option>
	</select>
	 or higher:<br>
	<blockquote style="margin-top:0px;">
		<label><input type="radio" name="spam_action" value="0" {if $group_spam_action==0}checked{/if}> Do nothing</label><br>
		<label><input type="radio" name="spam_action" value="1" {if $group_spam_action==1}checked{/if}> Delete</label><br>
		{if !empty($categories)}
		<label><input type="radio" name="spam_action" value="2" {if $group_spam_action==2}checked{/if}> Move to bucket for review: </label>
		<select name="spam_action_moveto" onclick="this.form.spam_action[2].checked=true;">
			{foreach from=$categories item=bucket key=bucket_id}
				<option value="{$bucket_id}" {if $group_spam_action_param==$bucket_id}selected{/if}>{$bucket->name}</option>
			{/foreach}
		</select>
		{/if}
	</blockquote>
	
	<div class="subtle2" style="margin:0px;">
	<h3>Group E-mail Preferences</h3>

	<b>Send replies as e-mail:</b> (optional, defaults to: {$settings->get('cerberusweb.core','default_reply_from','')})<br>
	<input type="text" name="sender_address" value="{$group_settings.reply_from|escape}" size="65"><br>
	<span style="color:rgb(30,150,30);">(Make sure the above address delivers to the helpdesk or you won't receive replies!)</span><br>
	<br>
	
	<b>Send replies as name:</b> (optional, defaults to: {$settings->get('cerberusweb.core','default_reply_personal','')})<br>
	<input type="text" name="sender_personal" value="{$group_settings.reply_personal|escape}" size="65"><br>
	<label><input type="checkbox" name="sender_personal_with_worker" value="1" {if !empty($group_settings.reply_personal_with_worker)}checked{/if}> Also prefix the replying worker's name as the sender.</label><br>
	<br>
	
	<label><input type="checkbox" name="subject_has_mask" value="1" onclick="toggleDiv('divGroupCfgSubject',(this.checked)?'block':'none');" {if $group_settings.subject_has_mask}checked{/if}> Include the ticket's ID in subject line:</label><br>
	<blockquote id="divGroupCfgSubject" style="margin-left:20px;margin-bottom:0px;display:{if $group_settings.subject_has_mask}block{else}none{/if}">
		<b>Subject prefix:</b> (optional, e.g. "Billing", "Tech Support")<br>
		Re: [ <input type="text" name="subject_prefix" value="{$group_settings.subject_prefix|escape}" size="24"> #MASK-12345-678]: This is the subject line<br>
	</blockquote>
	<br>
			
	<b>Group E-mail Signature:</b> (optional, defaults to helpdesk signature)<br>
	<div style="display:none">
		{assign var=default_signature value=$settings->get('cerberusweb.core','default_signature')}
		<textarea name="default_signature">{$default_signature}</textarea>	
	</div>
	<textarea name="signature" rows="10" cols="76" style="width:100%;" wrap="off">{$team->signature}</textarea><br>
		<button type="button" onclick="genericAjaxPost('frmGroupEdit','divTemplateTester','c=internal&a=snippetTest&snippet_context=cerberusweb.snippets.worker&snippet_field=signature');"><span class="cerb-sprite sprite-gear"></span> Test</button>
		<select name="sig_token" onchange="insertAtCursor(this.form.signature,this.options[this.selectedIndex].value);this.selectedIndex=0;this.form.signature.focus();">
			<option value="">-- insert at cursor --</option>
			{foreach from=$token_labels key=k item=v}
			<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v|escape}</option>
			{/foreach}
		</select>
		
		{if !empty($default_signature)}
		<button type="button" onclick="this.form.signature.value=this.form.default_signature.value;">set to default</button>
		{/if}
		<br>
		<div id="divTemplateTester"></div> 
	</div>
	<br>
    </div>
	
	<h3>Group Outgong SMTP Server:</b> (optional)</h3>
    <label><input type="checkbox" name="smtp_is_enabled" value="1" onclick="toggleDiv('configGroupSmtpSetting',(this.checked?'block':'none'));" {if $group_settings.smtp_is_enabled}checked{/if}> <b>Enable Group Specific Outgoing Settings?</b></label><br>
    <br>

    <div id="configGroupSmtpSetting" style="margin-left:15px;display:{if $group_settings.smtp_is_enabled}block{else}none{/if};">
		<br>
        <b>SMTP Hosts:</b><br>
		<input type="text" name="smtp_host" value="{if $group_settings.smtp_host}{$group_settings.smtp_host}{else}localhost{/if}" size="45">
        <i>(e.g. localhost)</i>
        <br>
        <br>

        <b>SMTP Port:</b><br>
        <input type="text" name="smtp_port" value="{if $group_settings.smtp_port}{$group_settings.smtp_port}{else}25{/if}" size="5">
        <i>(usually '25')</i>
        <br>
        <br>

        <b>SMTP Encryption:</b> (optional)<br>
        <label><input type="radio" name="smtp_enc" value="None" {if $group_settings.smtp_enc == 'None'}checked{/if}>None</label>&nbsp;&nbsp;&nbsp;
        <label><input type="radio" name="smtp_enc" value="TLS" {if $group_settings.smtp_enc == 'TLS'}checked{/if}>TLS</label>&nbsp;&nbsp;&nbsp;
        <label><input type="radio" name="smtp_enc" value="SSL" {if $group_settings.smtp_enc == 'SSL'}checked{/if}>SSL</label><br>
        <br>

        <b>SMTP Authentication:</b> (optional)<br>
        <label><input type="checkbox" name="smtp_auth_enabled" value="1" onclick="toggleDiv('configGeneralSmtpAuth',(this.checked?'block':'none'));if(!this.checked){literal}{{/literal}this.form.smtp_auth_user.value='';this.form.smtp_auth_pass.value='';{literal}}{/literal}" {if $group_settings.smtp_auth_enabled}checked{/if}> Enabled</label><br>
        <br>

        <div id="configGeneralSmtpAuth" style="margin-left:15px;display:{if $group_settings.smtp_auth_enabled}block{else}none{/if};">
            <b>Username:</b><br>
            <input type="text" name="smtp_auth_user" value="{$group_settings.smtp_auth_user}" size="45"><br>
            <br>

            <b>Password:</b><br>
            <input type="text" name="smtp_auth_pass" value="{$group_settings.smtp_auth_pass}" size="45"><br>
            <br>
        </div>

        <b>SMTP Timeout:</b><br>
        <input type="text" name="smtp_timeout" value="{if $group_settings.smtp_timeout}{$group_settings.smtp_timeout}{else}30{/if}" size="4">
        seconds
        <br>
        <br>

        <b>Maximum Deliveries Per SMTP Connection:</b><br>
        <input type="text" name="smtp_max_sends" value="{if $group_settings.smtp_max_sends}{$group_settings.smtp_max_sends}{else}20{/if}" size="5">
        <i>(tuning this depends on your mail server; default is 20)</i>
        <br>
        <br>

        <div id="configSmtpTest"></div>
        <button type="button" onclick="genericAjaxGet('configSmtpTest','c=config&a=getSmtpTest&host='+this.form.smtp_host.value+'&port='+encodeURIComponent(this.form.smtp_port.value)+'&enc='+encodeURIComponent($('input[name=\'smtp_enc\']:checked').val())+'&smtp_user='+encodeURIComponent(this.form.smtp_auth_user.value)+'&smtp_pass='+encodeURIComponent(this.form.smtp_auth_pass.value));"><span class="cerb-sprite sprite-gear"></span> Test SMTP</button>
        <br>
        <br>
    </div>

	<h3>New Ticket Auto-Response</h3>
	
	<label><input type="checkbox" name="auto_reply_enabled" value="1" onclick="toggleDiv('divGroupCfgAutoReply',(this.checked)?'block':'none');" {if $group_settings.auto_reply_enabled}checked{/if}> <b>Send an auto-response when this group receives a new message?</b></label><br>
	<div style="margin-top:10px;margin-left:20px;display:{if $group_settings.auto_reply_enabled}block{else}none{/if};" id="divGroupCfgAutoReply">
		<b>Send the following message:</b><br>
		<textarea name="auto_reply" rows="10" cols="76">{$group_settings.auto_reply}</textarea><br>
			<b>E-mail Tokens:</b>
			
			<select name="autoreply_token" onchange="insertAtCursor(this.form.auto_reply,this.options[this.selectedIndex].value);this.selectedIndex=0;this.form.auto_reply.focus();">
				<option value="">-- choose --</option>
				<optgroup label="General">
					<option value="#timestamp#">Current Time</option>
				</optgroup>
				<optgroup label="First Requester">
					<option value="#sender#">E-mail</option>
					<option value="#sender_first#">First Name</option>
				</optgroup>
				<optgroup label="First Message">
					<option value="#orig_body#">Message Body</option>
				</optgroup>
				<optgroup label="Ticket">
					<option value="#mask#">Reference ID</option>
					<option value="#ticket_id#">Internal ID</option>
					<option value="#subject#">Subject</option>
					<!-- 
					<option value="#group#">Group Name</option>
					<option value="#bucket#">Bucket Name</option>
					 -->
				</optgroup>
			</select>
		<br>
	</div> 
	<br>
	
	<h3>Close Ticket Auto-Response</h3>
	
	<label><input type="checkbox" name="close_reply_enabled" value="1" onclick="toggleDiv('divGroupCfgCloseReply',(this.checked)?'block':'none');" {if $group_settings.close_reply_enabled}checked{/if}> <b>Send an auto-response when a ticket in this group is closed?</b></label><br>
	<div style="margin-top:10px;margin-left:20px;display:{if $group_settings.close_reply_enabled}block{else}none{/if};" id="divGroupCfgCloseReply">
		<b>Send the following message:</b><br>
		<textarea name="close_reply" rows="10" cols="76">{$group_settings.close_reply}</textarea><br>
			E-mail Tokens: 
			<select name="closereply_token" onchange="insertAtCursor(this.form.close_reply,this.options[this.selectedIndex].value);this.selectedIndex=0;this.form.close_reply.focus();">
				<option value="">-- choose --</option>
				<optgroup label="General">
					<option value="#timestamp#">Current Time</option>
				</optgroup>
				<optgroup label="First Requester">
					<option value="#sender#">E-mail</option>
					<option value="#sender_first#">First Name</option>
				</optgroup>
				<optgroup label="First Message">
					<option value="#orig_body#">Message Body</option>
				</optgroup>
				<optgroup label="Ticket">
					<option value="#mask#">Reference ID</option>
					<option value="#ticket_id#">Internal ID</option>
					<option value="#subject#">Subject</option>
				</optgroup>
			</select>
		<br>
	</div> 
	<br>
	
	<h3>{$translate->_('config.mail.message.status.reply')|capitalize} {$translate->_('config.mail.message.status')|capitalize}</h3>
	
	<label><input type="radio" name="ticket_reply_status" value="255" {if $ticket_reply_status eq 255}checked{/if}> {$translate->_('config.mail.message.status.default')|capitalize}</label>
	<label><input type="radio" name="ticket_reply_status" value="0" {if $ticket_reply_status eq 0}checked{/if}> {$translate->_('status.open')|capitalize}</label>
	<label><input type="radio" name="ticket_reply_status" value="1" {if $ticket_reply_status eq 1}checked{/if}> {$translate->_('status.waiting')|capitalize}</label>
	<label><input type="radio" name="ticket_reply_status" value="2" {if $ticket_reply_status eq 2}checked{/if}> {$translate->_('status.closed')|capitalize}</label>
	<br>
	<br>
	
	<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>	
	</div>
</div>

</form>