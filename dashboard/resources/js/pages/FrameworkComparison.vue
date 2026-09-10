<template>
    <div style="margin: 1em">

        <!-- Header -->
        <div class="fc-header">
            <div>
                <h1 class="fc-title">Framework Comparison</h1>
                <p class="fc-subtitle">Compare framework performance across projects.</p>
            </div>
            <div class="fc-header-controls">
<!--                <v-btn outlined class="fc-control-btn" small>-->
<!--                    <v-icon left small>mdi-calendar</v-icon>-->
<!--                    {{ dateRange }}-->
<!--                    <v-icon right small>mdi-chevron-down</v-icon>-->
<!--                </v-btn>-->
<!--                <v-btn outlined class="fc-control-btn" small>-->
<!--                    Show Frameworks-->
<!--                    <span class="fc-control-count">{{ showFrameworks }}</span>-->
<!--                    <v-icon right small>mdi-chevron-down</v-icon>-->
<!--                </v-btn>-->
<!--                <v-btn outlined class="fc-control-btn" small>-->
<!--                    <v-icon left small>mdi-filter-variant</v-icon>-->
<!--                    Filters-->
<!--                </v-btn>-->
                <v-text-field
                    v-model="searchText"
                    label="Search"
                    clearable
                    dense
                    outlined
                    hide-details
                ></v-text-field>
            </div>
        </div>

        <!-- Summary stat cards -->
        <v-row class="fc-stats-row">
            <v-col v-for="stat in summaryStats" :key="stat.label" cols="12" sm="6" class="fc-stat-col">
                <v-card class="fc-stat-card" outlined>
                    <div class="fc-stat-icon" :style="{ backgroundColor: stat.iconBg, color: stat.iconColor }">
                        <v-icon :color="stat.iconColor">{{ stat.icon }}</v-icon>
                    </div>
                    <div class="fc-stat-body">
                        <div class="fc-stat-label">{{ stat.label }}</div>
                        <div class="fc-stat-value">{{ stat.value }}</div>
                        <div class="fc-stat-sub">{{ stat.sub }}</div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- Comparison table -->
        <v-card class="fc-table-card" outlined>
            <v-data-table
                :headers="headers"
                :items="frameworks"
                :items-per-page="5"
                :search="searchText"
                class="fc-table"
            >
                <template v-slot:item.name="{ item }">
                    <div class="fc-fw-cell">
                        <v-avatar :color="item.color" size="28" class="fc-fw-avatar">
                            <span class="white--text">{{ item.name.charAt(0) }}</span>
                        </v-avatar>
                        <span class="fc-fw-name">{{ item.name }}</span>
                    </div>
                </template>

                <template v-slot:item.localSpend="{ item }">
                    <span class="fc-amount">{{ item.localSpend }}</span>
                    <span class="fc-pct fc-pct-blue">{{ item.localSpendPct }}</span>
                </template>

                <template v-slot:item.smeSpend="{ item }">
                    <span class="fc-amount">{{ item.smeSpend }}</span>
                    <span class="fc-pct fc-pct-blue">{{ item.smeSpendPct }}</span>
                </template>

                <template v-slot:item.smeSubContractors="{ item }">
                    <span class="fc-amount">{{ item.smeSubContractors }}</span>
                    <span class="fc-pct fc-pct-blue">{{ item.smeSubContractorsPct }}</span>
                </template>

                <template v-slot:item.socialValue="{ item }">
                    <span class="fc-amount">{{ item.socialValue }}</span>
                    <span class="fc-pct fc-pct-green">{{ item.socialValuePct }}</span>
                </template>
            </v-data-table>
        </v-card>

        <!-- Bar chart comparisons -->
        <v-row class="fc-charts-row">
            <v-col cols="12" md="6">
                <v-card class="fc-chart-card" outlined>
                    <div class="fc-chart-title">Local Spend % Comparison</div>
                    <div class="fc-chart">
                        <div v-for="bar in localSpendChart" :key="bar.name" class="fc-bar-row">
                            <div class="fc-bar-label">{{ bar.name }}</div>
                            <div class="fc-bar-track">
                                <div class="fc-bar-fill" :style="{ width: bar.pct + '%', backgroundColor: bar.color }"></div>
                                <span class="fc-bar-value">{{ bar.pct }}%&nbsp;&nbsp;{{ bar.amount }}</span>
                            </div>
                        </div>
                        <div class="fc-bar-axis">
                            <span v-for="tick in axisTicks" :key="tick">{{ tick }}%</span>
                        </div>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" md="6">
                <v-card class="fc-chart-card" outlined>
                    <div class="fc-chart-title">SME Spend % Comparison</div>
                    <div class="fc-chart">
                        <div v-for="bar in smeSpendChart" :key="bar.name" class="fc-bar-row">
                            <div class="fc-bar-label">{{ bar.name }}</div>
                            <div class="fc-bar-track">
                                <div class="fc-bar-fill" :style="{ width: bar.pct + '%', backgroundColor: bar.color }"></div>
                                <span class="fc-bar-value">{{ bar.pct }}%&nbsp;&nbsp;{{ bar.amount }}</span>
                            </div>
                        </div>
                        <div class="fc-bar-axis">
                            <span v-for="tick in axisTicks" :key="tick">{{ tick }}%</span>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";
