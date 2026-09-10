<template>
    <div style="margin: 1em">

        <h3 v-if="projectName !== ''">{{ projectName }}</h3>

        <v-row class="mt-2 mb-2">
            <v-col md="2">
                <v-autocomplete
                    v-model="filters.projectId"
                    :items="options.projects"
                    :loading="filtersLoader"
                    :search-input.sync="searchProject"
                    label="Project"
                    item-text="name"
                    item-value="id"
                    dense
                    outlined
                    hide-details
                    chips
                    small-chips
                ></v-autocomplete>
            </v-col>
            <v-col md="2">
                <v-autocomplete
                    v-model="filters.worksPackageId"
                    :items="options.worksPackages"
                    :loading="filtersLoader"
                    :search-input.sync="searchWorksPackage"
                    label="Works Package"
                    item-text="name"
                    item-value="id"
                    dense
                    outlined
                    hide-details
                    chips
                    small-chips
                ></v-autocomplete>
            </v-col>
            <v-col md="2">
                <v-autocomplete
                    v-model="filters.stage"
                    :items="options.stages"
                    label="Stage"
                    item-text="stage_str"
                    item-value="stage"
                    return-object
                    dense
                    outlined
                    hide-details
                ></v-autocomplete>
            </v-col>
            <v-col md="6">
                <v-btn class="float-right" color="primary" @click="exportCsv()">Export CSV</v-btn>
                <v-btn class="float-right mr-1" color="primary" v-if="tab === 0" @click="exportWdPdf()">Export WD PDF</v-btn>
                <v-btn class="float-right mr-1" color="primary" v-if="showReviews()" @click="gotoReviewTemplates()">Review Templates</v-btn>
            </v-col>
        </v-row>

        <v-tabs v-model="tab" align-tabs="left">
            <v-tab :value="0" :disabled="disableTabs">Overall</v-tab>
            <v-tab :value="1" :disabled="disableTabs">Materials Procurement</v-tab>
            <v-tab :value="2" v-show="!isSubContractor()" :disabled="disableTabs">Trades Procurement</v-tab>
            <v-tab :value="3" :disabled="disableTabs">Social E/Apprenticeships</v-tab>
            <v-tab v-if="showOtherTab()" :value="4">Other</v-tab>
        </v-tabs>

        <v-window v-model="tab">
            <v-window-item :key="0" :value="0">
                <v-container fluid>
                    <div v-if="dataFound">
                        <v-row>
                            <v-col cols="12" md="6">
                                <project-quotes-map
                                    :districts="projectDistricts"
                                ></project-quotes-map>
                            </v-col>
                            <v-col cols="12" md="6">
                                <contractor-quotes-map
                                    :projectId="filters.projectId"
                                    :worksPackageId="filters.worksPackageId"
                                ></contractor-quotes-map>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col cols="12">
                                <v-card>
                                    <v-card-title style="justify-content: space-between">
                                        Total Spend
                                        <v-tooltip top>
                                            <template v-slot:activator="{ on, attrs }">
                                                <v-btn class="btn-icon" icon small v-bind="attrs" v-on="on">
                                                    <v-icon>mdi-information</v-icon>
                                                </v-btn>
                                            </template>
                                            <span>Sum of all accepted quote values, split by Trades (Marketplace) and Materials (Purchase & Hire), showing cumulative project expenditure.</span>
                                        </v-tooltip>
                                    </v-card-title>
                                    <v-row>
                                        <v-col sm="6" v-if="!isSubContractor()">
                                            <div class="cr-total-spend">
                                                <h3>Trades</h3>
                                                {{ parseFloat(tradesSpent) | formatPrice }}
                                            </div>
                                        </v-col>

                                        <v-col sm="6" v-if="showMaterialsPrices()">
                                            <div class="cr-total-spend">
                                                <h3>Materials</h3>
                                                {{ parseFloat(materialsSpent) | formatPrice }}
                                            </div>
                                        </v-col>
                                    </v-row>
                                </v-card>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col md="4">
                                <v-card>
                                    <v-card-title style="justify-content: space-between">
                                        SMEs quoted in project
                                    </v-card-title>
                                    <v-card-text>
                                        <div> Total: {{ totalUsersSme }}</div>
                                        <pie-chart :chart-data="percentUsersSmeChartData" :options="chartOptions" class="cr-chart"></pie-chart>
                                    </v-card-text>
                                </v-card>
                            </v-col>

                            <v-col md="8">
                                <v-card>
                                    <v-card-title>Quotes by Postcode Area</v-card-title>
                                    <v-card-text>
                                        <postcode-districts-table :districts="projectDistricts"></postcode-districts-table>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                        </v-row>
                    </div>
                    <div v-else>
                        No data available
                    </div>
                </v-container>
            </v-window-item>
            <v-window-item :key="1" :value="1">
                <v-container fluid>
                    <v-row>
                        <v-col cols="12">
                            <v-card class="mb-3">
                                <v-card-title>Accepted Quotes</v-card-title>
                                <v-card-text>
                                    <accepted-quotes-table
                                        :quotes="materialsQuotesAccepted"
                                        :targets="projectTargets"
                                        type="materials"
                                    ></accepted-quotes-table>
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <v-col cols="12">
                            <v-card v-if="showMaterialsPrices()">
                                <v-card-text>
                                    <framework-totals-table :totals="materialsFrameworkTotals"></framework-totals-table>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="12">
                            <v-card class="mb-3">
                                <v-card-title>Quotes with Substitutions</v-card-title>
                                <v-card-text>
                                    <quotes-with-substitutions-table
                                        :quotes="materialsQuotesWithSubs"
                                        type="materials"
                                    ></quotes-with-substitutions-table>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="12">
                            <v-card>
                                <v-card-title>All Quotes</v-card-title>
                                <v-card-text>
                                    <quotes-table :enquiries="materialsEnquiries" type="materials"></quotes-table>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-container>
            </v-window-item>
            <v-window-item :key="2" :value="2">
                <v-container fluid>
                    <v-row>
                        <v-col cols="12">
                            <v-card class="mb-3">
                                <v-card-title>Accepted Quotes</v-card-title>
                                <v-card-text>
                                    <accepted-quotes-table
                                        :quotes="tradesQuotesAccepted"
                                        :targets="projectTargets"
                                        @sendReview="showDialogSendReview"
                                        @startReview="showDialogStartReview"
                                        @viewReview="showDialogViewReview"
                                        @showUserSummary="showUserSummary"
                                        type="trades"
                                    ></accepted-quotes-table>
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <v-col cols="12">
                            <v-card class="mb-3">
                                <v-card-text>
                                    <framework-totals-table :totals="tradesFrameworkTotals"></framework-totals-table>
                                </v-card-text>
                            </v-card>

                            <v-card>
                                <v-card-text>
                                    <project-report-table :projectId="projectId"></project-report-table>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="12">
                            <v-card>
                                <v-card-title>All Quotes</v-card-title>
                                <v-card-text>
                                    <quotes-table :enquiries="tradesEnquiries" type="trades"></quotes-table>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-container>
            </v-window-item>
            <v-window-item :key="3" :value="3">
                <project-time-tracking :projectId="projectId" :targets="projectTargets"></project-time-tracking>
            </v-window-item>
            <v-window-item :key="4" :value="4">
                <v-container fluid>
                    <v-row>
                        <v-col cols="12">
                            <div v-if="dataFound">
                                <v-row>
                                    <v-col md="4">
                                        <v-card>
                                            <v-card-title style="justify-content: space-between">
                                                Quotes
                                                <v-tooltip top>
                                                    <template v-slot:activator="{ on, attrs }">
                                                        <v-btn class="btn-icon" icon small v-bind="attrs" v-on="on">
                                                            <v-icon>mdi-information</v-icon>
                                                        </v-btn>
                                                    </template>
                                                    <span>Displays the total number of quotes this project has received through The Build Chain platform.</span>
                                                </v-tooltip>
                                            </v-card-title>
                                            <pie-chart :chart-data="totalQuotesChartData" :options="chartOptions" class="cr-chart"></pie-chart>
                                        </v-card>
                                    </v-col>

                                    <v-col md="4">
                                        <v-card>
                                            <v-card-title style="justify-content: space-between">
                                                Enquiries
                                                <v-tooltip top>
                                                    <template v-slot:activator="{ on, attrs }">
                                                        <v-btn class="btn-icon" icon small v-bind="attrs" v-on="on">
                                                            <v-icon>mdi-information</v-icon>
                                                        </v-btn>
                                                    </template>
                                                    <span>Shows how many enquiries have been submitted for this project - each enquiry can generate multiple supplier responses.</span>
                                                </v-tooltip>
                                            </v-card-title>
                                            <pie-chart :chart-data="totalEnquiriesChartData" :options="chartOptions" class="cr-chart"></pie-chart>
                                        </v-card>
                                    </v-col>

                                    <v-col md="4">
                                        <v-card>
                                            <v-card-title style="justify-content: space-between">
                                                Works Packages
                                                <v-tooltip top>
                                                    <template v-slot:activator="{ on, attrs }">
                                                        <v-btn class="btn-icon" icon small v-bind="attrs" v-on="on">
                                                            <v-icon>mdi-information</v-icon>
                                                        </v-btn>
                                                    </template>
                                                    <span>Counts distinct work-package submissions for this project. Duplicate enquiries for the same package are not double-counted.</span>
                                                </v-tooltip>
                                            </v-card-title>
                                            <pie-chart :chart-data="totalWorksPackagesChartData" :options="chartOptions" class="cr-chart"></pie-chart>
                                        </v-card>
                                    </v-col>
                                </v-row>

                                <v-row>
                                    <v-col md="4">
                                        <v-card>
                                            <v-card-title style="justify-content: space-between">
                                                Accepted Quotes
                                                <v-tooltip top>
                                                    <template v-slot:activator="{ on, attrs }">
                                                        <v-btn class="btn-icon" icon small v-bind="attrs" v-on="on">
                                                            <v-icon>mdi-information</v-icon>
                                                        </v-btn>
                                                    </template>
                                                    <span>Indicates the number of quotes selected or confirmed as accepted for this project.</span>
                                                </v-tooltip>
                                            </v-card-title>
                                            <pie-chart :chart-data="acceptedQuotesChartData" :options="chartOptions" class="cr-chart"></pie-chart>
                                        </v-card>
                                    </v-col>

                                    <v-col md="4">
                                        <v-card>
                                            <v-card-title style="justify-content: space-between">
                                                New Relationships Offered
                                                <v-tooltip top>
                                                    <template v-slot:activator="{ on, attrs }">
                                                        <v-btn class="btn-icon" icon small v-bind="attrs" v-on="on">
                                                            <v-icon>mdi-information</v-icon>
                                                        </v-btn>
                                                    </template>
                                                    <span>Tally of quotes received from suppliers who were not previously preferred or known to the project.</span>
                                                </v-tooltip>
                                            </v-card-title>
                                            <pie-chart :chart-data="relationshipsOfferedChartData" :options="chartOptions" class="cr-chart"></pie-chart>
                                        </v-card>
                                    </v-col>

                                    <v-col md="4">
                                        <v-card>
                                            <v-card-title style="justify-content: space-between">
                                                New Relationships Activated
                                                <v-tooltip top>
                                                    <template v-slot:activator="{ on, attrs }">
                                                        <v-btn class="btn-icon" icon small v-bind="attrs" v-on="on">
                                                            <v-icon>mdi-information</v-icon>
                                                        </v-btn>
                                                    </template>
                                                    <span>Number of accepted quotes (or live-chat interactions) that establish first-time supplier relationships not previously classified as preferred.</span>
                                                </v-tooltip>
                                            </v-card-title>
                                            <pie-chart :chart-data="relationshipsActivatedChartData" :options="chartOptions" class="cr-chart"></pie-chart>
                                        </v-card>
                                    </v-col>
                                </v-row>

                                <v-row>
                                    <v-col md="4">
                                        <v-card>
                                            <v-card-title style="justify-content: space-between">
                                                Sub-contractors used live chat
                                                <v-tooltip top>
                                                    <template v-slot:activator="{ on, attrs }">
                                                        <v-btn class="btn-icon" icon small v-bind="attrs" v-on="on">
                                                            <v-icon>mdi-information</v-icon>
                                                        </v-btn>
                                                    </template>
                                                    <span>Percentage of quoting subcontractors who initiated a live-chat interaction after submitting their quote.</span>
                                                </v-tooltip>
                                            </v-card-title>
                                            <pie-chart :chart-data="usedLiveChatChartData" :options="chartOptions" class="cr-chart"></pie-chart>
                                        </v-card>
                                    </v-col>

                                    <v-col md="4">
                                        <v-card v-if="!isSubContractor()">
                                            <v-card-title style="justify-content: space-between">
                                                ESG Savings in Trades
                                                <v-tooltip top>
                                                    <template v-slot:activator="{ on, attrs }">
                                                        <v-btn class="btn-icon" icon small v-bind="attrs" v-on="on">
                                                            <v-icon>mdi-information</v-icon>
                                                        </v-btn>
                                                    </template>
                                                    <span>Aggregates the ESG savings (e.g. CO₂ reductions) from all accepted trade-related quotes in the marketplace.</span>
                                                </v-tooltip>
                                            </v-card-title>
                                            <pie-chart :chart-data="esgTradesChartData" :options="chartOptions" class="cr-chart"></pie-chart>
                                        </v-card>
                                    </v-col>

                                    <v-col md="4">
                                        <v-card>
                                            <v-card-title style="justify-content: space-between">
                                                ESG Savings in Materials
                                                <v-tooltip top>
                                                    <template v-slot:activator="{ on, attrs }">
                                                        <v-btn class="btn-icon" icon small v-bind="attrs" v-on="on">
                                                            <v-icon>mdi-information</v-icon>
                                                        </v-btn>
                                                    </template>
                                                    <span>Aggregates the ESG savings (e.g. carbon impact avoided) from all accepted quotes in the purchase-and-hire materials category.</span>
                                                </v-tooltip>
                                            </v-card-title>
                                            <pie-chart :chart-data="esgMaterialsChartData" :options="chartOptions" class="cr-chart"></pie-chart>
                                        </v-card>
                                    </v-col>
                                </v-row>
                            </div>
                            <div v-else>
                                No data available
                            </div>
                        </v-col>
                    </v-row>
                </v-container>
            </v-window-item>
        </v-window>

        <v-dialog v-model="dialogSendReview" max-width="600px">
            <v-card>
                <v-card-title>
                    <div>
                        <div class="headline">Send Performance Review</div>
                        <div class="body-2 text--secondary font-weight-regular mt-1">
                            Select the review template you would like to send. <br>
                            An email will be sent to the project manager with a link to complete the review.
                        </div>
                    </div>
                </v-card-title>
                <v-card-text>
                    <v-autocomplete
                        v-model="selectedTemplateId"
                        :items="options.templates"
                        label="Select Template"
                        item-text="name"
                        item-value="id"
                        dense
                        outlined
                        hide-details
                    ></v-autocomplete>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="dialogSendReview=false">Cancel</v-btn>
                    <v-btn color="primary" @click="createReview()" :disabled="!selectedTemplateId">Send</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="dialogStartReview" max-width="900px">
            <review-form
                v-if="dialogStartReviewId"
                :id="dialogStartReviewId"
                @close="dialogStartReview = false"
                @submitted="onReviewSubmitted()"
            ></review-form>
        </v-dialog>

        <v-dialog v-model="dialogViewReview" max-width="900px">
            <review-summary
                v-if="dialogViewReviewId"
                :id="dialogViewReviewId"
                @close="dialogViewReview = false"
            ></review-summary>
        </v-dialog>

        <v-dialog v-model="dialogUserSummary" max-width="900px">
            <user-summary
                :userId="dialogUserSummaryId"
                @close="dialogUserSummary = false"
                @viewReview="showDialogViewReview"
            ></user-summary>
        </v-dialog>

        <v-overlay :value="pdfLoading" z-index="9999">
            <div class="text-center">
                <v-progress-circular indeterminate size="64" class="mb-4"></v-progress-circular>
                <div class="white--text text-h6">Creating PDF</div>
            </div>
        </v-overlay>

    </div>
