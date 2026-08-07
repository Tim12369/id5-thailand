import Alpine from "alpinejs";
import { MarkerClusterer } from "@googlemaps/markerclusterer";

/**
 * Great-circle distance in kilometres (Haversine formula).
 */
function distanceKm(lat1, lng1, lat2, lng2) {
    const R = 6371;
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLng = ((lng2 - lng1) * Math.PI) / 180;
    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos((lat1 * Math.PI) / 180) *
            Math.cos((lat2 * Math.PI) / 180) *
            Math.sin(dLng / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

/** Escapes text interpolated into the raw HTML InfoWindow template. */
function esc(value) {
    return String(value ?? "").replace(
        /[&<>"']/g,
        (c) =>
            ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[
                c
            ])
    );
}

/**
 * Alpine component backing the /offices index page: search, province/region
 * filter, "near me" sorting, and the office list <-> map bridge.
 */
Alpine.data("officeLocator", (offices, provinces, regions = []) => ({
    offices,
    provinces,
    regions,
    search: "",
    province: "",
    region: "",
    userLocation: null,
    locating: false,
    locationError: false,
    selectedId: null,

    init() {
        // Marker click on the map highlights + scrolls to the matching list card.
        window.addEventListener("office-selected", (e) => {
            this.selectedId = e.detail.id;
            this.$nextTick(() => {
                document
                    .getElementById(`office-card-${e.detail.id}`)
                    ?.scrollIntoView({ behavior: "smooth", block: "nearest" });
            });
        });
    },

    get filtered() {
        let list = this.offices;

        if (this.region) {
            list = list.filter((o) => o.region === this.region);
        }

        if (this.province) {
            list = list.filter((o) => o.province === this.province);
        }

        if (this.search.trim()) {
            const q = this.search.trim().toLowerCase();
            list = list.filter((o) =>
                [o.name_th, o.name_en, o.province, o.region, o.code, o.address]
                    .filter(Boolean)
                    .some((field) => field.toLowerCase().includes(q))
            );
        }

        if (this.userLocation) {
            list = list
                .map((o) => ({
                    ...o,
                    distance_km: distanceKm(
                        this.userLocation.lat,
                        this.userLocation.lng,
                        o.lat,
                        o.lng
                    ),
                }))
                .sort((a, b) => a.distance_km - b.distance_km);
        }

        return list;
    },

    findNearMe() {
        if (!navigator.geolocation) {
            this.locationError = true;
            return;
        }

        this.locating = true;
        this.locationError = false;

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                this.userLocation = {
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                };
                this.locating = false;

                if (window.officeMapController) {
                    window.officeMapController.showUserLocation(this.userLocation);
                    window.officeMapController.highlightNearest(
                        this.filtered.slice(0, 3).map((o) => o.id)
                    );
                }
            },
            () => {
                this.locating = false;
                this.locationError = true;
            }
        );
    },

    selectOffice(office) {
        this.selectedId = office.id;
        if (window.officeMapController) {
            window.officeMapController.focus(office);
        }
    },

    directionsUrl(office) {
        return (
            office.google_maps_url ||
            `https://www.google.com/maps/dir/?api=1&destination=${office.lat},${office.lng}`
        );
    },
}));

/**
 * Builds the rich InfoWindow HTML for a single office marker: photo, address,
 * opening hours, phone, official website (or a friendly "not available"
 * notice), directions, and an "ask this office" contact link.
 */