import moment from "moment";

export default {
    name: 'FrameworkComparison',
    components: {

    },
    data() {
        return {
            searchText: '',
            dateRange: '1 Jan 2023 - 31 Dec 2024',
            showFrameworks: 5,
            axisTicks: [0, 10, 20, 30, 40, 50],

            headers: [
                { text: 'Framework', value: 'name', align: 'start', sortable: false },
                { text: 'Projects', value: 'projects', align: 'center' },
                { text: 'Value', value: 'value', align: 'center' },
                { text: 'Local Spend', value: 'localSpend', align: 'center' },
                { text: 'SME Spend', value: 'smeSpend', align: 'center' },
                { text: 'Total Sub Contractors', value: 'totalSubContractors', align: 'center' },
                { text: 'SME Sub Contractors', value: 'smeSubContractors', align: 'center' },
                { text: 'Social Value', value: 'socialValue', align: 'center' },
            ],

            summaryStats: [
                {
                    label: 'Total Projects',
                    value: '1,247',
                    sub: 'Across 78 Frameworks',
                    icon: 'mdi-briefcase-outline',
                    iconBg: '#eef1fe',
                    iconColor: '#4a5bd4',
                },
                {
                    label: 'Total Project Value',
                    value: '£3.48B',
                    sub: '100% of total',
                    icon: 'mdi-cash',
                    iconBg: '#e9f6ef',
                    iconColor: '#2e9e5b',
                },
                {
                    label: 'Local Spend',
                    value: '£1.42B',
                    sub: '40.8% of total value',
                    icon: 'mdi-map-marker-outline',
                    iconBg: '#eef1fe',
                    iconColor: '#4a5bd4',
                },
                {
                    label: 'SME Spend',
                    value: '£1.18B',
                    sub: '34.0% of total value',
                    icon: 'mdi-account-group-outline',
                    iconBg: '#eef1fe',
                    iconColor: '#4a5bd4',
                },
                {
                    label: 'Social Value',
                    value: '£285.6M',
                    sub: '8.2% of total value',
                    icon: 'mdi-heart-outline',
                    iconBg: '#fdecec',
                    iconColor: '#d64545',
                },
            ],

            frameworks: [
                {
                    name: 'SCAPE',
                    color: '#4a5bd4',
                    projects: 312,
                    value: '£1.12B',
                    localSpend: '£517.4M',
                    localSpendPct: '46.2%',
                    smeSpend: '£411.2M',
                    smeSpendPct: '36.8%',
                    totalSubContractors: '2,473',
                    smeSubContractors: '1,213',
                    smeSubContractorsPct: '49.1%',
                    socialValue: '£96.2M',
                    socialValuePct: '8.6%',
                },
                {
                    name: 'Pagabo',
                    color: '#7a52c7',
                    projects: 278,
                    value: '£854.4M',
                    localSpend: '£357.4M',
                    localSpendPct: '41.7%',
                    smeSpend: '£290.4M',
                    smeSpendPct: '33.9%',
                    totalSubContractors: '2,156',
                    smeSubContractors: '1,041',
                    smeSubContractorsPct: '48.3%',
                    socialValue: '£71.3M',
                    socialValuePct: '8.3%',
                },
                {
                    name: 'Fusion21',
                    color: '#3aa8c1',
                    projects: 231,
                    value: '£742.8M',
                    localSpend: '£286.6M',
                    localSpendPct: '38.5%',
                    smeSpend: '£231.7M',
                    smeSpendPct: '31.2%',
                    totalSubContractors: '1,894',
                    smeSubContractors: '876',
                    smeSubContractorsPct: '46.3%',
                    socialValue: '£58.7M',
                    socialValuePct: '7.9%',
                },
                {
                    name: 'LHC',
                    color: '#2e9e5b',
                    projects: 198,
                    value: '£512.6M',
                    localSpend: '£185.1M',
                    localSpendPct: '36.1%',
                    smeSpend: '£150.7M',
                    smeSpendPct: '29.4%',
                    totalSubContractors: '1,542',
                    smeSubContractors: '714',
                    smeSubContractorsPct: '46.3%',
                    socialValue: '£41.5M',
                    socialValuePct: '8.1%',
                },
                {
                    name: 'YorBuild',
                    color: '#e8823a',
                    projects: 156,
                    value: '£248.3M',
                    localSpend: '£82.5M',
                    localSpendPct: '33.2%',
                    smeSpend: '£64.8M',
                    smeSpendPct: '26.1%',
                    totalSubContractors: '1,103',
                    smeSubContractors: '488',
                    smeSubContractorsPct: '44.2%',
                    socialValue: '£18.0M',
                    socialValuePct: '7.2%',
                },
                {
                    name: 'Crown Commercial Projects',
                    color: '#0a9858',
                    projects: 146,
                    value: '£232.7M',
                    localSpend: '£72.3M',
                    localSpendPct: '31.1%',
                    smeSpend: '£52.1M',
                    smeSpendPct: '22.4%',
                    totalSubContractors: '842',
                    smeSubContractors: '314',
                    smeSubContractorsPct: '37.3%',
                    socialValue: '£14.7M',
                    socialValuePct: '6.3%',
                },
                {
                    name: 'Beacon',
                    color: '#eeb01e',
                    projects: 134,
                    value: '£198.6M',
                    localSpend: '£68.0M',
                    localSpendPct: '34.2%',
                    smeSpend: '£49.2M',
                    smeSpendPct: '24.8%',
                    totalSubContractors: '753',
                    smeSubContractors: '276',
                    smeSubContractorsPct: '36.6%',
                    socialValue: '£12.1M',
                    socialValuePct: '6.1%',
                },
                {
                    name: 'NEPO',
                    color: '#182de5',
                    projects: 112,
                    value: '£156.3M',
                    localSpend: '£49.2M',
                    localSpendPct: '31.5%',
                    smeSpend: '£32.4M',
                    smeSpendPct: '20.9%',
                    totalSubContractors: '612',
                    smeSubContractors: '214',
                    smeSubContractorsPct: '35.0%',
                    socialValue: '£8.9M',
                    socialValuePct: '5.7%',
                },
                {
                    name: 'TCHC',
                    color: '#6e1eae',
                    projects: 98,
                    value: '£128.9M',
                    localSpend: '£39.2M',
                    localSpendPct: '30.4%',
                    smeSpend: '£24.7M',
                    smeSpendPct: '19.2%',
                    totalSubContractors: '498',
                    smeSubContractors: '159',
                    smeSubContractorsPct: '31.9%',
                    socialValue: '£6.6M',
                    socialValuePct: '5.3%',
                },
                {
                    name: 'Wales Procurement Alliance',
                    color: '#44536a',
                    projects: 87,
                    value: '£94.5M',
                    localSpend: '£26.1M',
                    localSpendPct: '27.7%',
                    smeSpend: '£17.2M',
                    smeSpendPct: '18.2%',
                    totalSubContractors: '372',
                    smeSubContractors: '118',
                    smeSubContractorsPct: '31.9%',
                    socialValue: '£4.6M',
                    socialValuePct: '4.9%',
                },
                {
                    name: 'Southern Construction Framework',
                    color: '#0894aa',
                    projects: 74,
                    value: '£78.6M',
                    localSpend: '£21.7M',
                    localSpendPct: '27.6%',
                    smeSpend: '£12.6M',
                    smeSpendPct: '16.0%',
                    totalSubContractors: '298',
                    smeSubContractors: '86',
                    smeSubContractorsPct: '28.8%',
                    socialValue: '£3.2M',
                    socialValuePct: '4.1%',
                },
                {
                    name: 'YPO',
                    color: '#41506a',
                    projects: 65,
                    value: '£65.3M',
                    localSpend: '£16.4M',
                    localSpendPct: '25.1%',
                    smeSpend: '£9.3M',
                    smeSpendPct: '14.3%',
                    totalSubContractors: '247',
                    smeSubContractors: '67',
                    smeSubContractorsPct: '27.4%',
                    socialValue: '£2.3M',
                    socialValuePct: '3.5%',
                },
            ],
        }
    },

    mounted() {

    },

    watch: {

    },

    computed: {
        localSpendChart() {
            return this.frameworks.map(fw => ({
                name: fw.name,
                color: fw.color,
                pct: parseFloat(fw.localSpendPct),
                amount: fw.localSpend,
            }));
        },
        smeSpendChart() {
            return this.frameworks.map(fw => ({
                name: fw.name,
                color: fw.color,
                pct: parseFloat(fw.smeSpendPct),
                amount: fw.smeSpend,
            }));
        },
    },

    methods: {

    },
};
</script>

