<template>
    <div style="margin: 1em">
<!--        <h2>Reports</h2>-->

        <v-btn color="primary" v-on:click="downloadLogisticsReport()">Download Logistics Report</v-btn>

        <div style="width:25%">
            <v-simple-table>
                <template>
                    <tr>
                        <th>Total Quotes</th><td>{{ itemTotals.quoted }}</td>
                    </tr>
                    <tr>
                        <th>Total Enquiries Received</th><td>{{ itemTotals.received }}</td>
                    </tr>
                    <tr>
                        <th>Total Enquiries Missed</th><td>{{ itemTotals.missed }}</td>
                    </tr>
                    <tr>
                        <th>Total Price Quoted</th><td>&pound;{{ parseFloat(itemTotals.price).toFixed(2) }}</td>
                    </tr>
                    <tr>
                        <th>Fastest Quote</th><td>{{ itemTotals.lowest }}</td>
                    </tr>
                    <tr>
                        <th>Average Quote Time</th><td>{{ itemTotals.average }}</td>
                    </tr>
                </template>
            </v-simple-table>
        </div>

        <div>
            <v-data-table
                :expanded.sync="expanded"
                :headers="headers"
                :items="items"
                :loading="loader"
                :options.sync="options"
                :server-items-length="serverItemsLength"
                :single-expand="singleExpand"
                item-key="id"
                loading-text="Loading... Please wait"
                mobile-breakpoint="1000"
            >
                <template v-slot:item.Enquiry_Created='{ item }'>
                    {{ item.Enquiry_Created | formatDateTimeAdm }}
                </template>
                <template v-slot:item.Collect_Date='{ item }'>
                    {{ item.Collect_Date | formatDate }}
                </template>
                <template v-slot:item.Delivery_Date='{ item }'>
                    {{ item.Delivery_Date | formatDate }}
                </template>
                <template v-slot:item.Price_quoted='{ item }'>
                    <span v-if="!!item.Price_quoted">&pound;</span>{{ item.Price_quoted }}
                </template>
                <template v-slot:item.Quote_Date='{ item }'>
                    {{ item.Quote_Date | formatDateTimeAdm }}
                </template>
                <template v-slot:item.Last_Chat='{ item }'>
                    {{ item.Last_Chat | formatDate }}
                </template>
                <template v-slot:item.Min_Price_Quoted='{ item }'>
                    <span v-if="!!item.Min_Price_Quoted">&pound;</span>{{ item.Min_Price_Quoted }}
                </template>
                <template v-slot:item.Max_Price_Quoted='{ item }'>
                    <span v-if="!!item.Max_Price_Quoted">&pound;</span>{{ item.Max_Price_Quoted }}
                </template>
            </v-data-table>
        </div>

        <v-row>
            <v-col cols="6">
                <TotalsSupplierOnEnquiries></TotalsSupplierOnEnquiries>
            </v-col>
            <v-col cols="6">
                <CategoriesPercentageSelectedForMerchant></CategoriesPercentageSelectedForMerchant>
            </v-col>
            <v-col cols="6">
                <EnquiriesToTimeMerchant></EnquiriesToTimeMerchant>
            </v-col>
            <v-col cols="6">
                <img src="/img/morereports.png"/>
            </v-col>
        </v-row>

    </div>
</template>

<script>
import ReportTotalEnquiries from '../components/Reports/ReportTotalEnquiries'
import ReportTotalQuotes from '../components/Reports/ReportTotalQuotes'
import ReportBranchPerformance from '../components/Reports/ReportBranchPerformance'
import TotalsContractorOnEnquiries from '../components/Reports/TotalsContractorOnEnquiries'
import TotalsSupplierOnEnquiries from '../components/Reports/TotalsSupplierOnEnquiries'
import CategoriesPercentageSelectedForMerchant from "../components/Reports/CategoriesPercentageSelectedForMerchant";
import CategoriesPercentageSelectedForContractor from "../components/Reports/CategoriesPercentageSelectedForContractor";
import EnquiriesToTimeMerchant from "../components/Reports/EnquiriesToTimeMerchant";
import EnquiriesToTimeContractor from "../components/Reports/EnquiriesToTimeContractor";
import permissions from "../common/permissions.js";
import api from "../common/api";
import * as XLSX from "xlsx";
import QuestionForm from "../components/QuestionForm.vue";
import EnquiryDuplicateForm from "../components/EnquiryDuplicateForm.vue";
import ContractorQuotesMap from "../components/Reports/ContractorQuotesMap.vue";

