<?php

    // curl -I -H 'Accept: application/rdf+xml' http://127.0.0.1:3200/10.5281/zenodo.3614905
    // curl -I  http://127.0.0.1:3200/10.5281/zenodo.3614905

    // handles the content negotiation for the cetaf_ids
    $all_headers = getallheaders();

    // no accept header assume html
    if(!isset($all_headers['Accept'])){
        send_303_redirect($zenodo_doi, 'html');
    }else{
        $accepts = explode(',', $all_headers['Accept']);

        foreach($accepts as $accept){

            // we work in order and ignore any weighting
            $a = explode(';', $accept)[0];

            switch ($a) {
                case 'text/html':
                    send_303_redirect($zenodo_doi, 'html');
                break;
                case 'application/xhtml+xml':
                    send_303_redirect($zenodo_doi, 'html');
                    break;
                case 'application/rdf+xml':
                    send_303_redirect($zenodo_doi, 'rdf');
                    break;
                default:
                    send_303_redirect($zenodo_doi, 'html');
                    break;
            }
        }

    }

    function send_303_redirect($zenodo_doi, $format){
       header("HTTP/1.1 303 See Other");
       header("Location: /$zenodo_doi/$format");
       exit;
    }

?>

