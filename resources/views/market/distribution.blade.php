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
    @include('layouts.navbars.auth.topnav', ['title' => 'Persebaran Muatan Pasar'])
    <div class="full-screen-bg"></div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex align-items-center">
                    <h4 class="text-capitalize">Persebaran Muatan Pasar</h4>
                </div>
            </div>
            <div class="card-body pt-1">
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
            const map = L.map('map').setView([37.7749, -122.4194], 10);

            // Add the base tile layer (OpenStreetMap)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 25
            }).addTo(map);

            // Create a layer group for markers
            const markersLayer = L.layerGroup().addTo(map);

            // Function to fetch points from the API
            async function fetchPoints() {
                $.ajax({
                    type: 'GET',
                    url: '/pasar/data?&regency=3578',
                    success: function(response) {
                        const data = [];
                        response.data.forEach(point => {
                            data.push({
                                id: point.id,
                                name: point.name,
                                lat: point.latitude,
                                lng: point.longitude
                            });
                        });

                        moveToLocation(data[0].lat, data[0].lng); 

                        // Add markers for each point
                        data.forEach(point => {
                            addMarker(point);
                        });

                        // Adjust map view to fit all markers
                        if (data.length > 0) {
                            const bounds = markersLayer.getBounds();
                            map.fitBounds(bounds);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert("Failed to load map points. Please try again later.");
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
                    url: '/pasar/data?&regency=3578',
                    success: function(response) {
                        const details = {
                            id: pointId,
                            name: tooltipContent, // Use stored name
                            description: `This is a detailed description for location #${pointId}.`,
                            visitors: Math.floor(Math.random() * 1000000),
                            established: 1900 + Math.floor(Math.random() * 120),
                            status: Math.random() > 0.5 ? "Open" : "Closed"
                        };

                        // Update popup with details
                        popup.setContent(`
                <div class="popup-content">
                    <div class="popup-title">${details.name}</div>
                    <div class="popup-detail"><strong>Description:</strong> ${details.description}</div>
                    <div class="popup-detail"><strong>Annual Visitors:</strong> ${details.visitors.toLocaleString()}</div>
                    <div class="popup-detail"><strong>Established:</strong> ${details.established}</div>
                    <div class="popup-detail"><strong>Status:</strong> ${details.status}</div>
                </div>
            `);
                        marker.openPopup();
                    },
                    error: function(xhr, status, error) {
                        alert("Gagal memuat detail titik. Log sudah disimpan.");
                    }
                });
            }

            // Function to move map to specific coordinates
            function moveToLocation(lat, lng, zoom = 15) {
                map.setView([lat, lng], zoom);
            }

            // Example usage of moveToLocation function:
            // moveToLocation(-6.2088, 106.8456); // Move to Jakarta
            // moveToLocation(-7.7956, 110.3695, 14); // Move to Yogyakarta with zoom level 14

            // Load the points when the page loads
            document.addEventListener('DOMContentLoaded', fetchPoints);
        </script>
    @endpush
@endsection
