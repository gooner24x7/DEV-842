<template>
    <div style="margin: 1em">
<!--        <h2>Tender Notices v2</h2>-->

        <div style="position: relative;">
            <v-data-table
                :headers="headers"
                :items="items"
                :loading="loader"
                :options.sync="options"
                item-key="id"
                loading-text="Loading... Please wait"
                mobile-breakpoint="1000"
                hide-default-footer
            >

                <template v-slot:top>
                    <v-row>
                        <v-col cols="12" sm="2">
                            <v-autocomplete
                                v-model="filters.types"
                                :items="typeOptions"
                                label="Type"
                                chips
                                dense
                                multiple
                                outlined
                                small-chips
                                hide-details
                                clearable
                            ></v-autocomplete>
                        </v-col>
                        <v-col cols="12" sm="2">
                            <v-autocomplete
                                v-model="filters.statuses"
                                :items="statusOptions"
                                label="Status"
                                chips
                                dense
                                multiple
                                outlined
                                small-chips
                                hide-details
                                clearable
                            ></v-autocomplete>
                        </v-col>
                        <v-col cols="12" sm="2">
                            <v-autocomplete
                                v-model="filters.regions"
                                :items="regionOptions"
                                label="Region"
                                chips
                                dense
                                multiple
                                outlined
                                small-chips
                                hide-details
                                clearable
                            ></v-autocomplete>
                        </v-col>
                        <v-col cols="12" sm="3">
                            <v-text-field
                                v-model="filters.keyword"
                                label="Search keyword"
                                dense
                                outlined
                                hide-details
                                clearable
                            ></v-text-field>
                        </v-col>
                        <v-col cols="12" sm="3">
                            <v-text-field
                                v-model="filters.postcode"
                                label="Search postcode"
                                dense
                                outlined
                                hide-details
                                clearable
                            ></v-text-field>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="12" sm="2">
                            <v-menu
                                v-model="menu1"
                                :close-on-content-click="false"
                                max-width="290"
                            >
                                <template v-slot:activator="{ on, attrs }">
                                    <v-text-field
                                        v-model="filters.publishedFrom"
                                        label="Published From"
                                        v-bind="attrs"
                                        v-on="on"
                                        dense
                                        outlined
                                        hide-details
                                        clearable
                                    ></v-text-field>
                                </template>
                                <v-date-picker
                                    v-model="datePublishedFrom"
                                    no-title
                                    @change="menu1 = false"
                                ></v-date-picker>
                            </v-menu>
                        </v-col>
                        <v-col cols="12" sm="2">
                            <v-menu
                                v-model="menu2"
                                :close-on-content-click="false"
                                max-width="290"
                            >
                                <template v-slot:activator="{ on, attrs }">
                                    <v-text-field
                                        v-model="filters.publishedTo"
                                        label="Published To"
                                        v-bind="attrs"
                                        v-on="on"
                                        dense
                                        outlined
                                        hide-details
                                        clearable
                                    ></v-text-field>
                                </template>
                                <v-date-picker
                                    v-model="datePublishedTo"
                                    no-title
                                    @change="menu2 = false"
                                ></v-date-picker>
                            </v-menu>
                        </v-col>
                        <v-col cols="12" sm="2">
                            <v-menu
                                v-model="menu3"
                                :close-on-content-click="false"
                                max-width="290"
                            >
                                <template v-slot:activator="{ on, attrs }">
                                    <v-text-field
                                        v-model="filters.deadlineFrom"
                                        label="Deadline From"
                                        v-bind="attrs"
                                        v-on="on"
                                        dense
                                        outlined
                                        hide-details
                                        clearable
                                    ></v-text-field>
                                </template>
                                <v-date-picker
                                    v-model="dateDeadlineFrom"
                                    no-title
                                    @change="menu3 = false"
                                ></v-date-picker>
                            </v-menu>
                        </v-col>
                        <v-col cols="12" sm="2">
                            <v-menu
                                v-model="menu4"
                                :close-on-content-click="false"
                                max-width="290"
                            >
                                <template v-slot:activator="{ on, attrs }">
                                    <v-text-field
                                        v-model="filters.deadlineTo"
                                        label="Deadline To"
                                        v-bind="attrs"
                                        v-on="on"
                                        dense
                                        outlined
                                        hide-details
                                        clearable
                                    ></v-text-field>
                                </template>
                                <v-date-picker
                                    v-model="dateDeadlineTo"
                                    no-title
                                    @change="menu4 = false"
                                ></v-date-picker>
                            </v-menu>
                        </v-col>
                        <v-col cols="12" sm="2">
                            <v-text-field
                                v-model="filters.valueFrom"
                                label="Value From"
                                type="number"
                                placeholder="25000.00"
                                dense
                                outlined
                                hide-details
                                clearable
                            ></v-text-field>
                        </v-col>
                        <v-col cols="12" sm="2">
                            <v-text-field
                                v-model="filters.valueTo"
                                label="Value To"
                                type="number"
                                placeholder="250000.00"
                                dense
                                outlined
                                hide-details
                                clearable
                            ></v-text-field>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="12" sm="2">
                            <v-select
                                v-model="filters.isSubcontract"
                                label="Is Subcontract"
                                :items="booleanOptions"
                                dense
                                outlined
                                hide-details
                                clearable
                            ></v-select>
                        </v-col>
                        <v-col cols="12" sm="2">
                            <v-select
                                v-model="filters.suitableForSme"
                                label="Suitable for SME"
                                :items="booleanOptions"
                                dense
                                outlined
                                hide-details
                                clearable
                            ></v-select>
                        </v-col>
                        <v-col cols="12" sm="2">
                            <v-select
                                v-model="filters.suitableForVco"
                                label="Suitable for VCO"
                                :items="booleanOptions"
                                dense
                                outlined
                                hide-details
                                clearable
                            ></v-select>
                        </v-col>
                        <v-col cols="12" sm="2">
                            <v-select
                                v-model="filters.awardedToSme"
                                label="Awarded to SME"
                                :items="booleanOptions"
                                dense
                                outlined
                                hide-details
                                clearable
                            ></v-select>
                        </v-col>
                        <v-col cols="12" sm="2">
                            <v-select
                                v-model="filters.awardedToVcse"
                                label="Awarded to VCSE"
                                :items="booleanOptions"
                                dense
                                outlined
                                hide-details
                                clearable
                            ></v-select>
                        </v-col>
                        <v-col cols="12" sm="2">
                            <v-text-field
                                v-model="filters.radius"
                                label="Radius"
                                type="number"
                                placeholder="0.5"
                                dense
                                outlined
                                hide-details
                                clearable
                            ></v-text-field>
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
                    <v-row>
                        <v-col md="10">
                            <h5 v-if="hitCount > 0">Total Results: {{ hitCount }}</h5>
                        </v-col>
                        <v-col md="2">
                            <v-select
                                v-model="size"
                                label="Show"
                                :items="[10, 20, 50, 100]"
                                dense
                                outlined
                                hide-details
                            ></v-select>
                        </v-col>
                    </v-row>
                </template>

                <template v-slot:item.item.title="{ item }">
                    <router-link :to="{name: 'tender-notice-details', query: {id: item.item.id}}">{{ item.item.title }}</router-link>
                </template>

                <template v-slot:item.item.publishedDate="{ item }">
                    {{ item.item.publishedDate | formatDateTime }}
                </template>

                <template v-slot:item.actions="{ item }">

                </template>
            </v-data-table>
        </div>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions.js";
