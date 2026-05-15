<?php
/**
 * Copyright (c) 2005 bitweaver.org
 * All Rights Reserved. See below for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details
 *
 * created 2005/12/10
 *
 * @package newsletters
 * @author spider <spider@steelsun.com>
 */

/** 
 * Initialization
 */
use Bitweaver\KernelTools;
require_once( '../kernel/includes/setup_inc.php' );

$gBitSystem->verifyPackage( 'newsletters' );

require_once( NEWSLETTERS_PKG_INCLUDE_PATH.'lookup_newsletter_edition_inc.php' );

$listHash = [];
$newsletters = $gContent->mNewsletter->getList( $listHash );
$gBitSmarty->assign( 'newsletters', $newsletters );

if (isset($_REQUEST["remove"] ) && $gContent->isValid() ) {
	if( !empty( $_REQUEST['cancel'] ) ) {
		// user cancelled - just continue on, doing nothing
	} elseif( empty( $_REQUEST['confirm'] ) ) {
		$formHash['remove'] = TRUE;
		$formHash['edition_id'] = $gContent->mEditionId;
		$gBitSystem->confirmDialog( $formHash,
			[
				'warning' => KernelTools::tra('Are you sure you want to delete this newsletter edition?'). ' ' . $gContent->getTitle(),
			],
		);
	} else {
		if( $gContent->expunge() ) {
			header( "Location: ".NEWSLETTERS_PKG_URL.'edition.php' );
			die;
		}
	}
}

if( $gContent->isValid() ) {
	$title = $gContent->mInfo['title'];
	$mid = 'bitpackage:newsletters/view_edition.tpl';
} else {
	$listHash = [];
	$editions = $gContent->getList( $listHash );
	$gBitSmarty->assign( 'editionList', $editions );
	$gBitSmarty->assign( 'listInfo', $listHash );
	$title = KernelTools::tra("List Editions");
	$mid = 'bitpackage:newsletters/list_editions.tpl';
}

// Display the template
$gBitSystem->display( $mid, $title , [ 'display_mode' => 'edit' ]);

