<template>
    <div class="map-section">
        <!--    filters-->
        <v-row>
            <v-col>
                <v-autocomplete
                    v-model="filters.radius"
                    :items="options.radius"
                    label="Radius"
                    chips
                    dense
                    outlined
                    small-chips
                    hide-details
                ></v-autocomplete>
            </v-col>
            <v-col>
                <v-autocomplete
                    v-model="filters.type"
                    :items="options.type"
                    label="Quote Type"
                    chips
                    dense
                    multiple
                    outlined
                    small-chips
                    hide-details
                ></v-autocomplete>
            </v-col>
        </v-row>
        <!--    generate map button-->
        <div class="button-container">
            <v-btn color="primary" v-on:click="generateMapReport()">Generate Map Report</v-btn>
            <v-btn color="primary" v-on:click="exportMap()">Export PNG</v-btn>
        </div>
        <!--    map container-->
        <div id="contractorQuotesMap" class="map-container"></div>
        <!--    legend-->
        <div class="map-legend">
        <v-row>
            <v-col>
                <div class="legend-circle" style="background-color: blue"></div>
                <div class="legend-label">Trade Quotes</div>
            </v-col>
            <v-col>
                <div class="legend-circle" style="background-color: yellow"></div>
                <div class="legend-label">Material Quotes</div>
            </v-col>
        </v-row>
        <v-row>
            <v-col>
                <div class="legend-circle" style="background-color: limegreen"></div>
                <div class="legend-label">Accepted Trade Quotes</div>
            </v-col>
            <v-col>
                <div class="legend-circle" style="background-color: magenta"></div>
                <div class="legend-label">Accepted Material Quotes</div>
            </v-col>
        </v-row>
        </div>
    </div>
</template>

<script>
import api from "../../common/api"
import { Loader } from "@googlemaps/js-api-loader"
import jsPDF from "jspdf";
import html2canvas from "html2canvas";
import permissions from "../../common/permissions";

