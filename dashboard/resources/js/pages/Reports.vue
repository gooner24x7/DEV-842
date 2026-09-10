<template>
    <div style="margin: 1em">
<!--        <h2>Reports</h2>-->

        <v-btn color="primary" v-if="subContractorReportsAllowed()" v-on:click="showBuyerReportDialog()">Buyer Report</v-btn>
        <v-btn color="primary" v-if="contractorReportsAllowed()" v-on:click="showBuyerReportDialog('Main Contractor')">Main Contractor Report</v-btn>
        <v-btn color="primary" v-if="supplierReportsAllowed()" v-on:click="downloadMerchantReport">Download Merchant Report</v-btn>

        <contractor-quotes-map v-if="contractorReportsAllowed()"></contractor-quotes-map>

        <div style="width:25%">
            <v-simple-table v-if="supplierReportsAllowed()">
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
            <v-data-table v-if="supplierReportsAllowed()"
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
                <template v-slot:item.Enquiry_Due='{ item }'>
                    {{ item.Enquiry_Due | formatDate }}
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

        <v-row v-if="branchReportsAllowed()">
            <v-col cols="4" sm="12" md="4">
                <ReportTotalEnquiries :branch_id="user.branch_id"/>
            </v-col>
            <v-col cols="4" sm="12" md="4">
                <ReportTotalQuotes :branch_id="user.branch_id"/>
            </v-col>
            <v-col cols="4" sm="12" md="4">
                <ReportBranchPerformance :branch_id="user.branch_id"/>
            </v-col>
        </v-row>

        <v-row>
            <v-col cols="6" v-if="contractorReportsAllowed()">
                <TotalsContractorOnEnquiries></TotalsContractorOnEnquiries>
            </v-col>
            <v-col cols="6" v-if="supplierReportsAllowed()">
                <TotalsSupplierOnEnquiries></TotalsSupplierOnEnquiries>
            </v-col>

            <v-col cols="6" v-if="contractorReportsAllowed()">
                <CategoriesPercentageSelectedForContractor></CategoriesPercentageSelectedForContractor>
            </v-col>
            <v-col cols="6" v-if="supplierReportsAllowed()">
                <CategoriesPercentageSelectedForMerchant></CategoriesPercentageSelectedForMerchant>
            </v-col>

            <v-col cols="6" v-if="contractorReportsAllowed()">
                <EnquiriesToTimeContractor></EnquiriesToTimeContractor>
            </v-col>
            <v-col cols="6" v-if="supplierReportsAllowed()">
                <EnquiriesToTimeMerchant></EnquiriesToTimeMerchant>
            </v-col>

            <v-col cols="6" v-if="contractorReportsAllowed() || supplierReportsAllowed()">
                <img src="/img/morereports.png"/>
            </v-col>
        </v-row>

        <v-row v-if="adminReportsAllowed()">
            <v-col cols="6">
                <TotalsContractorOnEnquiries></TotalsContractorOnEnquiries>
            </v-col>
            <v-col cols="6">
                <TotalsSupplierOnEnquiries></TotalsSupplierOnEnquiries>
            </v-col>

            <v-col cols="6">
                <CategoriesPercentageSelectedForContractor></CategoriesPercentageSelectedForContractor>
            </v-col>
            <v-col cols="6">
                <CategoriesPercentageSelectedForMerchant></CategoriesPercentageSelectedForMerchant>
            </v-col>

            <v-col cols="6">
                <EnquiriesToTimeContractor></EnquiriesToTimeContractor>
            </v-col>
            <v-col cols="6">
                <EnquiriesToTimeMerchant></EnquiriesToTimeMerchant>
            </v-col>
        </v-row>

        <v-dialog v-model="buyerReportDialog" max-width="500px">
            <v-card>
                <v-card-title>
                    <span class="headline">{{ buyerReportDialogTitle }}</span>
                </v-card-title>

                <v-card-text>
                    <v-container>
                        <v-autocomplete
                            :items="projects"
                            item-text="name"
                            item-value="id"
                            v-model="selectedProject"
                            label="Select Project"
                        >
                        </v-autocomplete>
                        <v-autocomplete
                            v-if="selectedProject != null"
                            :items="worksPackages"
                            item-text="name"
                            item-value="id"
                            v-model="selectedWorksPackage"
                            label="Select Works Package"
                        >
                        </v-autocomplete>
                    </v-container>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="blue darken-1" text @click="downloadBuyerReport">Get Report</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
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
                    text: "Category",
                    value: "Category",
                    sortable: true,
                },
                {
                    text: "Site Post Code",
                    value: "Site_Post_Code",
                    sortable: true,
                },
                {
                    text: "Enquiry Created",
                    value: "Enquiry_Created",
                    sortable: true,
                },
                {
                    text: "Enquiry Due",
                    value: "Enquiry_Due",
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
                    text: "Min until Deadline",
                    value: "Min_until_Deadline",
                    sortable: true,
                },
                {
                    text: "Min to Quote",
                    value: "Min_to_Quote",
                    sortable: true,
                },
                {
                    text: "Time Difference",
                    value: "Time_Difference",
                    sortable: true,
                },
                {
                    text: "Last Chat",
                    value: "Last_Chat",
                    sortable: true,
                },
                {
                    text: "Min Price",
                    value: "Min_Price_Quoted",
                    sortable: true,
                },
                {
                    text: "Max Price",
                    value: "Max_Price_Quoted",
                    sortable: true,
                },
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
            items: [],
            itemTotals: [],
            serverItemsLength: 0,
            options: {
                page: 1,
                itemsPerPage: 5,
                sortBy: ["Account_Name"],
                sortDesc: [true],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false,
            },

            expanded: [],
            singleExpand: true,
            buyerReportDialog: false,
            buyerReportDialogTitle: 'Buyer Report',
            projects: [],
            selectedProject: null,
            worksPackages: [],
            selectedWorksPackage: null,
        };
    },

    mounted() {
        const self = this;

        self.loadProjects();
    },

    watch: {
        options: {
            handler(n, o) {
                this.load();
            },
            deep: true,
        },
        selectedProject: {
            handler(n, o) {
                if (n != null) {
                    this.loadWorksPackages(n);
                }
            }
        }
    },

    methods: {
        async load() {
            const self = this;
            self.loader = true;

            try {
                const response = await api.supplierReport(this.user().id);
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

        async loadProjects() {
            const self = this;
            self.loader = true;

            //const response = await api.loadProjects();
            const response = await api.getBuyerReportOptions();

            self.projects = response.data ?? [];
            self.loader = false;
        },

        async loadWorksPackages(projectId) {
            const self = this;
            self.loader = true;

            const response = await api.loadWorksPackages(projectId);

            self.worksPackages = [{id: 0, name: 'All'}];
            response.data.forEach((item) => {
                self.worksPackages.push(item);
            });

            self.loader = false;
        },

        async downloadMerchantReport() {
            const report = await api.supplierReport(this.user().id);

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

        subContractorReportsAllowed() {
            return permissions.hasRole(permissions.role_user);
        },

        contractorReportsAllowed() {
            return permissions.hasRole(permissions.role_contractor);
        },

        supplierReportsAllowed() {
            return permissions.hasRole(permissions.role_company);
        },

        adminReportsAllowed() {
            return permissions.hasRole(permissions.role_admin);
        },

        branchReportsAllowed() {
            const self = this;

            if (!permissions.can(permissions.view_merchant_report)) {
                return false;
            }

            return self.user().branch_id > 0;
        },

        showBuyerReportDialog(title) {
            const self = this;

            if (title) {
                self.buyerReportDialogTitle = title;
            }

            self.buyerReportDialog = true;
        },

        downloadBuyerReport() {
            const self = this;

            //const response = await api.getBuyerReport({projectName: self.selectedProject});
            // if (response.data.url) {
            //     window.open(response.data.url, '__blank');
            // }

            self.$store.commit("showLoader");

            if (self.subContractorReportsAllowed()) {
                api.getBuyerReportPdf({projectId: self.selectedProject, worksPackageId: self.selectedWorksPackage})
                    .then((response) => {
                        self.$store.commit("hideLoader");
                        window.open(response.data.url, '__blank');
                    }).catch((error) => {
                        self.$store.commit("hideLoader");
                        self.$store.commit("showSnackbar", {
                            message: error.message,
                            color: "error",
                        });
                    });
            } else if (self.contractorReportsAllowed()) {
                api.getContractorReportPdf({projectId: self.selectedProject, worksPackageId: self.selectedWorksPackage})
                    .then((response) => {
                        self.$store.commit("hideLoader");
                        window.open(response.data.url, '__blank');
                    }).catch((error) => {
                        self.$store.commit("hideLoader");
                        self.$store.commit("showSnackbar", {
                            message: error.message,
                            color: "error",
                        });
                    });
            }

            // download the pdf
            // let blob = new Blob([response.data], { type: 'application/pdf' });
            // let link = document.createElement('a');
            //
            // link.href = window.URL.createObjectURL(blob);
            // link.download = 'buyer_report.pdf';
            // link.click();
        }
    },
};
</script>
<style>
</style>