<style scoped>
.fc-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 24px;
}

.fc-title {
    font-size: 26px;
    font-weight: 700;
    color: #1a1a2e;
    margin: 0;
}

.fc-subtitle {
    font-size: 14px;
    color: #8a8a9e;
    margin: 4px 0 0;
}

.fc-header-controls {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.fc-control-btn {
    text-transform: none;
    letter-spacing: normal;
    border-color: #e2e2ec !important;
    background-color: #fff;
    color: #4a4a5e !important;
    font-weight: 500;
}

.fc-control-count {
    display: inline-block;
    margin-left: 8px;
    padding: 0 6px;
    background-color: #eef1fe;
    color: #4a5bd4;
    border-radius: 4px;
    font-weight: 600;
}

/* Stat cards */
.fc-stats-row {
    margin-bottom: 8px;
}

/* Five evenly-sized cards on desktop (Vuetify grid can't do fifths natively) */
@media (min-width: 960px) {
    .fc-stat-col {
        flex: 1 0 20%;
        max-width: 20%;
    }
}

.fc-stat-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    border-radius: 12px !important;
    border-color: #ececf3 !important;
    height: 100%;
}

.fc-stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.fc-stat-label {
    font-size: 12px;
    color: #8a8a9e;
    margin-bottom: 2px;
}

