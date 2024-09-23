<?php
include("classes/cls_harvester.php");
#https://lindat.cz/ rdfa
$cts = file('data/cts.txt');
$h = new catalog_harvester('http://wdc-climate.de');
print_r($h->root_type);

foreach($cts as $ct){
    print($ct);
    $h = new catalog_harvester(trim($ct));
    print_r($h->root_type);
    print_r($h->catalog_metadata);
}
