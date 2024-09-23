<?php
include("classes/cls_harvester.php");
$_REQUEST['url'] ='https://dummyrepository.org';
if(isset($_REQUEST['url'])){
    $url = $_REQUEST['url'];
    $response = array('query'=>$url);
    if(filter_var($url, FILTER_VALIDATE_URL)){
        $h = new catalog_harvester($url);
        #print_r($h->root_type);

        $response['metadata'] = $h->catalog_metadata;
        $response['logging'] = $h->logging;
    }else{
        $response['logging'][] = array('error'=>'Invalid URI: '.(string)$url);
    }
    header('Content-Type: application/json; charset=utf-8');
    print(json_encode($response));
}
