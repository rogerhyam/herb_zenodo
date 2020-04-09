<?php
    require_once('config.php');
?>
<html>
<head>
    <title>Herb Zenodo</title>
</head>
<body>
<div style="max-width: 40rem; margin: 1rem; padding: 1rem; border: solid gray 1px;">
<h1>Herb Zenodo</h1>
<p>
This site is a wrapper around the specimens that have been placed in the Zenodo Community 
"<a href="https://zenodo.org/communities/herbariamundi">Herbaria Mundi</a>".
It is does not have a user interface beyond this page and a simple rendering of each specimen.
It exists to provide web service wrappers around the Zenodo data so that the specimens
can appear in <a href="https://wwww.herbariamundi.org">Herbaria Mundi</a> and be consumed by other systems.
To search for and manipulate specimens please use <a href="https://wwww.herbariamundi.org">Herbaria Mundi</a>.
</p>

<h2>CETAF ID</h2>
<p>It provides CETAF ID compatible semantic web URIs for specimens. These take the form the Zendodo DOI prepended by https://data.herbariamundi.org/ e.g.</p>
<a href="https://data.herbariamundi.org/10.5281/zenodo.3617427"><code>https://data.herbariamundi.org/10.5281/zenodo.3617427</code></a>

<p>As per good semantic web practice when these URIs are resolved (a.k.a.dereferenced) a process of content negotiation
is results in a 303 redirect to either a simple, human readable HTML landing page or an RDF rendition of the data.</p>

<h2>IIIF for Images</h2>
<p>An <a href="https://iiif.io/" >International Image Interoperability Framework</a> manifest is provided
for each specimen. The manifest contains metadata from the specimen and references the images of the specimen.
The manifest URIs take the form:
</p>
<a href="https://data.herbariamundi.org/10.5281/zenodo.3617427"><code>https://data.herbariamundi.org/10.5281/zenodo.3617427</code></a>
<p>
An image service allows the images to be viewed at different resolutions and zoomed.
 </p>

<h2>Status</h2>
<p>
<?php
    if(file_exists('harvest.pid')){
        $pid = file_get_contents('harvest.pid');
        echo "<strong> Harvester is running with PID $pid since " . date ("d F Y H:i:s", filemtime('harvest.pid')) . " UTC</strong>";
    }else{
        echo "Harvester is inactive.";
    }
?>
</p>

<p>OAI Syncronisation last run: <strong><?php
echo file_get_contents('oai_from.txt');
?></strong></p>

<p>Record changes queued to be harvested:<strong>
<?php
    $result = $mysqli->query("SELECT COUNT(*) as n FROM oai_changes");
    $row = $result->fetch_assoc();
    echo number_format($row['n']);
?></strong></p>

<p>Specimen records cached: <strong>
<?php
$record_count = exec('find . -type f -name zenodo_record.json -print | wc -l');
echo number_format($record_count);
?></strong></p>

<p>Total JPEGS (including tiles) in cached: <strong>
<?php
$record_count = exec('find . -type f -name *.jpg -print | wc -l');
echo number_format($record_count);
?></strong></p>

<h2>Support</h2>
<p>This facility is hosted by the <a href="https://www.rbge.org.uk">Royal Botanic Garden Edinburgh</a>.
<br/>
For support please contact <a href="mailto:rhyam@rbge.org.uk">Roger Hyam</a>.</p>
</div>


</body>
</html>