export default {
    components: {
        EnquiryDuplicateForm, QuestionForm,
        ReportTotalEnquiries,
        ReportTotalQuotes,
        ReportBranchPerformance,
        TotalsContractorOnEnquiries,
        TotalsSupplierOnEnquiries,
        CategoriesPercentageSelectedForMerchant,
        CategoriesPercentageSelectedForContractor,
        EnquiriesToTimeMerchant,
        EnquiriesToTimeContractor,
        ContractorQuotesMap
    },
    data() {
        return {
            loader: false,
            filtersLoader: false,
            expanded: [],
            singleExpand: true,
            items: [],
            itemTotals: [],
            serverItemsLength: 0,
            headers: [
                {
                    text: "Account Name",
                    value: "Account_Name",
                    sortable: true,
                },
                {
                    text: "Post Code",
                    value: "Post_Code",
                    sortable: true,
                },
                {
                    text: "Company Name",
                    value: "Company_Name",
                    sortable: true,
                },
                {
                    text: "ID",
                    value: "ID",
                    sortable: true,
                },
                {
                    text: "Collect Post Code",
                    value: "Collect_Post_Code",
                    sortable: true,
                },
                {
                    text: "Delivery Post Code",
                    value: "Delivery_Post_Code",
                    sortable: true,
                },
                {
                    text: "Enquiry Created",
                    value: "Enquiry_Created",
                    sortable: true,
                },
                {
                    text: "Collect Date",
                    value: "Collect_Date",
                    sortable: true,
                },
                {
                    text: "Delivery Date",
                    value: "Delivery_Date",
                    sortable: true,
                },
                {
                    text: "Quoted Yes/No",
                    value: "Quoted Yes/No",
                    sortable: true,
                },
                {
                    text: "Price quoted",
                    value: "Price_quoted",
                    sortable: true,
                },
                {
                    text: "Quote Date",
                    value: "Quote_Date",
                    sortable: true,
                },
                {
                    text: "Min until Collect",
                    value: "Min_until_Collect",
                    sortable: true,
                },
                {
                    text: "Min until Delivery",
                    value: "Min_until_Delivery",
                    sortable: true,
                },
                {
                    text: "Min to Quote",
                    value: "Min_to_Quote",
                    sortable: true,
                },
                {
                    text: "Collect Time Difference",
                    value: "Collect_Time_Difference",
                    sortable: true,
                },
                {
                    text: "Delivery Time Difference",
                    value: "Delivery_Time_Difference",
                    sortable: true,
                },
                {
                    text: "Last Chat",
                    value: "Last_Chat",
                    sortable: true,
                },
                // {
                //     text: "Min Price",
                //     value: "Min_Price_Quoted",
                //     sortable: true,
                // },
                // {
                //     text: "Max Price",
                //     value: "Max_Price_Quoted",
                //     sortable: true,
                // },
                {
                    text: "Min Time",
                    value: "Min_Time",
                    sortable: true,
                },
                {
                    text: "Max Time",
                    value: "Max_Time",
                    sortable: true,
                },
            ],
            options: {
                page: 1,
                itemsPerPage: 5,
                sortBy: ["Account_Name"],
                sortDesc: [true],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false,
            }
        };
    },

    mounted() {
        const self = this;
    },

    watch: {
        options: {
            handler(n, o) {
                this.load();
            },
            deep: true,
        },
    },

    methods: {
        async load() {
            const self = this;
            self.loader = true;

            try {
                const response = await api.logisticsReport();
                response.data.items = JSON.parse(JSON.stringify(response.data.items).replace(/"([\w\s]+)":/g,
                function (m) {
                            return m.replace(/\s+/g, '_');
                        }
                    )
                );
                self.items = response.data.items;
                self.serverItemsLength = response.data.total;

                self.itemTotals.quoted   = 0;
                self.itemTotals.missed   = 0;
                self.itemTotals.received = 0;
                self.itemTotals.price    = 0;
                self.itemTotals.lowest   = 0;
                let largest = Number.POSITIVE_INFINITY;
                let t;
                let timeTotal = 0;

                self.items.forEach(item => {
                    for (let v in item) {
                        if (v === 'Quoted Yes/No') {
                            if (item[v] === 'Yes')  {
                                self.itemTotals.quoted ++;
                                t = item.Min_to_Quote;
                                if (t < largest) largest = t;
                                if (! isNaN(t)) {
                                    timeTotal += t;
                                }
                            } else {
                                self.itemTotals.missed ++;
                            }
                        }
                        if (v === 'Price_quoted') {
                            if( ! item.Price_quoted) item.Price_quoted = 0;
                            self.itemTotals.price = self.itemTotals.price + item.Price_quoted;
                        }
                    }
                    self.itemTotals.received ++;
                });
                self.itemTotals.average = timeTotal / self.itemTotals.quoted;
                self.itemTotals.average = +self.itemTotals.average || 0;
                self.itemTotals.lowest  = Number.isFinite(largest) ? largest : 0;

            } catch (e) {}

            //self.serverItemsLength = self.itemTotals.received;
            self.loader = false;
        },

        async downloadLogisticsReport() {
            const report = await api.logisticsReport();

            const itemTotals = [Object.assign({}, this.itemTotals)];

            const data = XLSX.utils.json_to_sheet(report.data.items, {});
            const totals = XLSX.utils.json_to_sheet(itemTotals, {});

            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, data, 'data');
            XLSX.utils.book_append_sheet(wb, totals, 'totals');
            XLSX.writeFile(wb,'report.xlsx');
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        isLogisticsUser() {
            return permissions.hasRole(permissions.role_logistics);
        },
    },
};
</script>
<style>
</style>
