<?php

require_once('config.php');

// checks validity of harvested zenodo specimens

// get a list of all of the specimens with local manifests uris set

$response = $mysqli->query("SELECT * FROM specimen 
        WHERE cetaf_id_normative 
        LIKE '//data.herbariamundi.org/10.5281/zenodo%' 
        AND iiif_local_manifest_uri IS NOT NULL");

while($row = $response->fetch_assoc()){
    echo $row['iiif_local_manifest_uri'] . "\n";
}



?>