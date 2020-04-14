<?php

// returns the files modified since.

$days_back = (int)@$_GET['days_back'];
if(!is_int($days_back) || $days_back < 1){
    http_response_code (400);
    echo 'Bad request: Only positive integer of days_back param please';
    exit;
}

$out = array();
$out['days_back'] = $days_back;
$out['request_time'] = date('c');

$files = array();
$command = "find cache -mtime -{$days_back} -d 4 -name zenodo_record.json -print";
$out['command'] = $command; 
exec($command, $files);

// turn the cache path back into a CETAF ID without having to open any files.
$cetaf_ids = array();
foreach($files as $f){
    $matches = array();
    preg_match('/cache\/([0-9]{4})\/([0-9]{4})\/([0-9]{4})\/zenodo_record.json/', $f, $matches);
    $n = (int) ($matches[1] . $matches[2] . $matches[3]);
    $cetaf_id = "https://data.herbariamundi.org/10.5281/zenodo.". $n;

    $cetaf_ids[] = $cetaf_id;

}

$out['ids'] = $cetaf_ids;

header('Content-Type: application/json');
echo JSON_encode($out);

?>

