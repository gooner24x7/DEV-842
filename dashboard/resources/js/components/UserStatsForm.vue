<template>
    <v-card>
        <v-card-title>
            <span class="headline">{{ title }}</span>
        </v-card-title>

        <v-card-text>
                <v-container>

                    <v-row>
                        <v-col md="6">
                            <date-picker
                                v-model="value.pipeline_of_work"
                                value-type="format"
                                class="mn-datepicker"
                                type="date"
                                placeholder="Pipeline Of Work"
                            ></date-picker>
                        </v-col>
                        <v-col md="6">
                            <v-text-field
                                id="potential_users"
                                v-model="value.potential_users"
                                label="Potential Users"
                                name="potential_users"
                                type="number"
                            />
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col md="6">
                            <v-text-field
                                id="turnover"
                                v-model="value.turnover"
                                label="Turnover"
                                name="turnover"
                                type="number"
                            />
                        </v-col>
                        <v-col md="6">
                            <v-text-field
                                id="number_of_employees"
                                v-model="value.number_of_employees"
                                label="Number Of Employees"
                                name="number_of_employees"
                                type="number"
                            />
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col md="12">
                            <v-text-field
                                id="pain_points"
                                v-model="value.pain_points"
                                label="Pain Points"
                                name="pain_points"
                                type="text"
                            />
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col md="6">
                            <date-picker
                                v-model="value.onboarding_call"
                                value-type="format"
                                class="mn-datepicker"
                                type="datetime"
                                placeholder="Onboarding Call"
                                :show-second="false"
                            ></date-picker>
                        </v-col>
                        <v-col md="6">
                            <date-picker
                                v-model="value.next_call"
                                value-type="format"
                                class="mn-datepicker"
                                type="datetime"
                                placeholder="Next Call"
                                :show-second="false"
                            ></date-picker>
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col md="6">
                            <date-picker
                                v-model="value.onboarding_end_date"
                                value-type="format"
                                class="mn-datepicker"
                                type="date"
                                placeholder="Onboarding End Date"
                            ></date-picker>
                        </v-col>
                    </v-row>

                </v-container>
        </v-card-text>

        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="blue darken-1" text @click="cancel">Cancel</v-btn>
            <v-btn color="blue darken-1" text @click="save">Save</v-btn>
        </v-card-actions>
    </v-card>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";
import moment from "moment";
import DatePicker from 'vue2-datepicker';
import 'vue2-datepicker/index.css';

