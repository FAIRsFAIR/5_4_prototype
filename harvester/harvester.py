import json
import re
from urllib.parse import urlparse, urljoin

import rdflib
from lxml import html
import requests
from rdflib import RDF, DCAT, SDO, DC, DCTERMS, FOAF


class CatalogMetadataHarvester:
    def __init__(self,catalog_url):
        self.catalog_url = catalog_url
        self.catalog_html = None
        self.signposting_jsonld_links = {}
        #TODO: add browser like Agent info
        if str(self.catalog_url).startswith('http'):
            #try:
            if 1==1:
                response = requests.get(self.catalog_url)
                self.catalog_html = response.text
                self.catalog_header = response.headers
                self.set_signposting_header_jsonld_links()
                self.set_signposting_html_jsonld_links()
                ejson = self.get_embedded_jsonld()
                print(self.get_catalog_metadata(ejson))
                for jsonld_links in self.signposting_jsonld_links:
                    ljson = self.get_linked_jsonld(jsonld_links)
                    print(self.get_catalog_metadata(ljson))
                    #print(jsonld.get("@type"))
            #except Exception as e:
            #    print('HTTP request error: ', e)

    def get_catalog_metadata(self, jstr):
        metadata = {}
        SMA = rdflib.Namespace("http://schema.org/")
        VCARD = rdflib.Namespace("http://www.w3.org/2006/vcard/ns#")
        if isinstance(jstr, str):
            #print(jstr[:1000])
            jsonldgraph = rdflib.ConjunctiveGraph()
            jg = jsonldgraph.parse(data=jstr, format='json-ld')
            print('Checking for Catalog entries...')
            #for catalog in list(jg.objects(RDF.type, DCAT.Catalog)) \
            for catalog in list(jg[: RDF.type : DCAT.Catalog])+ list(jg[: RDF.type : SMA.DataCatalog]):
                metadata["resource_type"] = []
                resourcetypes = jg.objects(catalog, RDF.type)
                for resourcetype in resourcetypes:
                    metadata["resource_type"].append(str(resourcetype))
                metadata["title"]  = str(
                    jg.value(catalog, DCTERMS.title) or
                    jg.value(catalog, SDO.name) or jg.value(catalog, SMA.name) or
                    jg.value(catalog, FOAF.name) or ''
                )
                metadata["description"] = str(
                    jg.value(catalog, DCTERMS.description) or
                    jg.value(catalog, SDO.description) or jg.value(catalog,SMA.description) or
                    jg.value(catalog, SDO.disambiguatingDescription) or jg.value(catalog, SMA.disambiguatingDescription) or ''
                )
                metadata["language"] = str(
                    jg.value(catalog, DCTERMS.language) or
                    jg.value(catalog, SDO.inLanguage) or jg.value(catalog, SMA.inLanguage)  or ''
                )
                metadata["accessterms"] = str(

                )
                metadata["url"] = str(
                    jg.value(catalog, SDO.url) or jg.value(catalog, SMA.url) or
                    jg.value(catalog) or
                    jg.value(catalog, FOAF.homepage) or
                    jg.value(catalog, DC.identifier) or ''
                )
                publishers = (list(jg.objects(catalog, DCTERMS.publisher)) or list(jg.objects(catalog, SDO.publisher)) or list(jg.objects(catalog, SMA.publisher)))
                metadata["publisher"] = []
                metadata["country"] = []
                for publisher in publishers:
                    publisher_name = str(
                        jg.value(publisher, FOAF.name) or
                        jg.value(publisher, SDO.name) or jg.value(publisher, SMA.name) or ''
                    )
                    publisher_address = (jg.value(publisher, SDO.address) or jg.value(publisher, SMA.address) or publisher)
                    publisher_country = str(
                        jg.value(publisher_address, VCARD['country-name']) or
                        jg.value(publisher_address, SDO.addressCountry) or jg.value(publisher_address, SMA.addressCountry) or''
                    )
                    if publisher_country:
                        metadata["country"].append(publisher_country)
                    if publisher_name:
                        metadata["publisher"].append(publisher_name)
        else:
            print('Expecting JSON-LD string not: ', type(jstr))
        return metadata

    def set_signposting_header_jsonld_links(self):
        header_link_str = self.catalog_header.get('Link') or self.catalog_header.get('link') or None
        if header_link_str:
            try:
                for preparsed_link in header_link_str.split(","):
                    found_type, type_match, anchor_match = None, None, None
                    found_rel, rel_match = None, None
                    found_formats, formats_match = None, None
                    parsed_link = preparsed_link.strip().split(";")
                    found_link = parsed_link[0].strip()
                    for link_prop in parsed_link[1:]:
                        link_prop = str(link_prop).strip()
                        if link_prop.startswith("rel"):
                            rel_match = re.search(r'rel\s*=\s*\"?([^,;"]+)\"?', link_prop)
                        elif link_prop.startswith("type"):
                            type_match = re.search(r'type\s*=\s*\"?([^,;"]+)\"?', link_prop)
                        elif link_prop.startswith("profile"):
                            formats_match = re.search(r'profile\s*=\s*\"?([^,;"]+)\"?', link_prop)
                    if type_match:
                        found_type = type_match[1]
                    if rel_match:
                        found_rel = rel_match[1]
                    if formats_match:
                        found_formats = formats_match[1]
                    url = found_link[1:-1]
                    type = str(found_type).strip()
                    rel = str(found_rel).strip()
                    signposting_link_dict = {
                        "type": type,
                        "rel": rel,
                        "profile": found_formats
                    }
                    if url and rel in ['describedby'] and type in ["application/ld+json"]:
                        if url in self.signposting_jsonld_links:
                            if len(str(self.signposting_jsonld_links.get(url))) < len(str(signposting_link_dict)):
                                self.signposting_jsonld_links[url] = signposting_link_dict
                        else:
                            self.signposting_jsonld_links[url] = signposting_link_dict
            except Exception as e:
                print('Signposting detection in HTTP Header Error: ', e)

    def set_signposting_html_jsonld_links(self):
        if isinstance(self.catalog_html, str):
            if self.catalog_html:
                try:
                    dom = html.fromstring(self.catalog_html.encode("utf8"))
                    links = dom.xpath("/*/head/link")
                    for link in links:
                        href = link.attrib.get("href")
                        rel = link.attrib.get("rel")
                        type = link.attrib.get("type")
                        profile = link.attrib.get("profile")
                        type = str(type).strip()
                        # handle relative paths
                        linkparts = urlparse(href)
                        if linkparts.scheme == "":
                            href = urljoin(self.catalog_url, href)
                        if rel in ["describedby"] and type in ["application/json+ld"]:
                            signposting_link_dict = {"type": type, "rel": rel, "profile": profile}
                            if href:
                                if href in self.signposting_jsonld_links:
                                    if len(str(self.signposting_jsonld_links.get(href))) < len(str(signposting_link_dict)):
                                        self.signposting_jsonld_links[href] = signposting_link_dict
                                else:
                                    self.signposting_jsonld_links[href] = signposting_link_dict
                except Exception as e:
                    print('Signposting detection in HTML Error: ', e)
    def get_linked_jsonld(self, typed_link):
        ljson = None
        try:
            ljson = requests.get(typed_link).json()
            ljson = json.dumps(ljson)
        except json.JSONDecodeError as je:
            print('Loading malformed linked JSON-LD Error: ', je)
        except Exception as e:
            print('Loading linked JSON-LD Error: ', e)
        return ljson
    def get_embedded_jsonld(self):
        ejson = None
        jsp = r"<script\s+type=\"application\/ld\+json\">(.*?)<\/script>"
        if isinstance(self.catalog_html, str):
            try:
                jsr = re.search(jsp, self.catalog_html,re.DOTALL)
                if jsr:
                    ejson = jsr[1]
                    json.loads(ejson)
            except Exception as e:
                print('Loading embedded JSON-LD Error: ', e)
        return ejson