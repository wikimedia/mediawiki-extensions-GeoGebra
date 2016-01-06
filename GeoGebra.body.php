<?php

class ExtGeoGebra{

  static $divs = Array();
  public static function geogebraTag($input,$args,$parser){
	global $wgGeoGebraTechnology;
	$CRLF = "\r\n";

	if(!isset($args['width']) || !isset($args['height']) ||
 		(!isset($args['id']) && !isset($args['filename']) && !isset($args['ggbbase64']))){
		$error_message = wfMessage( 'geogebra-missing-parameter' )->escaped();
		return wfMessage( 'geogebra-error' )->rawParams( $error_message )->parseAsBlock() . $CRLF;
	}

	$parameters = '';
	$shortKeyMap = array('id'=>'material_id','border' => 'borderColor',	 'rc'=>'enableRightClick',
			'ai'=>'showAlgebraInput' , 'sdz'=>'enableShiftDragZoom',
			'smb'=>'showMenuBar','stb'=>'showToolBar','stbh'=>'showToolBarHelp','showtoolbar'=>'showToolBar',
			'enableRightClick'=>'enableRightClick');
	foreach($args as $key => $value){
		if($key == "filename"){
				$ggbFile = wfLocalFile( $value );
                   if ( !( $ggbFile->exists() ) ) {
					return wfMessage( 'geogebra-file-not-found' )->rawParams( $ggbBinary )->escaped();
                   } else {
                  $fc = file_get_contents($ggbFile->getLocalRefPath());
				  $parameters .= ',ggbBase64:"'.base64_encode($fc).'"';
				  }
			continue;
		}
		$shortKey = isset($shortKeyMap[$key]) ? $shortKeyMap[$key] : htmlspecialchars( strip_tags( $key ) );
		$parameters .= ','.$shortKey . ':"' . htmlspecialchars( strip_tags( $value ) ) . '"';
	}

	$div = md5(rand());
	self::$divs[] = $div;
	$iframe = '<script>  if(!window.ggbParams){window.ggbParams ={};}; window.ggbParams["'.$div.'"] = {'.substr($parameters,1).'}; </script> '.$CRLF
	  ."<div id=\"ggbContainer$div\"></div>";
	

	return $iframe;
  }
  
  static function injectJS($out)
  {
    $technology = isset( $wgGeoGebraTechnology ) ? htmlspecialchars( strip_tags($wgGeoGebraTechnology)) : "preferhtml5";
	$deployGGBUrl = isset( $wgGeoGebraDeployURL ) ? htmlspecialchars( strip_tags($wgGeoGebraDeployURL)) : "//tube-beta.geogebra.org/scripts/deployggb.js";
	
    if(!count(self::$divs)) return true;
    
    global $wgJsMimeType;
    $out->addScript("<script type='$wgJsMimeType' src='$deployGGBUrl'></script>\n");
    $scriptBody = "for(var key in window.ggbParams){
	var c=window.ggbParams[key];	
	new GGBApplet(c,'',{'is3D':!!c['is3D'],'AV':!!c['gui']}).inject('ggbContainer'+key,c['at']?c['at']:'$technology');}\n";
    
    $out->addScript("<script type='$wgJsMimeType'>$scriptBody</script>\n");
    return true;
  }
}