export default {
    name: "ContractorQuotesMap",
    props: ['projectId', 'worksPackageId'],
    components: {},
    data() {
        return {
            options: {
                radius: [
                    {
                        text: "10 Miles",
                        value: 10,
                    },
                    {
                        text: "20 Miles",
                        value: 20,
                    },
                    {
                        text: "40 Miles",
                        value: 40,
                    },
                ],
                type: [
                    {
                        text: "Material Quotes",
                        value: 1,
                    },
                    {
                        text: "Trade Quotes",
                        value: 2,
                    },
                    {
                        text: "Accepted Material Quotes",
                        value: 3,
                    },
                    {
                        text: "Accepted Trade Quotes",
                        value: 4,
                    },
                ],
                worksPackages: [],
                projects: []
            },
            filters: {
                radius: null,
                type: null,
                worksPackageIds: [],
                projectId: null,
            },
            quotes: [],
            filtersLoader: false,
            searchWorksPackage: '',
            searchProject: '',
            mapCentreLat: 53.5157035,
            mapCentreLng: -1.1275345,
            projectCoords: { lat: null, long: null}
        }
    },
    async mounted() {
        const self = this;

        self.filters.radius = 40;

        await self.load();
        await self.generateMapReport();
    },
    watch: {
        projectId: {
            immediate: true,
            handler(n, o) {
                const self = this;
                self.filters.projectId = n;
                self.filters.worksPackageIds = [];
            }
        },
        worksPackageId: {
            immediate: true,
            handler(n, o) {
                const self = this;
                self.filters.worksPackageIds = n ? [n] : [];
            }
        }
    },
    methods: {
        async load() {
            const self = this;

            const loader = new Loader({
                apiKey: "AIzaSyAQg4W3bgC0Pd63MGI61iJsttcwuR3XPiA",
                version: "weekly"
            });

            const { Map, Circle, InfoWindow } = await loader.importLibrary("maps");
            const { Marker } = await loader.importLibrary("marker"); // todo: use AdvancedMarkerElement instead of Marker (deprecated)
            const { SymbolPath } = await loader.importLibrary("core");

            const markerColors = {1: 'yellow', 2: 'blue', 3: 'magenta', 4: 'limegreen'};

            let circlesArr = [];
            let zoom = 8;

            if (self.filters.radius === 10) {
                circlesArr.push(10);
                zoom = 10.5;
            } else if (self.filters.radius === 20) {
                circlesArr.push(10, 20);
                zoom = 9.5;
            } else if (self.filters.radius === 40) {
                circlesArr.push(10, 20, 40);
                zoom = 8.5;
            }

            let mapOptions = {
                center: { lat: self.mapCentreLat, lng: self.mapCentreLng},
                zoom: zoom,
                mapId: 'TBC_CONTRACTOR_QUOTES_MAP'
            };

            // render the map
            let map = new Map(document.getElementById("contractorQuotesMap"), mapOptions);

            // render markers for each quote
            for(let quote of self.quotes) {
                let circle = {
                    path: SymbolPath.CIRCLE,
                    fillColor: markerColors[quote.type],
                    fillOpacity: 1.0,
                    scale: 5.0,
                    strokeColor: 'white',
                    strokeWeight: 1
                };

                let marker = new Marker({
                    map: map,
                    position: { lat: quote.lat, lng: quote.long },
                    icon: circle
                });

                // show info window on hover
                let content =
                    `<div id="content">
                        <ul style="list-style: none; padding: 0;">
                            <li>Quote ID: ${quote.id}</li>
                            <li>Name: ${quote.name}</li>`;

                            if (self.isContractor() && (quote.type === 1 || quote.type === 3)) {
                                content += `<li>Local Material Spend: ${this.formatPrice(quote.local_material_spend)}</li>`;
                            } else {
                                content += `<li>Price: ${this.formatPrice(quote.price)}</li>`;
                            }

                content +=
                            `<li>Distance: ${quote.distance.toFixed(2)}</li>
                        </ul>
                    </div>`;

                let infoWindow = new InfoWindow({
                    content: content,
                    ariaLabel: "Quote Info",
                    headerDisabled: true
                });

                marker.addListener("mouseover", () => {
                    infoWindow.open({
                        anchor: marker,
                        map,
                    });
                });

                marker.addListener("mouseout", () => {
                    infoWindow.close();
                });
            }

            // render circles to represent selected radius
            if (self.projectCoords.lat != null && self.filters.radius != null) {
                for (let value of circlesArr) {
                    let radiusCircle = new Circle({
                        strokeColor: "#FF0000",
                        strokeOpacity: 0.8,
                        strokeWeight: 2,
                        fillColor: "#FF0000",
                        fillOpacity: 0.35,
                        map,
                        center: self.projectCoords,
                        radius: value * 1609,
                        title: value + 'miles'
                    });
                }
            }

        },

         generateMapReport() {
            const self = this;

            self.filtersLoader = true;

            api.getContractorMapData(self.filters)
                .then((response) => {
                    self.quotes = response.data.quotes;
                    self.projectCoords = response.data.project_coords;

                    if (self.projectCoords.lat != null) {
                        self.mapCentreLat = self.projectCoords.lat;
                        self.mapCentreLng = self.projectCoords.lng;
                    }

                    self.load();
                })
                .catch((error) => {
                    console.log(error.message);
                })
                .finally(() => {
                    self.filtersLoader = false;
                });
        },

        exportMap() {
            //let pdf = new jsPDF('p', 'px');
            let html = document.getElementById('contractorQuotesMap');
            let width = html.offsetWidth;

            html2canvas(html, {width: width, height: 800, allowTaint: false, useCORS: true})
                .then(function (canvas) {
                    let img = canvas.toDataURL("image/png");

                    let a = document.createElement("a");
                    a.href = img;
                    a.download = "map.png";
                    a.click();

                    // pdf.addImage(img, 'PNG', 10, 10, 600, 800);
                    // pdf.save('map.pdf');
                });
        },

        isSubContractor() {
            return permissions.hasRole(permissions.role_user);
        },

        isContractor() {
            return permissions.hasRole(permissions.role_contractor);
        },

        formatPrice(value) {
            if (value) {
                return '£ ' + parseFloat(value).toFixed(2);
            }

            return '';
        },
    }
}
</script>

<style scoped>

</style>