import moment from "moment";

export default {
    name: 'TenderNotices',
    components: {

    },
    data() {
        return {
            loader: false,
            menu1: false,
            menu2: false,
            menu3: false,
            menu4: false,
            datePublishedFrom: '',
            datePublishedTo: '',
            dateDeadlineFrom: '',
            dateDeadlineTo: '',
            items: [],
            size: 10,
            hitCount: 0,
            filters: {
                types: [],
                statuses: ["Open"],
                regions: [],
                keyword: null,
                postcode: null,
                radius: null,
                publishedFrom: null,
                publishedTo: null,
                deadlineFrom: null,
                deadlineTo: null,
                valueFrom: 5000000,
                valueTo: null,
                isSubcontract: null,
                suitableForSme: null,
                suitableForVco: null,
                awardedToSme: null,
                awardedToVcse: null,
            },
            headers: [
                {
                    text: "Notice ID",
                    value: "item.noticeIdentifier",
                    sortable: true,
                },
                {
                    text: "Title",
                    value: "item.title",
                    sortable: true,
                },
                {
                    text: "Organisation",
                    value: "item.organisationName",
                    sortable: true,
                },
                {
                    text: "Type",
                    value: "item.noticeType",
                    sortable: true,
                },
                {
                    text: "Status",
                    value: "item.noticeStatus",
                    sortable: true,
                },
                {
                    text: "Value Low",
                    value: "item.valueLow",
                    sortable: true,
                },
                {
                    text: "Value High",
                    value: "item.valueHigh",
                    sortable: true,
                },
                {
                    text: "Date Published",
                    value: "item.publishedDate",
                    sortable: true,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: false,
                },
            ],
            options: {
                page: 1,
                itemsPerPage: 100,
                sortBy: [],
                sortDesc: [],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false,
            },
            typeOptions: [
                'Contract',
                'Pipeline',
                'PreProcurement'
            ],
            statusOptions: [
                'Open',
                'Closed',
                'Awarded'
            ],
            regionOptions: [
                "East Midlands",
                "East of England",
                "North East",
                "North West",
                "Yorkshire and The Humber",
                "West Midlands",
                "London",
                "South East",
                "South West",
                "Ireland",
                "Isle of Man",
                "Northern Ireland",
                "Scotland",
                "Wales"
            ],
            booleanOptions: [true, false]
        };
    },

    async mounted() {
        const self = this;

        let storedFilters = this.$store.getters.getTenderNoticesFilters;

        if (Object.keys(storedFilters).length > 0) {
            self.filters = storedFilters;
        }

        await self.load();
    },

    watch: {
        datePublishedFrom:  function (n, o) {
            this.filters.publishedFrom = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        },
        datePublishedTo:  function (n, o) {
            this.filters.publishedTo = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        },
        dateDeadlineFrom:  function (n, o) {
            this.filters.deadlineFrom = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        },
        dateDeadlineTo:  function (n, o) {
            this.filters.deadlineTo = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        },
        size: function(n ,o) {
            this.load();
        }
    },

    computed: {

    },

    methods: {
        async load() {
            const self = this;
            self.loader = true;

            let params = self.filters;
            params.size = self.size;

            try {
                const response = await api.loadTenderNotices(params);

                self.items = response.data.noticeList;
                self.hitCount = response.data.hitCount;
            } catch (e) {
                console.log(e);
            }

            self.loader = false;
        },

        async applyFilters() {
            const self = this;

            this.$store.commit("setTenderNoticesFilters", self.filters);

            await self.load();
        },

        async clearFilters() {
            const self = this;

            self.filters = {
                types: [],
                statuses: ["Open"],
                regions: [],
                keyword: null,
                postcode: null,
                radius: null,
                publishedFrom: null,
                publishedTo: null,
                deadlineFrom: null,
                deadlineTo: null,
                valueFrom: 5000000,
                valueTo: null,
                isSubcontract: null,
                suitableForSme: null,
                suitableForVco: null,
                awardedToSme: null,
                awardedToVcse: null,
            };

            this.$store.commit("setTenderNoticesFilters", self.filters);

            await self.load();
        }
    }
};
</script>

<style>
</style>
