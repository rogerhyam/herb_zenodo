<?php

require_once('config.php');

/*
    .htaccess routes the following uri patterns here
    /iiif/....
    //10.5281/zenodo.  ...
*/

// handling /iiif
$matches = array();
if (preg_match('/\/iiif(.*)/', $_SERVER["REQUEST_URI"], $matches)) {
    $iiif_path = $matches[1];

    // looking for presenation api services
    if(preg_match('/^-p\//', $iiif_path)){

        // if it doesn't end in manifest redirect them to the manifest
        if(preg_match('/\/manifest$/', $iiif_path)){
            require_once('iiif/presentation_manifest.php');
        }else{
            $protocol = $_SERVER['HTTPS'] ? 'https://' : 'http://';
            $man_uri = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"];
            if(preg_match('/\/$/', $man_uri)){
                $man_uri .= 'manifest';
            }else{
                $man_uri .= '/manifest';
            }
            header("HTTP/1.1 303 See Other");
            header("Location: $man_uri");   
        }

        return true;
    }

    // looking for image api services
    if(preg_match('/^-i\//', $iiif_path)){

        if(preg_match('/info.json$/', $iiif_path)){
            // they want the image info
            require_once('iiif/image_info.php');
        }elseif(preg_match('/\.jpg$/', $iiif_path)){
            // they are asking for a jpeg
            require_once('iiif/image_server.php');
        }else{
            // Don't know what they want.
            // redirect to the json
            $protocol = $_SERVER['HTTPS'] ? 'https://' : 'http://';
            $info_uri = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"];
            if(preg_match('/\/$/', $info_uri)){
                $info_uri .= 'info.json';
            }else{
                $info_uri .= '/info.json';
            }
            header("HTTP/1.1 303 See Other");
            header("Location: $info_uri");     
        }

        return true;

    }

    // not defined presentation or image API so display welcome page.
    require_once('iiif/welcome.php');

    // end routing
    return true;
}

// handle cetaf id requests
// https://data.herbariamundi.org/10.5281/zenodo.3614905
$matches = array();
if (preg_match('/\/(10.5281\/zenodo\.([0-9]+)[.]*(.*))/', $_SERVER["REQUEST_URI"], $matches)) {
    
    $zenodo_doi = $matches[1];
    $zenodo_id = $matches[2];
    $format = $matches[3];
    
    // we load a file to handle that format
    if(!$format) $format = 'redirect';
    $path = "cetaf/$format.php";
    if(file_exists($path)){
        require_once("cetaf/$format.php");
    }else{
        header("HTTP/1.1 404 Not Found");
        echo "Format not found";
    }
    
    return true;
}

// no matches so continue with nothing
// only in dev this happens
return false;


?>