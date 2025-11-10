<?php
require_once __DIR__ . '/config/db.php';

$pdo = $pdo ?? null;
$biens = [];
if ($pdo) {
    // Use correct Photos primary key column name (id_photo) from the schema
    $stmt = $pdo->query("SELECT b.id_biens, b.nom_biens, b.description_biens, b.superficie_biens, b.nb_couchage, c.latitude_commune, c.longitude_commune, c.nom_commune, p.lien_photo
        FROM Biens b
        LEFT JOIN Commune c ON b.id_commune = c.id_commune
        LEFT JOIN Photos p ON b.id_biens = p.id_biens
        LEFT JOIN (SELECT id_biens, MIN(id_photo) as min_id FROM Photos GROUP BY id_biens) min_p ON p.id_biens = min_p.id_biens AND p.id_photo = min_p.min_id
        WHERE c.latitude_commune IS NOT NULL AND c.longitude_commune IS NOT NULL");
    $biens = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        body { margin:0; font-family: Arial, Helvetica, sans-serif; }
        #map { height: 80vh; }
        .topbar { padding: 12px; background: #fff; border-bottom: 1px solid #eee; display:flex; align-items:center; gap:12px; }
        .back { color:#a100b8; text-decoration:none; font-weight:600 }
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

    // Layer group to manage markers and allow easy clearing
    const markerGroup = L.layerGroup().addTo(map);
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

        function addMarkers(items) {
            markerGroup.clearLayers();
            const seenIds = new Set();
            for (const b of items) {
                const lat = parseFloat(b.latitude_commune);
                const lon = parseFloat(b.longitude_commune);
                const id = parseInt(b.id_biens, 10);
                if (isNaN(lat) || isNaN(lon) || isNaN(id)) continue;
                if (seenIds.has(id)) continue; // avoid duplicates
                seenIds.add(id);

                const marker = L.circleMarker([lat, lon], {
                    color: '#a100b8',
                    fillColor: '#a100b8',
                    fillOpacity: 0.8,
                    radius: 8
                });

                marker.bindPopup(createPopupContent(b));
                // keep reference to open specific popups later
                markerById.set(id, marker);
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
                markerGroup.addLayer(marker);
            }
        }

        // Initial render
        addMarkers(dedupedBiens);

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
            addMarkers(visible.length ? visible : dedupedBiens);
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
</body>
</html>