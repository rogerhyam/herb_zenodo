<?php

require_once('config.php');
require_once('iiif/functions.php');

/* 
Takes the Zenodo record id that have been stored in the database by the OAI harvester
and calls them to update or create the specimen in the cache


Later .. 

Auto tagging with family ending in aceae or 

Apiaceae=Umbelliferae
Arecaceae=Palmae 
Asteraceae=Compositae
Brassicaceae=Cruciferae
Clusiaceae=Guttiferae
Fabaceae=Leguminosae
Lamiaceae=Labiatae
Poaceae=Gramineae
*/

echo "Starting harvest.\n";

// check if there is another instance running
if(file_exists('harvest.pid')){
    $pid = file_get_contents('harvest.pid');
    echo "harvest.pid exists with process id $pid so stopping now";
    exit();
}else{
    file_put_contents('harvest.pid', getmypid());
}

$ops = getopt('l:');
if(isset($ops['l'])){
    echo "Limit set to: " . $ops['l'] . "\n";
    $limit = " LIMIT " . $ops['l'];
}else{
    echo "Limit not set.\n";
    $limit = "";
}

$result = $mysqli->query("SELECT zenodo_id FROM oai_changes ORDER BY change_noticed ASC $limit");

$total_rows = $result->num_rows;

echo "Found $total_rows to process.\n";

$row_count = 0;
while($row = $result->fetch_assoc()){
    import_specimen($row['zenodo_id']);
    //sleep(1); // zenodo will block us if we go too fast
    $row_count++;
    echo "$row_count of $total_rows\n";
}

unlink('harvest.pid');

echo "Finished.\n";

// --------- F U N C T I O N S ---------------

function import_specimen($record_id){

    global $mysqli;

    $factory = new \DanielKm\Zoomify\ZoomifyFactory;
    $zoomify = $factory();

    $dir_path = get_cache_path_for_specimen($record_id);
    $record_path = $dir_path . 'zenodo_record.json';
  
    $url = "https://zenodo.org/api/records/$record_id";
    echo "\tCalling Zenodo with : $url \n";
    $response = file_get_contents($url);
    $header = parseHeaders($http_response_header);
    
    if($header['reponse_code'] == '429'){
        echo "\tToo many connections - waiting a minute then will try next one.\n";
        sleep(60);
        return;
    }

    if($header['reponse_code'] != '200'){
        echo "\tSomethings not right, didn't get a 200 got a " . $header['reponse_code'] . ".\n";
        return;
    }

    $zenodo = json_decode($response);

    // here on in we deal with concept IDs and record IDs so we can track versions
    // The specimen ID is the concept ID. That is what we use in the CETAF_ID
    // https://data.herbariamundi.org/10.5281/zenodo.3588258

    // check dir exists
    if(!file_exists($dir_path)) mkdir($dir_path, 0777, true);

    // write the data record there
    // overwriting the last one if it was there
    file_put_contents($record_path, $response);

    // now let's create an image tile pyramid if it doesn't already exist

    foreach($zenodo->files as $file){

        // we are only interested in jpg files
        if($file->type != 'jpg') continue;

        $image_local_path =  $dir_path . $file->key;

        // if the image is already there we don't do it again
        if(file_exists($image_local_path)){
            echo "\tImage exists $image_local_path not downloading again.\n";
            continue;
        }

        echo "\tDownloading {$file->key}.\n";
        file_put_contents($image_local_path, fopen($file->links->self, 'r'));
        echo "\tGenerating zoomify for {$file->key}.\n";
        $result = $zoomify->process($image_local_path);
        echo "\tDone generating zoomify for {$file->key}.\n";

    }

    // take it off the do list
    $mysqli->query("DELETE FROM oai_changes WHERE zenodo_id = $record_id");
    if($mysqli->error){
        echo $mysqli->error;
    }else{
        echo "\tRemoved $record_id from do list.\n";
    }
}

function parseHeaders( $headers )
{
    $head = array();
    foreach( $headers as $k=>$v )
    {
        $t = explode( ':', $v, 2 );
        if( isset( $t[1] ) )
            $head[ trim($t[0]) ] = trim( $t[1] );
        else
        {
            $head[] = $v;
            if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) )
                $head['reponse_code'] = intval($out[1]);
        }
    }
    return $head;
}


?>