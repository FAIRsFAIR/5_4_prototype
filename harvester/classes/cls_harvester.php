<?php
require 'vendor/autoload.php';
include("cls_signposting.php");
error_reporting(E_ERROR );
class catalog_harvester
{
    function http_get($url){
        $this->logging[$url] = array();
        $body = $header = $status = null;
        $ch = curl_init();
        try {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headers = substr($response, 0, $header_size);
            foreach (explode(PHP_EOL, $headers) as $hl) {
                if (strpos($hl, ': ') !== False) {
                    $hp = explode(': ', $hl);
                    $header[$hp[0]] = $hp[1];
                }
            }
            $body = substr($response, $header_size);
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                $this->logging[$url][]= array('error'=>'HTTP GET CURL error: ',  $error_msg);
            }
            $this->logging[$url][]= array('info'=>'Successfully performed GET request on: '.$url);
        }catch (Exception $e) {
            $this->logging[$url][]= array('error'=>'HTTP GET exception: ',  $e->getMessage());
        }
        return (object) ["body"=> $body, "header"=>$header, "status"=>$status];
    }

    function __construct($catalog_url)
    {
        $this->logging = [];
        #$this->root_type = [];
        $this->catalog_metadata = [];
        $this->catalog_url = $catalog_url;
        $this->catalog_html = null;
        $this->metadata_standards =[];
        $this->load_metadata_standards();
        if(strpos($this->catalog_url, 'http') === 0){
            $res = $this->http_get($this->catalog_url);
            if(isset($res->body)){
                $this->catalog_html = $res->body;
                $this->catalog_header = $res->header;
            }
            $sph = new signposting_helper($res);
            $ejson = $this->get_embedded_jsonld($this->catalog_html);
            $embmetadata = $this->get_catalog_metadata($ejson,$this->catalog_url);
            if(sizeof($embmetadata)>0){
                $embmetadata['source'] = 'embedded';
                $embmetadata['format'] = 'jsonld';
                $this->catalog_metadata[] = $embmetadata;
                $this->logging[$this->catalog_url][]= array('success'=>'Found embedded metadata at : '.  $this->catalog_url);
            }

            foreach($sph->get_signposting_link('describedby') as $metalink){

                $sjson = $this->get_signposting_jsonld($metalink);
                $suri = $metalink['url'];
                $this->logging[$this->catalog_url][]= array('info'=>'Found signposting link: '.  $suri);
                $signmetadata = $this->get_catalog_metadata($sjson,$suri);
                if(sizeof($signmetadata)>0){
                    $signmetadata['source'] = 'signposting';
                    $signmetadata['format'] = 'jsonld';
                    $this->catalog_metadata[] = $signmetadata;
                    $this->logging[$suri][]= array('success'=>'Found signposting metadata at : '.  $suri);
                }

            }
            $rdag = $this->get_rdfa_data();
            $rdfa_metadata = $this->get_catalog_metadata($rdag,$this->catalog_url,'rdfa');

            if(sizeof($rdfa_metadata)>0){
                $rdfa_metadata['source']='embedded';
                $rdfa_metadata['format']='rdfa';
                $this->catalog_metadata[] = $rdfa_metadata;
                $this->logging[$this->catalog_url][]= array('success'=>'Found RDFa metadata at : '.  $this->catalog_url);

            }
        }
    }
    function get_rdfa_data(){
        $jg = null;
        try{
            $jg = new \EasyRdf\Graph($this->catalog_url,$this->catalog_html,'rdfa');
            if(!$jg->isEmpty()){
                $this->logging[$this->catalog_url][]= array('info'=>'Found embedded RDFa, trying to ingest.. ');
            }
        }catch (Exception $e) {
            $this->logging[$this->catalog_url][]= array('error'=>'RDFa loading exception: ',  $e->getMessage());
        }
        return $jg;
    }

    function get_graph_root_types($g){
        $rootcand=[];
        $gtypes = $g->all($g->getUri(),'rdf:type');
        foreach($gtypes as $gtype){
            $rootcand[]=(string)$gtype;
        }
        if(sizeof($rootcand)==0) {
            foreach ($g->resources() as $to) {
                if ($to->hasProperty('rdf:type')) {
                    $revprops = $to->reversePropertyUris();
                    if (sizeof($revprops) == 0) {
                        $rootcand = $to->types();
                    }
                }
            }
        }
        return $rootcand;
    }

    function load_metadata_standards(){
        $ms = file_get_contents("./data/metadata_standards.json");
        $mjsn = json_decode($ms, true);
        foreach($mjsn as $mk=>$mm){
            foreach ($mm['urls'] as $mu){
                $this->metadata_standards[$mu] = array('id'=>$mk,'title'=>$mm['title']);
            }
        }
    }

    function get_signposting_jsonld($signposting_record){
        $jsonld = '';
        if(isset($signposting_record['type'])){
            $url = $signposting_record['url'];
            $this->logging[$signposting_record['url']][]= array('info'=>'Found valid signposting link, trying to load : '.$signposting_record['url']);
            $content = $this->http_get($url);
            try {
                $djson = json_decode($content->body, true);
                #$this->root_type[$url.'#jsonld'] = $djson["@type"];
                $jsonld =json_encode($djson);
            }catch (Exception $e) {
                $this->logging[$url][]= array('error'=>'Signposting JSON-LD loading exception: ',  $e->getMessage());
            }
        }
        return $jsonld;
    }

    function get_catalog_metadata($ginput, $guri=null,$format='jsonld'){
        $this->logging[$guri][]= array('info'=>'Trying to parse '.strtoupper($format).' catalogue metadata from URI: '.$guri);
        #$metadata=['root_type'=>null];#root of graph types
        if(is_string($ginput) | is_a($ginput,'\EasyRdf\Graph')){
            \EasyRdf\RdfNamespace::set('dct', 'http://purl.org/dc/terms/');
            \EasyRdf\RdfNamespace::set('foaf', 'http://xmlns.com/foaf/0.1/');
            \EasyRdf\RdfNamespace::set('dcat', 'http://www.w3.org/ns/dcat#');
            \EasyRdf\RdfNamespace::set('schema', 'http://schema.org/');
            \EasyRdf\RdfNamespace::set('vcard', 'http://www.w3.org/2006/vcard/ns#');
            \EasyRdf\RdfNamespace::set('premis', 'http://www.loc.gov/premis/rdf/v3/');
            \EasyRdf\RdfNamespace::set('dqv', 'http://www.w3.org/ns/dqv#');
            \EasyRdf\RdfNamespace::set('oa', 'http://www.w3.org/ns/oa#');
            try{
                if(is_string($ginput)) {
                    $this->logging[$guri][]= array('info'=>'Try to parse '.strtoupper($format).' string as  RDF Graph');
                    $jg = new \EasyRdf\Graph($guri, $ginput, $format);

                }
                else{
                    $this->logging[$guri][]= array('info'=>'Assuming input is '.strtoupper($format).' RDF Graph');
                    $jg = $ginput;
                }
                if(is_a($jg,'\EasyRdf\Graph')) {
                    /*if(isset($this->root_type[$guri.'#'.$format])){
                        $metadata['root_type'] = $this->root_type[$guri.'#'.$format];
                    }*/
                    $this->logging[$guri][]= array('info'=>'Successfully parsed '.strtoupper($format).' string as  RDF Graph');
                    $root_types = $this->get_graph_root_types($jg);
                    if(sizeof($root_types)>0){
                        $metadata['root_type'] = $root_types;
                    }
                    $catalogs = ($jg->all('dcat:Catalog', '^rdf:type') +
                        $jg->all('schema:DataCatalog', '^rdf:type'));
                    if(sizeof($catalogs) > 0){
                        $this->logging[$guri][]= array('success'=>'Found catalogue info (dcat:Catalog, schema:DataCatalog) in '.strtoupper($format).' graph');
                    }else{
                        $this->logging[$guri][]= array('warning'=>'Could not find catalogue info (dcat:Catalog, schema:DataCatalog) in '.strtoupper($format).' graph');
                    }
                    foreach ($catalogs as $catalog) {
                        $metadata['resource_types'] = $jg->types($catalog);
                        $metadata['title'] = (string)$catalog->get('dct:title|schema:name');
                        $metadata['description'] = (string)$catalog->get('dct:description|schema:description|schema:disambiguatingDescription');
                        $metadata['subject'] = [];
                        $subjects = $catalog->all('dcat:theme|dct:subject|schema:keywords');
                        foreach ($subjects as $subject) {
                            if (!is_a($subject, 'EasyRdf\Literal')) {
                                $subject = (string)$subject->get('schema:termCode|schema:name');
                            } else {
                                $subject = (string)$subject;
                            }
                            $metadata['subject'][] = $subject;
                        }
                        $metadata['language'] = (string)$catalog->get('dct:language|schema:inLanguage|schema:language');
                        $metadata['url'] = (string)$catalog->get('dct:identifier|foaf:homepage|schema:url');
                        $publishers = $catalog->all('dct:publisher|schema:publisher');
                        $metadata['publisher'] = [];
                        $metadata['country'] = [];
                        foreach ($publishers as $publisher) {
                            $metadata['publisher'][] = (string)$publisher->get('foaf:name|schema:name');
                            if ($publisher->get('vcard:country-name')) {
                                $metadata['country'][] = (string)$publisher->get('vcard:country-name');
                            }
                            if ($publisher->get('schema:address/schema:addressCountry')) {
                                $metadata['country'][] = (string)$publisher->get('schema:address/schema:addressCountry');
                            }
                        }
                        $contactpoint = $catalog->get('schema:contactPoint|dcat:contactPoint');
                        if ($contactpoint) {
                            if (!is_a($contactpoint, 'EasyRdf\Literal')) {
                                if ($contactpoint->get('schema:url|vcard:hasURL|vcard:hasEmail|vcard:hasTelephone')) {
                                    $contactpoint = $contactpoint->get('schema:url|schema:email|schema:telephone|vcard:hasURL|vcard:hasEmail|vcard:hasTelephone');
                                }
                            }
                            $metadata['contactpoint'] = (string)$contactpoint;
                        }
                        $metadata['accessterms'] = (string)$catalog->get('dct:accessRights|dct:rights|schema:conditionsOfAccess');
                        $metadata['license'] = (string)$catalog->get('dct:license|schema:license');
                        $qualityinfo = $catalog->get('dqv:hasQualityAnnotation|schema:hasCertification');
                        $qualityissuer = $qualityurl = null;
                        $metadata['certification'] = [];
                        if (isset($qualityinfo)) {
                            $qualityurl = (string )$qualityinfo->get('oa:hasBody|schema:url');
                            $qualityissuer = $qualityinfo->get('dct:creator');
                            if (!isset($qualityissuer))
                                $qualityissuer = $qualityinfo->get('schema:issuedBy/schema:name');
                            $metadata['certification'] = array('url' => $qualityurl, 'issuer' => (string )$qualityissuer);
                        }
                        $metadata['compliance'] = [];
                        $apis = array_merge(
                            $catalog->all('schema:offers/schema:itemOffered'),
                            $catalog->all('dcat:service'));
                        foreach ($apis as $api) {
                            if (in_array(explode(':', strtolower((string)$api->type()))[1], array('webapi', 'dataservice'))) {
                                $ctype = 'api';
                            } else {
                                $ctype = 'standard';
                            }
                            $api_type = $api->get('dct:conformsTo|schema:documentation');
                            $api_url = $api->get('dcat:endpointURL|schema:url');
                            if (!isset($api_url))
                                $api_url = (string)$api;
                            if (strpos($api_url, '_:') === 0)
                                unset($api_url);
                            if (array_key_exists((string)$api_type, $this->metadata_standards)) {
                                $ctype = 'metadata';
                                $metadata['metadata'] = $api_url;
                            }
                            if($ctype =='api'){
                                $metadata['api'] = (string)$api_url;
                            }

                            $metadata['compliance'][] = array(
                                'doc' => (string)$api_type,
                                'type' => $ctype,
                                'url' => (string)$api_url,
                                'category' => 'standard'
                            );
                        }
                        $compliances = array_merge(
                            $catalog->all('dct:conformsTo'), $catalog->all('schema:publishingPrinciples')
                        );
                        foreach ($compliances as $compliance) {
                            $atype = strtolower((string)$compliance->get('schema:additionalType|rdfs:seeAlso'));

                            $curl = (string)$compliance->get('schema:url');
                            if (!isset($curl) | $curl == '')
                                $curl = (string)$compliance;

                            $pcat = 'compliance';
                            $ptype = 'unknown';
                            $pdoc = '';
                            $ctype = strtolower((string)$compliance->type());
                            if (strpos($ctype, 'standard') !== false) {
                                $pcat = 'standard';
                                if (array_key_exists((string)$curl, $this->metadata_standards)) {
                                    $ptype = 'metadata';
                                    $metadata['metadata'] = $curl;
                                } elseif (strpos($atype, 'identifier') !== false){
                                    $ptype = 'identifier';
                                    $metadata['pid'] = $curl;
                                }
                            } elseif (strpos($ctype, 'policy') !== false | strpos($ctype, 'creativework') !== false) {
                                $pcat = 'policy';
                                if (strpos($ctype, 'preservation') !== false | strpos($atype, 'preservation') !== false){
                                    $ptype = 'preservation';
                                    $metadata['preservation'] = $curl;
                                }
                                elseif (strpos($ctype, 'curation') !== false | strpos($atype, 'curation') !== false){
                                    $ptype = 'curation';
                                    $metadata['curation'] = $curl;
                                }
                                elseif (strpos($ctype, 'accrual') !== false | strpos($atype, 'accrual') !== false){
                                    $ptype = 'termsofdeposit';
                                    $metadata['termsofdeposit'] = $curl;
                                }
                                else
                                    $ptype = $pcat;
                            } else {
                                $pdoc = $curl;
                                $curl = '';
                            }

                            $metadata['compliance'][] = array(
                                'category' => $pcat,
                                'doc' => $pdoc,
                                'type' => $ptype,
                                'url' => $curl);


                        }

                    }
                }else{
                    $this->logging[$guri][]= array('warning'=>'Not a RDF Graph: '.$jg);
                }
            }catch (Exception $e){
                $this->logging[$guri][]= array('error'=>'Metadata graph parsing exception: ', $guri, $e->getMessage());
            }
        }
        return $metadata;
    }

    function get_embedded_jsonld(){
        $ejson = null;
        $matches = [];
        $jsp ="/<script(?:[^>]*)\"application\/ld\+json\"(?:[^>]*)>(.*?)<\/script>/s";
        if(is_string($this->catalog_html)){
            try{
                preg_match($jsp, $this->catalog_html,$matches);
                if(sizeof($matches)==2){
                    $this->logging[$this->catalog_url][]= array('info'=>'Found embedded JSON-LD, trying to ingest.. ');
                    $ejson = $matches[1];
                    $djson = json_decode($ejson,true);
                    /*
                    if(isset($djson["@graph"])){
                        $this->root_type[$this->catalog_url.'#jsonld'] = $djson["@graph"][0]["@type"];
                    }else{
                        $this->root_type[$this->catalog_url.'#jsonld'] = $djson["@type"];
                    }
                    */
                }
            }catch (Exception $e) {
                $this->logging[$this->catalog_url][]= array('error'=>'Embedded JSON-LD loading exception: ',  $e->getMessage());

            }
        }
        return $ejson;
    }
}

