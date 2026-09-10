<template>
    <div style="margin: 1em">

        <v-data-table
            :headers="headers"
            :items="filteredEnquiries"
            :loading="loader"
            :options.sync="options"
            item-key="id"
            loading-text="Loading... Please wait"
            mobile-breakpoint="1000"
        >
            <template v-slot:top>
                <v-btn
                    class="m-2"
                    color="primary"
                    style="left: 0"
                    @click="newEnquiry()"
                >
                    New Enquiry
                </v-btn>

                <v-row style="margin-top: 10px;">
                    <v-col>
                        <v-autocomplete
                            v-model="filters.appliedFor"
                            :items="[{value: true, text: 'yes'}, {value: false, text: 'no'}]"
                            chips
                            clearable
                            dense
                            item-text="text"
                            item-value="value"
                            label="Applied For"
                            outlined
                            small-chips
                        ></v-autocomplete>
                    </v-col>
                    <v-col>
                        <v-autocomplete
                            v-model="filters.trades"
                            :items="tradeOptions"
                            chips
                            clearable
                            dense
                            label="Trade"
                            multiple
                            outlined
                            small-chips
                        ></v-autocomplete>
                    </v-col>
                    <v-col>
                        <v-autocomplete
                            v-model="filters.projects"
                            :items="projectOptions"
                            chips
                            clearable
                            dense
                            label="Project"
                            multiple
                            outlined
                            small-chips
                        ></v-autocomplete>
                    </v-col>
                    <v-col>
                        <v-autocomplete
                            v-model="filters.worksPackages"
                            :items="worksPackageOptions"
                            chips
                            clearable
                            dense
                            label="Works Package"
                            multiple
                            outlined
                            small-chips
                        ></v-autocomplete>
                    </v-col>
                </v-row>
            </template>

            <template v-slot:item.paid_or_unpaid="{ item }">
                <div class="text-center">
                    <div :class="paidChipClass(item)" class="type-chip-sm d-inline-block px-4">
                        {{ item.paid_or_unpaid }}
                    </div>
                </div>
            </template>

            <template v-slot:item.is_meaningful="{ item }">
                <div class="text-center">
                    <div :class="meaningfulChipClass(item)" class="type-chip-sm d-inline-block px-4">
                        {{ item.is_meaningful }}
                    </div>
                </div>
            </template>

            <template v-slot:item.actions="{ item }">
                <div class="text-center">
                    <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                class="mr-2"
                                color="black"
                                icon
                                small
                                v-bind="attrs"
                                @click="viewChat(item)"
                                v-on="on"
                            >
                                <v-icon>
                                    mdi-forum
                                </v-icon>
                            </v-btn>
                        </template>
                        <span>Live Chat</span>
                    </v-tooltip>

                    <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                class="mr-2"
                                color="black"
                                icon
                                small
                                v-bind="attrs"
                                @click="viewComments(item)"
                                v-on="on"
                            >
                                <v-icon>
                                    mdi-format-list-bulleted-square
                                </v-icon>
                            </v-btn>
                        </template>
                        <span>View Comments</span>
                    </v-tooltip>

                    <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                class="mr-2"
                                color="black"
                                icon
                                small
                                v-bind="attrs"
                                @click="archiveItem(item)"
                                v-on="on"
                            >
                                <v-icon>
                                    mdi-folder-open
                                </v-icon>
                            </v-btn>
                        </template>
                        <span>Archive Enquiry</span>
                    </v-tooltip>
                </div>

                <div class="text-center">
                    <v-btn color="primary" small @click="showApprenticeships(item)">
                        Apprenticeships
                    </v-btn>
                </div>
            </template>
        </v-data-table>

        <v-dialog v-model="dialogEnquiryForm" max-width="600px">
            <apprenticeship-enquiry-form
                :enquiry="dialogEnquiryFormItem"
                @close="dialogEnquiryForm = false"
            ></apprenticeship-enquiry-form>
        </v-dialog>

    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions.js";
import ApprenticeshipEnquiryForm from "../components/ApprenticeshipEnquiryForm.vue";

