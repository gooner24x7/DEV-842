<template>
    <div style="margin: 1em">
        <div class="d-flex align-center mb-2">
            <h1 class="cb-title mr-2">{{ selectedContractor.name }}</h1>
            <div class="type-chip-sm chip-blue">Main Contractor</div>
        </div>

        <!-- Profile overview -->
        <v-row>
            <v-col md="2">
                <div class="text-center">
                    <v-avatar :color="selectedContractor.color" size="40">
                        <span class="white--text">{{ selectedContractor.name ? selectedContractor.name.charAt(0) : '' }}</span>
                    </v-avatar>
                </div>
            </v-col>
            <v-col md="2">
                <div class="cb-profile-item">
                    <div class="cb-profile-item-label">Website</div>
                    <div class="cb-profile-item-value"><a :href="selectedContractor.website">{{ selectedContractor.website }}</a></div>
                </div>
            </v-col>
            <v-col md="2">
                <div class="cb-profile-item">
                    <div class="cb-profile-item-label">Head Office</div>
                    <div class="cb-profile-item-value">{{ selectedContractor.address }}</div>
                </div>
            </v-col>
            <v-col md="2">
                <div class="cb-profile-item">
                    <div class="cb-profile-item-label">Joined The Build Chain</div>
                    <div class="cb-profile-item-value">{{ selectedContractor.created_at }}</div>
                </div>
            </v-col>
            <v-col md="2">
                <div class="cb-profile-item">
                    <div class="cb-profile-item-label">Total Projects</div>
                    <div class="cb-profile-item-value">{{ selectedContractor.total_projects }}</div>
                </div>
            </v-col>
            <v-col md="2">
                <div class="cb-profile-item">
                    <div class="cb-profile-item-label">Total Users</div>
                    <div class="cb-profile-item-value">{{ selectedContractor.total_users }}</div>
                </div>
            </v-col>
        </v-row>

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

                    <p class="cb-card-footnote my-2">
                        Each project is counted once in Unique Projects.
                    </p>

                    <div class="cb-card-link">View all projects →</div>
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

                    <p class="cb-card-footnote my-2">
                        Response Rate = Quotes Received ÷ Enquiries Sent
                    </p>

                    <div class="cb-card-link">View procurement details →</div>
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

                    <p class="cb-card-footnote my-2">
                        SME Participation = SME Subcontractors Quoted ÷ Total Subcontractors Quoted
                    </p>

                    <div class="cb-card-link">View supply chain details →</div>
                </v-card>
            </v-col>
        </v-row>

        <!-- Charts -->
        <v-row>
            <!-- Projects -->
            <v-col cols="12" md="4">
                <v-card outlined class="cb-summary-card fill-height">
                    <div class="d-flex align-center mb-2">
                        <span class="cb-card-heading-lg">Projects by Current Stage</span>
                    </div>

                    <div class="cb-chart-container mb-2">
                        <doughnut-chart :chart-data="chartData1" />
                    </div>

                    <div class="cb-card-link">View projects breakdown →</div>
                </v-card>
            </v-col>

            <!-- Quotes received -->
            <v-col cols="12" md="4">
                <v-card outlined class="cb-summary-card fill-height">
                    <div class="d-flex align-center mb-2">
                        <span class="cb-card-heading-lg">Quotes Received Over Time</span>
                    </div>

                    <div class="cb-chart-container mb-2">
                        <line-chart :chart-data="chartData2"></line-chart>
                    </div>

                    <div class="cb-card-link">View full activity report →</div>
                </v-card>
            </v-col>

            <!-- Response rate -->
            <v-col cols="12" md="4">
                <v-card outlined class="cb-summary-card fill-height">
                    <div class="d-flex align-center mb-2">
                        <span class="cb-card-heading-lg">Response Rate Over Time (%)</span>
                    </div>

                    <div class="cb-chart-container mb-2">
                        <line-chart :chart-data="chartData3"></line-chart>
                    </div>

                    <div class="cb-card-link">View full activity report →</div>

                </v-card>
            </v-col>
        </v-row>

        <!-- Tables -->
        <v-row>
            <!-- Top Works Packages -->
            <v-col cols="12" md="4">
                <v-card outlined class="cb-summary-card fill-height">
                    <div class="d-flex align-center mb-1">
                        <span class="cb-card-heading-lg">Top Works Packages by Quotes Received</span>
                    </div>

                    <v-simple-table class="cb-table">
                        <template v-slot:default>
                            <thead>
                                <tr>
                                    <th class="text-left">
                                        Works Package
                                    </th>
                                    <th class="text-left">
                                        Enquiries Sent
                                    </th>
                                    <th class="text-left">
                                        Quotes Received
                                    </th>
                                    <th class="text-left">
                                        Response Rate
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in worksPackages"
                                    :key="item.name"
                                >
                                    <td>{{ item.name }}</td>
                                    <td>{{ item.total_enquiries }}</td>
                                    <td>{{ item.total_quotes }}</td>
                                    <td>{{ item.response_rate }}</td>
                                </tr>
                            </tbody>
                        </template>
                    </v-simple-table>

                    <div class="cb-card-link">View all works packages →</div>
                </v-card>
            </v-col>

            <!-- Top subcontractors -->
            <v-col cols="12" md="4">
                <v-card outlined class="cb-summary-card fill-height">
                    <div class="d-flex align-center mb-1">
                        <span class="cb-card-heading-lg">Top Subcontractors by Engagement</span>
                    </div>

                    <v-simple-table class="cb-table">
                        <template v-slot:default>
                            <thead>
                            <tr>
                                <th class="text-left">
                                    Subcontractor
                                </th>
                                <th class="text-left">
                                    Invited
                                </th>
                                <th class="text-left">
                                    Quoted
                                </th>
                                <th class="text-left">
                                    Awarded
                                </th>
                                <th class="text-left">
                                    SME
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr
                                v-for="item in subcontractors"
                                :key="item.name"
                            >
                                <td>{{ item.name }}</td>
                                <td>{{ item.invited }}</td>
                                <td>{{ item.quoted }}</td>
                                <td>{{ item.awarded }}</td>
                                <td v-html="getCertIcon(item.is_sme)"></td>
                            </tr>
                            </tbody>
                        </template>
                    </v-simple-table>

                    <div class="cb-card-link">View all subcontractors→</div>
                </v-card>
            </v-col>

            <!-- Activity List -->
            <v-col cols="12" md="4">
                <v-card outlined class="cb-summary-card fill-height">
                    <div class="d-flex align-center mb-1">
                        <span class="cb-card-heading-lg">Recent Activity</span>
                    </div>

                    <v-list two-line>
                        <template v-for="(item, index) in activityList">
                            <v-list-item :key="index">
                                <v-list-item-avatar>
                                    <v-avatar :color="item.color" size="30" class="cb-avatar mr-2">
                                        <v-icon color="white" size="20">{{ item.icon }}</v-icon>
                                    </v-avatar>
                                </v-list-item-avatar>

                                <v-list-item-content>
                                    <v-list-item-title v-html="item.title"></v-list-item-title>
                                    <v-list-item-subtitle v-html="item.subtitle"></v-list-item-subtitle>
                                </v-list-item-content>
                            </v-list-item>
                        </template>
                    </v-list>

                    <div class="cb-card-link">View all activity →</div>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";
