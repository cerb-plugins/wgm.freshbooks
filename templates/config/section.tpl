<h2>{'wgm.freshbooks.common'|devblocks_translate}</h2>

<form id="frmFreshbooksConfigTab" action="{devblocks_url}c=config{/devblocks_url}" method="POST" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="freshbooks">
<input type="hidden" name="action" value="save">

<fieldset>
	<legend>{'wgm.freshbooks.common.api_authentication'|devblocks_translate}</legend>

	<table cellpadding="2" cellspacing="0" border="0">
		<tr>
			<td align="right" valign="top"><b>{'wgm.freshbooks.common.api_authentication.url'|devblocks_translate}:</b></td>
			<td>
				<input type="text" name="api_url" value="{$params.api_url}" class="input_url" size="65">
				<br>
				<i>e.g. https://<b>example</b>.freshbooks.com/api/2.1/xml-in</i>
			</td>
		</tr>
		<tr>
			<td><b>{'wgm.freshbooks.common.api_authentication.token'|devblocks_translate}:</b></td>
			<td>
				<input type="text" name="api_token" value="{$params.api_token}" class="input_password" size="45">
			</td>
		</tr>
	</table>

	<div class="status"></div>
	
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	<button type="button" class="tester"><span class="glyphicons glyphicons-cogwheel"></span> Test</button>
</fieldset>

</form>

<script>
	$frm = $('#frmFreshbooksConfigTab');
	$frm.find('BUTTON.submit').click(function() {
		Devblocks.showSuccess('#frmFreshbooksConfigTab div.status', "Saving...", false, false);

		$(this.form).find('input:hidden[name=action]').val('save');

		genericAjaxPost('frmFreshbooksConfigTab','',null,function(json) {
			$o = $.parseJSON(json);
			if(false == $o || false == $o.status) {
				Devblocks.showError('#frmFreshbooksConfigTab div.status',$o.error);
			} else {
				Devblocks.showSuccess('#frmFreshbooksConfigTab div.status',$o.message);
			}
			
			$this.show();
		});
	});
	$frm.find('BUTTON.tester').click(function() {
		Devblocks.showSuccess('#frmFreshbooksConfigTab div.status', "Testing... please wait.", false, false);
		
		$(this.form).find('input:hidden[name=action]').val('testAuthentication');
		
		genericAjaxPost('frmFreshbooksConfigTab','',null,function(json) {
			$o = $.parseJSON(json);
			if(false == $o || false == $o.status) {
				Devblocks.showError('#frmFreshbooksConfigTab div.status',$o.error);
			} else {
				Devblocks.showSuccess('#frmFreshbooksConfigTab div.status',$o.message);
			}
			
			$this.show();
		});
	});
</script>