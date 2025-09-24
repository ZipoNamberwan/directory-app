@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <link rel="stylesheet" href="/vendor/leaflet/draw/leaflet.draw.css" />

    <link href="/vendor/tabulator/tabulator_bootstrap3.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        #map {
            height: 100vh;
            width: 100%;
        }

        .popup-content {
            padding: 10px;
        }

        .popup-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .popup-detail {
            margin-bottom: 5px;
        }

        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            font-style: italic;
            color: #666;
        }

        .marker-label {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 2px 5px;
            font-size: 12px;
            white-space: nowrap;
        }

        .scrollable-card-body {
            max-height: 75vh; 
            overflow-y: auto; 
        }

        .tabulator-cell{
            font-size: smaller;
        }
    </style>
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Persebaran Muatan Sentra Ekonomi'])
    <div class="full-screen-bg"></div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex align-items-center">
                    <h4 class="text-capitalize">Duplikasi Data Usaha</h4>
                </div>
            </div>
            <div class="card-body pt-1">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <!-- Row kedua: flex column -->
                        @hasrole('adminprov')
                        <div class="d-flex flex-column gap-3">
                            <div class="flex-fill">
                                <label class="form-control-label">Satker <span class="text-danger">*</span></label>
                                <select id="organization" class="form-control" data-toggle="select">
                                    <option value="0" disabled selected> -- Filter Kabupaten -- </option>
                                    @foreach ($regencies as $regency)
                                        <option value="{{ $regency->id }}"
                                            {{ old('regency') == $regency->id ? 'selected' : '' }}>
                                            [{{ $regency->short_code }}] {{ $regency->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-fill">
                                <label class="form-control-label">Kecamatan</label>
                                <select style="width: 100%;" id="subdistrict" name="subdistrict" class="form-control"
                                    data-toggle="select">
                                    <option value="0" disabled selected> -- Filter Kecamatan -- </option>
                                    @foreach ($subdistricts as $subdistrict)
                                        <option value="{{ $subdistrict->id }}"
                                            {{ old('subdistrict') == $subdistrict->id ? 'selected' : '' }}>
                                            [{{ $subdistrict->short_code }}] {{ $subdistrict->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="d-flex flex-row gap-3 mb-3">
                            <div class="flex-fill">
                                <label class="form-control-label">Desa</label>
                                <select id="village" name="village" class="form-control" data-toggle="select"
                                    name="village"></select>
                            </div>
                            <div class="flex-fill">
                                <label class="form-control-label">SLS</label>
                                <select id="sls" name="sls" class="form-control" data-toggle="select"></select>
                            </div>
                        </div>
                        @endhasrole
                        <!-- Row pertama: flex row -->
                        <div class="d-flex flex-row gap-3 mb-3">
                            <div class="flex-fill">
                                <p class="mb-2 text-muted small">Jumlah usaha yang difilter: <span id="total-records" class="fw-bold">0</span></p>
                            </div>
                            <div class="flex-fill">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="only-pending" id="view-only-duplicate" checked>
                                    <label class="form-check-label" for="view-only-duplicate">Tampilkan Hanya Belum Ditindaklanjuti</label>
                                </div>
                            </div>
                        </div>

                        

                            
                        <div id="data-duplicate">

                        </div>
                    </div>
                    <div class="col-md-8">
                        <div id="map" class="maps"></div>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.footers.auth.footer')
    </div>

    @push('js')
        <script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
        <script src="/vendor/select2/select2.min.js"></script>

        <script src="/vendor/sweetalert2/sweetalert2.js"></script>
        <script src="/vendor/tabulator/tabulator.min.js"></script>
        <script src="/vendor/loading-overlay/loadingoverlay.js"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>        

        <script>
            
            let markerUsaha1 = null;
            let markerUsaha2 = null;
            let lineUsaha12 = null;

            var w = window.innerWidth;
            var h = window.innerHeight;
            var mapx = document.getElementsByClassName("maps");
            for (var i = 0; i < mapx.length; i++ ) {
                mapx[i].style.height = (h*0.82)+"px";
                h=(h*0.48)+"px";
                document.getElementById("map").style.height = (h*0.82)+"px";
            }

            let table;

            // Define column configurations for different modes
            const getColumnConfig = (mode) => {
                const baseColumns = [{
                        title: "Usaha 1",
                        field: "usaha_1",
                        responsive: 0,
                        formatter: function(cell) {
                            return `<div class="text-wrap font-weight-bold">${$("<div>").text(cell.getValue()).html()}</div>`;
                        }
                    },
                    {
                        title: "Usaha 2",
                        field: "usaha_2",
                        responsive: 1,
                        formatter: function(cell) {
                            return `<div class="text-wrap font-weight-bold">${$("<div>").text(cell.getValue()).html()}</div>`;
                        }
                    },
                    {
                        title: "Similarity (%)",
                        field: "similarity",
                        responsive: 2,
                        formatter: function(cell) {
                            return `<div class="text-wrap font-weight-bold">${$("<div>").text(cell.getValue().toFixed(2)).html()}</div>`;
                        }
                    },
                    {
                        title: "Jarak (m)",
                        field: "jarak",
                        responsive: 3,
                        formatter: function(cell) {
                            return `<div class="text-wrap font-weight-bold">${$("<div>").text(cell.getValue().toFixed(2)).html()}</div>`;
                        }
                    },
                ];

                // Apply mode-specific configurations
                if (mode === "fit") {
                    // No width/minWidth, no responsive collapse column
                    return baseColumns;
                } else if (mode === "responsive") {
                    // Add responsive collapse column at the beginning
                    const responsiveColumn = {
                        formatter: "responsiveCollapse",
                        width: 30,
                        hozAlign: "center",
                        resizable: false,
                        headerSort: false
                    };

                    // Set widths for responsive mode
                    baseColumns[0].widthGrow = 3;
                    baseColumns[0].minWidth = 150;
                    baseColumns[1].width = 250;
                    baseColumns[1].minWidth = 200;
                    baseColumns[2].width = 100;
                    baseColumns[2].minWidth = 80;
                    baseColumns[3].width = 200;
                    baseColumns[3].minWidth = 150;
                    baseColumns[4].width = 200;
                    baseColumns[4].minWidth = 150;

                    return [responsiveColumn, ...baseColumns];
                } else { // scroll horizontal
                    // Set widths for horizontal scroll mode
                    baseColumns[0].widthGrow = 3;
                    baseColumns[0].minWidth = 150;
                    baseColumns[1].width = 250;
                    baseColumns[1].minWidth = 200;
                    baseColumns[2].width = 100;
                    baseColumns[2].minWidth = 80;
                    baseColumns[3].width = 200;
                    baseColumns[3].minWidth = 150;
                    baseColumns[4].width = 200;
                    baseColumns[4].minWidth = 150;

                    return baseColumns;
                }
            };

            // Get table configuration based on mode
            const getTableConfig = (mode,params,onlypending) => {
                params['onlypending'] = onlypending;
                const baseConfig = {
                    height: h,
                    layout: "fitColumns",
                    ajaxURL: "/maps/duplicate-data",
                    ajaxParams:params,
                    ajaxRequesting: function (url, params) {
                        $.LoadingOverlay("show", {
                            image: "",
                            fontawesome: "fa fa-spinner fa-spin",
                            background: "rgba(255,255,255,0.7)",
                            text: "Memuat data...",
                            textResizeFactor: 0.15
                        });
                    },
                    ajaxError: function (xhr, textStatus, errorThrown) {
                        $.LoadingOverlay("hide");
                    },
                    progressiveLoad: "scroll",
                    paginationSize: 50,
                    placeholder: "Tidak ada usaha yang ditemukan",
                    textDirection: "auto",
                    ajaxResponse: function(url, params, response) {
                        $.LoadingOverlay("hide");
                        document.getElementById("total-records").textContent = response.total_records;
                        return response;
                    },
                    columns: getColumnConfig(mode),
                    rowFormatter:function(row){
                        let data = row.getData();
                        if(data.is_action == 0){
                            row.getElement().style.backgroundColor = "#ff9800"; 
                        } else if(data.is_action == 1 || data.is_action == 2){
                            row.getElement().style.backgroundColor = "#4caf50"; 
                        } else {
                            row.getElement().style.backgroundColor = "#2196f3"; 
                        }
                    },
                };

                if (mode === "responsive") {
                    baseConfig.responsiveLayout = "collapse";
                    baseConfig.responsiveLayoutCollapseStartOpen = false;
                }

                return baseConfig;
            };

            // Initialize table with default mode
            const initializeTable = (mode = "fit",$params,onlypending) => {
                table = new Tabulator("#data-duplicate", getTableConfig(mode,$params,onlypending));
                // HARUS setelah inisialisasi
                table.on("rowClick", function(e, row){
                    showDetail( row.getData());
                });
            };

            // Recreate table with new mode
            const recreateTable = (mode,onlypending) => {
                if (table) {
                    table.destroy();
                }
                initializeTable(mode,{},1);
            };

            // Initialize table on page load
            document.addEventListener('DOMContentLoaded', function() {
                // Get initial mode from checked radio button
                const checkedRadio = document.querySelector('input[name="mode"]:checked');
                const checkedCheckbox = document.querySelector('input[name="only-pending"]');
                const initialMode = checkedRadio ? checkedRadio.value : "fit";
                const onlypending = checkedCheckbox.checked ? 1 : 0;
                
                let id=$('#organization').val();
                params={};
                if(id){
                    params={'kabkota':id};
                }
                initializeTable(initialMode,{},onlypending);
                
                document.getElementById('organization').addEventListener("change", function() {
                    loadSubdistrict(null, null);
                    loadDataUsaha()
                });

                document.getElementById('subdistrict').addEventListener("change", function() {
                    loadVillage(null, null);
                    loadDataUsaha()
                });

                document.getElementById('village').addEventListener("change", function() {
                    loadSls(null, null);
                    loadDataUsaha()
                });

                document.getElementById('sls').addEventListener("change", function() {
                    loadDataUsaha()
                });
            });

            function showDetail(data){
                let koord1=data.koordinat_usaha_1.replace(/,/g, '.').split("#"); 
                let koord2=data.koordinat_usaha_2.replace(/,/g, '.').split("#"); 
                
                if (markerUsaha1) {
                    map.removeLayer(markerUsaha1);
                }
                if (markerUsaha2) {
                    map.removeLayer(markerUsaha2);
                }
                if (lineUsaha12) {
                    map.removeLayer(lineUsaha12);
                }
                markerUsaha1=L.marker([koord1[0],koord1[1]]).addTo(map).bindPopup(data.usaha_1);
                markerUsaha2=L.marker([koord2[0],koord2[1]]).addTo(map).bindPopup(data.usaha_2);
                lineUsaha12=L.polyline([[koord1[0],koord1[1]],[koord2[0],koord2[1]]],{color:'red'}).addTo(map);
                map.fitBounds(lineUsaha12.getBounds(),{padding:[50,50]});

                $.ajax({
                    type: 'POST',
                    url: '/maps/detail-data',
                    data : {'id': data.id },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend : function (){
                        $(".map_duplicate_details").html("");
                        $.LoadingOverlay("show", {
                            image: "",
                            fontawesome: "fa fa-spinner fa-spin",
                            background: "rgba(255,255,255,0.7)",
                            text: "Memuat data...",
                            textResizeFactor: 0.15
                        });
                    },
                    success: function(response) {
                        if(response.status){                            
                            data.id1=response.data.usaha1.id;
                            data.sector1=response.data.usaha1.sector;
                            data.address1=response.data.usaha1.address;
                            data.desc1=response.data.usaha1.description;
                            data.owner1=response.data.usaha1.owner;
                            data.kab1=response.data.usaha1.kd_kab;
                            data.kec1=response.data.usaha1.kd_kec;
                            data.desa1=response.data.usaha1.kd_desa;
                            data.sls1=response.data.usaha1.kd_sls;
                            data.status1=response.data.usaha1.status;
                            data.note1=response.data.usaha1.note;
                            data.source1=response.data.usaha1.source;
                            data.id2=response.data.usaha2.id;
                            data.sector2=response.data.usaha2.sector;
                            data.address2=response.data.usaha2.address;
                            data.desc2=response.data.usaha2.description;
                            data.owner2=response.data.usaha2.owner;
                            data.kab2=response.data.usaha2.kd_kab;
                            data.kec2=response.data.usaha2.kd_kec;
                            data.desa2=response.data.usaha2.kd_desa;
                            data.sls2=response.data.usaha2.kd_sls;
                            data.status2=response.data.usaha2.status;
                            data.note2=response.data.usaha2.note;
                            data.source2=response.data.usaha2.source;
                            addDetails(data);
                        } else {
                            alert(response.message);
                            $.LoadingOverlay("hide");
                        }
                    }
                });
            }
            // Add progress bar HTML right before the script starts
            const mapContainer = document.getElementById('map');
            const progressBar = document.createElement('div');
            progressBar.id = 'map-loading-progress';
            progressBar.className = 'progress-bar';
            progressBar.style.cssText = `
                width: 100%;
                height: 4px;
                background-color: #f1f1f1;
                position: absolute;
                top: 0;
                left: 0;
                z-index: 1000;
                margin: 0;
                display: none;
            `;

            const progressIndicator = document.createElement('div');
            progressIndicator.className = 'progress-indicator';
            progressIndicator.style.cssText = `
                height: 100%;
                width: 0%;
                background-color: #4CAF50;
                position: absolute;
                transition: width 0.3s ease;
            `;

            progressBar.appendChild(progressIndicator);
            mapContainer.style.position = 'relative'; // Ensure the map container is positioned
            mapContainer.appendChild(progressBar); // Append to the map container instead of before it

            // Function to control the progress bar
            function showMapLoading(show, progress = 0) {
                const progressBar = document.getElementById('map-loading-progress');
                const progressIndicator = progressBar.querySelector('.progress-indicator');

                if (show) {
                    progressBar.style.display = 'block';
                    progressIndicator.style.width = progress + '%';
                } else {
                    // Quickly complete the progress bar before hiding
                    progressIndicator.style.width = '100%';
                    setTimeout(() => {
                        progressBar.style.display = 'none';
                        progressIndicator.style.width = '0%';
                    }, 300);
                }
            }

            const selectConfigs = [{
                    selector: '#organization',
                    placeholder: 'Pilih Satker'
                },
                {
                    selector: '#subdistrict',
                    placeholder: 'Pilih Kecamatan'
                },
                {
                    selector: '#village',
                    placeholder: 'Pilih Desa'
                },
                {
                    selector: '#sls',
                    placeholder: 'Pilih SLS'
                },
            ];

            selectConfigs.forEach(({
                selector,
                placeholder
            }) => {
                $(selector).select2({
                    placeholder,
                    allowClear: true
                });
            });

            const eventHandlers = {
                '#organization': () => {
                    loadSubdistrict(null, null);
                    loadDataUsaha()
                },
                '#subdistrict': () => {
                    loadVillage(null, null);
                    loadDataUsaha()
                },
                '#village': () => {
                    loadSls(null, null);
                    loadDataUsaha()
                },
                '#sls': () => {
                    loadDataUsaha()
                },
                '#view-only-duplicate': () => {
                    loadDataUsaha()
                },
            };

            Object.entries(eventHandlers).forEach(([selector, handler]) => {
                $(selector).on('change', handler);
            });

            function loadDataUsaha() {
                let organizationSelector = `#organization`;

                let id = $(organizationSelector).val();
                if (id) {
                    params['kabkota'] = id;
                } else {
                    if ('kabkota' in params && params['kabkota'] !== null) {
                       delete params.kabkota;
                    }
                }

                let kdkec = $("#subdistrict").val();
                if (kdkec) {
                    params['kec'] = kdkec;
                } else {
                    if ('kec' in params && params['kec'] !== null) {
                       delete params.kec;
                    }
                }

                let kddesa = $("#village").val();
                if (kddesa) {
                    params['desa'] = kddesa;
                }  else {
                    if ('desa' in params && params['desa'] !== null) {
                       delete params.desa;
                    }
                }

                let kdsls = $("#sls").val();
                if (kdsls) {
                    params['sls'] = kdsls;
                } else {
                    if ('sls' in params && params['sls'] !== null) {
                       delete params.sls;
                    }
                }
                console.log(params);

                const checkedCheckbox = document.querySelector('input[name="only-pending"]');
                const onlypending = checkedCheckbox.checked ? 1 : 0;

                const checkedRadio = document.querySelector('input[name="mode"]:checked');
                const initialMode = checkedRadio ? checkedRadio.value : "fit";

                initializeTable(initialMode,params,onlypending);
            }

            const map = L.map('map').setView([-7.536, 112.238], 8);
            const BASEMAP_OSM = "https://tile.openstreetmap.org/{z}/{x}/{y}.png";
            const BASEMAP_GOOGLE_HYBRID = "http://mt0.google.com/vt/lyrs=y&hl=en&x={x}&y={y}&z={z}&s=Ga";
            const BASEMAP_ESRI = "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}";
            const BASEMAP_CARTO = "https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png";

            const esriLayer = L.tileLayer(BASEMAP_ESRI, {
                minZoom: 0,
                maxZoom: 19,
                attribution: '&copy; <a href="https://server.arcgisonline.com/arcgis/rest/services/World_Imagery/MapServer" target="_blank">Esri</a> &copy; <a href="https://server.arcgisonline.com/" target="_blank">World Imagery</a>',
                ext: 'png',
            });

            const osmLayer = L.tileLayer(BASEMAP_OSM, {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            });

            const googleHybridLayer = L.tileLayer(BASEMAP_GOOGLE_HYBRID, {
                attribution: '&copy; <a href="https://www.google.com/maps">Google Maps</a>',
                maxZoom: 20
            }).addTo(map);

            const cartoLayer = L.tileLayer(BASEMAP_CARTO, {
                attribution: '&copy; <a href="https://carto.com/">CARTO</a>',
                subdomains: 'abcd',
                maxZoom: 21
            });

            const noBasemap = L.layerGroup();

            const baseMaps = {
                "Google Hybrid": googleHybridLayer,
                "Esri World Imagery": esriLayer,
                "OpenStreetMap": osmLayer,
                "CARTO Voyager": cartoLayer,
                "No Basemap": noBasemap
            };
            
            L.control.layers(baseMaps).addTo(map);

            function addDetails(data){
                
                let duplicate=document.getElementsByClassName("map_duplicate_details");
                if (duplicate.length > 0) {
                   let innerHTML = "<div class=\'card-group\'><div class=\'card border-success m-1\' ><div class=\'card-body text-success mb-0 p-2\'><h5 class=\'card-title\'>"+data.usaha_1+"</h5><dl class=\'row\'><dt class=\'col-sm-4\'>Deskripsi</dt><dd class=\'col-sm-8 mb-0\'>"+data.desc1+"</dd><dt class=\'col-sm-4\'>Alamat</dt><dd class=\'col-sm-8 mb-0\'>"+data.address1+"</dd><dt class=\'col-sm-4\'>Sektor</dt><dd class=\'col-sm-8 mb-0\'>"+data.sector1+"</dd><dt class=\'col-sm-4\'>Pemilik</dt><dd class=\'col-sm-8 mb-0\'>"+data.owner1+"</dd><dt class=\'col-sm-4\'>Status Bangunan Usaha</dt><dd class=\'col-sm-8 mb-0\'>"+data.status1+"</dd><dt class=\'col-sm-4\'>Wilayah</dt><dd class=\'col-sm-8 mb-0\'><ul><li>SLS : "+data.sls1+"</li><li>Desa : "+data.desa1+"</li><li>Kecamatan : "+data.kec1+"</li><li>Kabupaten/Kota : "+data.kab1+"</li></ul></dd><dt class=\'col-sm-4\'>Catatan</dt><dd class=\'col-sm-8 mb-0\'>["+data.source1+"] "+data.note1+"<br>"+data.id1+"</dd></dl></div><div class=\'card-footer bg-transparent d-grid p-1 border-success\'><div  onClick=\"simpan(\'"+data.id+"\',\'"+data.source1+"#"+data.id1+"\',\'"+data.source2+"#"+data.id2+"\',\'data1\');\"  class=\'duplicate-button btn mb-0 btn-block btn-primary\'>Simpan Usaha 1</div><small class=\'text-muted\'>Note: Jika melakukan simpan usaha 1, maka usaha 2 akan dihapus.</small></div></div><div class=\'card border-success m-1\' ><div class=\'card-body text-success mb-0 p-2\'><h5 class=\'card-title\'>"+data.usaha_2+"</h5><dl class=\'row\'><dt class=\'col-sm-4\'>Deskripsi</dt><dd class=\'col-sm-8 mb-0\'>"+data.desc2+"</dd><dt class=\'col-sm-4\'>Alamat</dt><dd class=\'col-sm-8 mb-0\'>"+data.address2+"</dd><dt class=\'col-sm-4\'>Sektor</dt><dd class=\'col-sm-8 mb-0\'>"+data.sector2+"</dd><dt class=\'col-sm-4\'>Pemilik</dt><dd class=\'col-sm-8 mb-0\'>"+data.owner2+"</dd><dt class=\'col-sm-4\'>Status Bangunan Usaha</dt><dd class=\'col-sm-8 mb-0\'>"+data.status2+"</dd><dt class=\'col-sm-4\'>Wilayah</dt><dd class=\'col-sm-8 mb-0\'><ul><li>SLS : "+data.sls2+"</li><li>Desa : "+data.desa2+"</li><li>Kecamatan : "+data.kec2+"</li><li>Kabupaten/Kota : "+data.kab2+"</li></ul></dd><dt class=\'col-sm-4\'>Catatan</dt><dd class=\'col-sm-8 mb-0\'>["+data.source2+"] "+data.note2+"<br>"+data.id2+"</dd></dl></div><div class=\'card-footer bg-transparent d-grid p-1 border-success\'><div onClick=\"simpan(\'"+data.id+"\',\'"+data.source1+"#"+data.id1+"\',\'"+data.source2+"#"+data.id2+"\',\'data2\');\" class=\'duplicate-button btn mb-0 btn-block btn-primary\'>Simpan Usaha 1</div><small class=\'text-muted\'>Note: Jika melakukan simpan usaha 2, maka usaha 1 akan dihapus.</small></div></div></div><div class=\'card-footer bg-transparent d-grid p-1 border-success\'><div onClick=\"simpan(\'"+data.id+"\',\'"+data.source1+"#"+data.id1+"\',\'"+data.source2+"#"+data.id2+"\',\'all\');\"  class=\'duplicate-button btn btn-block btn-success\'>Simpan Keduanya</div></div>";
                   $(".map_duplicate_details").html(innerHTML);
                } else {
                    var details = L.control({position: "bottomleft"});
                    details.onAdd = function (map) {
                        var div = L.DomUtil.create("div", "map_duplicate_details bg-white");
                        div.innerHTML += "<div class=\'card-group\'><div class=\'card border-success m-1\' ><div class=\'card-body text-success mb-0 p-2\'><h5 class=\'card-title\'>"+data.usaha_1+"</h5><dl class=\'row\'><dt class=\'col-sm-4\'>Deskripsi</dt><dd class=\'col-sm-8 mb-0\'>"+data.desc1+"</dd><dt class=\'col-sm-4\'>Alamat</dt><dd class=\'col-sm-8 mb-0\'>"+data.address1+"</dd><dt class=\'col-sm-4\'>Sektor</dt><dd class=\'col-sm-8 mb-0\'>"+data.sector1+"</dd><dt class=\'col-sm-4\'>Pemilik</dt><dd class=\'col-sm-8 mb-0\'>"+data.owner1+"</dd><dt class=\'col-sm-4\'>Status Bangunan Usaha</dt><dd class=\'col-sm-8 mb-0\'>"+data.status1+"</dd><dt class=\'col-sm-4\'>Wilayah</dt><dd class=\'col-sm-8 mb-0\'><ul><li>SLS : "+data.sls1+"</li><li>Desa : "+data.desa1+"</li><li>Kecamatan : "+data.kec1+"</li><li>Kabupaten/Kota : "+data.kab1+"</li></ul></dd><dt class=\'col-sm-4\'>Catatan</dt><dd class=\'col-sm-8 mb-0\'>["+data.source1+"] "+data.note1+"<br>"+data.id1+"</dd></dl></div><div class=\'card-footer bg-transparent d-grid p-1 border-success\'><div onClick=\"simpan(\'"+data.id+"\',\'"+data.source1+"#"+data.id1+"\',\'"+data.source2+"#"+data.id2+"\',\'data1\');\" class=\'duplicate-button btn mb-0 btn-block btn-primary\'>Simpan Usaha 1</div><small class=\'text-muted\'>Note: Jika melakukan simpan usaha 1, maka usaha 2 akan dihapus.</small></div></div><div class=\'card border-success m-1\' ><div class=\'card-body text-success mb-0 p-2\'><h5 class=\'card-title\'>"+data.usaha_2+"</h5><dl class=\'row\'><dt class=\'col-sm-4\'>Deskripsi</dt><dd class=\'col-sm-8 mb-0\'>"+data.desc2+"</dd><dt class=\'col-sm-4\'>Alamat</dt><dd class=\'col-sm-8 mb-0\'>"+data.address2+"</dd><dt class=\'col-sm-4\'>Sektor</dt><dd class=\'col-sm-8 mb-0\'>"+data.sector2+"</dd><dt class=\'col-sm-4\'>Pemilik</dt><dd class=\'col-sm-8 mb-0\'>"+data.owner2+"</dd><dt class=\'col-sm-4\'>Status Bangunan Usaha</dt><dd class=\'col-sm-8 mb-0\'>"+data.status2+"</dd><dt class=\'col-sm-4\'>Wilayah</dt><dd class=\'col-sm-8 mb-0\'><ul><li>SLS : "+data.sls2+"</li><li>Desa : "+data.desa2+"</li><li>Kecamatan : "+data.kec2+"</li><li>Kabupaten/Kota : "+data.kab2+"</li></ul></dd><dt class=\'col-sm-4\'>Catatan</dt><dd class=\'col-sm-8 mb-0\'>["+data.source2+"] "+data.note2+"<br>"+data.id2+"</dd></dl></div><div class=\'card-footer bg-transparent d-grid p-1 border-success\'><div onClick=\"simpan(\'"+data.id+"\',\'"+data.source1+"#"+data.id1+"\',\'"+data.source2+"#"+data.id2+"\',\'data2\');\" class=\'duplicate-button btn mb-0 btn-block btn-primary\'>Simpan Usaha 1</div><small class=\'text-muted\'>Note: Jika melakukan simpan usaha 2, maka usaha 1 akan dihapus.</small></div></div></div><div class=\'card-footer bg-transparent d-grid p-1 border-success\'><div onClick=\"simpan(\'"+data.id+"\',\'"+data.source1+"#"+data.id1+"\',\'"+data.source2+"#"+data.id2+"\',\'all\');\" class=\'duplicate-button btn btn-block btn-success\'>Simpan Keduanya</div></div>";
                        
                        return div;
                    };

                    details.addTo(map);
                }
                $.LoadingOverlay("hide");
            }

            function simpan(d,a,b,c){

                Swal.fire({
                    title: `Konfirmasi Aksi ?`,
                    html: `<strong>${name}</strong><br>Aksi ini tidak bisa dikembalikan lagi.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya',
                    cancelButtonText: 'Tidak',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: 'POST',
                            url: '/maps/handle-duplicate',
                            data : {'a': a,'b':b,'c':c,'d':d },
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            beforeSend : function (){
                                $(".map_duplicate_details").html("");
                                $.LoadingOverlay("show", {
                                    image: "",
                                    fontawesome: "fa fa-spinner fa-spin",
                                    background: "rgba(255,255,255,0.7)",
                                    text: "Memuat data...",
                                    textResizeFactor: 0.15
                                });
                            },
                            success: function(response) {
                                response=JSON.parse(response);
                                $.LoadingOverlay("hide");
                                Swal.fire({
                                    title: response.status ? 'Berhasil' : 'Gagal',
                                    text: response.message,
                                    icon: response.status ? 'success' : 'error',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                        });

                        
                    }
                })
            }

            function loadSubdistrict(regencyid = null, selectedvillage = null) {

            let regencySelector = `#organization`;
            let subdistrictSelector = `#subdistrict`;
            let villageSelector = `#village`;
            let slsSelector = `#sls`;

            let id = $(regencySelector).val();
            if (regencyid != null) {
                id = regencyid;
            }

            $(subdistrictSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);
            $(villageSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);
            $(slsSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);

            if (id != null) {
                $.ajax({
                    type: 'GET',
                    url: '/kec/' + id,
                    success: function(response) {
                        $(subdistrictSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih Kecamatan -- </option>`);
                        $(villageSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih Desa -- </option>`);
                        $(slsSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih SLS -- </option>`);

                        response.forEach(element => {
                            let selected = selectedvillage == String(element.id) ? 'selected' : '';
                            $(subdistrictSelector).append(
                                `<option value="${element.id}" ${selected}>[${element.short_code}] ${element.name}</option>`
                            );
                        });
                    }
                });
            } else {
                $(subdistrictSelector).empty().append(`<option value="0" disabled> -- Pilih Kecamatan -- </option>`);
                $(villageSelector).empty().append(`<option value="0" disabled> -- Pilih Desa -- </option>`);
                $(slsSelector).empty().append(`<option value="0" disabled> -- Pilih SLS -- </option>`);
            }
        }

        function loadVillage(subdistrictid = null, selectedvillage = null) {

            let subdistrictSelector = `#subdistrict`;
            let villageSelector = `#village`;
            let slsSelector = `#sls`;

            let id = $(subdistrictSelector).val();
            if (subdistrictid != null) {
                id = subdistrictid;
            }

            $(villageSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);
            $(slsSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);

            if (id != null) {
                $.ajax({
                    type: 'GET',
                    url: '/desa/' + id,
                    success: function(response) {
                        $(villageSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih Desa -- </option>`);
                        $(slsSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih SLS -- </option>`);
                        response.forEach(element => {
                            let selected = selectedvillage == String(element.id) ? 'selected' : '';
                            $(villageSelector).append(
                                `<option value="${element.id}" ${selected}>[${element.short_code}] ${element.name}</option>`
                            );
                        });
                    }
                });
            } else {
                $(villageSelector).empty().append(`<option value="0" disabled> -- Pilih Desa -- </option>`);
                $(slsSelector).empty().append(`<option value="0" disabled> -- Pilih SLS -- </option>`);
            }
        }

        function loadSls(villageid = null, selectedsls = null) {

            let villageSelector = `#village`;
            let slsSelector = `#sls`;

            let id = $(villageSelector).val();
            if (villageid != null) {
                id = villageid;
            }

            $(slsSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);

            if (id != null) {
                $.ajax({
                    type: 'GET',
                    url: '/sls/' + id,
                    success: function(response) {
                        $(slsSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih SLS -- </option>`);
                        response.forEach(element => {
                            let selected = selectedsls == String(element.id) ? 'selected' : '';
                            $(slsSelector).append(
                                `<option value="${element.id}" ${selected}>[${element.short_code}] ${element.name}</option>`
                            );
                        });
                    }
                });
            } else {
                $(slsSelector).empty().append(`<option value="0" disabled> -- Pilih SLS -- </option>`);
            }
        }
        </script>
    @endpush
@endsection
