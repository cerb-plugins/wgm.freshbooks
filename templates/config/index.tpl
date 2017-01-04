<h2>{'wgm.freshbooks.common'|devblocks_translate}</h2>

<form action="javascript:;" method="post" id="frmSetupFreshbooks" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="freshbooks">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="status"></div>

<fieldset>
	<legend>Authentication</legend>
	
	<b>Subdomain:</b><br>
	<input type="text" name="consumer_key" value="{$credentials.consumer_key}" size="64" spellcheck="false">
	<br>
	<i>e.g. https://<b>example</b>.freshbooks.com/</i>
	<br>
	<br>
	
	<b>OAuth Secret:</b><br>
	<input type="password" name="consumer_secret" value="{$credentials.consumer_secret}" size="64" spellcheck="false"><br>
	<br>
	
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>	
</fieldset>

<fieldset>
	<legend>Synchronization</legend>
	
	<b>Synchronize clients and invoices using connected account:</b><br>
	<button type="button" class="chooser-abstract" data-field-name="sync_account_id" data-context="{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}" data-single="true" data-query="service:freshbooks"><span class="glyphicons glyphicons-search"></span></button>
	<ul class="bubbles chooser-container">
		{if $sync_account}
		<li>
			<input type="hidden" name="sync_account_id" value="{$sync_account->id}">
			<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}" data-context-id="{$sync_account->id}">{$sync_account->name}</a>
		</li>
		{/if}
	</ul>
	<br>
	<br>
	
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>	
</fieldset>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmSetupFreshbooks');
	
	$frm.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
	
	$frm.find('.chooser-abstract')
		.cerbChooserTrigger()
		;
	
	$frm.find('BUTTON.submit')
		.click(function(e) {
			genericAjaxPost('frmSetupFreshbooks','',null,function(json) {
				$o = $.parseJSON(json);
				if(false == $o || false == $o.status) {
					Devblocks.showError('#frmSetupFreshbooks div.status', $o.error);
				} else {
					Devblocks.showSuccess('#frmSetupFreshbooks div.status', $o.message);
				}
			});
		})
	;
});
</script>