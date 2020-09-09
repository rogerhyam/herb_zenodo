<?php 

    require_once('config.php');
    require_once('iiif/functions.php');

    echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";

    // load the record file 
    $cache_dir = get_cache_path_for_specimen($zenodo_id);
    $cache_url = '/' . $cache_dir;
    $record_json = file_get_contents($cache_dir . 'zenodo_record.json');
    $record = json_decode($record_json);
    $cetaf_id = "https://data.herbariamundi.org/" . $record->doi;

?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns:xs="http://www.w3.org/2001/XMLSchema"
    xmlns:dwc="http://rs.tdwg.org/dwc/terms/"
    xmlns:dwcc="http://rs.tdwg.org/dwc/curatorial/"
    xmlns:dc="http://purl.org/dc/terms/"
    xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#"
    xmlns:owl ="http://www.w3.org/2002/07/owl#"
    xmlns:dwciri="http://rs.tdwg.org/dwc/iri/"
    >
<!--This is metadata about this metadata document-->
<rdf:Description
    rdf:about="<?php echo $cetaf_id; ?>/rdf">
    <dc:creator>Herb Zenodo PHP Script</dc:creator>
    <dc:created><?php echo date('c'); ?></dc:created>
    <dc:hasVersion rdf:resource="<?php echo $cetaf_id; ?>/html" />
</rdf:Description>
    
<!--This is metadata about this specimen-->
<rdf:Description rdf:about="<?php echo $cetaf_id; ?>">
    <!-- Assertions made in simple Dublin Core -->
    <dc:publisher rdf:resource="https://data.herbariamundi.org" />
    <dc:title><?php echo htmlspecialchars( $record->metadata->title  )?></dc:title>
    <dc:description><![CDATA[
        <?php echo $record->metadata->description ?>
    ]]></dc:description>
    <?php
    $recorded_by = array();
    foreach($record->metadata->creators as $creator){
        echo "<dc:creator>{$creator->name}</dc:creator>";
        $recorded_by[] = $creator->name;
    }

    if(isset($record->metadata->publication_date)){
        echo "<dc:created>{$record->metadata->publication_date}</dc:created>";
    }

    ?>
    <!-- Assertions based on Darwin Core -->
    <dwc:sampleID><?php echo $cetaf_id ?></dwc:sampleID>
    <dc:modified><?php echo $record->metadata->publication_date ?></dc:modified>
    <dwc:earliestDateCollected><?php echo $record->metadata->publication_date ?></dwc:earliestDateCollected>
    <dwc:basisOfRecord>Specimen</dwc:basisOfRecord>
    <dc:type>Specimen</dc:type>
    <dwc:institutionCode>Zenodo</dwc:institutionCode>
    <dwc:collectionCode>Zenodo</dwc:collectionCode>
    <dwc:catalogNumber><?php echo htmlspecialchars($record->doi) ?></dwc:catalogNumber>
    <dwc:recordNumber><?php echo htmlspecialchars($record->id) ?></dwc:recordNumber>
    <dwc:recordedBy><?php echo htmlspecialchars(implode('; ',$recorded_by ))?></dwc:recordedBy>

    <?php
        
        // pull the family names out of the title and description
        $families = get_families( $record->metadata->title . ' ' . $record->metadata->description);
        foreach($families as $family){
            echo "<dwc:family>".htmlspecialchars($family)."</dwc:family>";
        }

        // pull the genus names out of the title only (description has too many false positives)
        $genera = get_genera( $record->metadata->title );
        foreach($genera as $genus){
            echo "<dwc:genus>".htmlspecialchars($genus). "</dwc:genus>";
        }

        // decode the subjects
        foreach($record->metadata->subjects as $subject){
            if($subject->scheme != 'url')continue;
            echo "<dc:subject rdf:resource=\"{$subject->identifier}\"/>";
        }
    
        /*
        FIXME - would be lovely to get these from somewhere!
        <geo:lat>3.083333</geo:lat>
        <geo:long>10.416667</geo:long>
        <dwc:scientificName>Duboscia viridiflora (K.Schum.) Mildbr.</dwc:scientificName>
        
        <dwc:genus>Duboscia</dwc:genus>
        
        <dwc:specificEpithet>viridiflora</dwc:specificEpithet>
        <dwc:higherGeography>Tropical Africa</dwc:higherGeography>
        <dwc:locality>Bipinde</dwc:locality>
        <dwc:decimalLongitude>10.416667</dwc:decimalLongitude>
        <dwc:decimalLatitude>3.083333</dwc:decimalLatitude>
        <dwc:country>CM</dwc:country>
        <dwc:countryCode>CM</dwc:countryCode>
        */

    ?>
     	
	<!-- Images associated with the specimen -->