import moment from "moment";
import DoughnutChart from "../components/Reports/Charts/DoughnutChart";
import LineChart from "../components/Reports/Charts/LineChart";

export default {
    name: 'ContractorProfile',
    components: {
        LineChart,
        DoughnutChart

    },
    data() {
        return {
            selectedContractor: {
                name: '',
                website: '',
                address: '',
                created_at: '',
                total_projects: 0,
                total_users: 0,
                color: '#4a5bd4',
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

            chartData1: {
                labels: ['Pre-Tender', 'Tender', 'Live', 'Completed'],
                datasets: [
                    {
                        label: "Projects by Current Stage",
                        data: [17, 27, 40, 16],
                        backgroundColor: ['#4a5bd4', '#e8823a', '#7a52c7', '#2e9e5b'],
                    }
                ]
            },

            chartData2: {
                labels: ['Apr 24', 'May 24', 'Jun 24', 'Jul 24', 'Aug 24', 'Sep 24', 'Oct 24', 'Nov 24', 'Dec 24', 'Jan 25', 'Feb 25', 'Mar 25'],
                datasets: [
                    {
                        label: "Quotes Received",
                        data: [590, 310, 365, 415, 560, 640, 720, 680, 620, 580, 600, 640],
                        backgroundColor: '#4a5bd4',
                        borderColor: '#4a5bd4',
                        fill: false,
                        tension: 0.1,
                    }
                ]
            },

            chartData3: {
                labels: ['Apr 24', 'May 24', 'Jun 24', 'Jul 24', 'Aug 24', 'Sep 24', 'Oct 24', 'Nov 24', 'Dec 24', 'Jan 25', 'Feb 25', 'Mar 25'],
                datasets: [
                    {
                        label: "Response Rate (%)",
                        data: [80, 75, 85, 90, 88, 87, 89, 87, 86, 82, 84, 88],
                        backgroundColor: '#4a5bd4',
                        borderColor: '#4a5bd4',
                        fill: false,
                        tension: 0.1,
                    }
                ]
            },

            contractors: [
                {
                    id: 1,
                    name: 'Willmott Dixon',
                    website: 'https://www.willmottdixon.com/',
                    address: 'Letchworth Garden City, Hertfordshire',
                    created_at: '2023-01-01',
                    total_projects: 248,
                    total_users: 56,
                    color: '#4a5bd4',
                },
                {
                    id: 2,
                    name: 'Balfour Beatty',
                    website: 'https://www.balfourbeatty.com/',
                    address: 'Canary Wharf, London',
                    created_at: '2023-11-01',
                    total_projects: 187,
                    total_users: 24,
                    color: '#7a52c7',
                },
                {
                    id: 3,
                    name: 'Laing O\'Rourke',
                    website: 'https://www.laingorourke.com/',
                    address: 'Dartford, Kent',
                    created_at: '2024-05-01',
                    total_projects: 156,
                    total_users: 22,
                    color: '#3aa8c1',
                },
                {
                    id: 4,
                    name: 'Bouygues UK',
                    website: 'https://www.bouygues-uk.com/',
                    address: '1 Lambeth Palace Rd, London',
                    created_at: '2025-02-01',
                    total_projects: 138,
                    total_users: 18,
                    color: '#2e9e5b',
                },
                {
                    id: 5,
                    name: 'Kier Group',
                    website: 'https://www.kier.co.uk/',
                    address: 'Clippers Quay, Salford',
                    created_at: '2024-09-01',
                    total_projects: 124,
                    total_users: 15,
                    color: '#e8823a',
                }
            ],

            worksPackages: [
                {
                    name: 'Groundworks & Civil',
                    total_enquiries: 1248,
                    total_quotes: 978,
                    response_rate: '78%',
                },
                {
                    name: 'Structural Frame',
                    total_enquiries: 1012,
                    total_quotes: 721,
                    response_rate: '71%',
                },
                {
                    name: 'Mechanical & Electrical',
                    total_enquiries: 1156,
                    total_quotes: 832,
                    response_rate: '70%',
                },
                {
                    name: 'Finishes',
                    total_enquiries: 987,
                    total_quotes: 664,
                    response_rate: '67%',
                },
                {
                    name: 'External Works',
                    total_enquiries: 845,
                    total_quotes: 591,
                    response_rate: '70%',
                },
            ],

            subcontractors: [
                {
                    name: 'ABC Construction Ltd',
                    invited: 18,
                    quoted: 15,
                    awarded: 9,
                    is_sme: true,
                },
                {
                    name: 'XYZ Mechanical Ltd',
                    invited: 16,
                    quoted: 13,
                    awarded: 8,
                    is_sme: true,
                },
                {
                    name: 'BuildRight Solutions',
                    invited: 14,
                    quoted: 12,
                    awarded: 7,
                    is_sme: false,
                },
                {
                    name: 'Prime Electrical Ltd',
                    invited: 13,
                    quoted: 11,
                    awarded: 6,
                    is_sme: true,
                },
                {
                    name: 'CivCore Ltd',
                    invited: 12,
                    quoted: 10,
                    awarded: 5,
                    is_sme: true,
                }
            ],

            activityList: [
                {
                    title: 'Quote received from ABC Construction Ltd',
                    subtitle: 'Project: New Victoria Quarter',
                    date: 'Today at 10:23',
                    icon: 'mdi-file-document-outline',
                    color: '#2e9e5b'
                },
                {
                    title: 'Enquiry sent: Structural Frame',
                    subtitle: 'Project: Sheffield City Centre Offices',
                    date: 'Today at 09:15',
                    icon: 'mdi-email-outline',
                    color: '#4a5bd4'
                },
                {
                    title: 'Subcontractor Scaffolding Ltd awarded',
                    subtitle: 'Project: Riverside Residential Development',
                    date: 'Yesterday at 16:45',
                    icon: 'mdi-account-group-outline',
                    color: '#7a52c7'
                },
                {
                    title: 'Quote received from M&E Solutions',
                    subtitle: 'Project: Leeds Commercial Hub',
                    date: 'Yesterday at 11:30',
                    icon: 'mdi-file-document-outline',
                    color: '#e8823a'
                }
            ]
        };
    },

    mounted() {
        const self = this;

        let contractorId = self.$route.query.id || 1;

        let filtered = self.contractors.filter((item) => {
            return item.id === parseInt(contractorId);
        });

        if (filtered.length > 0) {
            self.selectedContractor = filtered[0];
        } else {
            self.selectedContractor = self.contractors[0];
        }
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

        getCertIcon(val) {
            if (val) {
                return `<i class="v-icon mdi mdi-check-circle-outline" style="color: green"></i>`;
            }

            return `<i class="v-icon mdi mdi-close-circle-outline" style="color: red"></i>`;
        }
    },
};
</script>

<style scoped>

</style>
