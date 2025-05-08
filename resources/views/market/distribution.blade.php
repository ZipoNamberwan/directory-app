@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />

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
    </style>
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Persebaran Muatan Sentra Ekonomi'])
    <div class="full-screen-bg"></div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex align-items-center">
                    <h4 class="text-capitalize">Persebaran Muatan Sentra Ekonomi</h4>
                </div>
            </div>
            <div class="card-body pt-1">
                <div class="row mb-3">
                    @hasrole('adminprov')
                        <div class="col-md-3">
                            <label class="form-control-label">Satker <span class="text-danger">*</span></label>
                            <select id="organization" class="form-control" data-toggle="select">
                                <option value="0" disabled selected> -- Pilih Satker -- </option>
                                @foreach ($organizations as $organization)
                                    <option value="{{ $organization->id }}"
                                        {{ old('organization') == $organization->id ? 'selected' : '' }}>
                                        [{{ $organization->short_code }}] {{ $organization->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endhasrole
                    <div class="col-md-3">
                        <label class="form-control-label">Sentra Ekonomi <span class="text-danger">*</span></label>
                        <select id="market" class="form-control" data-toggle="select">
                            <option value="0" disabled selected> -- Pilih Sentra Ekonomi -- </option>
                            @foreach ($markets as $market)
                                <option value="{{ $market->id }}" {{ old('market') == $market->id ? 'selected' : '' }}>
                                    [{{$market->village_id}}] {{ $market->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="map"></div>
            </div>
        </div>
        @include('layouts.footers.auth.footer')
    </div>

    @push('js')
        <script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
        <script src="/vendor/select2/select2.min.js"></script>


        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

        <script>
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
                    selector: '#market',
                    placeholder: 'Pilih Sentra Ekonomi'
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
                    loadMarket(null, null)
                    // fetchPoints()
                },
                '#market': () => {
                    fetchPoints() // Refetch points when market changes
                },
            };

            Object.entries(eventHandlers).forEach(([selector, handler]) => {
                $(selector).on('change', handler);
            });

            function loadMarket(organizationid = null, selectedmarket = null) {
                let organizationSelector = `#organization`;
                let marketSelector = `#market`;

                let id = $(organizationSelector).val();
                if (organizationid != null) {
                    id = organizationid;
                }

                $(marketSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);

                if (id != null) {
                    $.ajax({
                        type: 'GET',
                        url: '/pasar/kab/' + id,
                        success: function(response) {
                            $(marketSelector).empty().append(
                                `<option value="0" disabled selected> -- Pilih Sentra Ekonomi -- </option>`);
                            response.forEach(element => {
                                let selected = selectedmarket == String(element.id) ? 'selected' : '';
                                $(marketSelector).append(
                                    `<option value="${element.id}" ${selected}>[${element.village_id}] ${element.name}</option>`
                                );
                            });
                        }
                    });
                } else {
                    $(marketSelector).empty().append(`<option value="0" disabled> -- Pilih Sentra Ekonomi -- </option>`);
                }
            }

            const map = L.map('map').setView([-7.536, 112.238], 8);

            // Add the base tile layer (OpenStreetMap)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 25
            }).addTo(map);

            // Create a layer group for markers
            const markersLayer = L.featureGroup().addTo(map);

            // Function to fetch points from the API with filter parameters
            async function fetchPoints() {
                // Show loading indicator
                showMapLoading(true, 10);

                // Clear existing markers
                markersLayer.clearLayers();

                // Build URL with filter parameters
                let apiUrl = '/pasar/peta?';

                // Add organization filter if selected
                const organizationValue = $('#organization').val();
                if (organizationValue && organizationValue != '0') {
                    apiUrl += `&organization=${organizationValue}`;
                }

                // Add market filter if selected
                const marketValue = $('#market').val();
                if (marketValue && marketValue != '0') {
                    apiUrl += `&market=${marketValue}`;
                }

                // Simulate progress advancement
                let progressInterval = setInterval(() => {
                    const currentProgress = parseInt(document.querySelector('.progress-indicator').style.width) ||
                        10;
                    if (currentProgress < 80) {
                        showMapLoading(true, currentProgress + 10);
                    }
                }, 200);

                $.ajax({
                    type: 'GET',
                    url: apiUrl,
                    success: function(response) {
                        // Clear the progress interval
                        clearInterval(progressInterval);
                        showMapLoading(true, 90);

                        const data = [];
                        response.forEach(point => {
                            data.push({
                                id: point.id,
                                name: point.name,
                                lat: point.latitude,
                                lng: point.longitude
                            });
                        });
                        // Add markers for each point
                        data.forEach(point => {
                            addMarker(point);
                        });

                        // Adjust map view to fit all markers
                        if (data.length > 0) {
                            if (data.length === 1) {
                                // If only one point, center on it with fixed zoom
                                moveToLocation(data[0].lat, data[0].lng, 15);
                            } else {
                                // If multiple points, fit bounds to show all markers
                                // Check if the layer has any markers before getting bounds
                                if (markersLayer.getLayers().length > 0) {
                                    // Use try-catch to handle any potential issues with getBounds
                                    try {
                                        const bounds = markersLayer.getBounds();
                                        map.fitBounds(bounds);
                                    } catch (e) {
                                        console.log(e)
                                        console.log("Could not fit bounds, using default view");
                                        // Fallback to a default view if bounds calculation fails
                                        map.setView([data[0].lat, data[0].lng], 10);
                                    }
                                }
                            }
                        }

                        // Hide loading indicator
                        setTimeout(() => {
                            showMapLoading(false);
                        }, 200);
                    },
                    error: function(xhr, status, error) {
                        // Clear the progress interval
                        clearInterval(progressInterval);
                        // Hide loading indicator
                        showMapLoading(false);
                        alert("Gagal membuka map. Log error sudah disimpan.");
                    }
                });
            }

            // Function to add a marker to the map
            function addMarker(point) {
                const marker = L.marker([point.lat, point.lng]).addTo(markersLayer);

                // Store the point name in the marker options for later use
                marker.pointName = point.name;

                // Add a permanent tooltip showing the point name
                marker.bindTooltip(point.name, {
                    permanent: true,
                    direction: 'top',
                    className: 'marker-label'
                });

                // Add click handler to show details
                marker.on('click', () => {
                    fetchPointDetails(point.id, marker);
                });

                // Add a handler for popup close to ensure tooltip is visible again
                marker.on('popupclose', () => {
                    // Re-enable the tooltip with the original name
                    if (!marker.getTooltip()) {
                        marker.bindTooltip(marker.pointName, {
                            permanent: true,
                            direction: 'top',
                            className: 'marker-label'
                        });
                    }
                });
            }

            // Function to fetch and display details for a specific point
            async function fetchPointDetails(pointId, marker) {
                const popup = L.popup();

                // Show loading state
                popup.setContent('<div class="loading">Loading details...</div>');

                // Store the tooltip content before removing it
                const tooltipContent = marker.pointName;

                // Temporarily unbind tooltip while popup is open
                marker.unbindTooltip();

                marker.bindPopup(popup).openPopup();

                $.ajax({
                    type: 'GET',
                    url: '/pasar/muatan/' + pointId,
                    success: function(response) {
                        // Update popup with details
                        popup.setContent(`
                <div class="popup-content">
                    <div class="popup-title">${response.name}</div>
                    <div class="popup-detail"><strong>Status Bangunan:</strong> ${response.status}</div>
                    <div class="popup-detail"><strong>Alamat Lengkap:</strong> ${response.address}</div>
                    <div class="popup-detail"><strong>Deksripsi Aktivitas:</strong> ${response.description}</div>
                    <div class="popup-detail"><strong>Sektor:</strong> ${response.sector}</div>
                    <div class="popup-detail"><strong>Catatan:</strong> ${response.note}</div>
                </div>
            `);
                        marker.openPopup();
                    },
                    error: function(xhr, status, error) {
                        alert("Silakan refresh halaman untuk mencoba lagi.");
                    }
                });
            }

            // Function to move map to specific coordinates
            function moveToLocation(lat, lng, zoom = 15) {
                map.setView([lat, lng], zoom);
            }

            // Load the points when the page loads
            document.addEventListener('DOMContentLoaded', fetchPoints);
        </script>
    @endpush
@endsection