</template>

<script>
import api from "../common/api";
import permissions from "../common/permissions.js";
import QuotesTable from "../components/Reports/QuotesTable.vue";
import AcceptedQuotesTable from "../components/Reports/AcceptedQuotesTable.vue";
import ContractorQuotesMap from "../components/Reports/ContractorQuotesMap.vue";
import ProjectReportTable from "../components/Reports/ProjectReportTable.vue";
import FrameworkTotalsTable from "../components/Reports/FrameworkTotalsTable.vue";
import PieChart from "../components/Reports/Charts/PieChart.js";
import ProjectTimeTracking from "../components/Reports/ProjectTimeTracking.vue";
import ProjectQuotesMap from "../components/Reports/ProjectQuotesMap.vue";
import QuotesWithSubstitutionsTable from "../components/Reports/QuoteWithSubstitutionsTable.vue";
import QuotesTableTender from "../components/Reports/QuotesTableTender.vue";
import PostcodeDistrictsTable from "../components/Reports/PostcodeDistrictsTable.vue";
import ReviewForm from "../components/Reviews/ReviewForm.vue";
import ReviewSummary from "../components/Reviews/ReviewSummary.vue";
import html2canvas from "html2canvas";
import UserSummary from "../components/Reviews/UserSummary.vue";

export default {
    components: {
        UserSummary,
        ReviewSummary,
        ReviewForm,
        PostcodeDistrictsTable,
        QuotesTableTender,
        QuotesWithSubstitutionsTable,
        ProjectQuotesMap,
        ProjectTimeTracking,
        QuotesTable,
        AcceptedQuotesTable,
        ContractorQuotesMap,
        ProjectReportTable,
        FrameworkTotalsTable,
        PieChart
    },
    data() {
        return {
            loader: false,
            pdfLoading: false,
            disableTabs: true,
            selectedProjectGroup: null,
            projectId: null,
            projectName: '',
            projectTargets: {},
            projectDistricts: [],
            data: [],
            tab: 0,
            colors: [
                '#ff6384',
                '#37a2eb',
                '#4cc0c0',
                '#ff9e40',
                '#e6e6e6'
            ],
            chartOptions: {
                responsive: true,
                cutout: '90%',
            },
            totalQuotesChartData: {},
            totalEnquiriesChartData: {},
            totalWorksPackagesChartData: {},
            acceptedQuotesChartData: {},
            relationshipsOfferedChartData: {},
            relationshipsActivatedChartData: {},
            usedLiveChatChartData: {},
            esgTradesChartData: {},
            esgMaterialsChartData: {},
            percentUsersSmeChartData: {},
            tradesSpent : 0,
            materialsSpent: 0,
            tradesQuotesAccepted: {
                framework: [],
                client: [],
            },
            materialsQuotesAccepted: {
                framework: [],
                client: [],
            },
            materialsQuotesWithSubs: [],
            tradesEnquiries: [],
            materialsEnquiries: [],
            tradesFrameworkTotals: [0, 0, 0, 0],
            materialsFrameworkTotals: [0, 0, 0, 0],
            filters: {
                worksPackageId: null,
                projectId: null,
                stage: null
            },
            options: {
                worksPackages: [],
                projects: [],
                stages: [],
                templates: []
            },
            filtersLoader: false,
            searchWorksPackage: '',
            searchProject: '',
            dataFound: false,
            targetHoursAp: 0,
            targetHoursSe: 0,
            totalUsersSme: 0,
            dialogSendReview: false,
            dialogStartReview: false,
            dialogViewReview: false,
            dialogSendReviewItem: null,
            dialogStartReviewId: null,
            dialogViewReviewId: null,
            dialogUserSummary: false,
            dialogUserSummaryId: null,
            selectedTemplateId: null,
        };
    },

    async mounted() {
        const self = this;

        self.projectId = this.$route.query.id ? parseInt(this.$route.query.id) : null;

        await self.loadProjectOptions();
        await self.loadWorksPackageOptions();
        await self.loadTemplateOptions();

        if (self.projectId !== null) {
            // use project id from url
            self.filters.projectId = self.projectId;
        } else {
            // sort projects by id desc
            let sorted = self.options.projects.sort(function(a, b) {
                if ( a.id > b.id ) {
                    return -1;
                }
                if ( a.id < b.id ) {
                    return 1;
                }
                return 0;
            });

            if (sorted.length > 0) {
                // select highest project id by default
                self.projectName = sorted[0].name;
                self.projectId = sorted[0].id;
                self.filters.projectId = sorted[0].id;
            }
        }

        //self.load();
    },

    watch: {
        'filters.projectId': {
            handler(n, o) {
                const self = this;
                let projectIds = [];
                let filtered = [];

                if (n !== null) {
                    projectIds.push(n);

                    filtered = self.options.projects.filter(function(item, index) {
                        return item.id === n;
                    });
                }

                self.projectId = n;

                self.loadWorksPackageOptions(self.searchWorksPackage, projectIds);
                self.load();

                if (filtered.length > 0) {
                    self.projectName = filtered[0].name;
                    self.options.stages = filtered[0].lifecycle_projects;
                }
            },
            deep: true,
        },
        'filters.stage': {
            handler(n, o) {
                const self = this;

                if (n) {
                    self.filters.projectId = n.id;
                }

                //self.load();
            },
            deep: true,
        },
    },


    computed: {
        currentWorksPackageName() {
            const wp = this.options.worksPackages.find(w => w.id === this.filters.worksPackageId);
            return wp ? wp.name : '';
        },
    },

    methods: {
        async init() {
            const self = this;

            self.projectName = self.data.project_name ?? '';
            self.projectTargets = self.data.project_targets ?? {};
            self.tradesSpent = (self.data.totals?.trades_spent) ? self.data.totals.trades_spent : 0;
            self.materialsSpent = (self.data.totals?.materials_spent) ? self.data.totals.materials_spent : 0;
            self.tradesEnquiries = (self.data.enquiries?.trades) ? self.data.enquiries.trades : [];
            self.materialsEnquiries = (self.data.enquiries?.materials) ? self.data.enquiries.materials : [];
            self.tradesFrameworkTotals = (self.data.quotes_accepted?.trades?.totals) ? self.data.quotes_accepted.trades.totals : [0, 0, 0];
            self.materialsFrameworkTotals = (self.data.quotes_accepted?.materials?.totals) ? self.data.quotes_accepted.materials.totals : [0, 0, 0];
            self.projectDistricts = self.data.project_districts ?? [];

            self.totalQuotesChartData = {
                labels: ['Materials', 'Trades'],
                datasets: [
                    {
                        label: "Dataset 1",
                        data: [self.data.totals.materials_quotes, self.data.totals.trades_quotes],
                        backgroundColor: [self.colors[0], self.colors[1]]
                    }
                ]
            };

            self.totalEnquiriesChartData = {
                labels: ['Materials', 'Trades'],
                datasets: [
                    {
                        label: "Dataset 1",
                        data: [self.data.totals.materials_enquiries, self.data.totals.trades_enquiries],
                        backgroundColor: [self.colors[0], self.colors[1]]
                    }
                ]
            };

            self.totalWorksPackagesChartData = {
                labels: ['Materials', 'Trades'],
                datasets: [
                    {
                        label: "Dataset 1",
                        data: [self.data.totals.materials_wps, self.data.totals.trades_wps],
                        backgroundColor: [self.colors[0], self.colors[1]]
                    }
                ]
            };

            self.acceptedQuotesChartData = {
                labels: ['Materials', 'Trades'],
                datasets: [
                    {
                        label: "Dataset 1",
                        data: [self.data.quotes_accepted.materials.data.length, self.data.quotes_accepted.trades.data.length],
                        backgroundColor: [self.colors[0], self.colors[1]]
                    }
                ]
            };

            self.relationshipsOfferedChartData = {
                labels: ['Materials', 'Trades'],
                datasets: [
                    {
                        label: "Dataset 1",
                        data: [self.data.totals.materials_nro, self.data.totals.trades_nro],
                        backgroundColor: [self.colors[0], self.colors[1]]
                    }
                ]
            };

            self.relationshipsActivatedChartData = {
                labels: ['Materials', 'Trades'],
                datasets: [
                    {
                        label: "Dataset 1",
                        data: [self.data.totals.materials_nra, self.data.totals.trades_nra],
                        backgroundColor: [self.colors[0], self.colors[1]]
                    }
                ]
            };

            let percentUsedLiveChat = parseFloat(self.data.percent_used_live_chat);

            self.usedLiveChatChartData = {
                labels: ['Used Live Chat'],
                datasets: [
                    {
                        label: "Dataset 1",
                        data: [percentUsedLiveChat, (100 - percentUsedLiveChat)],
                        backgroundColor: [self.colors[1], self.colors[4]]
                    }
                ]
            };

            let esgTrades = parseFloat(self.data.trades_average_esg);

            self.esgTradesChartData = {
                labels: ['ESG Saving'],
                datasets: [
                    {
                        label: "Dataset 1",
                        data: [esgTrades, (100 - esgTrades)],
                        backgroundColor: [self.colors[1], self.colors[4]]
                    }
                ]
            };

            let esgMaterials = parseFloat(self.data.materials_average_esg);

            self.esgMaterialsChartData = {
                labels: ['ESG Saving'],
                datasets: [
                    {
                        label: "Dataset 1",
                        data: [esgMaterials, (100 - esgMaterials)],
                        backgroundColor: [self.colors[1], self.colors[4]]
                    }
                ]
            };

            let percentUsersSme = parseFloat(self.data.percent_users_sme);

            self.percentUsersSmeChartData = {
                labels: ['SME', 'Non-SME'],
                datasets: [
                    {
                        label: "Dataset 1",
                        data: [percentUsersSme, (100 - percentUsersSme)],
                        backgroundColor: [self.colors[1], self.colors[4]]
                    }
                ]
            };

            self.tradesQuotesAccepted.framework = self.data.quotes_accepted.trades.data;
            self.tradesQuotesAccepted.client = self.data.quotes_accepted.trades.data;
            self.materialsQuotesAccepted.framework = self.data.quotes_accepted.materials.data;
            self.materialsQuotesAccepted.client = self.data.quotes_accepted.materials.data;
            self.materialsQuotesWithSubs = self.data.materials_quotes_with_subs;
            self.totalUsersSme = self.data.totals.users_sme;
        },

        async load() {
            const self = this;
            self.loader = true;

            try {
                const response = await api.getContractorReport({projectId: self.projectId});
                self.data = response.data ?? [];
                self.dataFound = true;

                await self.init();
                self.disableTabs = false;
            } catch(error) {
                self.dataFound = false;
                console.log(error);
            }

            self.loader = false;
        },

        async loadWorksPackageOptions(search = '', projectIds = []) {
            const self = this;

            self.filtersLoader = true;

            const resp = await api.getWorksPackageOptions({search: search, projectIds: projectIds});

            self.options.worksPackages = resp.data;
            self.filtersLoader = false;
        },

        async loadProjectOptions(search = '') {
            const self = this;

            self.filtersLoader = true;

            const resp = await api.getProjectOptions({search: search, grouped: 1});

            self.options.projects = resp.data;
            self.filtersLoader = false;
        },

        async loadTemplateOptions() {
            const self = this;

            const resp = await api.getReviewTemplateOptions();
            self.options.templates = resp.data;
        },

        isContractor() {
            return permissions.hasRole(permissions.role_contractor);
        },

        isSubContractor() {
            return permissions.hasRole(permissions.role_user) && !permissions.hasRole(permissions.role_contractor);
        },

        isFramework() {
            return permissions.hasRole(permissions.role_framework);
        },

        isClient() {
            return permissions.hasRole(permissions.role_client);
        },

        showMaterialsPrices() {
            return !this.isContractor() && !this.isFramework() && !this.isClient();
        },

        showOtherTab() {
            return permissions.hasRole(permissions.role_admin);
        },

        exportCsv() {
            const self = this;

            api.getContractorReportCsv({projectId: self.filters.projectId, worksPackageId: self.filters.worksPackageId})
                .then((response) => {
                    //window.open(response.data.url, '__blank');

                    // download the file
                    let blob = new Blob([response.data], { type: 'text/csv' });
                    let link = document.createElement('a');

                    link.href = window.URL.createObjectURL(blob);
                    link.download = 'contractor_report.csv';
                    link.click();
                }).catch((error) => {
                    console.log(error);
                });
        },

        exportPdf() {
            const self = this;

            api.getContractorReportPdf({projectId: self.filters.projectId, worksPackageId: self.filters.worksPackageId})
                .then((response) => {
                    window.open(response.data.url, '__blank');
                }).catch((error) => {
                console.log(error);
            });
        },

        async exportWdPdf() {
            const self = this;

            self.pdfLoading = true;

            let quotesMapHtml = document.getElementById('contractorQuotesMap');
            let postcodeMapHtml = document.getElementById('mapContainer');

            let quotesMapImg = await this.convertMapToImage(quotesMapHtml);
            let postcodeMapImg = await this.convertMapToImage(postcodeMapHtml);

            api.getContractorReportWdPdf({
                projectId: self.filters.projectId,
                worksPackageId: self.filters.worksPackageId,
                quotesMapImg: quotesMapImg,
                postcodeMapImg: postcodeMapImg
            }).then((response) => {
                window.open(response.data.url, '__blank');
            }).catch((error) => {
                console.log(error);
            }).finally(() => {
                self.pdfLoading = false;
            });
        },

        async convertMapToImage(html) {
            const width = html.offsetWidth;
            const height = html.offsetHeight;

            return html2canvas(html, {width: width, height: height, allowTaint: false, useCORS: true})
                .then(function (canvas) {
                    return canvas.toDataURL("image/png");
                })
                .catch(function (error) {
                    console.log(error);
                });
        },

        showReviews() {
            return permissions.hasRole(permissions.role_admin) || permissions.hasRole(permissions.role_contractor);
        },

        gotoReviewTemplates() {
            this.$router.push({name: 'review-templates'});
        },

        createReview() {
            const self = this;

            let params = {
                template_id: self.selectedTemplateId,
                quote_id: self.dialogSendReviewItem.id
            }

            api.storeReview(params)
                .then(function (response) {
                    self.load();
                    self.dialogSendReview = false;
                    self.dialogSendReviewItem = null;
                    self.selectedTemplateId = null;

                    self.$store.commit("showSnackbar", {
                        message: "Review sent",
                        color: "success",
                    });
                })
                .catch(function (error) {
                    console.log(error);

                    self.$store.commit("showSnackbar", {
                        message: "An error occurred",
                        color: "error",
                    });
                });
        },

        showDialogSendReview(item) {
            const self = this;

            self.dialogSendReview = true;
            self.dialogSendReviewItem = item;
        },

        showDialogStartReview(id) {
            this.dialogStartReview = true;
            this.dialogStartReviewId = id;
        },

        onReviewSubmitted() {
            this.load();
            this.dialogStartReview = false;
            this.$store.commit('showSnackbar', { message: 'Review submitted', color: 'success' });
        },

        showDialogViewReview(id) {
            const self = this;

            self.dialogViewReview = true;
            self.dialogViewReviewId = id;
        },

        showUserSummary(userId) {
            const self = this;

            self.dialogUserSummary = true;
            self.dialogUserSummaryId = userId;
        }
    },
};
</script>
<style>
.cr-chart {
    padding: 10px;
}

