<?php

require_once('config.php');

/* 

This pulls changed record ids into a database table so that zenodo_harvest can then call the api to get data for them.

We don't actually bother parsing the OAI response as it doesn't contain the data we want beyond the record key.
Protocol description here

http://www.openarchives.org/OAI/openarchivesprotocol.html#ListRecords

FIXME: handle deleting records ?

Example calls
    
https://zenodo.org/oai2d?verb=ListRecords&set=user-bravo&metadataPrefix=oai_dc?verb=ListRecords&set=user-bravo&metadataPrefix=oai_dc&from=2000-01-01T01:01:01Z
https://zenodo.org/oai2d?verb=ListRecords&set=user-bravo&metadataPrefix=oai_dc&from=2020-01-22T14:42:37+00:00

https://zenodo.org/oai2d?verb=ListRecords&resumptionToken=.eJwVjbEOgjAYhN_ln9G0CBFJGETt4CSKAi6m0kIaaNq0VUwM72698fsud1-wnDNI0TLCqyRcRSgJ0QZjHK8DsK1R4_gQ3sO-JvheH-OiioezvKEm7PPtP7kjH1RUTzm688Gh4qovTJJdWea6HHTU4CO5T1kGAWjac0jDAIaJmt5C-vXnzm-_LDeLp6Fv5VudUdIzzY0U1oo390xyRxl19GR4Jz7eKioerIV5_gGZSjyS.Xim_Fw.Ek1UJHVXc-KZp4_EbwMMLfDEI30
https://zenodo.org/oai2d?verb=ListRecords&resumptionToken=.eJwVjT0LgzAYhP_LO9ui1oAKDrWtg1O1tn4sEjVK0GBIUhXE_970loN7jrsdJCEd-ObZQRcPeci1LcfV7hkgWzFPU001hnsRWVURoyRHY8o-ZmkP4fWvUEXb5OYNm1T6UGby5q-ORbcsC3k2cqe04qhagwAM4Hgg4NsGjCsWgwR_199Kb38lEadG4GXWrV7MTGecCEalpAvRGSMKd1jhpyA93TSdMa27Fo7jB5puPKU.Xim96Q.rnYvaJCeqcKBPJ9SPMeOK9VDgaQ


*/

$resumptionToken = true;
$date_file_name = 'oai_from.txt';

// if we are triggered with -a we start from the beginning
$ops = getopt('a:');
if(isset($ops['a'])) unlink($date_file_name);

// prime the start date
if(!file_exists($date_file_name)){
    file_put_contents($date_file_name, '2000-01-01T01:01:01Z');
}

while($resumptionToken){    

    $matches = array();

    $start_date = file_get_contents($date_file_name);

    if(is_string($resumptionToken)){
        // e.g. https://zenodo.org/oai2d?verb=ListRecords&resumptionToken=.eJwVjbEOgjAYhN_ln9G0xBIkYRC1A5MgirKYSgtp ...
        $url = ZENODO_OAI_PMH_URI . "?verb=ListRecords&resumptionToken=$resumptionToken";
    }else{
        $url = ZENODO_OAI_PMH_URI . '?verb=ListRecords&set=user-bravo&metadataPrefix=oai_dc&from=' . $start_date;
    }
   
    echo "$url\n";
    
    $response = file_get_contents($url);

    preg_match_all('/https:\/\/zenodo.org\/record\/([0-9]+)/', $response, $matches, PREG_PATTERN_ORDER);

    // if we don't have any record ids then we are done here
    if(count($matches[1]) == 0){
        echo "\nNo more records modified since:  $start_date \n"; 
        break;
    } 

    // work through the matching records
    foreach($matches[1] as $record_id){
        $mysqli->query("INSERT INTO oai_changes (zenodo_id, change_noticed) VALUES ('$record_id', now()) ON DUPLICATE KEY UPDATE change_noticed = now();");
    }

    // if there is a resumptionToken then we go around a gain.
    // if not we break
    $response = str_replace(array("\n", "\r"), '', $response); // just incase it is split cross lines
    $token_matches = array();
    if(preg_match('/<resumptionToken [^>]*>(.*)<\/resumptionToken>/', $response, $token_matches)){
        $resumptionToken = trim($token_matches[1]);
        echo "\nResumption token set so continuing: $resumptionToken \n";
        echo "\nResumption token set so continuing: $token_matches[0] \n";
    }else{
        echo "\nNo resumption token set so stopping. \n";
        $resumptionToken = false;
    }

}

// update the from date to now.
file_put_contents($date_file_name, date( 'c' ));

?>