<template>
    <div style="margin: 1em">

        <v-data-table
            :options = "options"
            :expanded.sync="expanded"
            :headers="headers"
            :items="items"
            :loading="loader"
            item-key="id"
            loading-text="Loading... Please wait"
        >
            <template v-slot:top>
                <v-row>
                    <v-col cols="12" md="6">
                        <v-text-field
                            v-model="filters.search"
                            label="Search projects"
                            prepend-inner-icon="mdi-magnify"
                            clearable
                            dense
                            outlined
                            hide-details
                        ></v-text-field>
                    </v-col>

                    <v-col cols="12" md="3">
                        <v-menu
                            v-model="menu1"
                            :close-on-content-click="false"
                            max-width="290"
                        >
                            <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                    v-model="filters.dateStartFrom"
                                    label="Date Start From"
                                    v-bind="attrs"
                                    v-on="on"
                                    dense
                                    outlined
                                    hide-details
                                    clearable
                                ></v-text-field>
                            </template>
                            <v-date-picker
                                v-model="dateStartFrom"
                                no-title
                                @change="menu1 = false"
                            ></v-date-picker>
                        </v-menu>
                    </v-col>

                    <v-col cols="12" md="3">
                        <v-menu
                            v-model="menu2"
                            :close-on-content-click="false"
                            max-width="290"
                        >
                            <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                    v-model="filters.dateStartTo"
                                    label="Date Start To"
                                    v-bind="attrs"
                                    v-on="on"
                                    dense
                                    outlined
                                    hide-details
                                    clearable
                                ></v-text-field>
                            </template>
                            <v-date-picker
                                v-model="dateStartTo"
                                no-title
                                @change="menu2 = false"
                            ></v-date-picker>
                        </v-menu>
                    </v-col>
                </v-row>

                <v-row>
                    <v-col>
                        <v-btn
                            @click="applyFilters()"
                            color="primary"
                        >
                            Apply Filters
                        </v-btn>
                        <v-btn
                            @click="clearFilters()"
                            color="primary"
                        >
                            Clear Filters
                        </v-btn>
                    </v-col>
                </v-row>
            </template>

            <template v-slot:item.date_start="{ item }">
                {{ item.date_start | formatDate }}
            </template>

            <template v-slot:item.date_end="{ item }">
                {{ item.date_end | formatDate }}
            </template>

            <template v-slot:item.documents="{ item }">
                <v-tooltip top>
                    <template v-slot:activator="{ on, attrs }">
                        <v-btn
                            class="mr-2"
                            color="black"
                            icon
                            small
                            v-bind="attrs"
                            @click="showDocuments(item)"
                            v-on="on"
                        >
                            <v-icon>
                                mdi-file-document
                            </v-icon>
                        </v-btn>
                    </template>
                    <span>Documents</span>
                </v-tooltip>
            </template>

            <template v-slot:item.actions="{ item }">
                <v-btn class="primary" small v-on:click="view(item.id)">View</v-btn>
                <v-btn class="primary" small v-on:click="viewDetails(item)">Details</v-btn>
            </template>
        </v-data-table>

        <v-dialog v-model="dialog" max-width="900px">
            <v-card>
                <v-card-title>Project Details</v-card-title>
                <v-card-text>
                    <v-form ref="projectDetailsForm" :disabled="true">
                        <v-container>
                            <v-row>
                                <v-col>
                                    <v-text-field
                                        v-model="dialogItem.sector"
                                        label="Sector"
                                    ></v-text-field>
                                    <v-text-field
                                        v-model="dialogItem.region"
                                        label="Region"
                                    ></v-text-field>
                                    <v-text-field
                                        v-model="dialogItem.frame_type"
                                        label="Frame Type"
                                    ></v-text-field>
                                    <v-text-field
                                        v-model="dialogItem.procurement_route"
                                        label="Procurement Route"
                                    ></v-text-field>
                                    <v-text-field
                                        v-model="dialogItem.boq_standard"
                                        label="BoQ Standard"
                                    ></v-text-field>
                                    <v-autocomplete
                                        v-model="dialogItem.stage"
                                        :items="stageOptions"
                                        label="Project Stage"
                                    ></v-autocomplete>
                                    <v-autocomplete
                                        v-model="dialogItem.status"
                                        :items="statusOptions"
                                        label="Project Status"
                                    ></v-autocomplete>
                                    <v-autocomplete
                                        v-model="dialogItem.level"
                                        :items="levelOptions"
                                        label="Data Confidence Level"
                                    ></v-autocomplete>
                                </v-col>
                                <v-col>
                                    <v-text-field
                                        v-model="dialogItem.project_value"
                                        label="Project Value"
                                        type="number"
                                        prefix="£"
                                    ></v-text-field>
                                    <v-text-field
                                        v-model="dialogItem.area_sqm"
                                        label="Gross Internal Area"
                                        type="number"
                                        suffix="m2"
                                    ></v-text-field>
                                    <v-text-field
                                        v-model="dialogItem.perc_services"
                                        label="Services Percentage"
                                        type="number"
                                        suffix="%"
                                    ></v-text-field>
                                    <v-text-field
                                        v-model="dialogItem.perc_prelim"
                                        label="Preliminaries Percentage"
                                        type="number"
                                        suffix="%"
                                    ></v-text-field>
                                    <v-text-field
                                        v-model="dialogItem.perc_labour"
                                        label="Labour Percentage"
                                        type="number"
                                        suffix="%"
                                    ></v-text-field>
                                    <v-text-field
                                        v-model="dialogItem.perc_materials"
                                        label="Materials Percentage"
                                        type="number"
                                        suffix="%"
                                    ></v-text-field>
                                </v-col>
                            </v-row>

                            <h5 class="mt-2">Calculated Values</h5>
                            <v-row>
                                <v-col>
                                    <v-text-field
                                        v-model="dialogItem.total_packages"
                                        label="Total Package Count"
                                        type="number"
                                    ></v-text-field>
                                    <v-text-field
                                        v-model="dialogItem.total_quotes"
                                        label="Total Supplier Quotes"
                                        type="number"
                                    ></v-text-field>
                                    <v-text-field
                                        v-model="dialogItem.min_quote_value"
                                        label="Lowest Tender Value"
                                        type="number"
                                        prefix="£"
                                    ></v-text-field>
                                    <v-text-field
                                        v-model="dialogItem.awarded_value"
                                        label="Awarded Value"
                                        type="number"
                                        prefix="£"
                                    ></v-text-field>
                                    <v-text-field
                                        v-model="dialogItem.cost_per_sqm"
                                        label="Benchmark Cost per m2"
                                        type="number"
                                        prefix="£"
                                    ></v-text-field>
                                </v-col>
                                <v-col>
                                    <v-text-field
                                        v-model="dialogItem.created_by"
                                        label="Created By"
                                    ></v-text-field>
                                    <v-text-field
                                        v-model="dialogItem.created_at"
                                        label="Created At"
                                    ></v-text-field>
                                    <v-text-field
                                        v-model="dialogItem.updated_at"
                                        label="Updated At"
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                        </v-container>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="blue darken-1" text @click="dialog=false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";
import moment from "moment";

