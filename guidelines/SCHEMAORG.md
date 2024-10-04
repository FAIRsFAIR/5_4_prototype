1. ### ***Exposing Repository Information with schema.org***

Unlike ESIP’s model[^1], which defines a data repository as an instance of schema:Organization, schema:ResearchProject, and schema:Service, we model a data repository (or at least the operational part of a data repository) simply as an instance of schema:Project and schema:DataCatalog. We chose schema:Project instead of schema:ResearchProject because a data repository usually does not perform research as the main purpose. Since every data repository should also have a catalogue of its datasets, we propose to use schema:DataCatalog in addition to schema:Project. If a data repository is an independent organisational or legal entity, schema:Organization can optionally be used as an additional type or in replacement of schema:Project  to model a data repository. These schema.org types allow us to describe all essential DRAWG properties which we propose to map to schema.org as follows.

1. #### Descriptive Metadata

Since schema:DataCatalog is a subtype of schema:CreativeWork some descriptive properties are available which easily can be mapped to DRAWG.

**Table 5 \- Mapping of DRAWG attributes to schema.org properties.**

| DRAWG | schema.org |
| :---- | :---- |
| Repository Name | schema:name |
| URL | schema:url |
| Description | schema:description |
| Language | schema:inLanguage |
| Research Area | schema:keywords |
| Organization | schema:publisher |
| Country | schema:publisher \>= schema:address \=\> schema:addressCountry |
| Dataset Use License | schema:license |
| Terms of Access | schema:conditionsOfAccess |
| Contact | schema:contactPoint  |

For the schema.org keywords property, it is possible to use the type schema:DefinedTerm which allows to use ontology terms unambiguously specifying research areas. Since the DRAWG property ‘Country’ is defined as *“The country in which the repository operates”*, we map this DRAWG property to the country information (schema:addressCountry) contained in a schema:publisher’s schema:address property. 

To indicate the contact information for a given repository we recommend to use the schema:contactPoint property which is part of schema:Project and allows to include detailed schema:ContactPoint properties such as phone, email, fax etc.

2. #### Supported Standards

To expose information of available APIs supporting machine interoperability of a data repository, we propose to use the schema:offers property, which may list several instances of schema:Offer, which then links to instances of schema:WebAPI (a subclass of schema:Service) via their schema:itemOffered property. There, we follow the example of FAIRiCAT[^2] and use the schema:documentation to describe the type of service. This should be the web link to the documentation of the standard the web API follows. The schema:url can be used to  describe the endpoint URI of the API.

![][image1]  
**Figure 7 \- Example of exposure of available APIs**

An initial list of web APIs and their documentation links can be found in Appendix 1\.

Similarly, other standards supported by a data repository can be described using schema.org as an schema:Offer, which then should be a schema:Service instead of a schema:WebAPI. We propose to use the property schema:serviceType to unambiguously indicate which DRAWG service category  (persistent identifier or metadata standard) is described. We recommend using the FAIR vocabulary terms Identifier Service[^3] and Metadata Schema[^4] respectively to do so.

![][image2]  
**Figure 8 \- Example of standards exposure**

Again, we use the schema:documentation to indicate the PID or metadata standard a data repository supports.

Similarly as mentioned in the previous section, to indicate supported persistent identifier (PID) types, we recommend using the home URI of a PID system (e.g. DOI[^5], Handle[^6], etc.), which uniquely identifies a PID system.

For metadata standards, we recommend unique identifiers such as a FAIRsharing identifier (DOI), a DCC identifier, or the unique namespace or schema URI of e.g. XML metadata standards.

**Table 6 \- Mapping of DRAWG attributes to schema.org properties.**

| DRAWG | schema.org |
| :---- | :---- |
| Machine Interoperability | Schema:offers \=\> schema:Offer \=\> itemOffered \=\> schema:WebAPI |
| Persistent Identifiers | Schema:offers \=\> schema:Offer \=\> itemOffered \=\> schema:Service |
| Metadata | Schema:offers \=\> schema:Offer \=\> itemOffered \=\> schema:Service |

3. #### Policies and Principles

Unfortunately, schema.org does not offer a generic way to describe policies such as dc:Policy via dc:conformsTo. However, schema:DataCatalog inherited the schema:publishingPrinciples from schema:CreativeWork which may serve to point to *“a document describing the editorial principles*”, which therefore is well suited to link to DRAWG terms of deposit etc.. We include the DRAWG ‘Curation’ property here because it may be explained in a dedicated data curation policy document. 

**Table 7 \- Mapping of schema:publishingPrinciples to several DRAWG attributes.**

| DRAWG | schema.org |
| :---- | :---- |
| Curation | schema:publishingPrinciples |
| Terms of Deposit | schema:publishingPrinciples |
| Preservation | schema:publishingPrinciples |

To clarify which policy actually is described, we recommend to use the schema:additionalType property and here to use the values premis:PreservationPolicy to indicate the preservation policy and dct:accrualPolicy to indicate the terms of deposit.

![][image3]  
**Figure 9 \- Example of exposure of policy**

4. #### Certification and Quality Information

As mentioned above, we chose schema:Project to model a data repository which allows us to include additional DRAWG properties. From these, we recommend to use the schema:hasCertification property to indicate certification details such as a given CoreTrustSeal certification. This property requires the use of a schema:Certification instance which allows linking to a certification document via the schema:url property which should  be the DOI of a CoreTrustSeal certificate. It further allows to include useful information about certificates such as audit date, validity and issuer. 

![][image4]

**Figure 10 \- Example of exposure of certification information**

**Table 8 \- Mapping of DRAWG certification attributes to schema.org properties.**

| DRAWG | schema.org |
| :---- | :---- |
| Certification | schema:hasCertification |
| Contact | schema:contactPoint |

In addition, the use of schema:Project this would allow information about funding sources and other useful things which are relevant for the scientific community.  


[^1]:  [https://github.com/ESIPFed/science-on-schema.org/blob/main/guides/DataRepository.md](https://github.com/ESIPFed/science-on-schema.org/blob/main/guides/DataRepository.md) 

[^2]:  [https://signposting.org/FAIRiCat](https://signposting.org/FAIRiCat) 

[^3]:  [https://w3id.org/fair/fip/latest/Identifier-service](https://w3id.org/fair/fip/latest/Identifier-service) 

[^4]:  [https://w3id.org/fair/fip/latest/Metadata-schema](https://w3id.org/fair/fip/latest/Metadata-schema) 

[^5]:  [https://doi.org](https://doi.org) 

[^6]:  [https://handle.net](https://handle.net) 
