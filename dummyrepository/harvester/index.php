<?php
include("classes/cls_harvester.php");
if(isset($_REQUEST['url'])){
    $url = $_REQUEST['url'];
    $response = array('query'=>$url,'metadata'=>[]);
    if(filter_var($url, FILTER_VALIDATE_URL)){
        try {
            $h = new catalog_harvester($url);
            $response['metadata'] = $h->catalog_metadata;
            $response['logging'] = $h->logging;
        }catch (Exception $e){
            $response['logging'][$url][] = array('error'=>'Catalogue harvesting failed: '.$e);
        }
    }else{
        $response['logging'][$url][] = array('error'=>'Invalid URI: '.(string)$url);
    }
    header('Content-Type: application/json; charset=utf-8');
    print(json_encode($response));
}