<?php
    foreach($record->files as $file){
        if($file->type != 'jpg') continue;

        // url to full size image through iiif server
        $image_url = "https://data.herbariamundi.org/iiif-i/" . $record->doi . "/full/max/0/default.jpg";
?>
    <dwc:associatedMedia rdf:resource="<?php echo $image_url ?>" />
    <dc:relation>
        <rdf:Description  rdf:about="<?php echo $image_url ?>" >
            <dc:identifier rdf:resource="<?php echo $image_url ?>" />
            <dc:type rdf:resource="http://purl.org/dc/dcmitype/Image" />
            <dc:subject rdf:resource="<?php echo $cetaf_id ?>" />
            <dc:format>image/jpeg</dc:format>
            <dc:description xml:lang="en">Image of herbarium specimen</dc:description>
        </rdf:Description>
    </dc:relation>
<?php
    }
?>

	<!-- IIIF resources associated with the specimen -->
<?php
    $manifest_uri = "https://data.herbariamundi.org/iiif-p/" . $record->id . "/manifest";
?>
    <dc:relation>
	<rdf:Description  rdf:about="<?php echo $manifest_uri ?>" >
		<dc:identifier rdf:resource="<?php echo $manifest_uri ?>" />
		<dc:type rdf:resource="http://iiif.io/api/presentation/3#Manifest" />
		<dc:subject rdf:resource="<?php echo $cetaf_id ?>" />
		<dc:format>application/ld+json</dc:format>
		<dc:description xml:lang="en">A IIIF resource for this specimen.</dc:description>
	</rdf:Description>
</dc:relation>
        
    </rdf:Description>
    
</rdf:RDF>    
   
<?php

function get_families($text){

    $families = array();
    $words = str_word_count($text, 1);
    
    foreach($words as $word){

        if(preg_match('/^[A-Z][a-z]+aceae$/', $word)){
            $families[] = $word;
            continue;
        }

        // 8 families seen as exceptions
        switch ($word) {
            case 'Umbelliferae': 
                $families[] = 'Apiaceae';
                break;
            case 'Palmae':
                $families[] = 'Arecaceae';
                break;
            case 'Compositae':
                $families[] = 'Asteraceae';
                break;
            case 'Cruciferae':
                $families[] = 'Brassicaceae';
                break;
            case 'Guttiferae':
                $families[] = 'Clusiaceae';
                break;
            case 'Leguminosae':
                $families[] = 'Fabaceae';
                break;
            case 'Labiatae':
                $families[] = 'Lamiaceae';
                break;
            case 'Gramineae':
                $families[] = 'Poaceae';
                break;
        }
 

    }

    return array_unique($families);

}

function get_genera($text){

    global $mysqli;

    $genera = array();
    $words = str_word_count($text, 1);
    $words = array_unique($words);

    foreach($words as $word){

        // we assume capitalised first letter - no numbers
        if(!preg_match('/^[A-Z][a-z]+$/', $word)) continue;

        // longer than two unless it is the trivial pusuit genera
        if(strlen($word) < 3 && $word != 'Aa' && $word != 'Io') continue;
        
        $sql = "select * from genus_name where genus = '$word'";
        $r = $mysqli->query($sql);

        if($r->num_rows > 0){
            $genera[] = $word;
        }

    }

    return $genera;

}
?>
    
    
