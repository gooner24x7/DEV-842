<template>
    <v-card>
        <v-card-title>
            <span class="headline">{{ title }}</span>
        </v-card-title>

        <v-card-text>

            <v-autocomplete
                v-model="duplicateUserId"
                v-show="!isEdit"
                :items="userList"
                :item-text="getUserFullName"
                item-value="id"
                label="Duplicate User"
                outlined
                dense
            ></v-autocomplete>

            <v-tabs
                v-model="tab"
                background-color="transparent"
                color="basil"
                grow
            >
                <v-tab v-show="showTabs()" href="#general">
                    General
                </v-tab>
                <v-tab v-show="showTabs()" href="#pref_suppliers">
                    Preferred suppliers
                </v-tab>
                <v-tab v-show="showTabs()" href="#pref_subcontractors">
                    Preferred subcontractors
                </v-tab>
            </v-tabs>

            <v-tabs-items v-model="tab">
                <v-tab-item value="general">
                    <v-container>
                        <v-row>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="value.first_name"
                                    :error="errors.first_name"
                                    label="First Name"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="value.last_name"
                                    :error="errors.last_name"
                                    label="Last Name"
                                ></v-text-field>
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="value.username"
                                    :error="errors.username"
                                    label="Username"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="value.job_title"
                                    :error="errors.job_title"
                                    label="Job Title"
                                ></v-text-field>
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="value.email"
                                    :error="errors.email"
                                    autocomplete="off"
                                    label="Email"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="value.phone"
                                    :error="errors.phone"
                                    autocomplete="off"
                                    label="Telephone"
                                ></v-text-field>
                            </v-col>
                        </v-row>

                        <v-text-field
                            v-model="value.password"
                            :error="errors.password"
                            :rules="passwordRule()"
                            autocomplete="off"
                            label="Password"
                            type="password"
                        />

                        <v-row>
                            <v-col md="6">
                                <v-text-field
                                    v-model="value.postcode"
                                    :disabled="postcodeDisabled()"

                                    :error="errors.postcode"
                                    :messages="(postcodeDisabled()) ? 'if you need to change contact customer services' : ''"
                                    :rules="postcodeRule()"
                                    label="Postcode"
                                    placeholder="M60 1NW"
                                ></v-text-field>
                            </v-col>
                            <v-col md="6">
                                <v-text-field
                                    id="city"
                                    v-model="value.city"
                                    :error="errors.city"
                                    label="Town or City"
                                    name="city"
                                />
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col md="6">
                                <v-text-field
                                    id="addr_line_1"
                                    v-model="value.addr_line_1"
                                    :error="errors.addr_line_1"
                                    label="Address line 1"
                                    name="addr_line_1"
                                />
                            </v-col>
                            <v-col md="6">
                                <v-text-field
                                    id="addr_line_2"
                                    v-model="value.addr_line_2"
                                    label="Address line 2"
                                    name="addr_line_2"
                                />
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col md="6">
                                <v-text-field
                                    id="company_number"
                                    v-model="value.company_number"
                                    label="Company Number"
                                    name="company_number"
                                />
                            </v-col>
                            <v-col md="6">
                                <!--<v-text-field
                                    id="pipeline_of_work"
                                    v-model.number="value.pipeline_of_work"
                                    label="Pipeline Of Work"
                                    name="pipeline_of_work"
                                />-->
                                <date-picker
                                    v-model="value.pipeline_of_work"
                                    value-type="format"
                                    class="mn-datepicker"
                                    type="date"
                                    placeholder="Pipeline Of Work"
                                ></date-picker>
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col md="6">
                                <v-text-field
                                    id="potential_users"
                                    v-model="value.potential_users"
                                    label="Potential Users"
                                    name="potential_users"
                                />
                            </v-col>
                            <v-col md="6">
                                <v-text-field
                                    id="turnover"
                                    v-model="value.turnover"
                                    label="Turnover"
                                    name="turnover"
                                />
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col md="6">
                                <v-text-field
                                    id="number_of_employees"
                                    v-model="value.number_of_employees"
                                    label="Number Of Employees"
                                    name="number_of_employees"
                                />
                            </v-col>
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
                        </v-row>

                        <v-row>
                            <v-col md="12">
                                <v-text-field
                                    id="pain_points"
                                    v-model="value.pain_points"
                                    label="Pain Points"
                                    name="pain_points"
                                />
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col cols="12">
                                <v-autocomplete
                                    v-model="value.role_ids"
                                    :error="errors.role_ids"
                                    :items="roles"
                                    chips
                                    dense
                                    item-text="name"
                                    item-value="id"
                                    label="Roles"
                                    multiple
                                    outlined
                                    small-chips
                                ></v-autocomplete>
                            </v-col>
                        </v-row>

                        <v-row v-if="isBillingUser()">
                            <v-col cols="12">
                                <v-autocomplete
                                    v-model="value.company_type"
                                    :error="errors.company_type"
                                    :items="companyTypes"
                                    item-text="name"
                                    item-value="slug"
                                    label="Company Type"
                                    dense
                                    outlined
                                ></v-autocomplete>
                            </v-col>
                        </v-row>

                        <v-row v-if="isCompany() || isUser() || isLogistics()">
                            <v-col cols="12">
                                <v-checkbox v-model="value.is_global" dense label="Is National Supplier"></v-checkbox>
                            </v-col>
                        </v-row>

                        <v-row v-if="isBillingUser() && isAdmin()">
                            <v-col cols="12">
                                <v-checkbox v-model="value.is_gold_account" dense label="Is Gold Account"></v-checkbox>
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col cols="12">
                                <v-checkbox v-model="value.is_test_account" dense label="Test Account"></v-checkbox>
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col cols="12">
                                <v-checkbox v-model="value.is_sme" dense label="Is SME"></v-checkbox>
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col>
                                <v-checkbox
                                    v-model="value.can_check_competency"
                                    label="Can check competency"
                                    dense
                                ></v-checkbox>
                            </v-col>
                        </v-row>

                        <v-row v-if="!isManagerSelected">
                            <div>Select All Products:
                                <v-radio-group row>
                                    <v-checkbox v-for="(v, i) in categoryTypes" v-model="selectedCategories" :label="v.name" :value="v.id" :key="i" dense></v-checkbox>
                                </v-radio-group>
                            </div>
                            <v-col cols="12">
                                <v-autocomplete
                                    v-model="value.product_ids"
                                    :error="errors.products"
                                    :items="productItems"
                                    chips
                                    dense
                                    item-text="name"
                                    item-value="id"
                                    label="Products"
                                    multiple
                                    outlined
                                    small-chips
                                >
                                </v-autocomplete>
                            </v-col>
                        </v-row>

                        <v-row v-if="isCompanyUserSelected">
                            <v-col cols="12">
                                <v-checkbox v-model="value.can_assign_to_enquiries" dense label="Can be assigned to enquiries"></v-checkbox>
                            </v-col>
                        </v-row>

                        <v-row v-if="isManagerSelected">
                            <v-col cols="6">
                                <v-autocomplete
                                    v-model="value.manager_type"
                                    :items="managerTypes"
                                    dense
                                    item-text="name"
                                    item-value="id"
                                    label="Manager Type"
                                    outlined
                                ></v-autocomplete>
                            </v-col>

                            <v-col v-if="value.manager_type === managerTypes[0].id" cols="6">
                                <v-autocomplete
                                    v-model="value.branch_id"
                                    :items="branches"
                                    :search-input.sync="searchBranches"
                                    dense
                                    item-text="first_name"
                                    item-value="id"
                                    label="Branch"
                                    outlined
                                ></v-autocomplete>

                            </v-col>
                        </v-row>


                        <v-row>
                            <v-col v-if="isGroupUserOrCompany && !isPartner()" cols="12">
                                <v-autocomplete
                                    v-model="value.billing_user_id"
                                    :error="errors.billing_user_id"
                                    :items="billingUsers"
                                    :loading="isLoading"
                                    :search-input.sync="search"
                                    chips
                                    dense
                                    item-text="first_name"
                                    item-value="id"
                                    label="Billing User"
                                    outlined
                                    small-chips
                                ></v-autocomplete>
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col v-if="value.is_billing_user" cols="12">
                                <v-autocomplete
                                    v-model="user_plans"
                                    :items="stripePlans"
                                    :loading="isLoading"
                                    :search-input.sync="searchStripePlan"
                                    chips
                                    dense
                                    item-text="product.name"
                                    item-value="id"
                                    label="Stripe Plan"
                                    outlined
                                    small-chips
                                ></v-autocomplete>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col v-if="value.is_billing_user" cols="12">
                                <v-menu
                                    v-model="menu1"
                                    :close-on-content-click="false"
                                    max-width="290"
                                >
                                    <template v-slot:activator="{ on, attrs }">
                                        <v-text-field
                                            v-model="value.trial_ends"
                                            v-bind="attrs"
                                            v-on="on"
                                            :type="'text'"
                                            clearable
                                            label="Trial Ends"
                                        ></v-text-field>
                                    </template>
                                    <v-date-picker
                                        v-model="trialEnds"
                                        no-title
                                        @change="menu1 = false"
                                    ></v-date-picker>
                                </v-menu>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col v-if="value.is_billing_user" cols="12">
                                <div><a :href="value.credit_application_form_url" target="_blank">Download Credit Application Form</a></div>
                                <v-file-input cols="10" placeholder="Credit Application Form"
                                              type="file" v-on:change="(f) => { attachment = f; }"/>
                            </v-col>
                        </v-row>

                        <v-row
                            v-if="!isPartner()">
                            <v-col cols="12">
                                <v-textarea label="Activity tracker AI mappping" v-model="value.activity_tracker_mapping"></v-textarea>
                            </v-col>
                        </v-row>

                    </v-container>
                </v-tab-item>
                <v-tab-item value="pref_suppliers">
                    <v-card flat>
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-btn
                                        v-if="value.preferred_suppliers_file"
                                        :href="getPreferredSuppliersFile()"
                                        class="mb-2" target="_blank">Download preferred
                                        suppliers file
                                    </v-btn>

                                    <preferred-table :user="value" :type="0"></preferred-table>
                                </v-col>
                            </v-row>
                        </v-container>
                    </v-card>
                </v-tab-item>
                <v-tab-item value="pref_subcontractors">
                    <v-card flat>
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <preferred-table :user="value" :type="1"></preferred-table>
                                </v-col>
                            </v-row>
                        </v-container>
                    </v-card>
                </v-tab-item>
            </v-tabs-items>


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
import PreferredTable from "./PreferredTable";
import moment from "moment";
import DatePicker from 'vue2-datepicker';
import 'vue2-datepicker/index.css';

