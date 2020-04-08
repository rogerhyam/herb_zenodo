<?php
    require_once('iiif/functions.php');

    // load the record file 
    $cache_dir = get_cache_path_for_specimen($zenodo_id);
    $cache_url = '/' . $cache_dir;
    $record_json = file_get_contents($cache_dir . 'zenodo_record.json');
    $record = json_decode($record_json);

    //echo 'HTML Rendering of ' . $zenodo_id;
?>
<html>
<head>
    <title>Herb Zenodo: <?php echo $record->metadata->title ?></title>
    <link rel="meta" type="application/rdf+xml" href="/10.5281/zenodo.<?php echo $zenodo_id ?>/rdf" />
</head>
<body>
<div style="max-width: 40rem; margin: 1rem; padding: 1rem; border: solid gray 1px;">
<a href="/"><h1>Herb Zenodo</h1></a>
<hr/>
<h2><?php echo $record->metadata->title ?></h2>
<p>This specimen is better viewed using one of these links:</p>
<ul>
    <li><a href="https://www.herbariamundi.org/#<?php echo $record->conceptdoi ?>">Herbaria Mundi</a> - an environment for sharing and browsing herbarium specimen images.</li>
    <li>
    <a href="<?php echo $record->links->conceptdoi ?>">Zenodo</a> - The original, archived data.
    <a href="<?php echo $record->links->conceptdoi ?>"><img style="vertical-align: middle;" src="<?php echo $record->links->conceptbadge ?>"/></a> 
    </li>
    <li><a href="/iiif/p/<?php echo $record->conceptdoi ?>/manifest">IIIF Manifest</a> - for loading into any IIIF viewer.</li>
</ul>
<hr/>
<h3>Images</h3>
FIXME IIIF to get thumbs of these
<?php

    foreach($record->files as $file){
        if($file->type != 'jpg') continue;
        echo "<ul>";
        echo "<li>$file->key</li>";
        echo "<li><a href=\"$cache_url$file->key\">Download cached version.</a></li>";
        echo "<li><a href=\"{$file->links->self}\">Download from Zenodo.</a></li>";
        echo "</ul>";
    }

?>

<hr/>
<h3>Description in Zenodo</h3>
<?php echo $record->metadata->description ?>
<hr/>


<pre>
<?php
   // print_r($record);
?>
</pre>
</div>
</body>
</html>