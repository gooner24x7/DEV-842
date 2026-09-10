<template>
    <div class="map-section">
        <div id="mapContainer" style="margin-top: 122px; height: 800px"></div>
    </div>
</template>

<script>
import api from "../../common/api"
import permissions from "../../common/permissions";
import "leaflet/dist/leaflet.css";
import L from 'leaflet';
import axios from 'axios';

export default {
    name: "ProjectQuotesMap",
    props: ['districts'],
    components: {},
    data() {
        return {
            map: null,
            layer: null,
            dataExample: {"DN1":4200,"DN2":9900,"DN3":24500,"DN4":7600,"DN5":15200,"DN6":3100,"DN7":11600,"DN8":6500,"DN9":5000,"DN10":13200,"DN11":7800,"DN12":3600,"DN14":8900},
            postcodeGeoJsonExample: {
                "type":"FeatureCollection","features":[
                    {"type":"Feature","properties":{"district":"DN1"},"geometry":{"type":"Polygon","coordinates":[[[-1.145,53.535],[-1.119,53.538],[-1.108,53.526],[-1.125,53.515],[-1.152,53.521],[-1.145,53.535]]]}},
                    {"type":"Feature","properties":{"district":"DN2"},"geometry":{"type":"Polygon","coordinates":[[[-1.115,53.545],[-1.060,53.552],[-1.035,53.530],[-1.075,53.505],[-1.118,53.520],[-1.115,53.545]]]}},
                    {"type":"Feature","properties":{"district":"DN3"},"geometry":{"type":"Polygon","coordinates":[[[-1.052,53.585],[-0.970,53.592],[-0.925,53.560],[-0.952,53.525],[-1.025,53.520],[-1.070,53.548],[-1.052,53.585]]]}},
                    {"type":"Feature","properties":{"district":"DN4"},"geometry":{"type":"Polygon","coordinates":[[[-1.160,53.515],[-1.115,53.510],[-1.070,53.490],[-1.085,53.455],[-1.155,53.458],[-1.205,53.482],[-1.160,53.515]]]}},
                    {"type":"Feature","properties":{"district":"DN5"},"geometry":{"type":"Polygon","coordinates":[[[-1.240,53.575],[-1.180,53.590],[-1.122,53.560],[-1.128,53.525],[-1.205,53.515],[-1.260,53.540],[-1.240,53.575]]]}},
                    {"type":"Feature","properties":{"district":"DN6"},"geometry":{"type":"Polygon","coordinates":[[[-1.280,53.640],[-1.190,53.660],[-1.120,53.630],[-1.145,53.580],[-1.238,53.575],[-1.300,53.598],[-1.280,53.640]]]}},
                    {"type":"Feature","properties":{"district":"DN7"},"geometry":{"type":"Polygon","coordinates":[[[-1.020,53.610],[-0.910,53.620],[-0.860,53.575],[-0.895,53.535],[-0.950,53.525],[-1.020,53.555],[-1.020,53.610]]]}},
                    {"type":"Feature","properties":{"district":"DN8"},"geometry":{"type":"Polygon","coordinates":[[[-1.050,53.705],[-0.955,53.720],[-0.890,53.685],[-0.920,53.630],[-1.025,53.615],[-1.085,53.655],[-1.050,53.705]]]}},
                    {"type":"Feature","properties":{"district":"DN9"},"geometry":{"type":"Polygon","coordinates":[[[-0.970,53.505],[-0.850,53.525],[-0.790,53.470],[-0.840,53.420],[-0.955,53.430],[-1.020,53.470],[-0.970,53.505]]]}},
                    {"type":"Feature","properties":{"district":"DN10"},"geometry":{"type":"Polygon","coordinates":[[[-0.995,53.410],[-0.850,53.420],[-0.755,53.365],[-0.820,53.315],[-0.980,53.330],[-1.055,53.370],[-0.995,53.410]]]}},
                    {"type":"Feature","properties":{"district":"DN11"},"geometry":{"type":"Polygon","coordinates":[[[-1.185,53.445],[-1.085,53.455],[-1.020,53.420],[-1.065,53.360],[-1.195,53.350],[-1.285,53.390],[-1.185,53.445]]]}},
                    {"type":"Feature","properties":{"district":"DN12"},"geometry":{"type":"Polygon","coordinates":[[[-1.320,53.520],[-1.225,53.525],[-1.195,53.480],[-1.235,53.435],[-1.350,53.455],[-1.380,53.495],[-1.320,53.520]]]}},
                    {"type":"Feature","properties":{"district":"DN14"},"geometry":{"type":"Polygon","coordinates":[[[-0.960,53.790],[-0.820,53.805],[-0.690,53.735],[-0.760,53.655],[-0.900,53.650],[-1.000,53.705],[-0.960,53.790]]]}}
                ]
            },
            data: {},
            postcodeGeoJson: {},
            quotes: [],
            projectCoords: { lat: null, long: null}
        }
    },
    async mounted() {
        const self = this;
    },
    watch: {
        districts: {
            async handler() {
                const self = this;
                await self.init();
            },
            deep: true,
            immediate: true
        }
    },
    methods: {
        async init() {
            const self = this;

            let districtStrings = [];

            if (self.districts) {
                districtStrings = Object.keys(self.districts);

                districtStrings.forEach((value) => {
                    self.data[value] = (self.districts[value].materials ?? 0) + (self.districts[value].trades ?? 0)
                });
            }

            await self.loadGeoJson(districtStrings);
            self.renderMap();
        },
        async loadGeoJson(districts) {
            const self = this;

            // Group districts by their postcode area (letter prefix)
            const areaMap = {};
            districts.forEach(district => {
                const area = district.replace(/[^A-Za-z]/g, '').toUpperCase();
                if (area && !areaMap[area]) {
                    areaMap[area] = [];
                }
                if (area) {
                    areaMap[area].push(district.toUpperCase());
                }
            });

            // Fetch each unique area GeoJSON file in parallel
            const areas = Object.keys(areaMap);
            const responses = await Promise.all(
                areas.map(area =>
                    axios.get(`/geojson/${area}.geojson`)
                        .then(response => response.data)
                        .catch((e) => console.log('Error fetching GeoJSON:', e))
                )
            );

            // Merge and filter features matching the requested districts
            const features = [];
            responses.forEach((geojson, index) => {
                if (!geojson || !geojson.features) return;
                const requestedDistricts = areaMap[areas[index]];

                geojson.features.forEach(feature => {
                    if (requestedDistricts.includes(feature.properties.name)) {
                        features.push(feature);
                    }
                });
            });

            self.postcodeGeoJson = {
                type: 'FeatureCollection',
                features: features
            };
        },
        renderMap() {
            const self = this;

            if (self.map) {
                self.map.remove();
                self.map = null;
            }

            self.map = L.map('mapContainer').setView([53.54,-1.05], 10);

            L.tileLayer(
                'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }
            ).addTo(self.map);

            const legend = L.control({position:'bottomright'});
            legend.onAdd = function() {
                const div = L.DomUtil.create('div','legend');
                div.innerHTML = '<b>Procured Amount</b><br>' +
                    '<i class="legend-icon" style="background:#166534"></i>£20k+<br>' +
                    '<i class="legend-icon" style="background:#22c55e"></i>£12k - £20k<br>' +
                    '<i class="legend-icon" style="background:#86efac"></i>£7k - £12k<br>' +
                    '<i class="legend-icon" style="background:#dcfce7"></i>Under £7k<br>' +
                    '<i class="legend-icon" style="background:#f8fafc"></i>No data';
                return div;
            };

            legend.addTo(self.map);
            if(self.layer) self.layer.remove();

            self.layer = L.geoJSON(self.postcodeGeoJson, {
                renderer: L.canvas(),
                style: (feature) => self.style(feature),
                onEachFeature: (feature, lyr) => {
                    const d = feature.properties.name;
                    const v = self.data[d] || 0;
                    lyr.bindPopup(`<h2 style="margin:0 0 6px">${d}</h2><b>Procured:</b> ${self.formatPrice(v)}<br><span>Marketplace / Purchase & Hire</span>`);
                    lyr.bindTooltip(`<span class="postcode-label">${d}</span>`, {permanent:true, direction:'center', className:'postcode-label'});
                }
            }).addTo(self.map);
        },
        districtFromPostcode(pc) {
            return String(pc || '').toUpperCase().replace(/\s+/g,'').match(/^[A-Z]{1,2}\d{1,2}[A-Z]?/)?.[0] || '';
        },
        formatPrice(n) {
            return '£' + Number(n||0).toLocaleString('en-GB', {maximumFractionDigits:2});
        },
        style(feature) {
            const self = this;
            const d = feature.properties.name;
            const v = self.data[d] || 0;

            return { fillColor: self.color(v), weight: 2, opacity: 1, color: v ? '#111827' : '#64748b', fillOpacity: v ? 0.55 : 0.20 };
        },
        color(v) {
            if(!v) return '#f8fafc';
            if(v >= 20000) return '#166534';
            if(v >= 12000) return '#22c55e';
            if(v >= 7000) return '#86efac';

            return '#dcfce7';
        }
    }
}
</script>

<style>
#mapContainer {
    height: 100vh;
}
.legend-icon {
    display: inline-block;
    height: 12px;
    width: 12px;
    vertical-align: middle;
    margin-right: 2px;
}
</style>
