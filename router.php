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
    if(preg_match('/^\/p\//', $iiif_path)){
        require_once('iiif/presentation_manifest.php');
    }

    // looking for image api services
    if(preg_match('/^\/i\//', $iiif_path)){

        if(preg_match('/info.json$/', $iiif_path)){
            require_once('iiif/image_info.php');
        }else{
            require_once('iiif/image_server.php');
        }

    }

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


// handle CETAF requests
$matches = array();
if (preg_match('/\/cetaf(.*)/', $_SERVER["REQUEST_URI"], $matches)) {
    echo "cetaf\n";
    echo $matches[1];
    return true;
}

// 

// no matches so continue with nothing
// only in dev this happens
return false;


?>