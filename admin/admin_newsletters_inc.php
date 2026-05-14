<?php
// $Header$

$formNewsletterFeatures = [
	"bitmailer_sender_email" => [
		'label' => 'From Email',
		'note' => 'If empty, it will default to the site Sender Email',
		'default' => $gBitSystem->getConfig( 'site_sender_email', $_SERVER['SERVER_ADMIN'] ),
	],
	"bitmailer_from" => [
		'label' => 'From Name',
		'note' => '',
		'default' => $gBitSystem->getConfig( 'siteTitle' ),
	],
	"bitmailer_servers" => [
		'label' => 'Mail Servers',
		'note' => '',
		'default' => $gBitSystem->getConfig( 'kernel_server_name', '127.0.0.1' ),
	],
	"bitmailer_smtp_username" => [
		'label' => 'SMTP Username',
		'note' => 'Only required for authenticated outbound mail servers.',
		'default' => $gBitSystem->getConfig( 'bitmailer_smtp_username' ),
	],
	"bitmailer_smtp_password" => [
		'label' => 'SMTP Password',
		'note' => 'Password for the above SMTP Username',
		'default' => $gBitSystem->getConfig( 'bitmailer_smtp_password' ),
	],
	"bitmailer_protocol" => [
		'label' => 'Protocol',
		'note' => '',
		'default' => 'smtp',
	],
	"bitmailer_word_wrap" => [
		'label' => 'Word wrap',
		'note' => '',
		'default' => '75',
	],
];
$gBitSmarty->assign( 'formNewsletterFeatures',$formNewsletterFeatures );

if( !empty( $_POST ) ) {

	foreach( array_keys( $formNewsletterFeatures ) as $key ) {
		if( empty( $_REQUEST[$key] ) || $_REQUEST[$key] != $formNewsletterFeatures[$key]['default'] ) {
			$gBitSystem->storeConfig( $key, $_REQUEST[$key] ?? NULL );
		}
	}
}

?>
