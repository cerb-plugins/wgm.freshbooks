<form id="frmFreshbooksConfigTab" action="{devblocks_url}c=config{/devblocks_url}" method="POST" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveTab">
<input type="hidden" name="ext_id" value="wgm.freshbooks.config_tab">

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
	
	<button type="button" class="submit"><span class="cerb-sprite sprite-check"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	<button type="button" class="tester"><span class="cerb-sprite sprite-gear"></span> Test</button>
	<span class="status" style="display:none;font-weight:bold;background-color:rgb(242,222,105);padding:5px;"></span>
</fieldset>

</form>

<script>
	$frm = $('#frmFreshbooksConfigTab');
	$frm.find('BUTTON.submit').click(function() {
		$button = $(this);
		genericAjaxPost('frmFreshbooksConfigTab','','',function() {
			$button.siblings('SPAN.status').clearQueue().html('Saved!').fadeIn('fast').delay('3000').fadeOut('slow');
		});
	});
	$frm.find('BUTTON.tester').click(function() {
		$button = $(this);
		genericAjaxPost('frmFreshbooksConfigTab','','c=wgm.freshbooks&a=testAuthentication',function(html) {
			$button.siblings('SPAN.status').clearQueue().html(html).fadeIn('fast').delay('3000').fadeOut('slow');
		});
	});
</script>