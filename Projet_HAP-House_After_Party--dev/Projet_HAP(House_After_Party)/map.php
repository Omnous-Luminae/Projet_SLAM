<?php
require_once __DIR__ . '/config/db.php';

$pdo = $pdo ?? null;
$biens = [];
$pts_interet = [];
if ($pdo) {
    // Get biens with their streets and commune info; will fetch precise coordinates from adresse API client-side
    $stmt = $pdo->query("SELECT b.id_biens, b.nom_biens, b.rue_biens, b.description_biens, b.superficie_biens, b.nb_couchage, c.latitude_commune, c.longitude_commune, c.nom_commune, c.id_commune, p.lien_photo
        FROM Biens b
        LEFT JOIN Commune c ON b.id_commune = c.id_commune
        LEFT JOIN Photos p ON b.id_biens = p.id_biens
        LEFT JOIN (SELECT id_biens, MIN(id_photo) as min_id FROM Photos GROUP BY id_biens) min_p ON p.id_biens = min_p.id_biens AND p.id_photo = min_p.min_id
        WHERE (b.is_hidden IS NULL OR b.is_hidden = FALSE)");
    $biens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch points of interest with coordinates
    $stmt_pts = $pdo->query("SELECT pi.id_pts_interet, pi.lib_pts_interet, pi.description_pts_interet, c.latitude_commune, c.longitude_commune, c.nom_commune, t.lib_type_points_interet
        FROM Pts_Interet pi
        LEFT JOIN Commune c ON pi.id_commune = c.id_commune
        LEFT JOIN Type_Pts_Interet t ON pi.id_type_points_interet = t.id_type_points_interet
        WHERE c.latitude_commune IS NOT NULL AND c.longitude_commune IS NOT NULL");
    $pts_interet = $stmt_pts->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Carte des biens</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            margin: 0;
            font-family: 'Montserrat', Arial, Helvetica, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
        }
        .topbar {
            padding: 18px 32px;
            background: rgba(255,255,255,0.95);
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 18px;
            box-shadow: 0 4px 24px rgba(102,126,234,0.08);
            border-radius: 0 0 24px 24px;
            margin-bottom: 18px;
        }
        .back {
            color: #764ba2;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1em;
            transition: color 0.2s;
        }
        .back:hover {
            color: #a100b8;
            text-decoration: underline;
        }
        .topbar h3 {
            margin: 0;
            font-size: 1.3em;
            color: #333;
            letter-spacing: 1px;
        }
        #map {
            height: 78vh;
            max-width: 1100px;
            margin: 0 auto 32px auto;
            border-radius: 24px;
            box-shadow: 0 12px 48px rgba(102,126,234,0.18), 0 2px 8px rgba(0,0,0,0.08);
            border: 3px solid #fff;
            overflow: hidden;
            background: #f7f7fa;
        }
        /* Custom Leaflet controls */
        .leaflet-control-zoom {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(102,126,234,0.12);
        }
        .leaflet-control-zoom a {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff !important;
            font-weight: bold;
            font-size: 1.3em;
            border: none !important;
            margin: 0;
            transition: background 0.2s;
        }
        .leaflet-control-zoom a:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
        }
        /* Popup styling */
        .leaflet-popup-content-wrapper {
            border-radius: 16px !important;
            box-shadow: 0 4px 24px rgba(102,126,234,0.18);
            background: #fff;
            border: 2px solid #764ba2;
        }
        .leaflet-popup-content {
            font-family: 'Montserrat', Arial, Helvetica, sans-serif;
            color: #333;
            font-size: 1em;
        }
        .leaflet-popup-tip {
            background: #764ba2;
        }
        /* Marker hover effect */
        .leaflet-interactive:hover {
            filter: drop-shadow(0 0 8px #764ba2);
            cursor: pointer;
        }
        /* Responsive */
        @media (max-width: 900px) {
            #map { max-width: 98vw; height: 60vh; }
            .topbar { padding: 12px 8px; font-size: 0.98em; }
        }
        @media (max-width: 600px) {
            #map { height: 48vh; border-radius: 12px; }
            .topbar { border-radius: 0 0 12px 12px; }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <a class="back" href="/index.php">&larr; Accueil</a>
        <h3 style="margin:0">Carte des biens</h3>
    </div>
    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const biens = <?= json_encode($biens, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
        // create map and limit excessive zooming
        const map = L.map('map', {
            minZoom: 2
        });

        // set a default view
        if (biens.length) {
            map.setView([biens[0].latitude_commune, biens[0].longitude_commune], 8);
        } else {
            map.setView([46.5, 2.5], 6); // center of France
        }

        // noWrap prevents repeating the world horizontally when zoomed out
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap',
            noWrap: true
        }).addTo(map);

        // limit panning to the valid world bounds to avoid repeated maps
        map.setMaxBounds([[-85, -180], [85, 180]]);

        // Deduplicate biens by id (some joins could return duplicates)
        const dedupedBiens = (function(items) {
            const seen = new Map();
            for (const it of items) {
                const id = parseInt(it.id_biens, 10);
                if (!seen.has(id)) seen.set(id, it);
            }
            return Array.from(seen.values());
        })(biens);

    // Layer groups to manage markers and allow easy clearing
    const biensMarkerGroup = L.layerGroup().addTo(map);
    const ptsInteretMarkerGroup = L.layerGroup().addTo(map);
    // Map of markers by bien id so we can open a specific marker when returning from the detail page
    const markerById = new Map();

        function createPopupContent(b) {
            let popupContent = `<div style="max-width: 250px;">`;
            if (b.lien_photo) {
                popupContent += `<img src="/${b.lien_photo}" alt="${b.nom_biens}" style="width: 100%; height: auto; border-radius: 4px; margin-bottom: 8px;">`;
            }
            popupContent += `<strong>${b.nom_biens}</strong><br>`;
            popupContent += `${b.nom_commune || ''}<br>`;
            popupContent += `Superficie: ${b.superficie_biens} m²<br>`;
            popupContent += `Couchages: ${b.nb_couchage}<br>`;
            if (b.description_biens) {
                popupContent += `<p style="margin: 4px 0;">${b.description_biens.substring(0, 100)}${b.description_biens.length > 100 ? '...' : ''}</p>`;
            }
                // link will be handled by JS so we can append current map view (lat/lng/zoom)
                popupContent += `<a href="#" class="open-annonce" data-id="${b.id_biens}" style="color: #a100b8;">Voir l'annonce</a>`;
            popupContent += `</div>`;
            return popupContent;
        }

        function createPopupContentPtsInteret(p) {
            let popupContent = `<div style="max-width: 250px;">`;
            popupContent += `<strong>${p.lib_pts_interet}</strong><br>`;
            popupContent += `${p.nom_commune || ''}<br>`;
            popupContent += `Type: ${p.lib_type_points_interet || ''}<br>`;
            if (p.description_pts_interet) {
                popupContent += `<p style="margin: 4px 0;">${p.description_pts_interet.substring(0, 100)}${p.description_pts_interet.length > 100 ? '...' : ''}</p>`;
            }
            popupContent += `</div>`;
            return popupContent;
        }

        // Cache for bien coordinates (bien id => {lat, lon})
        const coordCache = new Map();

        // Fetch precise coordinates for a bien from adresse API using street + commune
        function fetchBienCoordinates(bien, callback) {
            const id = parseInt(bien.id_biens, 10);
            if (coordCache.has(id)) {
                return callback(coordCache.get(id));
            }

            const rue = bien.rue_biens || '';
            const ville = bien.nom_commune || '';
            const q = (rue + ' ' + ville).trim();
            if (!q || !rue) {
                // fallback to commune coordinates
                const lat = parseFloat(bien.latitude_commune);
                const lon = parseFloat(bien.longitude_commune);
                if (!isNaN(lat) && !isNaN(lon)) {
                    coordCache.set(id, { lat: lat, lon: lon });
                    return callback({ lat: lat, lon: lon });
                }
                return callback(null);
            }

            fetch('https://api-adresse.data.gouv.fr/search/?q=' + encodeURIComponent(q) + '&limit=5')
                .then(r => r.json())
                .then(data => {
                    const features = data && data.features ? data.features : [];
                    // prefer Point geometry
                    let f = features.find(feat => feat.geometry && feat.geometry.type === 'Point');
                    if (!f) f = features[0];
                    if (f && f.geometry && f.geometry.coordinates) {
                        const c = f.geometry.coordinates;
                        const result = { lat: c[1], lon: c[0] };
                        coordCache.set(id, result);
                        return callback(result);
                    }
                    // fallback to commune if no result
                    const lat = parseFloat(bien.latitude_commune);
                    const lon = parseFloat(bien.longitude_commune);
                    if (!isNaN(lat) && !isNaN(lon)) {
                        coordCache.set(id, { lat: lat, lon: lon });
                        return callback({ lat: lat, lon: lon });
                    }
                    return callback(null);
                })
                .catch(err => {
                    // fallback to commune on error
                    const lat = parseFloat(bien.latitude_commune);
                    const lon = parseFloat(bien.longitude_commune);
                    if (!isNaN(lat) && !isNaN(lon)) {
                        coordCache.set(id, { lat: lat, lon: lon });
                        return callback({ lat: lat, lon: lon });
                    }
                    return callback(null);
                });
        }

        function addBiensMarkers(items) {
            biensMarkerGroup.clearLayers();
            const seenIds = new Set();
            let pendingCount = items.length;

            function tryAddMarker(b, coords) {
                if (!coords) return;
                const lat = coords.lat;
                const lon = coords.lon;
                const id = parseInt(b.id_biens, 10);
                if (isNaN(lat) || isNaN(lon) || isNaN(id)) return;
                if (seenIds.has(id)) return;
                seenIds.add(id);

                // marker for precise location
                const marker = L.circleMarker([lat, lon], {
                    color: '#a100b8',
                    fillColor: '#a100b8',
                    fillOpacity: 0.8,
                    radius: 8
                });

                // small zone around the street location
                const zone = L.circle([lat, lon], {
                    radius: 30,
                    color: '#a100b8',
                    weight: 1,
                    opacity: 0.4,
                    fillOpacity: 0.08
                });

                marker.bindPopup(createPopupContent(b));
                markerById.set(id, marker);

                // Keep popup open while hovering marker OR popup
                (function(m) {
                    let closeTimeout = null;
                    function clearClose() {
                        if (closeTimeout) { clearTimeout(closeTimeout); closeTimeout = null; }
                    }
                    m.on('mouseover', function() {
                        clearClose();
                        m.openPopup();
                    });
                    m.on('mouseout', function() {
                        clearClose();
                        closeTimeout = setTimeout(function() { m.closePopup(); }, 300);
                    });
                    m.on('popupopen', function(e) {
                        const px = e.popup.getElement();
                        if (!px) return;
                        px.addEventListener('mouseenter', function() { clearClose(); });
                        px.addEventListener('mouseleave', function() {
                            clearClose();
                            closeTimeout = setTimeout(function() { m.closePopup(); }, 300);
                        });
                    });
                })(marker);

                biensMarkerGroup.addLayer(zone);
                biensMarkerGroup.addLayer(marker);
            }

            if (items.length === 0) {
                pendingCount = 0;
            }

            for (const b of items) {
                (function(bien) {
                    fetchBienCoordinates(bien, function(coords) {
                        tryAddMarker(bien, coords);
                        pendingCount--;
                    });
                })(b);
            }
        }

        function addPtsInteretMarkers(items) {
            ptsInteretMarkerGroup.clearLayers();
            for (const p of items) {
                const lat = parseFloat(p.latitude_commune);
                const lon = parseFloat(p.longitude_commune);
                const id = parseInt(p.id_pts_interet, 10);
                if (isNaN(lat) || isNaN(lon) || isNaN(id)) continue;

                const marker = L.circleMarker([lat, lon], {
                    color: '#ff0000',
                    fillColor: '#ff0000',
                    fillOpacity: 0.8,
                    radius: 6
                });

                marker.bindPopup(createPopupContentPtsInteret(p));
                // Keep popup open while hovering marker OR popup so user can click links.
                (function(m) {
                    let closeTimeout = null;

                    function clearClose() {
                        if (closeTimeout) { clearTimeout(closeTimeout); closeTimeout = null; }
                    }

                    m.on('mouseover', function() {
                        clearClose();
                        m.openPopup();
                    });

                    m.on('mouseout', function() {
                        // delay close to allow moving into the popup
                        clearClose();
                        closeTimeout = setTimeout(function() { m.closePopup(); }, 300);
                    });

                    m.on('popupopen', function(e) {
                        const px = e.popup.getElement();
                        if (!px) return;
                        px.addEventListener('mouseenter', function() {
                            clearClose();
                        });
                        px.addEventListener('mouseleave', function() {
                            clearClose();
                            closeTimeout = setTimeout(function() { m.closePopup(); }, 300);
                        });
                    });
                })(marker);
                ptsInteretMarkerGroup.addLayer(marker);
            }
        }

        // Initial render
        addBiensMarkers(dedupedBiens);
        const ptsInteret = <?= json_encode($pts_interet, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
        addPtsInteretMarkers(ptsInteret);

        // If the page was opened with map parameters (coming back from an annonce detail), restore view & open the marker
        (function restoreViewFromParams() {
            try {
                const params = new URLSearchParams(window.location.search);
                const lat = parseFloat(params.get('map_lat'));
                const lng = parseFloat(params.get('map_lng'));
                const zoom = parseInt(params.get('map_zoom'), 10);
                const openId = params.get('open_id');
                if (!isNaN(lat) && !isNaN(lng) && !isNaN(zoom)) {
                    map.setView([lat, lng], zoom);
                }
                if (openId) {
                    const id = parseInt(openId, 10);
                    const marker = markerById.get(id);
                    if (marker) {
                        // open after a short delay to ensure markers are added
                        setTimeout(() => marker.openPopup(), 250);
                    }
                }
            } catch (e) {
                // noop
            }
        })();

        // When the map moves/zooms, re-render markers to reflect current bounds
        map.on('moveend', function() {
            const bounds = map.getBounds();
            const visible = dedupedBiens.filter(b => {
                const lat = parseFloat(b.latitude_commune);
                const lon = parseFloat(b.longitude_commune);
                if (isNaN(lat) || isNaN(lon)) return false;
                return bounds.contains([lat, lon]);
            });
            // If nothing visible, show all markers (keeps UX consistent)
            addBiensMarkers(visible.length ? visible : dedupedBiens);
            // Re-add points of interest markers
            addPtsInteretMarkers(ptsInteret);
        });

        // Intercept clicks on 'Voir l'annonce' from popups so we can attach map view params
        document.addEventListener('click', function(e) {
            const target = e.target;
            if (target && target.classList && target.classList.contains('open-annonce')) {
                e.preventDefault();
                const id = target.getAttribute('data-id');
                if (!id) return;
                const center = map.getCenter();
                const zoom = map.getZoom();
                // build URL that sends user to the annonce detail and keeps map state
                const url = `forms/annonce_detail.php?id=${encodeURIComponent(id)}&from=map&map_lat=${encodeURIComponent(center.lat)}&map_lng=${encodeURIComponent(center.lng)}&map_zoom=${encodeURIComponent(zoom)}`;
                window.location.href = url;
            }
        });
    </script>
    <?php include '../theme_toggle.php'; ?>
</body>
</html>
