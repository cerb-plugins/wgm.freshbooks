<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2018, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class PageSection_ProfilesFreshbooksClient extends Extension_PageSection {
	function render() {
		$request = DevblocksPlatform::getHttpRequest();

		$stack = $request->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // freshbooks_client
		@$context_id = intval(array_shift($stack));
		
		$context = 'wgm.freshbooks.contexts.client';
		
		Page_Profiles::renderProfile($context, $context_id);
	}
};