.cr-total-spend {
    text-align: center;
    font-size: 30px;
    margin: 40px 0;
}

.report-summary {
    display: inline-block;
}

.report-summary table, .report-summary th, .report-summary td {
    border: 2px solid black;
    border-collapse: collapse;
    padding: 10px;
}

.quotes-table {
    width: 100%;
    border-collapse: collapse;
    text-align: center;
}

.quotes-table th, .quotes-table td {
    padding: 10px 20px;
}

.quotes-table td {
    background: #eaeaea;
    border: none;
}

.quotes-table tr {
    border-bottom: 5px solid white;
    border-radius: 2px;
}

.btn-icon:after {
    content: none !important;
}

.btn-icon:hover, .btn-icon:hover > span {
    color: black !important;
}

.map-section {
    max-width: 800px;
    margin: 20px auto;
}

.map-container {
    display: block;
    height: 800px;
    margin: 0 auto;
}

.map-section .col {
    padding: 6px;
}

.map-legend {
    display: block;
    margin: 20px auto;
    text-align: center;
}

.legend-circle {
    height: 25px;
    width: 25px;
    background-color: #bbb;
    border-radius: 50%;
    display: inline-block;
    vertical-align: middle;
}

.legend-label {
    display: inline-block;
    vertical-align: middle;
}

.button-container {
    margin: 20px 0;
    display: flex;
    justify-content: center;
    gap: 10px;
}


</style>