export default {
    props: [],
    components: {

    },
    data() {
        return {
            loader: false,
            items: [],
            expanded: [],
            searchTimeout: null,
            dateStartFrom: '',
            dateStartTo: '',
            menu1: false,
            menu2: false,
            dialog: false,
            dialogItem: {
                id: null,
                sector: null,
                region: null,
                frame_type: null,
                procurement_route: null,
                boq_standard: null,
                stage: null,
                status: null,
                level: null,
                project_value: null,
                area_sqm: null,
                perc_services: null,
                perc_prelim: null,
                perc_labour: null,
                perc_materials: null,
                total_packages: null,
                total_quotes: null,
                min_quote_value: null,
                awarded_value: null,
                cost_per_sqm: null,
                created_by: null,
                created_at: null,
                updated_at: null,
            },
            filters: {
                search: null,
                dateStartFrom: null,
                dateStartTo: null,
            },
            options: {
                page: 1,
                itemsPerPage: 10,
                sortBy: ["id"],
                sortDesc: [true],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false,
                showAll: true,
            },
            stageOptions: [
                { value: 0, text: 'Live' },
                { value: 1, text: 'Tender' },
            ],
            statusOptions: [
                { value: 0, text: 'Live' },
                { value: 1, text: 'Tender' },
            ],
            levelOptions: [
                { value: 0, text: 'Bronze' },
                { value: 1, text: 'Silver' },
                { value: 2, text: 'Gold' },
            ],
            headers: [
                {
                    text: "ID",
                    value: "id",
                    sortable: true,
                },
                {
                    text: "Name",
                    value: "name",
                    sortable: true,
                },
                {
                    text: "Postcode",
                    value: "postcode",
                    sortable: true,
                },
                {
                    text: "Client",
                    value: "client_name",
                    sortable: true,
                },
                {
                    text: "Framework",
                    value: "framework",
                    sortable: true,
                },
                {
                    text: "Main Contractor",
                    value: "contractor_name",
                    sortable: true,
                },
                {
                    text: "Consultant",
                    value: "consultant_name",
                    sortable: true,
                },
                {
                    text: "Client Target Miles",
                    value: "target_miles_client",
                    sortable: true,
                },
                {
                    text: "Framework Target Miles",
                    value: "target_miles_framework",
                    sortable: true,
                },
                {
                    text: "Social Enterprise Budget",
                    value: "budget_se",
                    sortable: true,
                },
                {
                    text: "Social Enterprise Target",
                    value: "target_hours_se",
                    sortable: true,
                },
                {
                    text: "Apprenticeship Target Hours",
                    value: "target_hours_ap",
                    sortable: true,
                },
                {
                    text: "Start Date",
                    value: "date_start",
                    sortable: true,
                },
                {
                    text: "End Date",
                    value: "date_end",
                    sortable: true,
                },
                {
                    text: "",
                    value: "documents",
                    sortable: false,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: false,
                },
            ]
        };
    },

    async mounted() {
        const self = this;

        self.filters.dateStartFrom = moment().format('DD-MM-YYYY');
        self.dateStartFrom = moment().format('YYYY-MM-DD');

        await self.load();
    },

    watch: {
        // search() {
        //     const self = this;
        //     clearTimeout(self.searchTimeout);
        //     self.searchTimeout = setTimeout(() => {
        //         self.load();
        //     }, 300);
        // },
        dateStartFrom:  function (n, o) {
            this.filters.dateStartFrom = n ? moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY') : null;
        },
        dateStartTo:  function (n, o) {
            this.filters.dateStartTo = n ? moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY') : null;
        },
    },

    computed: {

    },

    methods: {
        view(projectId) {
            this.$router.push({
                name: "works-packages-pipeline",
                params: {
                    projectId: projectId
                }
            });
        },

        viewDetails(item) {
            const self = this;

            self.dialog = true;
            self.dialogItem = item;
        },

        async load() {
            const self = this;
            self.loader = true;

            let params = {
                ...self.options,
                page: self.options.page,
                itemsPerPage: self.options.itemsPerPage,
                sortBy: self.options.sortBy[0],
                sortDesc: self.options.sortDesc[0] ? 1 : 0,
                dateStartFrom: self.filters.dateStartFrom,
                dateStartTo: self.filters.dateStartTo,
                showAll: self.options.showAll,
                search: self.filters.search,
            };

            const response = await api.loadProjects(params);

            self.items = response.data;

            // filter out projects currentuser is assigned to
            self.items = self.items.filter(item => {
                let isAssigned = true;

                item.assigned_users.forEach(user => {
                    if (user.user_id === self.$store.getters.getSession.user.id) {
                        isAssigned = false;
                    }
                });

                return isAssigned;
            });

            self.loader = false;
        },

        async applyFilters() {
            const self = this;

            await self.load();
        },

        async clearFilters() {
            const self = this;

            self.filters = {
                search: null,
                dateStartFrom: null,
                dateStartTo: null,
            };

            self.dateStartFrom = '';
            self.dateStartTo = '';

            await self.load();
        },

        showDocuments(item) {
            this.$router.push({
                name: "project-document-categories",
                query: {
                    id: item.id
                }
            });
        },
    },
};
</script>

<style>

</style>
