<template>
    <div style="margin: 1em">

        <div style="position: relative; padding-top: 1em">

            <v-row>
                <v-col>
                    <v-btn class="primary" v-on:click="setFilter('contractor')">Contractor</v-btn>
                    <v-btn class="primary" v-on:click="setFilter('company')">Merchant</v-btn>
                    <v-btn class="primary" v-on:click="setFilter('manufacturer')">Manufacturer</v-btn>
                </v-col>
                <v-col>
                    <v-text-field v-model="filters.search" dense outlined label="Search"></v-text-field>
                </v-col>
            </v-row>

            <v-data-table
                :expanded.sync="expanded"
                :headers="getHeaders()"
                :items="items"
                :loading="loader"
                :options.sync="options"
                :server-items-length="serverItemsLength"
                :single-expand="singleExpand"
                item-key="id"
                loading-text="Loading... Please wait"
                mobile-breakpoint="1000"
            >

                <template v-slot:item.company_name="{ item }">
                    <a href="#" @click="(e) => { e.preventDefault(); showAdditionalInfo(item); }">{{
                            item.company_name
                        }}</a>
                </template>

                <template v-slot:expanded-item="{ headers, item }">
                    <td :colspan="headers.length">
                        <v-row v-if="displayAdditionalInfo" class="expanded-user-info">
                            <v-col>
                                <h4>{{ item.company_name }}</h4>
                                <b>Company Number:</b> {{ item.company_number }}<br>
                                <b>Email:</b> {{ item.email }}<br>
                                <b>Phone:</b> {{ item.phone }}<br>
                                <b>Billing Company:</b> {{ item.billing_company }}<br>
                                <b>Billing Email:</b> {{ item.billing_email }}<br>
                                <b>Billing Phone:</b> {{ item.billing_phone }}<br>
                                <!-- <b>Next Call:</b> {{ item.next_call }}<br>
                                <b>Pain Points:</b> {{ item.pain_points }}<br> -->
                                <b>Call Comments:</b> {{ item.comment }}
                            </v-col>
                            <v-col>
                                <onboarding-checklist :userData="userData"></onboarding-checklist>
                            </v-col>
                        </v-row>
                    </td>
                </template>

                <template v-slot:item.date_created="{ item }">
                    {{ item.date_created | formatDateTimeAdm }}
                </template>

                <template v-slot:item.turnover="{ item }">
                    {{ formatTurnover(item.turnover) }}
                </template>

                <template v-slot:item.onboarding_call="{ item }">
                    {{ item.onboarding_call | formatDateTimeAdm }}
                </template>

                <template v-slot:item.next_call="{ item }">
                    {{ item.next_call | formatDateTimeAdm }}
                </template>

                <template v-slot:item.paid="{ item }">
                    {{ formatPaid(item.paid) }}
                </template>

                <template v-slot:item.called_at="{ item }">
                    <v-checkbox readonly :input-value="!!item.called_at" v-on:click="markAsCalled(item)"></v-checkbox>
                    <span v-if="!!item.called_at">
                    Called at: {{ item.called_at | formatDateTimeAdm }}<br>
                    <!-- {{ item.comment }}-->
                    </span>
                    <v-btn class="primary" v-on:click="showAllEvents(item)">Notes</v-btn>
                    <v-btn class="primary" v-on:click="editItem(item)">Edit</v-btn>
                </template>
            </v-data-table>

        </div>

        <v-dialog v-model="dialogAllEvents" max-width="900px">
            <all-events :userId="selectedUserId"></all-events>
        </v-dialog>

        <v-dialog v-model="dialogEditUser" max-width="800px">
            <user-stats-form
                :visible="dialogEditUser"
                :billing_user_id="editedItem.billing_user_id || billing_user_id"
                v-model="editedItem"
                v-on:save="save"
                v-on:cancel="close"
                title="Edit User"
            />
        </v-dialog>
    </div>
</template>
<script>
import api from "../../common/api.js";
import permissions from "../../common/permissions.js";
import AllEvents from "./AllEvents";
import AnswerForm from "../../components/AnswerForm.vue";
import UserStatsForm from "../../components/UserStatsForm.vue";
import OnboardingChecklist from "../../components/OnboardingChecklist.vue";

