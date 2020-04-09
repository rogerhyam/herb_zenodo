<?php

require_once('functions.php');

$base_uri = get_base_uri();
$specimen_id = get_specimen_id();
$metadata = get_specimen_metadata();

$out = new stdClass();
$out->context = array("http://www.w3.org/ns/anno.jsonld","http://iiif.io/api/presentation/3/context.json");
$out->id = $base_uri . "/manifest";
$out->type = "Manifest";
$out->label = create_label( $metadata->metadata->title);

$out->summary = new stdClass();
$out->summary = array($metadata->metadata->description);
$out->viewingDirection = "left-to-right";

// the thumbnail for the manifest uses the first image
foreach($metadata->files as $file_data){
    if($file_data->type != 'jpg') continue;
    $image_base_uri = get_image_uri($specimen_id, $file_data);
    $out->thumbnail = array();
    $out->thumbnail[] = new stdClass();
    $out->thumbnail[0]->id = $image_base_uri . '/full/,200/0/default.jpg';
    $out->thumbnail[0]->type = "Image";
    $out->thumbnail[0]->service = array();
    $out->thumbnail[0]->service[0] = new stdClass();
    $out->thumbnail[0]->service[0]->id = $image_base_uri;
    $out->thumbnail[0]->service[0]->type = "ImageService3";
    $out->thumbnail[0]->service[0]->profile = "level0";
    break; // just do the one
}

$out->rights = $metadata->metadata->license->id; // FIXME: map this to Zenodo vocabulary?
$out->requiredStatement = create_key_value_label("Original Publication", $metadata->doi);

// work through all the files and add them as canvases
foreach($metadata->files as $file_data){

    $image_name = pathinfo($file_data->key, PATHINFO_FILENAME);
    $path = preg_replace('/^([0-9]{4})([0-9]{4})([0-9]{4})/', '$1/$2/$3/', str_pad($specimen_id, 12, '0', STR_PAD_LEFT));
    $image_path = ZENODO_SPECIMEN_CACHE . $path  . $image_name . '_zdata';

    $canvas_base_uri = "$base_uri#$image_name";
    $image_base_uri = get_image_uri($specimen_id, $file_data);
    $props = get_image_properties(  $image_path );

    $canvas = new stdClass();
    $out->items = array($canvas);
    $canvas->id = "$canvas_base_uri-canvas";
    $canvas->type = "Canvas";
    $canvas->label = create_label($file_data->key);
    $canvas->height = $props['height'];
    $canvas->width = $props['width'];
    // annotation page
    $canvas->items = array();
    $image_anno_page = new stdClass();
    $canvas->items[] = $image_anno_page;
    $image_anno_page->id = "$canvas_base_uri-annotation_page";
    $image_anno_page->type = "AnnotationPage";
    // annotation
    $image_anno = new stdClass();
    $image_anno_page->items = array($image_anno);
    $image_anno->id = "$canvas_base_uri-annotation";
    $image_anno->type = "Annotation";
    $image_anno->motivation = "Painting";
    $image_anno->body = new stdClass();
    $image_anno->body->id = "$image_base_uri/info.json";
    $image_anno->body->type = "Image";
    $image_anno->body->format = "image/jpeg";
    $service = new stdClass();
    $service->id = $image_base_uri;
    $service->type = "ImageService3";
    $service->profile = "level0";
    $image_anno->body->service = array($service);
    $image_anno->body->height = $props['height'];
    $image_anno->body->width = $props['width'];
    $image_anno->target = "$canvas_base_uri";

}

//$out->meta = $metadata;


//print_r($out);
$json = json_encode( $out, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES );
// total hack to add the @ to the context attribute (not acceptable in php)
$json = str_replace('"context":','"@context":', $json);
header('Content-Type: application/json');
echo $json;

?>