function buildInfoWindowContent(office) {
    const photo = office.image_url
        ? `<img src="${esc(office.image_url)}" alt="" style="width:100%;height:96px;object-fit:cover;border-radius:8px;margin-bottom:8px;">`
        : "";

    const hours = office.today_hours
        ? `<p style="font-size:12px;color:#475569;margin:0 0 4px;">🕒 ${esc(office.today_hours)}</p>`
        : `<p style="font-size:12px;color:#94a3b8;margin:0 0 4px;">🕒 Closed today</p>`;

    const phone = office.phone
        ? `<p style="font-size:12px;color:#475569;margin:0 0 4px;">📞 <a href="tel:${esc(office.phone)}" style="color:#1d4ed8;">${esc(office.phone)}</a></p>`
        : "";

    const websiteBtn = office.website
        ? `<a href="${esc(office.website)}" target="_blank" rel="noopener" style="display:inline-block;font-size:11px;font-weight:600;color:#1d4ed8;background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:4px 8px;margin:4px 4px 0 0;">Official website ↗</a>`
        : `<p style="font-size:11px;color:#94a3b8;margin:6px 0 0;">No official website available.</p>`;

    const directionsUrl =
        office.google_maps_url ||
        `https://www.google.com/maps/dir/?api=1&destination=${office.lat},${office.lng}`;
    const directionsBtn = `<a href="${esc(directionsUrl)}" target="_blank" rel="noopener" style="display:inline-block;font-size:11px;font-weight:600;color:#fff;background:#1d4ed8;border-radius:6px;padding:4px 8px;margin:4px 4px 0 0;">Directions</a>`;

    const askUrl = office.email ? `mailto:${office.email}` : office.url;
    const askBtn = `<a href="${esc(askUrl)}" style="display:inline-block;font-size:11px;font-weight:600;color:#334155;background:#f1f5f9;border-radius:6px;padding:4px 8px;margin:4px 0 0;">Ask this office</a>`;

    return `
        <div style="font-family: inherit; max-width: 240px;">
            ${photo}
            <p style="font-weight: 600; margin: 0 0 2px;">${esc(office.name)}</p>
            <p style="font-size: 11px; color: #94a3b8; margin: 0 0 6px;">${esc(office.province)}</p>
            <p style="font-size: 12px; color: #64748b; margin: 0 0 6px;">${esc(office.address ?? "")}</p>
            ${hours}
            ${phone}
            <div>${websiteBtn}${directionsBtn}${askBtn}</div>
            <a href="${esc(office.url)}" style="font-size: 12px; color: #1d4ed8; display:block; margin-top:8px;">View full details &rarr;</a>
        </div>
    `;
}

/**
 * Google Maps callback for the /offices index page. Reads office data from
 * `window.OFFICE_DATA` (set inline by the Blade view) and exposes a small
 * controller on `window.officeMapController` so the Alpine component above
 * can pan/highlight markers from list interactions.
 */
window.initOfficeMap = function initOfficeMap() {
    const mapEl = document.getElementById("office-map");
    if (!mapEl) return;

    const offices = window.OFFICE_DATA || [];

    const map = new google.maps.Map(mapEl, {
        center: { lat: 13.7563, lng: 100.5018 }, // Bangkok fallback
        zoom: 6,
        mapId: "OFFICE_LOCATOR_MAP",
    });

    const infoWindow = new google.maps.InfoWindow();
    const markers = {};
    const bounds = new google.maps.LatLngBounds();

    offices.forEach((office) => {
        if (!office.lat || !office.lng) return;

        const marker = new google.maps.Marker({
            position: { lat: office.lat, lng: office.lng },
            title: office.name,
        });

        marker.addListener("click", () => {
            infoWindow.setContent(buildInfoWindowContent(office));
            infoWindow.open(map, marker);

            window.dispatchEvent(
                new CustomEvent("office-selected", { detail: { id: office.id } })
            );
        });

        markers[office.id] = marker;
        bounds.extend(marker.getPosition());
    });

    const clusterer = new MarkerClusterer({ map, markers: Object.values(markers) });

    if (!bounds.isEmpty()) {
        map.fitBounds(bounds, 48);
    }

    let userMarker = null;

    window.officeMapController = {
        focus(office) {
            const marker = markers[office.id];
            if (!marker) return;
            map.panTo(marker.getPosition());
            map.setZoom(15);
            marker.setMap(map);
            google.maps.event.trigger(marker, "click");
        },

        showUserLocation(location) {
            if (userMarker) userMarker.setMap(null);
            userMarker = new google.maps.Marker({
                map,
                position: location,
                title: "Your location",
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 8,
                    fillColor: "#2563eb",
                    fillOpacity: 1,
                    strokeColor: "#ffffff",
                    strokeWeight: 2,
                },
            });
            map.panTo(location);
            map.setZoom(12);
        },

        highlightNearest(ids) {
            Object.entries(markers).forEach(([id, marker]) => {
                marker.setAnimation(
                    ids.includes(Number(id)) ? google.maps.Animation.BOUNCE : null
                );
            });
            setTimeout(() => {
                Object.values(markers).forEach((marker) => marker.setAnimation(null));
            }, 1500);
        },
    };

    window.dispatchEvent(new CustomEvent("office-map-ready"));
};

/**
 * Google Maps callback for the /offices/{slug} detail page. Renders a
 * single marker for `window.OFFICE_DETAIL`.
 */
window.initOfficeDetailMap = function initOfficeDetailMap() {
    const mapEl = document.getElementById("office-detail-map");
    if (!mapEl) return;

    const office = window.OFFICE_DETAIL;
    if (!office || !office.lat || !office.lng) return;

    const map = new google.maps.Map(mapEl, {
        center: { lat: office.lat, lng: office.lng },
        zoom: 15,
        mapId: "OFFICE_DETAIL_MAP",
    });

    new google.maps.Marker({
        map,
        position: { lat: office.lat, lng: office.lng },
        title: office.name,
    });
};
