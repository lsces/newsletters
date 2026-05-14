<?php
/**
 * @version $Header$
 */
global $gBitInstaller;

$infoHash = [
	'package'      => NEWSLETTERS_PKG_NAME,
	'version'      => str_replace( '.php', '', basename( __FILE__ )),
	'description'  => "Replace reserved name reads with hits",
	'post_upgrade' => NULL,
];

// Increase the size of the IP column to cope with IPv6
$gBitInstaller->registerPackageUpgrade( $infoHash, [

[ 'DATADICT' => [
	[ 'RENAMECOLUMN' => [
		'mail_queue' => [
			'`reads`' => '`hits` I2 NOTNULL DEFAULT 0',
		],
	]],
]],

]);
?>
