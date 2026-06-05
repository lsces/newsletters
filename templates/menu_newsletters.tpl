{strip}
{if $packageMenuTitle}<a class="dropdown-toggle" data-toggle="dropdown" href="#"> {tr}{$packageMenuTitle}{/tr} <b class="caret"></b></a>{/if}
<ul class="{$packageMenuClass}">
	<li><a class="item" href="{$smarty.const.NEWSLETTERS_PKG_URL}index.php">{biticon ipackage="icons" iname="view-list-text" iexplain="List Newsletters" ilocation=menu}</a></li>
	{if $gBitUser->hasPermission( 'p_newsletters_create' )}
		<li><a class="item" href="{$smarty.const.NEWSLETTERS_PKG_URL}newsletters.php?new=1">{biticon ipackage="icons" iname="view-list-text" iexplain="Create Newsletter" ilocation=menu}</a></li>
	{/if}
	<li><a class="item" href="{$smarty.const.NEWSLETTERS_PKG_URL}edition.php">{biticon ipackage="icons" iname="folder-open"   iexplain="List Editions" ilocation=menu}</a></li>
	{if $gBitUser->hasPermission( 'p_newsletters_create_editions' )}
		<li><a class="item" href="{$smarty.const.NEWSLETTERS_PKG_URL}edition_edit.php">{biticon ipackage="icons" iname="folder" iexplain="Create Edition" ilocation=menu}</a></li>
	{/if}
	{if $gBitUser->hasPermission( 'p_newsletters_admin' )}
		<li><a class="item" href="{$smarty.const.NEWSLETTERS_PKG_URL}admin/send.php">{biticon ipackage="icons" iname="internet-mail"   iexplain="Send Newsletters" ilocation=menu}</a></li>
	{/if}
</ul>
{/strip}
