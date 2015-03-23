<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

/** REGISTRATION */
$wgExtensionCredits['parserhook'][] = array(
	'path' => __FILE__,
	'name' => 'GeoGebra',
	'version' => '3.0.7',
	'url' => 'https://www.mediawiki.org/wiki/Extension:GeoGebra',
	'author' => array( 'Rudolf Grossmann','Zbynek Konecny'),
	'descriptionmsg' => 'geogebra-desc',
);

$wgAutoloadClasses['ExtGeoGebra'] = dirname( __FILE__ ) . '/GeoGebra.body.php';
$wgExtensionMessagesFiles['GeoGebra'] = dirname( __FILE__ ) . '/GeoGebra.i18n.php';
$wgMessagesDirs['GeoGebra'] = __DIR__ . '/i18n';

$wgHooks['ParserFirstCallInit'][] = 'wfGeoGebraInit';
$wgHooks['BeforePageDisplay'][] = 'ExtGeoGebra::injectJS';

/**
 * @param $parser Parser
 * @return bool
 */
function wfGeoGebraInit( $parser ) {
	$parser->setHook('ggb_applet','ExtGeoGebra::geogebraTag');
	return true;
}