export default {
    name: 'ApprenticeshipEnquiries',
    components: {
        ApprenticeshipEnquiryForm

    },
    data() {
        return {
            loader: false,
            dialogEnquiryForm: false,
            dialogEnquiryFormItem: null,
            options: {
                page: 1,
                itemsPerPage: 10,
                sortBy: ["id"],
                sortDesc: [true],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false,
            },
            filters: {
                appliedFor: null,
                trades: [],
                projects: [],
                worksPackages: [],
            },
            headers: [
                {
                    text: "ID",
                    value: "id",
                    sortable: true,
                },
                {
                    text: "Date Created",
                    value: "date_created",
                    sortable: true,
                },
                {
                    text: "Date Published",
                    value: "date_published",
                    sortable: true,
                },
                {
                    text: "Business Name",
                    value: "business_name",
                    sortable: true,
                },
                {
                    text: "Postcode",
                    value: "postcode",
                    sortable: true,
                },
                {
                    text: "Trade",
                    value: "trade",
                    sortable: true,
                },
                {
                    text: "Trades Level",
                    value: "trades_level",
                    sortable: true,
                },
                {
                    text: "Paid or Unpaid",
                    value: "paid_or_unpaid",
                    sortable: true,
                    align: "center",
                },
                {
                    text: "Is this Meaningful?",
                    value: "is_meaningful",
                    sortable: true,
                    align: "center",
                },
                {
                    text: "Assumed Start Date",
                    value: "assumed_start_date",
                    sortable: true,
                },
                {
                    text: "Assumed End Date",
                    value: "assumed_end_date",
                    sortable: true,
                },
                {
                    text: "Project",
                    value: "project",
                    sortable: true,
                },
                {
                    text: "Works Package",
                    value: "works_package",
                    sortable: true,
                },
                {
                    text: "Actions",
                    value: "actions",
                    sortable: false,
                    align: "center",
                },
            ],
            enquiries: [
                {
                    "id": 201,
                    "date_created": "16-05-2026",
                    "date_published": "16-05-2026",
                    "business_name": "Main Contractor Demo",
                    "postcode": "DN4 5HX",
                    "trade": "Electrical",
                    "trades_level": "Level 2",
                    "paid_or_unpaid": "Paid",
                    "is_meaningful": "Yes",
                    "assumed_start_date": "09-07-2026",
                    "assumed_end_date": "30-09-2026",
                    "project": "Newtown Development",
                    "works_package": "Electrical Works"
                },
                {
                    "id": 200,
                    "date_created": "15-05-2026",
                    "date_published": "15-05-2026",
                    "business_name": "Main Contractor Demo",
                    "postcode": "DN4 5HX",
                    "trade": "Carpentry & Joinery",
                    "trades_level": "Level 3",
                    "paid_or_unpaid": "Unpaid",
                    "is_meaningful": "Yes",
                    "assumed_start_date": "15-06-2026",
                    "assumed_end_date": "15-09-2026",
                    "project": "Newtown Development",
                    "works_package": "Carpentry First Fix"
                },
                {
                    "id": 199,
                    "date_created": "14-05-2026",
                    "date_published": "14-05-2026",
                    "business_name": "Main Contractor Demo",
                    "postcode": "DN4 5HX",
                    "trade": "Plumbing",
                    "trades_level": "Level 2",
                    "paid_or_unpaid": "Paid",
                    "is_meaningful": "Yes",
                    "assumed_start_date": "01-06-2026",
                    "assumed_end_date": "31-08-2026",
                    "project": "Steel Frame Project",
                    "works_package": "Plumbing & Heating"
                },
                {
                    "id": 198,
                    "date_created": "13-05-2026",
                    "date_published": "13-05-2026",
                    "business_name": "Main Contractor Demo",
                    "postcode": "DN4 5HX",
                    "trade": "Brickwork",
                    "trades_level": "Level 2",
                    "paid_or_unpaid": "Unpaid",
                    "is_meaningful": "No",
                    "assumed_start_date": "10-06-2026",
                    "assumed_end_date": "31-08-2026",
                    "project": "Steel Frame Project",
                    "works_package": "Masonry Works"
                },
                {
                    "id": 197,
                    "date_created": "10-05-2026",
                    "date_published": "10-05-2026",
                    "business_name": "Main Contractor Demo",
                    "postcode": "DN4 5HX",
                    "trade": "Painting & Decorating",
                    "trades_level": "Level 1",
                    "paid_or_unpaid": "Paid",
                    "is_meaningful": "Yes",
                    "assumed_start_date": "01-07-2026",
                    "assumed_end_date": "30-09-2026",
                    "project": "Project Demo",
                    "works_package": "Decoration"
                },
                {
                    "id": 196,
                    "date_created": "08-05-2026",
                    "date_published": "08-05-2026",
                    "business_name": "Main Contractor Demo",
                    "postcode": "DN4 5HX",
                    "trade": "Groundworks",
                    "trades_level": "Level 2",
                    "paid_or_unpaid": "Paid",
                    "is_meaningful": "Yes",
                    "assumed_start_date": "20-05-2026",
                    "assumed_end_date": "30-06-2026",
                    "project": "Demo v3",
                    "works_package": "Groundworks"
                }
            ],
        };
    },

    async mounted() {

    },

    watch: {

    },

    computed: {
        tradeOptions() {
            return this.distinctValues('trade');
        },

        projectOptions() {
            return this.distinctValues('project');
        },

        worksPackageOptions() {
            return this.distinctValues('works_package');
        },

        filteredEnquiries() {
            const self = this;

            return self.enquiries.filter(function (item) {
                if (self.filters.trades.length && !self.filters.trades.includes(item.trade)) {
                    return false;
                }

                if (self.filters.projects.length && !self.filters.projects.includes(item.project)) {
                    return false;
                }

                if (self.filters.worksPackages.length && !self.filters.worksPackages.includes(item.works_package)) {
                    return false;
                }

                return true;
            });
        },
    },

    methods: {
        distinctValues(field) {
            return [...new Set(this.enquiries.map((item) => item[field]))].sort();
        },

        paidChipClass(item) {
            return item.paid_or_unpaid === 'Paid' ? 'chip-green' : 'chip-blue';
        },

        meaningfulChipClass(item) {
            return item.is_meaningful === 'Yes' ? 'chip-green' : 'chip-red';
        },

        newEnquiry() {
            this.dialogEnquiryForm = true;
        },

        viewChat(item) {
            // TODO: open the live chat dialog once the backend is in place
        },

        viewComments(item) {
            // TODO: open the comments dialog once the backend is in place
        },

        archiveItem(item) {
            // TODO: archive the enquiry once the backend is in place
        },

        showApprenticeships(item) {
            this.$router.push({ name: 'apprenticeship-responses', query: { id: item.id } });
        },
    },
};
</script>

<style>

</style>