.fc-stat-value {
    font-size: 22px;
    font-weight: 700;
    color: #1a1a2e;
    line-height: 1.1;
}

.fc-stat-sub {
    font-size: 11px;
    color: #a0a0b0;
    margin-top: 2px;
}

/* Table */
.fc-table-card {
    border-radius: 12px !important;
    border-color: #ececf3 !important;
    margin-top: 16px;
    overflow: hidden;
}

.fc-table {
    margin-top: 20px;
}

::v-deep .fc-table .v-data-table__wrapper table {
    border-collapse: collapse!important;
}

/* Override global app.scss rule that greys out light data tables */
.fc-table.theme--light.v-data-table {
    background-color: #fff !important;
}

.fc-table >>> thead th {
    background-color: #ffffff;
    font-size: 12px !important;
    font-weight: 600 !important;
    color: #6a6a80 !important;
    vertical-align: top;
    padding-top: 12px !important;
    padding-bottom: 12px !important;
    height: auto !important;
}

.fc-th-sub {
    font-weight: 400;
    color: #a0a0b0;
}

.fc-table >>> tbody td {
    font-size: 13px;
    color: #3a3a4e;
    padding-top: 14px !important;
    padding-bottom: 14px !important;
}

.fc-fw-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.fc-fw-avatar span {
    font-size: 12px;
    font-weight: 600;
}

.fc-fw-name {
    font-weight: 600;
    color: #1a1a2e;
}

.fc-amount {
    font-weight: 600;
    color: #1a1a2e;
}

.fc-pct {
    margin-left: 8px;
    font-size: 12px;
    font-weight: 600;
}

.fc-pct-blue {
    color: #4a5bd4;
}

.fc-pct-green {
    color: #2e9e5b;
}

.fc-table-footer {
    padding: 12px 16px;
    border-top: 1px solid #f0f0f5;
}

/* Charts */
.fc-charts-row {
    margin-top: 16px;
}

.fc-chart-card {
    border-radius: 12px !important;
    border-color: #ececf3 !important;
    padding: 18px;
    height: 100%;
}

.fc-chart-title {
    font-size: 15px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 18px;
}

.fc-bar-row {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
}

.fc-bar-label {
    width: 72px;
    flex-shrink: 0;
    font-size: 12px;
    color: #6a6a80;
}

.fc-bar-track {
    position: relative;
    flex: 1;
    height: 20px;
    background-color: #f2f2f7;
    border-radius: 4px;
}

.fc-bar-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.fc-bar-value {
    position: absolute;
    top: 50%;
    left: 8px;
    transform: translateY(-50%);
    font-size: 11px;
    font-weight: 600;
    color: #ffffff;
    white-space: nowrap;
}

.fc-bar-axis {
    display: flex;
    justify-content: space-between;
    margin-left: 72px;
    margin-top: 8px;
    font-size: 11px;
    color: #a0a0b0;
}
</style>
