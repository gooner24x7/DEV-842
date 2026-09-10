<template>
    <div style="margin: 1em">

        <!-- Page header -->
        <div class="d-flex align-center mb-1">
            <h1 class="cb-title">Main Contractor Benchmarking</h1>
        </div>
        <p class="grey--text text--darken-1 mb-4">
            Compare and benchmark main contractors performance on The Build Chain platform.
        </p>

        <!-- Filters -->
        <v-row dense align="center">
            <v-col cols="12" sm="6" md="2">
                <v-autocomplete
                    v-model="filters.status"
                    :items="options.statuses"
                    label="Status"
                    dense
                    outlined
                    hide-details
                ></v-autocomplete>
            </v-col>
            <v-col cols="12" sm="6" md="2">
                <v-autocomplete
                    v-model="filters.region"
                    :items="options.regions"
                    label="Region"
                    dense
                    outlined
                    hide-details
                ></v-autocomplete>
            </v-col>
            <v-col cols="12" sm="6" md="2">
                <v-autocomplete
                    v-model="filters.sector"
                    :items="options.sectors"
                    label="Sector"
                    dense
                    outlined
                    hide-details
                ></v-autocomplete>
            </v-col>
            <v-col cols="12" sm="6" md="2">
                <v-autocomplete
                    v-model="filters.framework"
                    :items="options.frameworks"
                    label="Framework"
                    dense
                    outlined
                    hide-details
                ></v-autocomplete>
            </v-col>
            <v-col cols="12" sm="6" md="2">
                <v-autocomplete
                    v-model="filters.projectStage"
                    :items="options.projectStages"
                    label="Project Stage"
                    dense
                    outlined
                    hide-details
                ></v-autocomplete>
            </v-col>
            <v-col cols="12" sm="6" md="2">
                <v-text-field
                    v-model="filters.search"
                    label="Search"
                    dense
                    outlined
                    clearable
                    hide-details
                ></v-text-field>
            </v-col>
        </v-row>

        <!-- Export report -->
<!--        <div class="text-right my-3">-->
<!--            <v-btn text small color="primary" @click="exportReport()">-->
<!--                <v-icon small class="mr-1">mdi-download</v-icon>-->
<!--                Export Report-->
<!--            </v-btn>-->
<!--        </div>-->

        <!-- Summary cards -->
        <v-row>
            <!-- Projects -->
            <v-col cols="12" md="4">
                <v-card outlined class="cb-summary-card fill-height">
                    <div class="d-flex align-center mb-1">
                        <v-icon color="primary" class="mr-2">mdi-folder-multiple-outline</v-icon>
                        <span class="cb-card-heading">PROJECTS</span>
                    </div>
                    <p class="cb-card-subtitle">Projects by current stage (overlapping lifecycle counts)</p>

                    <v-row class="mt-1" no-gutters>
                        <v-col
                            v-for="stat in summary.projects"
                            :key="stat.label"
                            cols="6"
                            sm="4"
                            class="py-1"
                        >
                            <div class="cb-stat-value">{{ formatNumber(stat.value) }}</div>
                            <div class="cb-stat-label">{{ stat.label }}</div>
                        </v-col>
                    </v-row>

                    <p class="cb-card-footnote mt-2 mb-0">
                        Each project is counted once in Unique Projects. Stage counts show how many
                        projects have reached each stage. Projects can start in any stage, so stage
                        totals may overlap.
                    </p>
                </v-card>
            </v-col>

            <!-- Procurement -->
            <v-col cols="12" md="4">
                <v-card outlined class="cb-summary-card fill-height">
                    <div class="d-flex align-center mb-1">
                        <v-icon color="primary" class="mr-2">mdi-clipboard-text-outline</v-icon>
                        <span class="cb-card-heading">PROCUREMENT</span>
                    </div>
                    <p class="cb-card-subtitle">&nbsp;</p>

                    <v-row class="mt-1" no-gutters>
                        <v-col
                            v-for="stat in summary.procurement"
                            :key="stat.label"
                            cols="6"
                            sm="3"
                            class="py-1"
                        >
                            <div class="cb-stat-value">{{ stat.value }}</div>
                            <div class="cb-stat-label">{{ stat.label }}</div>
                        </v-col>
                    </v-row>

                    <p class="cb-card-footnote mt-2 mb-0">
                        Response Rate = Quotes Received ÷ Enquiries Sent
                    </p>
                </v-card>
            </v-col>

            <!-- Supply chain -->
            <v-col cols="12" md="4">
                <v-card outlined class="cb-summary-card fill-height">
                    <div class="d-flex align-center mb-1">
                        <v-icon color="primary" class="mr-2">mdi-account-group-outline</v-icon>
                        <span class="cb-card-heading">SUPPLY CHAIN</span>
                    </div>
                    <p class="cb-card-subtitle">&nbsp;</p>

                    <v-row class="mt-1" no-gutters>
                        <v-col
                            v-for="stat in summary.supplyChain"
                            :key="stat.label"
                            cols="6"
                            sm="3"
                            class="py-1"
                        >
                            <div class="cb-stat-value">{{ stat.value }}</div>
                            <div class="cb-stat-label">{{ stat.label }}</div>
                        </v-col>
                    </v-row>

                    <p class="cb-card-footnote mt-2 mb-0">
                        SME Participation = SME Subcontractors Quoted ÷ Total Subcontractors Quoted
                    </p>
                </v-card>
            </v-col>
        </v-row>

        <!-- Main contractors table -->
        <h3 class="cb-section-title mt-6 mb-2">Main Contractors ({{ contractors.length }})</h3>

        <v-card outlined>
            <v-data-table
                v-model="selected"
                :headers="headers"
                :items="contractors"
                :loading="loader"
                :items-per-page="itemsPerPage"
                :search="filters.search"
                item-key="id"
                show-select
                loading-text="Loading... Please wait"
                mobile-breakpoint="1200"
                class="cb-table"
                :footer-props="{ 'items-per-page-options': [10, 25, 50, 100] }"
            >
                <template v-slot:item.name="{ item }">
                    <div class="d-flex align-center py-2">
                        <v-avatar :color="item.color" size="30" class="cb-avatar mr-2">
                            <span class="white--text">{{ item.name.charAt(0) }}</span>
                        </v-avatar>
                        <div>
                            <div class="font-weight-medium">{{ item.name }}</div>
                            <div class="caption grey--text">{{ item.url }}</div>
                        </div>
                    </div>
                </template>

                <template v-slot:item.responseRate="{ item }">
                    <span class="green--text text--darken-1 font-weight-medium">{{ item.responseRate }}%</span>
                </template>

                <template v-slot:item.smeParticipation="{ item }">
                    <span class="green--text text--darken-1 font-weight-medium">{{ item.smeParticipation }}%</span>
                </template>

                <template v-slot:item.actions="{ item }">
                    <v-btn color="primary" depressed small @click="viewProfile(item)">View Profile</v-btn>
                </template>
            </v-data-table>
        </v-card>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";
