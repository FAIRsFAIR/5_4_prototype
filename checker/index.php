<html>
    <head>
        <title>Repository Metadata Checker</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script type="application/javascript">
            $(document).ready ( function () {
                $("#query").on("submit", function (e) {
                    e.preventDefault();
                    //reset
                    $("*[id^='i_']").each(function(i, el) {
                        $(el).attr("src", "icon/unknown.png");
                    });
                    $("*[id^='r_']").each(function(i, el) {
                        $(el).text('');
                    });
                    $('#logging').css("visibility","hidden");
                    $('#log-data').text('');
                    url = $('#QueryURL').val();
                    //check
                    $.post("../harvester/index.php", {"url": url}, function (r) {
                        var catalogmetadata = r.metadata;
                        var logging = r.logging;
                        if (catalogmetadata.length == 0){
                            catalogmetadata = [catalogmetadata];
                        }
                        console.log(r);
                        $.each(catalogmetadata, function(mdk, mdv){
                            $("*[id^='r_']").each(function(i, el) {
                                mpk = el.id.slice(2);
                                if(mdv[mpk]){
                                    mpv = mdv[mpk];
                                    console.log(mpv);
                                    console.log(mpk);
                                    if(mpv){
                                        if(!$.isEmptyObject(mpv)){
                                        //if(mpv.length > 0){
                                            $("#r_" + mpk).text(JSON.stringify(mpv));
                                            $("#i_" + mpk).attr("src","icon/passed.png");
                                        }else{
                                            if ($("#i_" + mpk).attr("src") != "icon/passed.png") {
                                                $("#i_" + mpk).attr("src", "icon/failed.png");
                                            }
                                        }
                                    }else {
                                        if ($("#i_" + mpk).attr("src") != "icon/passed.png") {
                                            $("#i_" + mpk).attr("src", "icon/failed.png");
                                        }
                                    }
                                }else {
                                    if ($("#i_" + mpk).attr("src") != "icon/passed.png") {
                                        $("#i_" + mpk).attr("src", "icon/failed.png");
                                    }
                                }
                            });
                        });//each
                        //logging
                        var numlog = 1;
                        logcolor = {'info':'alert-primary','success':'alert-success','warning':'alert-warning','error':'alert-danger'};
                        $.each(logging,function(ldk, ldv){
                            $('#log-data').append('<div id= "log_'+numlog+'"/><p class="text-start fw-bold">'+ldk+'</p>');
                            $.each(ldv,function(lpk, lpv){
                                for (const [lstatus, lmessage] of Object.entries(lpv)) {
                                    alerttype ='alert-primary';
                                    if(logcolor[lstatus]){
                                        alerttype = logcolor[lstatus];
                                    }else{

                                    }
                                    $('#log_'+numlog).append('<div class="alert '+alerttype+' text-start p-1 m-1 w-80"><b>'+lstatus+': </b>'+lmessage+'</div>');
                                }
                            });
                            numlog++;
                        });
                        //$('#logging').text(JSON.stringify(logging,null, 2));

                        $('#logging').css("visibility","visible");
                    });
                });
            });
        </script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </head>
    <body>
        <div class="container">
            <h3 class="">Repository Metadata Checker</h3>
            <div class="px-4 my-5 text-center w-80" id="query">
                <form class="p-4 p-md-5 border rounded-3 bg-body-tertiary" id="query">
                    <div class="row mb-3 justify-content-center">
                        <div class="col-auto">
                            <label class="col-form-label" for="QueryURL">Data catalogue homepage (URL):</label>
                        </div>
                        <div class="col-auto">
                            <input type="text" class="form-control" id="QueryURL" placeholder="https://your_repository.org">
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-primary" type="submit" id="go">Check</button>
                        </div>
                    </div>
                </form>
                <div id="logging" class="accordion w-80" style="visibility: hidden">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-log" aria-expanded="false" aria-controls="flush-log">
                                Log messages
                            </button>
                        </h2>
                        <div id="flush-log" class="accordion-collapse collapse  px-4 my-5 text-center" data-bs-parent="#logging">
                            <div class="accordion-body" id="log-data"/>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-4 my-5 text-center w-80">
                <ul class="list-group list-group w-80">
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto text-start">
                            <div class="fw-bold">Repository Name</div>
                            <p class="mb-1" id="r_title"></p>
                        </div>
                        <img class="img-fluid" id="i_title" src="icon/unknown.png" style="width:30px"/>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto text-start">
                            <div class="fw-bold">URL</div>
                            <p class="mb-1" id="r_url"></p>
                        </div>
                        <img class="img-fluid" id="i_url" src="icon/unknown.png" style="width:30px"/>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto text-start">
                            <div class="fw-bold">Description</div>
                            <p class="mb-1" id="r_description"></p>
                        </div>
                        <img  class="img-fluid" id="i_description" src="icon/unknown.png" style="width:30px"/>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto text-start">
                            <div class="fw-bold">Country</div>
                            <p class="mb-1" id="r_country"></p>

                        </div>
                        <img  class="img-fluid" id="i_country" src="icon/unknown.png" style="width:30px"/>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto text-start">
                            <div class="fw-bold">Language</div>
                            <p class="mb-1" id="r_language"></p>
                        </div>
                        <img  class="img-fluid" id="i_language" src="icon/unknown.png" style="width:30px"/>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto justify-content-start">
                            <div class="fw-bold text-start">Organization</div>
                            <p class="mb-1" id="r_publisher"></p>
                        </div>
                        <img  class="img-fluid" id="i_publisher" src="icon/unknown.png" style="width:30px"/>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto text-start">
                            <div class="fw-bold">Contact</div>
                            <p class="mb-1" id="r_contactpoint"></p>
                        </div>
                        <img  class="img-fluid" id="i_contactpoint" src="icon/unknown.png" style="width:30px"/>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto text-start">
                            <div class="fw-bold">Research Area</div>
                            <p class="mb-1" id="r_subject"></p>
                        </div>
                        <img  class="img-fluid" id="i_subject" src="icon/unknown.png" style="width:30px"/>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto text-start">
                            <div class="fw-bold">Persistent Identifier</div>
                            <p class="mb-1" id="r_pid"></p>
                        </div>
                        <img  class="img-fluid" id="i_pid" src="icon/unknown.png" style="width:30px"/>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto text-start">
                            <div class="fw-bold">Machine Interoperability</div>
                            <p class="mb-1" id="r_api"></p>
                        </div>
                        <img  class="img-fluid" id="i_api" src="icon/unknown.png" style="width:30px"/>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto text-start">
                            <div class="fw-bold">Metadata Standards</div>
                            <p class="mb-1" id="r_metadata"></p>
                        </div>
                        <img  class="img-fluid" id="i_metadata" src="icon/unknown.png" style="width:30px"/>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto text-start">
                            <div class="fw-bold">Curation / Preservation Policy</div>
                            <p class="mb-1" id="r_preservation"></p>
                        </div>
                        <img  class="img-fluid" id="i_preservation" src="icon/unknown.png" style="width:30px"/>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto text-start">
                            <div class="fw-bold">Terms of Deposit</div>
                            <p class="mb-1" id="r_termsofdeposit"></p>
                        </div>
                        <img  class="img-fluid" id="i_termsofdeposit" src="icon/unknown.png" style="width:30px"/>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto text-start">
                            <div class="fw-bold">Terms of Access</div>
                            <p class="mb-1" id="r_accessterms"></p>
                        </div>
                        <img  class="img-fluid" id="i_accessterms" src="icon/unknown.png" style="width:30px"/>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto text-start">
                            <div class="fw-bold">License</div>
                            <p class="mb-1" id="r_license"></p>
                        </div>
                        <img  class="img-fluid" id="i_license" src="icon/unknown.png" style="width:30px"/>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto text-start">
                            <div class="fw-bold">Certification</div>
                            <p class="mb-1" id="r_certification"></p>
                        </div>
                        <img  class="img-fluid" id="i_certification" src="icon/unknown.png" style="width:30px"/>
                    </li>
                </ul>
            </div>


        </div>
    </body>
</html>