export default {
    components: {DatePicker},
    props: ["value", "title", "billing_user_id", "visible"],
    data() {
        return {
            tab: '',
            isLoading: false,
            search: null,
            searchStripePlan: null,
            isManagerSelected: false,
            isGroupUserOrCompany: false,
            isCompanyUserSelected: false,
            trialEnds: '',
            searchBranches: '',
            stripePlans: [],
            branches: [],
            roles: [],
            billingUsers: [],
            formValid: true,
            user_plans: '',
            errors: {
            },
        };
    },
    computed: {
        userRoles() {
            const self = this;
            let rolesBuf = [];
            for (let id in self.value.role_ids) {
                let items = self.roles.filter((item) => {
                    return parseInt(item.id) === parseInt(self.value.role_ids[id]);
                });

                if (items.length === 0) {
                    return false;
                }

                rolesBuf.push(items[0]);
            }

            return rolesBuf;
        },
        // use these if we need to reformat the dates
        // and add 'format' attr to the datepicker input
        // onboardingCall: {
        //     get() {
        //         return this.value.onboarding_call;
        //     },
        //     set(val) {
        //         //this.$emit('updated', val);
        //         this.value.onboarding_call = val;
        //     }
        // },
        // nextCall: {
        //     get() {
        //         return this.value.next_call;
        //     },
        //     set(val) {
        //         //this.$emit('updated', val);
        //         this.value.next_call = val;
        //     }
        // }
    },
    watch: {
        'visible': {
            handler(val) {
                const self = this;
            }
        },

        'value.role_ids': {
            handler(val) {
                const self = this;

                self.isManagerSelected = self.isManager();

                self.value.is_billing_user = self.isBillingUser();
                self.isGroupUserOrCompany = self.hasGroupUserOrCompany();
                self.isCompanyUserSelected = self.isCompany();
            }
        },

        value: {
            handler(val){
                const self = this;

                self.reloadCurrentBillingUser();
                //self.reloadStripePlans();

                self.isManagerSelected = self.isManager();

                self.value.is_billing_user = self.isBillingUser();
                self.isGroupUserOrCompany = self.hasGroupUserOrCompany();
                self.isCompanyUserSelected = self.isCompany();
            },
            deep: true,
        },

        roles(val) {
            const self = this;
            self.isManagerSelected = self.isManager();
            self.value.is_billing_user = self.isBillingUser();
            self.isGroupUserOrCompany = self.hasGroupUserOrCompany();
            self.isCompanyUserSelected = self.isCompany();
        },

        search(val) {
            const self = this;

            self.loadBillingUsers()
        },

        async searchBranches(val) {
            const self = this;

            const resp = await api.companyUsersOptions(val)
            self.branches = resp.data;
        },
    },
    async mounted() {
        self = this;

        await self.init();
    },

    methods: {
        hasRole(role_slug) {
            const self = this;
            if (typeof self.userRoles != 'object') {
                return false;
            }

            let roles = self.userRoles.map(item => {
                return item.slug;
            });

            return roles.indexOf(role_slug) >= 0;
        },

        isBillingUser() {
            return this.hasRole('billing_user');
        },

        isManager: function () {
            return this.hasRole('manager');
        },

        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },

        isCompany() {
            return this.hasRole('company');
        },

        isContractor() {
            return this.hasRole('contractor');
        },

        isUser() {
            return this.hasRole('user');
        },

        isManufacturer() {
            return this.hasRole('manufacturer');
        },

        hasGroupUserOrCompany() {
            return this.isCompany() || this.isUser() || this.isContractor() || this.isManufacturer();
        },

        addNoPlan(items) {
            items.unshift({
                id: '',
                product: {
                    'name': 'No subscription',
                },
            }, {
                id: 'free',
                product: {
                    'name': 'Free',
                }
            });

            return items;
        },

        async reloadStripePlans() {
            const self = this
            if (!self.value.is_billing_user) {
                return
            }

            try {
                const result = await api.getStripePlans({})
                self.stripePlans = self.addNoPlan(result.data)
                await self.getUserPlan()
            } catch(err) {
                self.stripePlans = self.addNoPlan([])
            }
        },

        async loadBillingUsers() {
            const self = this;
            if (self.isLoading) return;

            self.isLoading = true;

            const resp = await api.billingUserOptions(self.search)

            self.billingUsers = resp.data;
            self.isLoading = false;
        },

        async getUserPlan() {
            const self = this;
            if (!self.value.id) {
                return;
            }

            const result = await api.getUserStripeSettings(self.value.id)

            const items = (result?.data ?? []).filter((item) => item != null);
            if (items.length <= 0) {
                return
            }

            self.user_plans = items[0].stripe_plan;
        },

        async reloadCurrentBillingUser() {
            const self = this;

            if (typeof self.value == "undefined") {
                return;
            }

            if (!self.value.billing_user_id) {
                return;
            }

            const resp = await api.getUser(self.value.billing_user_id)

            self.billingUsers = [resp.data];
        },

        cancel() {
            this.$emit("cancel", this.value);
        },

        isUserOrCompany(role) {
            return role === "company" || role === "user";
        },

        save() {
            if (this.billing_user_id > 0) {
                this.value.billing_user_id = this.billing_user_id;
            }

            this.formValid = true;

            for (let i in this.errors) {
                this.errors[i] = false;

                if (this.value[i] === "") {
                    this.formValid = false;
                    this.errors[i] = true;
                }
            }

            if (this.formValid) {
                this.$emit("save", this.value);
            }
        },

        async init() {
            const self = this;

            if (self.value.branch_id) {
                self.branches = [
                    {
                        "id": self.value.branch_id,
                        "first_name": self.value.branch_name,
                    },
                ];
            }

            const response = await api.loadRoles({paginate: 0,})
            self.roles = response.data.data;

            await self.loadBillingUsers();

            await self.reloadCurrentBillingUser();

            await self.reloadStripePlans();
        },
    },
};
</script>

<style></style>
