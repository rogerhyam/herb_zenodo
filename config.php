<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// db credentials are kept here out of github
require_once('../herb_zenodo_secret.php');

// composer packages
require('vendor/autoload.php');

// URI of OMI-PMH end point at Zenodo to follow
// this is the community with all the specimens in it
define('ZENODO_OAI_PMH_URI', 'https://zenodo.org/oai2d');

// Where do we store IIIF data from Zenodo
// check permissions on this when installing
// end in slash
define('ZENODO_SPECIMEN_CACHE', 'cache/'); // dev

// create and initialise the database connection
$mysqli = new mysqli($db_host, $db_user, $db_password, $db_database);

// connect to the database
if ($mysqli->connect_error) {
  echo $mysqli->connect_error;
}

if (!$mysqli->set_charset("utf8")) {
  echo printf("Error loading character set utf8: %s\n", $mysqli->error);
}

?>