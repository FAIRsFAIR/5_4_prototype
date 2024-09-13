<?php


class signposting_helper {
    
    function __construct($response)
    {
        $this->allowed_rels = ["describedby", "item", "license", "type", "collection", "author", "linkset",
            "cite-as", "api-catalog", "service-doc", "service-desc", "service-meta",];
        $this->target_header = [];
        $this->target_html ='';
        $this->signposting_links = [];
        if(isset($response->body)){
            $this->target_header = $response->header;
            $this->target_html = $response->body;
        }
        $this->set_signposting_header_links();
        $this->set_signposting_html_links();
    }

    function get_signposting_link($rel){
        $found_link = [];
        foreach($this->signposting_links as $link){
            if ($link['rel'] == $rel){
                $found_link[] = $link;
            }
        }
        return $found_link;
    }


    function set_signposting_header_links(){
        if(isset($this->target_header['Link']))
            $header_link_str = $this->target_header['Link'];
        elseif(isset($this->target_header['link']))
            $header_link_str = $this->target_header['link'];
        else
            $header_link_str = null;
        if(isset($header_link_str)){
            foreach(explode(',',$header_link_str) as $preparsed_link) {
                $href = substr(trim(explode(";", trim($preparsed_link))[0]),1,-1);
                preg_match_all('/(rel|type|profile)\s*=\s*\"?([^,;"]+)\"?/s', $preparsed_link, $p_match,PREG_SET_ORDER);
                $signposting_link_dict =['rel'=>null,'type'=>null,'profile'=>null,'url'=>$href];
                foreach($p_match as $am){
                    $signposting_link_dict[$am[1]]=$am[2];
                }
                if(in_array($signposting_link_dict['rel'],$this->allowed_rels)){
                    if (array_key_exists($href, $this->signposting_links)) {
                        if (strlen(serialize($this->signposting_links[$href])) < strlen(serialize($signposting_link_dict)))
                            $this->signposting_links[$href] = $signposting_link_dict;
                    } else {
                        $this->signposting_links[$href] = $signposting_link_dict;
                    }
                }
            }
        }

    }

    function set_signposting_html_links(){
        if(is_string($this->target_html)){
            if($this->target_html!=''){
                $dom = new DOMDocument();
                $dom->loadHTML($this->target_html);
                $links = $dom->getElementsByTagName('link');
                foreach($links as $link){
                    $href = $link->getAttribute('href');
                    $rel =  $link->getAttribute("rel");
                    $type =  $link->getAttribute("type");
                    $profile =  $link->getAttribute("profile");
                    if($href!='') {
                        # handle relative paths
                        $linkparts = parse_url(trim($href));
                        if (!isset($linkparts['scheme'])) {
                            $href = trim($this->catalog_url, '/') . '/' . trim($href, '/');
                        }
                        $signposting_link_dict = ["type" => $type, "rel" => $rel, "profile" => $profile,"url"=>$href];
                        if(in_array( $rel,$this->allowed_rels)){
                            if (array_key_exists($href, $this->signposting_links)) {
                                if (strlen(serialize($this->signposting_links[$href])) < strlen(serialize($signposting_link_dict)))
                                    $this->signposting_links[$href] = $signposting_link_dict;
                            } else {
                                $this->signposting_links[$href] = $signposting_link_dict;
                            }
                        }
                    }
                }
            }
        }
    }
}