export default {
    name: "UserStats",
    props: ["billing_user_id"],
    components: {OnboardingChecklist, AnswerForm, UserStatsForm, AllEvents},
    data() {
        return {
            role: '',
            items: [],
            loader: false,
            expanded: [],
            singleExpand: true,
            serverItemsLength: 0,
            editedIndex: -1,
            dialogEditUser: false,
            dialogAllEvents: false,
            displayAdditionalInfo: false,
            selectedUserId: '',
            userData: null,
            filters: {
                search: null,
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
            },
            editedItem: {
                billing_user_id: undefined,
                is_billing_user: false,
                role_ids: [],
                role_name: "",
                is_global: false,
                branch_id: "",
                pipeline_of_work: "",
                potential_users: "",
                turnover: "",
                number_of_employees: "",
                pain_points: "",
                onboarding_call: "",
                next_call: "",
                onboarding_end_date: ""
            },
            defaultItem: {
                billing_user_id: undefined,
                is_billing_user: false,
                role_ids: [],
                role_name: "",
                is_global: false,
                branch_id: "",
                pipeline_of_work: "",
                potential_users: "",
                turnover: "",
                number_of_employees: "",
                pain_points: "",
                onboarding_call: "",
                next_call: "",
                onboarding_end_date: ""
            },
        }
    },

    watch: {
        'role': async function () {
            await this.load()
        },
        options: {
            handler(n, o) {
                this.load();
            },
            deep: true,
        },
        filters: {
            handler(n, o) {
                this.load();
            },
            deep: true,
        },
        dialogEditUser(val) {
            val || this.close();
        },
    },

    methods: {
        async markAsCalled(item) {
            const self = this;
            const comment = prompt('Please, enter comment, before the record is marked as called')
            await api.setCalledFlag('', item.id, comment);

            await self.load()
        },

        async showAdditionalInfo(item) {
            const self = this;

            if (self.expanded.indexOf(item) > -1 && self.displayAdditionalInfo) {
                self.expanded = [];
                return;
            }

            self.displayAdditionalInfo = true;
            self.expanded = [item];

            self.userData = item;
        },

        getHeaders() {
            // to set a fixed width add widthPerc property to column with int value 0-100
            let headers = [
                {
                    text: "Company Name",
                    value: "company_name",
                    sortable: true,
                    widthPerc: 10
                },
                {
                    text: "Date Created",
                    value: "date_created",
                    sortable: true,
                },
                {
                    text: "Pipeline Of Work",
                    value: "pipeline_of_work",
                    sortable: true,
                },
                {
                    text: "Potential Users",
                    value: "potential_users",
                    sortable: true,
                },
                {
                    text: "Turnover",
                    value: "turnover",
                    sortable: true,
                },
                {
                    text: "Number Of Employees",
                    value: "number_of_employees",
                    sortable: true,
                },
                {
                    text: "Onboarding Call",
                    value: "onboarding_call",
                    sortable: true,
                },
                {
                    text: "Next Call",
                    value: "next_call",
                    sortable: true,
                },
                {
                    text: "Pain Points",
                    value: "pain_points",
                    sortable: true,
                },
                {
                    text: "Paid",
                    value: "paid",
                    sortable: true,
                },
                {
                    text: "Called",
                    value: "called_at",
                    sortable: false,
                }
            ]

            if (this.role === permissions.role_manufacturer) {
                let headersManufacturer =  [
                    {
                        text: "Plan",
                        value: "plan",
                        sortable: false,
                    },
                    {
                        text: "Plan End Date",
                        value: "plan_end_date",
                        sortable: false,
                    },
                    {
                        text: "Impressions",
                        value: "impressions",
                        sortable: false,
                    },
                    {
                        text: "Clicks",
                        value: "clicks",
                        sortable: false,
                    },
                    {
                        text: "Video Views",
                        value: "video_views",
                        sortable: false,
                    },
                ];

                headersManufacturer.forEach(col => {
                    let startIndex = 9
                    headers.splice( startIndex, 0, col);
                    startIndex++;
                });
            } else if (this.role === permissions.role_company) {
                let headersMerchant = [
                    {
                        text: "Trial/Plan",
                        value: "plan",
                        sortable: true,
                    },
                    {
                        text: "Trial/Plan End Date",
                        value: "plan_end_date",
                        sortable: true,
                    },
                    {
                        text: "Last Quote Date",
                        value: "last_quote_date",
                        sortable: true,
                    },
                    {
                        text: "Total Quotes",
                        value: "total_quotes",
                        sortable: true,
                    },
                ];

                headersMerchant.forEach(col => {
                    let startIndex = 9
                    headers.splice( startIndex, 0, col);
                    startIndex++;
                });
            } else {
                let headersDefault = [
                    {
                        text: "Trial/Plan",
                        value: "plan",
                        sortable: true,
                    },
                    {
                        text: "Trial/Plan End Date",
                        value: "plan_end_date",
                        sortable: true,
                    },
                    {
                        text: "Last enquiry date",
                        value: "last_enquiry_date",
                        sortable: true,
                    },
                    {
                        text: "Total Enquiries",
                        value: "total_enquiries",
                        sortable: true,
                    },
                ];

                headersDefault.forEach(col => {
                    let startIndex = 9
                    headers.splice( startIndex, 0, col);
                    startIndex++;
                });
            }

            let remainingWidth = 100;
            let widthPerc = remainingWidth/headers.length;

            // calculate and set column widths
            headers.forEach(col => {
               if (col.widthPerc) {
                   //console.log(`column: ${col.text} has a fixed width: ${col.widthPerc}`);

                   col.width = col.widthPerc + "%";

                   remainingWidth = remainingWidth - col.widthPerc;
                   widthPerc = remainingWidth/headers.length;
               } else {
                   col.width = widthPerc + "%";
               }
            });

            return headers;
        },

        setFilter(role) {
            this.role = role;
        },

        async load() {
            if (this.role === permissions.role_contractor) {
                return await this.loadContractor();
            }

            if (this.role === permissions.role_company) {
                return await this.loadMerchant();
            }

            if (this.role === permissions.role_manufacturer) {
                return await this.loadManufacturer();
            }

            return await this.loadContractor();
        },

        async loadContractor() {
            const self = this;
            self.loader = true;

            let params = {
                ...self.options,
                page: self.options.page,
                itemsPerPage: self.options.itemsPerPage,
                sortBy: self.options.sortBy[0],
                sortDesc: self.options.sortDesc[0] ? 1 : 0,
                billing_user_id: self.billing_user_id,
                search: self.filters.search,
            }

            try {
                const response = await api.getContractorStats(params)

                self.items = response.data.data;
                self.serverItemsLength = response.data.total;
            } catch (e) {}

            self.loader = false;
        },

        async loadMerchant() {
            const self = this;
            self.loader = true;

            let params = {
                ...self.options,
                page: self.options.page,
                itemsPerPage: self.options.itemsPerPage,
                sortBy: self.options.sortBy[0],
                sortDesc: self.options.sortDesc[0] ? 1 : 0,
                billing_user_id: self.billing_user_id,
                search: self.filters.search,
            }

            try {
                const response = await api.getMerchantStats(params)
                self.items = response.data.data;
                self.serverItemsLength = response.data.total;
            } catch (e) {}

            self.loader = false;
        },

        showAllEvents(item) {
            this.selectedUserId = item.id;
            this.dialogAllEvents = true;
        },

        async loadManufacturer() {
            const self = this;
            self.loader = true;

            let params = {
                ...self.options,
                page: self.options.page,
                itemsPerPage: self.options.itemsPerPage,
                sortBy: self.options.sortBy[0],
                sortDesc: self.options.sortDesc[0] ? 1 : 0,
                billing_user_id: self.billing_user_id,
                search: self.filters.search,
            }

            try {
                const response = await api.getManufacturerStats(params)
                self.items = response.data.data;
                self.serverItemsLength = response.data.total;
            } catch (e) {}

            self.loader = false;
        },

        editItem(item) {
            const self = this;

            self.editedIndex = self.items.indexOf(item);
            self.editedItem = Object.assign({}, item);

            // this bit throws error: cant iterate undefined
            // self.editedItem.is_billing_user = permissions.isAllowed(item, [
            //     permissions.manage_subscriptions,
            // ]);

            self.$nextTick(() => {
                self.dialogEditUser = true;
            });
        },

        buildFormData(formData, data, parentKey) {
            const self = this;
            if (data && typeof data === 'object' && !(data instanceof Date) && !(data instanceof File)) {
                Object.keys(data).forEach(key => {
                    self.buildFormData(formData, data[key], parentKey ? `${parentKey}[${key}]` : key);
                });
            } else {
                const value = data == null ? '' : data;

                formData.append(parentKey, value);
            }
        },

        async save(user) {
            const self = this;

            self.loader = true;

            let formData = new FormData();
            this.buildFormData(formData, user)

            await api.storeUserCsp(formData, user.id)
            await self.load();
            self.loader = false;

            self.close();
        },

        close() {
            const self = this;

            self.dialogEditUser = false;
            self.$nextTick(() => {
                self.editedItem = Object.assign({}, self.defaultItem);
                self.editedIndex = -1;
            });
        },

        formatPaid(val) {
            return val ? 'yes' : 'no';
        },

        formatTurnover(val) {
            return val ? '£' + val : '';
        },
    }
}
</script>

<style scoped>

</style>