import moment from "moment";

export default {
    name: 'ContractorBenchmarking',
    components: {

    },
    data() {
        return {
            loader: false,
            itemsPerPage: 10,
            selected: [],
            filters: {
                status: 'All',
                region: 'All',
                sector: 'All',
                framework: 'All',
                projectStage: 'All',
                search: '',
            },
            options: {
                statuses: ['All', 'Active', 'Inactive'],
                regions: ['All', 'London', 'South East', 'South West', 'Midlands', 'North', 'Scotland', 'Wales'],
                sectors: ['All', 'Residential', 'Commercial', 'Infrastructure', 'Education', 'Healthcare'],
                frameworks: ['All', 'SCAPE', 'Pagabo', 'Fusion21', 'Procure Partnerships'],
                projectStages: ['All', 'Pre-Tender', 'Tender', 'Live', 'Completed'],
            },

            // Example summary data — to be replaced with API data later
            summary: {
                projects: [
                    { label: 'Unique Projects', value: 523 },
                    { label: 'Reached Pre-Tender', value: 189 },
                    { label: 'Reached Tender', value: 237 },
                    { label: 'Reached Live', value: 162 },
                    { label: 'Reached Completed', value: 98 },
                ],
                procurement: [
                    { label: 'Work Packages', value: '842' },
                    { label: 'Enquiries Sent', value: '16,959' },
                    { label: 'Quotes Received', value: '12,041' },
                    { label: 'Response Rate', value: '71%' },
                ],
                supplyChain: [
                    { label: 'Subcontractors Invited', value: '1,245' },
                    { label: 'Subcontractors Quoted', value: '842' },
                    { label: 'Subcontractors Awarded', value: '512' },
                    { label: 'SME Participation', value: '41%' },
                ],
            },

            headers: [
                { text: 'Main Contractor', value: 'name', sortable: true, align: 'start' },
                { text: 'Unique Projects', value: 'uniqueProjects', sortable: true, align: 'center' },
                { text: 'Reached Pre-Tender', value: 'reachedPreTender', sortable: true, align: 'center' },
                { text: 'Reached Tender', value: 'reachedTender', sortable: true, align: 'center' },
                { text: 'Reached Live', value: 'reachedLive', sortable: true, align: 'center' },
                { text: 'Reached Completed', value: 'reachedCompleted', sortable: true, align: 'center' },
                { text: 'Work Packages', value: 'workPackages', sortable: true, align: 'center' },
                { text: 'Enquiries Sent', value: 'enquiriesSent', sortable: true, align: 'center' },
                { text: 'Quotes Received', value: 'quotesReceived', sortable: true, align: 'center' },
                { text: 'Response Rate', value: 'responseRate', sortable: true, align: 'center' },
                { text: 'Subcontractors Invited', value: 'subcontractorsInvited', sortable: true, align: 'center' },
                { text: 'Subcontractors Quoted', value: 'subcontractorsQuoted', sortable: true, align: 'center' },
                { text: 'Subcontractors Awarded', value: 'subcontractorsAwarded', sortable: true, align: 'center' },
                { text: 'SME Participation', value: 'smeParticipation', sortable: true, align: 'center' },
                { text: 'Action', value: 'actions', sortable: false, align: 'center' },
            ],

            // Example contractor rows — to be replaced with API data later
            contractors: [
                {
                    id: 1,
                    name: 'Willmott Dixon',
                    url: 'www.willmottdixon.co.uk',
                    logo: null,
                    color: '#4a5bd4',
                    uniqueProjects: 249,
                    reachedPreTender: 185,
                    reachedTender: 214,
                    reachedLive: 156,
                    reachedCompleted: 128,
                    workPackages: 59,
                    enquiriesSent: 40,
                    quotesReceived: '1,138',
                    responseRate: 71,
                    subcontractorsInvited: 562,
                    subcontractorsQuoted: 398,
                    subcontractorsAwarded: 245,
                    smeParticipation: 43,
                },
                {
                    id: 2,
                    name: 'Balfour Beatty',
                    url: 'www.balfourbeatty.com',
                    logo: null,
                    color: '#7a52c7',
                    uniqueProjects: 187,
                    reachedPreTender: 142,
                    reachedTender: 166,
                    reachedLive: 119,
                    reachedCompleted: 87,
                    workPackages: 34,
                    enquiriesSent: 22,
                    quotesReceived: 981,
                    responseRate: 71,
                    subcontractorsInvited: 421,
                    subcontractorsQuoted: 288,
                    subcontractorsAwarded: 191,
                    smeParticipation: 38,
                },
                {
                    id: 3,
                    name: "Laing O'Rourke",
                    url: 'www.laingorourke.com',
                    logo: null,
                    color: '#3aa8c1',
                    uniqueProjects: 156,
                    reachedPreTender: 123,
                    reachedTender: 144,
                    reachedLive: 108,
                    reachedCompleted: 97,
                    workPackages: 19,
                    enquiriesSent: 67,
                    quotesReceived: 34,
                    responseRate: 71,
                    subcontractorsInvited: 341,
                    subcontractorsQuoted: 241,
                    subcontractorsAwarded: 141,
                    smeParticipation: 41,
                },
                {
                    id: 4,
                    name: 'Bouygues UK',
                    url: 'www.bouygues-uk.com',
                    logo: null,
                    color: '#2e9e5b',
                    uniqueProjects: 138,
                    reachedPreTender: 110,
                    reachedTender: 128,
                    reachedLive: 107,
                    reachedCompleted: 76,
                    workPackages: 26,
                    enquiriesSent: 18,
                    quotesReceived: 903,
                    responseRate: 71,
                    subcontractorsInvited: 298,
                    subcontractorsQuoted: 210,
                    subcontractorsAwarded: 132,
                    smeParticipation: 45,
                },
                {
                    id: 5,
                    name: 'Kier Group',
                    url: 'www.kier.co.uk',
                    logo: null,
                    color: '#e8823a',
                    uniqueProjects: 124,
                    reachedPreTender: 98,
                    reachedTender: 114,
                    reachedLive: 85,
                    reachedCompleted: 64,
                    workPackages: 21,
                    enquiriesSent: 15,
                    quotesReceived: 812,
                    responseRate: 62,
                    subcontractorsInvited: 261,
                    subcontractorsQuoted: 184,
                    subcontractorsAwarded: 118,
                    smeParticipation: 45,
                },
            ],
        };
    },

    mounted() {

    },

    watch: {

    },

    computed: {

    },

    methods: {
        formatNumber(value) {
            if (value === null || value === undefined) {
                return value;
            }
            return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },

        initials(name) {
            return name
                .split(' ')
                .map(word => word.charAt(0))
                .join('')
                .substring(0, 2)
                .toUpperCase();
        },

        applyFilters() {
            // Placeholder — API filtering to be wired up later
        },

        exportReport() {
            // Placeholder — export to be wired up later
        },

        viewProfile(item) {
            // Placeholder — navigation to be wired up later
            this.$router.push({ name: 'contractor-profile', query: { id: item.id } });
        },
    },
};
</script>

<style scoped>


</style>
