<?php
/**
 * GeoGebra extension
 *
 * @author Rudolf Grossmann
 * @version 2.8b
 */
 
$ggb_version = "2.8b";
 
// This MediaWiki extension is based on the Java Applet extension by Phil Trasatti
// see: http://www.mediawiki.org/wiki/Extension:Java_Applet
 
//Avoid unstubbing $wgParser too early on modern (1.12+) MW versions, as per r35980
if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
        $wgHooks['ParserFirstCallInit'][] = 'ggb_AppletSetup';
} else {
        $wgExtensionFunctions[] = 'ggb_AppletSetup';
}
 
$wgExtensionCredits['parserhook'][] = array(
	'path'           => __FILE__,
	'name'           => 'GeoGebra',
	'author'         => 'Rudolf Grossmann',
	'url'            => 'https://www.mediawiki.org/wiki/Extension:GeoGebra',
	'descriptionmsg' => 'geogebra-desc',
	'version'        => $ggb_version
);
 
function ggb_AppletSetup() {
        global $wgParser;
        $wgParser->setHook( 'ggb_applet', 'get_ggbAppletOutput' );
        return true;
}
 
function get_ggbAppletOutput( $input, $args, $parser ) {
        global $wgServer; // URL of the WIKI's server
        global $wgVersion; // Version number of MediaWiki Software
        global $ggb_version; // see line 9 of this file
 
        $error_message = "no error"; //will be overwritten, if error occurs
        $debug = 'Debug: ';
        $CRLF = "\r\n";
        $quot='"';
        $appletBinary = "geogebra.jar" ;
        $codeBaseSigned = 'http://www.geogebra.org/webstart/';
        if( isset( $args['version'] ) ) {
          $version = htmlspecialchars(strip_tags($args['version']));
          $codeBaseUnsigned = 'http://www.geogebra.org/webstart/' . $version .'/unsigned/';
        } else {
          $codeBaseUnsigned = 'http://www.geogebra.org/webstart/unsigned/';
        }
 
        // Special parameters, not for parameter (name - value) tags. Use lowercase for sake of comparison!
        $special_parameters = array('width', 'height', 'uselocaljar', 'usesignedjar', 'substimage', 'filename', 'ggbbase64', 'version', 'debug'); 
        $noJavaText = 'Please <a href="http://java.sun.com/getjava">install Java</a> to use this page.'; 
 
        // retrieve URL of image file substituting GeoGebra applet if Java ist not installed
        $imgBinary = isset($args['substimage']) ? htmlspecialchars(strip_tags($args['substimage'])) : '';
        if ($imgBinary=='') {
          $imgBinary = 'filenotfound.jpg';
        }
        $imgFile = wfLocalFile($imgBinary);
        if ($imgFile->exists())
        {
          $imgURL = $imgFile->getURL();
          // if URL doesn't start with slash, add starting slash.
          if (substr($imgURL, 0, 1) != '/'){
                  $imgURL = '/' . $imgURL;
          }
          $imgURL = $wgServer . $imgURL;
          $noJavaText = "<img src=". $quot . $imgURL . $quot;
          $noJavaText = $noJavaText . " width=" . $quot . htmlspecialchars(strip_tags($args['width'])) . $quot; // Add width value to tag
          $noJavaText = $noJavaText . " height=" . $quot . htmlspecialchars(strip_tags($args['height'])) . $quot; // Add height value to tag
          $noJavaText = $noJavaText . " alt=" . $quot . "Image replacing GeoGebra applet" . $quot. " >". $CRLF;
          $noJavaText = $noJavaText . '<p>Please <a href="http://java.sun.com/getjava">install Java</a> to see a dynamic version of this image.</p>';
        }
		
        //Look for parameter 'useSignedJar'.
        $useSignedJar = isset($args['usesignedjar']) ? $args['usesignedjar'] : '';
        //Look for parameter 'useLocalJar'. Will be overwritten with 'true', if parameter 'filename is used'
        $useLocalJar = isset($args['uselocaljar']) ? $args['uselocaljar'] : '';
        $printDebug = isset($args['debug']) ? $args['debug'] : 'false';
 
        // Look for required parameters width, height, ggbBase64 or filename
        if( !(isset( $args['width'] ) ) )
        {
          $error_message = "Missing parameter 'width'.";
        } else {
          if( !(isset( $args['height'] ) ) )
          {
            $error_message = "Missing parameter 'height'.";
          } else {
            if( isset( $args['ggbbase64'] ) )
            {
              $ggbBase64String = htmlspecialchars(strip_tags($args['ggbbase64']));
              if ($ggbBase64String != '')
              {
                $ggbSource = '<param name="ggbBase64" value="' . $ggbBase64String . '">' . $CRLF;
              }
            } else {
              // No parameter ' ggbBase64'. Parameter 'filename' necessary
              if( !(isset( $args['filename'] ) ) )
              {
                $error_message = "No parameter 'ggbBase64' or 'filename'.";
              } else {
                // retrieve URL of *.ggb file
                $ggbBinary = htmlspecialchars(strip_tags($args['filename']));
                if ($ggbBinary == '') {
                  $ggbBinary = 'filenotfound.ggb';
                }
                $ggbFile = wfLocalFile($ggbBinary);
                if (!($ggbFile->exists()))
                {
                  $error_message = "File not found: " . $ggbBinary;
                }
                else
                {
                  $ggbURL = $ggbFile->getURL();
                  // if URL doesn't start with slash, add starting slash.
                  if (substr($ggbURL, 0, 1) != '/'){
                          $ggbURL = '/' . $ggbURL;
                  }
                  $ggbURL = $wgServer . $ggbURL;
                  // Add URL of *.ggb file to tag
                  $ggbSource = '<param name="filename" value="' . $ggbURL . '">' . $CRLF;
                  $useLocalJar = 'true'; //Avoid trouble (security exception)
                }
              }
            }
          }
        }
 
        // if error occured, discard applet and output error message
        if ($error_message == 'no error') {
          $debug .= 'No error<br>' . $CRLF;
          if ($useSignedJar != 'true'){  // Default
            $debug .= 'useSigned=false<br>' . $CRLF;
            if ($useLocalJar != 'true'){
              $debug .= 'useLocal=false<br>' . $CRLF;
              $codeBase = $codeBaseUnsigned; //Default
            } 
          } else {
              $debug .= 'User explicitly wants signed applet!<br>' . $CRLF;
              // User explicitly wants signed applet
              $codeBase = $codeBaseSigned;
              $useLocalJar = 'false';
          }
 
          if ($useLocalJar == 'true'){  // Parameter set or JAR from geogebra.org not found
            $debug .= 'useLocal=true<br>' . $CRLF;
            # The following line is code from http://code.activestate.com/recipes/576595/   "A more reliable DOCUMENT_ROOT"
            $docroot = realpath((getenv('DOCUMENT_ROOT') && ereg('^'.preg_quote(realpath(getenv('DOCUMENT_ROOT'))), realpath(__FILE__))) ? getenv('DOCUMENT_ROOT') : str_replace(dirname(@$_SERVER['PHP_SELF']), '', str_replace(DIRECTORY_SEPARATOR, '/', dirname(__FILE__))));
			# $docroot = $_SERVER['DOCUMENT_ROOT']; #ereg is deprecated and causes an error 
            $delta = substr(dirname(__FILE__), strlen($docroot));
            $codeBase = $wgServer . $delta;
            # replace backslash by slash
            $codeBase=str_replace('\\','/',$codeBase); 
            # add slash at ending
            if (substr($codeBase, strlen($codeBase)-1) != '/') {
              $codeBase = $codeBase . "/";
            }
          }
        }
 
        if ($error_message == 'no error') {
            // Assemble the applet tag
            $output = "<!-- GeoGebra Applet MediaWiki extension " . $ggb_version . " by R. Grossmann -->";
            $output .= '<applet code="geogebra.GeoGebraApplet"'; // Add code value to tag
            if( isset( $args['name'] )){
               $output = $output . " name=" . $quot . htmlspecialchars(strip_tags($args['name'])) . $quot; // Add name value to tag
            }
            $output .= " codebase=" . $quot . $codeBase . $quot; // Add codebase value to tag
            $output .= " width=" . $quot . htmlspecialchars(strip_tags($args['width'])) . $quot; // Add width value to tag
            $output .= " height=" . $quot . htmlspecialchars(strip_tags($args['height'])) . $quot; // Add height value to tag
            $output .= " archive=" . $quot. $appletBinary . $quot. " >"; // Add archive value to tag
            $output .= $ggbSource;
 
            // Add code for  non-special parameters
            foreach($args as $par_name => $par_value) {
              if (! in_array(strtolower($par_name), $special_parameters)){
                $parameter = htmlspecialchars(strip_tags($par_name));
                $value = htmlspecialchars(strip_tags($par_value));
                $debug .= '<p>Allgemein: ' . $par_name . ' => ' . $par_value . '</p>' . $CRLF;
                if(strlen($value) > 0) {
                   $output .= '<param name="' . $parameter .'" value="' . $value . '">' . $CRLF;
                }
              }
            }
            $output .= '<param name="java_arguments" value="-Djnlp.packEnabled=true">'. $CRLF; // Use packed JAR if available
            // Close applet tag
            $output .= $noJavaText . $CRLF; // Message if Java is not installed
            $output .= "</applet>" . $CRLF; // The closing applet tag
        } else {
            $output = "<p>Error in MediaWiki extension (GeoGebra.php): <em>" . $error_message. "</em></p>" . $CRLF;
        }
        if ($printDebug == 'true') {
           $output .= '<p>' . $debug . '</p>' . $CRLF;
        }
        // Send the output to the browser
        return $output;
} // missing php end tag to avoid troubles.

