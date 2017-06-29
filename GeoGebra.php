<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

/** REGISTRATION */
$wgExtensionCredits['parserhook'][] = [
	'path' => __FILE__,
	'name' => 'GeoGebra',
	'version' => '3.0.7',
	'url' => 'https://www.mediawiki.org/wiki/Extension:GeoGebra',
	'author' => [ 'Rudolf Grossmann','Zbynek Konecny' ],
	'descriptionmsg' => 'geogebra-desc',
];

$wgAutoloadClasses['ExtGeoGebra'] = __DIR__ . '/GeoGebra.body.php';
$wgExtensionMessagesFiles['GeoGebra'] = __DIR__ . '/GeoGebra.i18n.php';
$wgMessagesDirs['GeoGebra'] = __DIR__ . '/i18n';

$wgHooks['ParserFirstCallInit'][] = 'wfGeoGebraInit';
$wgHooks['BeforePageDisplay'][] = 'ExtGeoGebra::injectJS';

/**
 * @param Parser $parser
 * @return bool
 */
function wfGeoGebraInit( $parser ) {
	$parser->setHook( 'ggb_applet', 'ExtGeoGebra::geogebraTag' );
	return true;
}
