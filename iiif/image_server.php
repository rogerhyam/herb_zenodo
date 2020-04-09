<?php

require_once('functions.php');


// e.g. http://localhost:3100/iiif-i/MzU4ODI1OC9UT0xJLTIyNzQ5LUVTVC0wMS00LUExLTEwNA==/region/size/rotation/quality.format

// parse out that path describing the image request
// from api => {scheme}://{server}{/prefix}/{identifier}/{region}/{size}/{rotation}/{quality}.{format}
$matches = array();
if(!preg_match('/\/iiif-i\/([^\/]+)\/([^\/]+)\/([^\/]+)\/([^\/]+)\/([^\.]+)\.([a-z]+)$/',$_SERVER["REQUEST_URI"], $matches)){
    throw_badness('Invalid image URI' . $_SERVER["REQUEST_URI"]);
}else{
    list($path, $identifier, $region, $size, $rotation, $quality, $format) = $matches;
}

// get out of here if they are asking for unsupported features.
if($rotation != '0') throw_badness('Rotation other than 0 is not supported.');
if($quality != 'default') throw_badness('Only default quality is supported.');

$file_path_full = get_image_path();

// do we even have that file?
if(!file_exists($file_path_full)){
	throw_badness('Image does not exist.');
}

$image_props = get_image_properties();

// the size is the actual dimensions of the image to be returned
$size = explode(',', $size);
list($size_w, $size_h) = $size;

// the region of the original is specified at the scale of the whole image
// We are either asked for an x/y/w/h region or possibly 'full'
if($region == 'full'){

    // if it is full then we only support returning images of sizes that match the 
    // sizes of complete layers in the zoomify stack.
    for ($i=0; $i < count($image_props['zoomify_layers']); $i++) { 
        $zlayer = $image_props['zoomify_layers'][$i];
        //print_r($zlayer);
        if($zlayer['width'] == $size_w && $zlayer['height'] == $size_h){
            return_full_image($file_path_full, $i, $image_props);
            exit();
        }
    }

    // plus lets do some common thumbnails because these are often hard coded in viewers?
    // plus it makes it easy to give thumbnails without knowing image details.
    if(!$size_h){
        return_thumbnail($file_path_full, $size_w, 'width', $image_props);
    }

    if(!$size_w){
        return_thumbnail($file_path_full, $size_h, 'height', $image_props);
    }

    // got to here so they are not asking for a image size we understand.
    http_response_code(400);
    echo "Sorry: Can only handle full image requests of specific size.";
    exit;

}else{
    $region = explode(',', $region);
    list($region_x, $region_y, $region_w, $region_h) = $region;
}
// the scale factor is a whole number because we have specified 
// we only support scaling by different factors
// it can only be 0,1,2,4,8,16,32,64 - does it do 64?
$scale_factor = $region_w/$size_w;
$scale_factor = get_closest($scale_factor, array(1,2,4,8,16,32));
// openseadragon can ask for images that are less than a pixel high (it specifies a width but not a height)
// if it does this we tell it not to be so stupid
if($region_w / $scale_factor < 1 or $region_h / $scale_factor < 1){
        http_response_code(400);
        echo "Sorry: Can't handle requests for images less than 1 pixel in height or width";
        exit;
}

// zoomify works the other way around. Layer 0 tiles are lowest magnification (highest scale factor)
// get the closest zoomify layer 
$zoomify_layer = array_search($scale_factor, array_reverse($image_props['layers']));
$zoomify_layer++; // why do I need this?
// which zoomify column and row are we looking at?
// work out the size of the image at this zoom level
$zoomify_col = round(($region_x / $scale_factor) / 256);
$zoomify_row = round(($region_y / $scale_factor) / 256);
// we can get the magnification by comaring the width of the region with the width of the size asked for 
$tile_group = get_tile_group($image_props['zoomify_layers'], $zoomify_layer, $zoomify_col, $zoomify_row);
// example $uri = "http://data.rbge.org.uk/search/herbarium/scripts/getzoom3.php?path=$barcode.zip;file:/ImageProperties.xml&noCacheSfx=1544716865761";
$url = "$file_path_full/TileGroup$tile_group/$zoomify_layer-$zoomify_col-$zoomify_row.jpg";
header('Content-Type: image/jpeg');
readfile($url);



?>