export default {
    components: {
        PreferredTable,
        DatePicker
    },
    props: ["value", "title", "billing_user_id", "visible", "userList", "isEdit"],
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
            menu1: false,
            menu2: false,
            branches: [],
            roles: [],
            companyTypes: [],
            productItems: [],
            billingUsers: [],
            attachment: null,
            userListMutable: [],
            userData: this.value,
            duplicateUserId: null,
            categoryTypes: [
                {
                    id: 1,
                    name: 'Hire Enquiry',
                },
                {
                    id: 2,
                    name: 'Purchase Enquiry',
                },
                {
                    id: 3,
                    name: 'Marketplace',
                }
            ],
            selectedCategories: [],
            managerTypes: [
                {
                    'id': 'branch',
                    'name': 'Branch Manager',
                }
            ],
            formValid: true,
            user_plans: '',
            errors: {
                postcode: false,
                first_name: false,
                last_name: false,
                password: false,
                products: false,
                email: false,
                username: false,
                job_title: false,
                role_ids: false,
                company_type: false,
                phone: false,
                city: false,
                addr_line_1: false,
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
    },
    watch: {
        'value.role_ids': function (n, o) {
            this.value.is_billing_user = false;
            for (const role of this.userRoles) {
                if (String(role.slug) === permissions.role_partner || String(role.slug) === permissions.role_billing_user) {
                    this.value.is_billing_user = true;
                }
            }
        },

        selectedCategories: {
            handler(val){
                this.value.product_ids = this.productItems.map((item) => {
                    return (val.indexOf(item.category_id) >= 0) ? item.id : null
                }).filter((e) => e)
            },
            deep: true
        },

        trialEnds:  function (n, o) {
            this.value.trial_ends = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        },

        'value.is_billing_user': {
            handler(val) {
                const self = this;

                self.reloadCurrentBillingUser();
                self.reloadStripePlans();
            }
        },

        value: {
            handler(val){
                const self = this;

                // self.reloadCurrentBillingUser();
                // self.reloadStripePlans();

                self.tab = "";
                self.isManagerSelected = self.isManager();
                self.isGroupUserOrCompany = self.hasGroupUserOrCompany();
                self.isCompanyUserSelected = self.isCompany();
            },
            deep: true,
        },

        roles(val) {
            const self = this;
            self.isManagerSelected = self.isManager();
            self.isGroupUserOrCompany = self.hasGroupUserOrCompany();
            self.isCompanyUserSelected = self.isCompany();
        },

        search(val) {
            const self = this;

            self.loadBillingUsers();
        },

        async searchBranches(val) {
            const self = this;

            const resp = await api.companyUsersOptions(val)
            self.branches = resp.data;
        },

        duplicateUserId(val) {
            let selectedUser = this.userListMutable.filter(function (el) {
                return el.id === val;
            });

            delete selectedUser[0].id;

            this.populateForm(selectedUser[0]);
        }
    },
    async mounted() {
        const self = this;

        await self.init();
    },

    methods: {
        isPartner() {
            if (typeof this.$store?.getters?.getSession?.user?.role_name != 'object') {
                return false;
            }

            let roles = this.$store?.getters?.getSession?.user?.role_name ?? []

            return roles.join().toLowerCase().includes("partner");
        },

        getUserFullName(item) {
            return `${item.first_name} ${item.last_name}`;
        },

        populateForm(user) {
            this.$emit('update', user);
        },

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

        isLogistics() {
            return this.hasRole('logistics');
        },

        isManufacturer() {
            return this.hasRole('manufacturer');
        },

        isFramework() {
            return this.hasRole('framework');
        },

        isClient() {
            return this.hasRole('client');
        },

        isConsultant() {
            return this.hasRole('consultant');
        },

        hasGroupUserOrCompany() {
            return this.isCompany() || this.isUser() || this.isContractor() || this.isManufacturer() || this.isLogistics() || this.isFramework() || this.isClient() || this.isConsultant();

        },

        postcodeDisabled() {
            return !this.isAdmin() && !this.isPartner();
        },

        postcodeRule() {
            return [
                (v) => !!v || 'This field is required',
                (v) =>
                    /^([A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}|GIR ?0A{2})$/i.test(v) ||
                    'Must be a vaild UK postcode',
            ];
        },

        passwordRule() {
            // password is optional; only validate when a value is entered
            return [
                (v) => !v || v.length >= 12 || 'Password must be at least 12 characters',
                (v) => !v || /[A-Z]/.test(v) || 'Password must contain at least 1 uppercase letter',
                (v) => !v || /[a-z]/.test(v) || 'Password must contain at least 1 lowercase letter',
                (v) => !v || /[0-9]/.test(v) || 'Password must contain at least 1 number',
                (v) => !v || /[^A-Za-z0-9]/.test(v) || 'Password must contain at least 1 special character',
            ];
        },

        isValidPassword(password) {
            return password.length >= 12
                && /[A-Z]/.test(password)
                && /[a-z]/.test(password)
                && /[0-9]/.test(password)
                && /[^A-Za-z0-9]/.test(password);
        },

        showTabs() {
            return this.isWithPreferredSuppliersUser();
        },

        isWithPreferredSuppliersUser() {
            if (typeof this.value.role_name != 'object') {
                return false;
            }

            if (this.value.role_name.join().toLowerCase().includes("user")) {
                return true;
            }

            return this.value.role_name.join().toLowerCase().includes("billing");
        },

        getPreferredSuppliersFile() {
            return api.baseUrl + '/downloadFile?file=' + encodeURIComponent(this.value.preferred_suppliers_file);
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

            let result = {data: []}
            try {
                result = await api.getStripePlans({})
            } catch(err) {
            }
            self.stripePlans = self.addNoPlan(result.data)
            await self.getUserPlan()
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

                if (i === 'password' && typeof this.value.id != 'undefined') {
                    continue;
                }

                if (this.value[i] === "") {
                    this.formValid = false;
                    this.errors[i] = true;
                }
            }

            if (this.value.password && !this.isValidPassword(this.value.password)) {
                this.formValid = false;
                this.errors.password = true;
            }

            if (this.formValid) {
                this.value.postcode = this.value.postcode.toUpperCase();

                this.value.creditApplicationForm = this.attachment;

                this.value.activity_tracker_mapping = this.value.activity_tracker_mapping ?? '';
                this.value.company_number = this.value.company_number ?? '';

                if (this.isPartner()) {
                    this.value.billing_user_id = this.billing_user_id;
                }

                this.$emit("save", {"user_plans": this.user_plans, ...this.value});
            }
        },

        async init() {
            const self = this;

            self.userListMutable = _.cloneDeep(this.userList);

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

            self.companyTypes = self.roles.filter((role) => {
                const excludedRoles = ['admin', 'billing_user', 'manager', 'branch_manager', 'partner', 'customer_success_admin'];
                return !excludedRoles.includes(role.slug);
            });

            if (self.isPartner()) {
                self.roles = [self.roles.filter((role) => {
                    return role.slug === 'user';
                })[0]]
            }

            const resp = await api.productOptions()
            self.productItems = resp.data;

            await self.loadBillingUsers();

            await self.reloadCurrentBillingUser();

            await self.reloadStripePlans();
        },
    },
};
</script>

<style></style>
