<template>
    <div>
        <v-card>
            <v-card-title>
                <span class="headline">Virtual Expo Report</span>
            </v-card-title>
            <v-card-text>
                <v-row>
                    <v-col cols="6">
                        <v-autocomplete
                            :items="['All Time', 'Last Month', 'Last Week', 'Selected Range']"
                            v-model="reportRangeSwitch"
                            label="Report Date Range"
                        >
                        </v-autocomplete>
                    </v-col>
                </v-row>
                <v-row v-if="reportRangeSwitch === 'Selected Range'">
                    <v-col cols="6">
                        <v-menu
                            v-model="startDateMenu"
                            :close-on-content-click="false"
                            max-width="290"
                        >
                            <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                    v-model="startDate"
                                    label="Start Date"
                                    v-bind="attrs"
                                    v-on="on"
                                ></v-text-field>
                            </template>
                            <v-date-picker
                                v-model="selectedStartDate"
                                no-title
                                @change="startDateMenu = false"
                            ></v-date-picker>
                        </v-menu>
                    </v-col>
                    <v-col cols="6">
                        <v-menu
                            v-model="endDateMenu"
                            :close-on-content-click="false"
                            max-width="290"
                        >
                            <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                    v-model="endDate"
                                    label="End Date"
                                    v-bind="attrs"
                                    v-on="on"
                                ></v-text-field>
                            </template>
                            <v-date-picker
                                v-model="selectedEndDate"
                                no-title
                                @change="endDateMenu = false"
                            ></v-date-picker>
                        </v-menu>
                    </v-col>
                </v-row>
                <v-row v-if="virtualExpoId === '' && isAdmin()">
                    <v-col cols="6">
                        <v-autocomplete
                            :items="manufacturers"
                            v-model="selectedManufacturers"
                            label="Manufacturers"
                            item-text="first_name"
                            item-value="id"
                            :loading="loader"
                            :search-input.sync="searchManufacturer"
                            outlined
                            dense
                            chips
                            multiple
                            small-chips
                            full-width
                        >
                        </v-autocomplete>
                    </v-col>
                </v-row>
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="blue darken-1" text v-on:click="cancel">Cancel</v-btn>
                <v-btn color="blue darken-1" text v-on:click="getReport">Get Report</v-btn>
            </v-card-actions>
        </v-card>
    </div>
</template>

<script>
import moment from 'moment';
import api from '../../common/api'
import * as XLSX from 'xlsx';
import permissions from "../../common/permissions";

export default {
    name: 'virtualExpoReport',
    props: ['virtualExpoId'],
    data() {
        return {
            manufacturers: [],
            selectedManufacturers: [],
            startDate: '',
            endDate: '',
            selectedStartDate: '',
            selectedEndDate: '',
            startDateMenu: false,
            endDateMenu: false,
            reportRangeSwitch: 'All Time',
            loader: false,
            searchManufacturer: '',
        }
    },
    mounted() {
        const self = this;
        this.startDate = '';
        this.endDate = '';
    },
    watch: {
        searchManufacturer:  async function (n, o) {
            const self = this;

            const resp = await api.manufacturerUsersOptions(n)
            self.manufacturers = resp.data;
        },

        selectedStartDate:  function (n, o) {
            if (n === '') {
                this.startDate = ''
                return
            }

            this.startDate = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        },
        selectedEndDate:  function (n, o) {
            if (n === '') {
                this.endDate = ''
                return
            }

            this.endDate = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        },
        reportRangeSwitch: function (n, o) {
            const self = this;

            if (n === 'All Time') {
                self.selectedEndDate = '';
                self.selectedStartDate = '';
                return
            }

            self.selectedEndDate = moment().format('YYYY-MM-DD');

            if (n === 'Last Week') {
                self.selectedStartDate = moment().subtract(1, 'weeks').format('YYYY-MM-DD');
                return
            }

            self.selectedStartDate = moment().subtract(1, 'months').format('YYYY-MM-DD');
        }
    },
    methods: {
        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },

        cancel() {
            const self = this;
            self.$emit("close");
        },

        async getReport() {
            let response;
            if (this.virtualExpoId) {
                response = await this.getReportForExpo()
            } else {
                response = await this.getReportForAll()
            }

            const data = XLSX.utils.json_to_sheet(response.data.items, {skipHeader: true})

            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, data, 'data')
            XLSX.writeFile(wb,'report.xlsx')
        },

        async getReportForAll() {
            return await api.getVirtualExpoForAll(
                this.startDate,
                this.endDate,
                this.selectedManufacturers
            )
        },

        async getReportForExpo() {
            return await api.getVirtualExpoReport(
                this.virtualExpoId,
                this.startDate,
                this.endDate
            )
        }
    }
}
</script>

<style scoped>

</style>
