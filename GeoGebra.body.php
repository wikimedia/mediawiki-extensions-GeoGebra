<?php

use MediaWiki\MediaWikiServices;

class ExtGeoGebra {

	/** @var string[] */
	private static $divs = [];

	/**
	 * @param Parser $parser
	 */
	public static function init( $parser ) {
		$parser->setHook( 'ggb_applet', [ __CLASS__, 'geogebraTag' ] );
	}

	/**
	 * @param string|null $input
	 * @param string[] $args
	 * @param Parser $parser
	 *
	 * @return string
	 */
	public static function geogebraTag( $input, $args, $parser ) {
		$CRLF = "\r\n";

		if ( !isset( $args['width'] ) || !isset( $args['height'] ) ||
			( !isset( $args['id'] ) && !isset( $args['filename'] ) && !isset( $args['ggbbase64'] ) )
		) {
			$error_message = wfMessage( 'geogebra-missing-parameter' )->escaped();
			return wfMessage( 'geogebra-error' )->rawParams( $error_message )->parseAsBlock() . $CRLF;
		}

		$parameters = '';
		$shortKeyMap = [
			'id' => 'material_id',
			'border' => 'borderColor',
			'rc' => 'enableRightClick',
			'ai' => 'showAlgebraInput',
			'sdz' => 'enableShiftDragZoom',
			'smb' => 'showMenuBar',
			'stb' => 'showToolBar',
			'stbh' => 'showToolBarHelp',
			'showtoolbar' => 'showToolBar',
			'enableRightClick' => 'enableRightClick'
		];

		if ( method_exists( MediaWikiServices::class, 'getRepoGroup' ) ) {
			// MediaWiki 1.34+
			$localRepo = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo();
		} else {
			$localRepo = RepoGroup::singleton()->getLocalRepo();
		}
		foreach ( $args as $key => $value ) {
			if ( $key == "filename" ) {
				$ggbFile = $localRepo->newFile( $value );
				if ( !( $ggbFile->exists() ) ) {
					return wfMessage( 'geogebra-file-not-found' )
						->rawParams( $ggbFile )->escaped();
				} else {
					$fc = file_get_contents( $ggbFile->getLocalRefPath() );
					$parameters .= ',ggbBase64:"' . base64_encode( $fc ) . '"';
				}
				continue;
			}
			$shortKey = isset( $shortKeyMap[$key] )
				? $shortKeyMap[$key]
				: htmlspecialchars( strip_tags( $key ) );
			$parameters .= ',' . $shortKey . ':"' . htmlspecialchars( strip_tags( $value ) ) . '"';
		}

		$div = md5( rand() );
		self::$divs[] = $div;
		$iframe = '<script>  if(!window.ggbParams){window.ggbParams ={};}; window.ggbParams["' .
			$div . '"] = {' . substr( $parameters, 1 ) . '}; </script> ' . $CRLF .
			"<div id=\"ggbContainer$div\"></div>";

		return $iframe;
	}

	/**
	 * @param OutputPage $out
	 */
	public static function injectJS( $out ) {
		global $wgGeoGebraDeployURL;

		$deployGGBUrl = isset( $wgGeoGebraDeployURL )
			? htmlspecialchars( strip_tags( $wgGeoGebraDeployURL ) )
			: "https://cdn.geogebra.org/apps/deployggb.js";

		if ( !count( self::$divs ) ) {
			return;
		}

		$out->addScript( "<script type='text/javascript' src='$deployGGBUrl'></script>\n" );
		$scriptBody = "for(var key in window.ggbParams){\n" .
			"var c=window.ggbParams[key];\n" .
			"new GGBApplet(c,'',{'is3D':!!c['is3D'],'AV':!!c['gui']})" .
			".inject('ggbContainer'+key);}\n";

		$out->addScript( "<script type='text/javascript'>$scriptBody</script>\n" );
	}
}
