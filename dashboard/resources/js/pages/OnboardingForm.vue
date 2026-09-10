<template>
    <div style="margin: 1em">
        <v-stepper v-model="currentStep" alt-labels>
            <v-stepper-header class="form-header">
                <v-stepper-step :complete="currentStep > 1" step="1" @click="goToStep(1)">Assign Users</v-stepper-step>
                <v-divider></v-divider>
                <v-stepper-step :complete="currentStep > 2" step="2" @click="goToStep(2)">
                    <div class="step-header">Your Company Details</div>
                    <div class="step-chips">
                        <v-chip x-small class="mr-1 mt-1" v-for="alias in aliasesForStep('company_details')" :key="alias">{{ alias }}</v-chip>
                    </div>
                </v-stepper-step>
                <v-divider></v-divider>
                <v-stepper-step :complete="currentStep > 3" step="3" @click="goToStep(3)">
                    <div class="step-header">Common Assessment Approved</div>
                    <div class="step-chips">
                        <v-chip x-small class="mr-1 mt-1" v-for="alias in aliasesForStep('common_assessment_approved')" :key="alias">{{ alias }}</v-chip>
                    </div>
                </v-stepper-step>
                <v-divider></v-divider>
                <v-stepper-step :complete="currentStep > 4" step="4" @click="goToStep(4)">
                    <div class="step-header">Users and Roles</div>
                    <div class="step-chips">
                        <v-chip x-small class="mr-1 mt-1" v-for="alias in aliasesForStep('users_and_roles')" :key="alias">{{ alias }}</v-chip>
                    </div>
                </v-stepper-step>
                <v-divider></v-divider>
                <v-stepper-step :complete="currentStep > 5" step="5" @click="goToStep(5)">
                    <div class="step-header">Your Supply Chain</div>
                    <div class="step-chips">
                        <v-chip x-small class="mr-1 mt-1" v-for="alias in aliasesForStep('supply_chain')" :key="alias">{{ alias }}</v-chip>
                    </div>
                </v-stepper-step>
                <v-divider></v-divider>
                <v-stepper-step :complete="currentStep > 6" step="6" @click="goToStep(6)">
                    <div class="step-header">Line of Credit</div>
                    <div class="step-chips">
                        <v-chip x-small class="mr-1 mt-1" v-for="alias in aliasesForStep('line_of_credit')" :key="alias">{{ alias }}</v-chip>
                    </div>
                </v-stepper-step>
                <v-divider></v-divider>
                <v-stepper-step :complete="currentStep > 7" step="7" @click="goToStep(7)">
                    <div class="step-header">Your Company's ESG Policies</div>
                    <div class="step-chips">
                        <v-chip x-small class="mr-1 mt-1" v-for="alias in aliasesForStep('esg_policies')" :key="alias">{{ alias }}</v-chip>
                    </div>
                </v-stepper-step>
                <v-divider></v-divider>
                <v-stepper-step :complete="currentStep > 8" step="8" @click="goToStep(8)">
                    <div class="step-header">Your Company Certifications</div>
                    <div class="step-chips">
                        <v-chip x-small class="mr-1 mt-1" v-for="alias in aliasesForStep('company_certifications')" :key="alias">{{ alias }}</v-chip>
                    </div>
                </v-stepper-step>
            </v-stepper-header>

            <v-stepper-items>
                <!-- Step 1 -->
                <v-stepper-content step="1">
                    <onboarding-users-table
                        :can-assign="true"
                        title="Assign Users to onboarding stages"
                        subtitle="Add each user below and assign one or multiple onboarding tasks"
                        @users-loaded="onUsersLoaded"
                    ></onboarding-users-table>
                    <div class="form-btn-group">
                        <v-btn class="mt-2" color="primary" @click="currentStep = 2" :disabled="!validate.step1">Next</v-btn>
                    </div>
                </v-stepper-content>

                <!-- Step 2 -->
                <v-stepper-content step="2">
                    <v-form ref="form2" v-model="validate.step2" :disabled="!stepConfig.step2.enabled">
                        <h5 class="mt-2">1. Registered Company Name</h5>
                        <v-text-field
                            label="Company Name"
                            v-model="form.company_name"
                        ></v-text-field>

                        <h5 class="mt-2">2. Registered Company Number</h5>
                        <v-text-field
                            label="Company Registration Number"
                            v-model="form.company_registration_number"
                        ></v-text-field>

                        <h5 class="mt-2">3. Registered Company Address</h5>
                        <v-text-field
                            label="Address 1"
                            v-model="form.company_address1"
                        ></v-text-field>
                        <v-text-field
                            label="Address 2"
                            v-model="form.company_address2"
                        ></v-text-field>
                        <v-text-field
                            label="City"
                            v-model="form.company_city"
                        ></v-text-field>
                        <v-text-field
                            label="Postcode"
                            v-model="form.company_postcode"
                        ></v-text-field>

                        <h5 class="mt-2">4. VAT Number</h5>
                        <v-text-field
                            label="VAT Number"
                            v-model="form.company_vat_number"
                        ></v-text-field>

                        <h5 class="mt-2">5. Goods Supply?</h5>
                        <v-radio-group
                            v-model="form.goods_supply"
                            class="pl-2"
                        >
                            <v-radio
                                label='Yes'
                                :value="1"
                            ></v-radio>
                            <v-radio
                                label='No'
                                :value="0"
                            ></v-radio>
                        </v-radio-group>

                        <h5 class="mt-2">6. How does your company operate? (select all that apply)</h5>
                        <v-list>
                            <v-list-item>
                                <v-checkbox
                                    v-model="form.developer"
                                    label="Developer"
                                    :true-value="1"
                                    :false-value="0"
                                    hide-details
                                ></v-checkbox>
                            </v-list-item>
                            <v-list-item>
                                <v-checkbox
                                    v-model="form.framework"
                                    label="Framework"
                                    :true-value="1"
                                    :false-value="0"
                                    hide-details
                                ></v-checkbox>
                            </v-list-item>
                            <v-list-item>
                                <v-checkbox
                                    v-model="form.main_contractor"
                                    label="Main Contractor"
                                    :true-value="1"
                                    :false-value="0"
                                    hide-details
                                ></v-checkbox>
                            </v-list-item>
                            <v-list-item>
                                <v-checkbox
                                    v-model="form.sub_contractor"
                                    label="Sub Contractor"
                                    :true-value="1"
                                    :false-value="0"
                                    hide-details
                                ></v-checkbox>
                            </v-list-item>
                            <v-list-item>
                                <v-checkbox
                                    v-model="form.merchant"
                                    label="Merchant"
                                    :true-value="1"
                                    :false-value="0"
                                    hide-details
                                ></v-checkbox>
                            </v-list-item>
                            <v-list-item>
                                <v-checkbox
                                    v-model="form.manufacturer"
                                    label="Manufacturer"
                                    :true-value="1"
                                    :false-value="0"
                                    hide-details
                                ></v-checkbox>
                            </v-list-item>
                        </v-list>

                        <h5 class="mt-2">Are you a SME?</h5>
                        <v-radio-group
                            v-model="form.is_sme"
                            class="pl-2"
                        >
                            <v-radio
                                label='Yes'
                                :value="1"
                            ></v-radio>
                            <v-radio
                                label='No'
                                :value="0"
                            ></v-radio>
                        </v-radio-group>
                    </v-form>
                    <div class="form-btn-group">
                        <v-btn @click="currentStep = 1">Back</v-btn>
                        <v-btn color="primary" @click="currentStep = 3" :disabled="!validate.step2">Next</v-btn>
                    </div>
                </v-stepper-content>

                <!-- Step 3 -->
                <v-stepper-content step="3">
                    <v-form ref="form3" v-model="validate.step3" :disabled="!stepConfig.step3.enabled">
                        <h5 class="mt-2">7. Are you common assessment approved?</h5>
                        <v-radio-group
                            v-model="form.cas_approved"
                            class="pl-2"
                        >
                            <v-radio
                                label='Yes'
                                :value="1"
                            ></v-radio>
                            <v-radio
                                label='No'
                                :value="0"
                            ></v-radio>
                        </v-radio-group>

                        <h5 class="mt-2">8. Upload Your Common Assessment Standard certification</h5>
                        <v-file-input
                            v-model="form.file_cas"
                            accept=".csv, .xls, .xlsx"
                            label="Upload File"
                        ></v-file-input>
                        <div v-if="files.cas">Existing File: <a :href="files.cas.url" target="_blank">{{ files.cas.name }}</a></div>

                        <h5 class="mt-2">9. Select your Common Assessment Standard certifying body</h5>
                        <v-radio-group
                            v-model="form.cas_certifying_body"
                            class="pl-2"
                        >
                            <v-radio
                                v-for="(label, index) in certBodyOptions"
                                :key="index"
                                :label=label
                                :value="index"
                            ></v-radio>
                        </v-radio-group>

                        <h5 class="mt-2">10. Issue Date</h5>
                        <v-menu
                            v-model="menu1"
                            :close-on-content-click="false"
                            max-width="290"
                        >
                            <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                    v-model="form.cas_issue_date"
                                    label="Issue Date"
                                    type="text"
                                    v-bind="attrs"
                                    v-on="on"
                                ></v-text-field>
                            </template>
                            <v-date-picker
                                v-model="selectedIssueDate"
                                no-title
                                @change="menu1 = false"
                            ></v-date-picker>
                        </v-menu>

                        <h5 class="mt-2">11. Expiry Date</h5>
                        <v-menu
                            v-model="menu2"
                            :close-on-content-click="false"
                            max-width="290"
                        >
                            <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                    v-model="form.cas_expiry_date"
                                    label="Expiry Date"
                                    type="text"
                                    v-bind="attrs"
                                    v-on="on"
                                ></v-text-field>
                            </template>
                            <v-date-picker
                                v-model="selectedExpiryDate"
                                no-title
                                @change="menu2 = false"
                            ></v-date-picker>
                        </v-menu>
                    </v-form>
                    <div class="form-btn-group">
                        <v-btn @click="currentStep = 2">Back</v-btn>
                        <v-btn color="primary" @click="currentStep = 4" :disabled="!validate.step3">Next</v-btn>
                    </div>
                </v-stepper-content>

                <!-- Step 4 -->
                <v-stepper-content step="4">
                    <v-form ref="form4" v-model="validate.step4" :disabled="!stepConfig.step4.enabled">
                        <h5 class="mt-2">12. Upload your completed User Template file</h5>
                        <v-file-input
                            v-model="form.file_user_roles"
                            accept=".csv, .xls, .xlsx"
                            label="Upload File"
                        ></v-file-input>
                        <div v-if="files.user_roles">Existing File: <a :href="files.user_roles.url" target="_blank">{{ files.user_roles.name }}</a></div>
                    </v-form>

                    <onboarding-users-table
                        :can-assign="false"
                        title="Create Users"
                        subtitle="Add new users to your company"
                    ></onboarding-users-table>

                    <div class="form-btn-group">
                        <v-btn @click="currentStep = 3">Back</v-btn>
                        <v-btn color="primary" @click="currentStep = 5" :disabled="!validate.step4">Next</v-btn>
                    </div>
                </v-stepper-content>

                <!-- Step 5 -->
                <v-stepper-content step="5">
                    <v-form ref="form5" v-model="validate.step5" :disabled="!stepConfig.step5.enabled">
                        <h5 class="mt-2">13. Upload your Supply Chain code of conduct</h5>
                        <v-file-input
                            v-model="form.file_conduct"
                            accept=".csv, .xls, .xlsx"
                            label="Upload File"
                        ></v-file-input>
                        <div v-if="files.conduct">Existing File: <a :href="files.conduct.url" target="_blank">{{ files.conduct.name }}</a></div>

                        <h5 class="mt-2">14. Upload your completed Supply Chain and Preferred Supplier Template file</h5>
                        <v-file-input
                            v-model="form.file_pref_suppliers"
                            accept=".csv, .xls, .xlsx"
                            label="Upload File"
                        ></v-file-input>
                        <div v-if="files.pref_suppliers">Existing File: <a :href="files.pref_suppliers.url" target="_blank">{{ files.pref_suppliers.name }}</a></div>
                    </v-form>
                    <div class="form-btn-group">
                        <v-btn @click="currentStep = 4">Back</v-btn>
                        <v-btn color="primary" @click="currentStep = 6" :disabled="!validate.step5">Next</v-btn>
                    </div>
                </v-stepper-content>

                <!-- Step 6 -->
                <v-stepper-content step="6">
                    <v-form ref="form6" v-model="validate.step6" :disabled="!stepConfig.step6.enabled">
                        <h5 class="mt-2">15. Line of Credit</h5>
                        <a href="https://lenkie.com/partners/buildchain">Click here to apply for your line of credit</a>
                    </v-form>
                    <div class="form-btn-group">
                        <v-btn @click="currentStep = 5">Back</v-btn>
                        <v-btn color="primary" @click="currentStep = 7" :disabled="!validate.step6">Next</v-btn>
                    </div>
                </v-stepper-content>

                <!-- Step 7 -->
                <v-stepper-content step="7">
                    <v-form ref="form7" v-model="validate.step7" :disabled="!stepConfig.step7.enabled">
                        <h5 class="mt-2">16. Upload your company's published Modern Slavery policy</h5>
                        <v-file-input
                            v-model="form.file_modern_slavery"
                            accept=".csv, .xls, .xlsx"
                            label="Upload File"
                        ></v-file-input>
                        <div v-if="files.modern_slavery">Existing File: <a :href="files.modern_slavery.url" target="_blank">{{ files.modern_slavery.name }}</a></div>

                        <h5 class="mt-2">17. Upload your company's published Bribery and Corruption policy</h5>
                        <v-file-input
                            v-model="form.file_bribery"
                            accept=".csv, .xls, .xlsx"
                            label="Upload File"
                        ></v-file-input>
                        <div v-if="files.bribery">Existing File: <a :href="files.bribery.url" target="_blank">{{ files.bribery.name }}</a></div>
                    </v-form>
                    <div class="form-btn-group">
                        <v-btn @click="currentStep = 6">Back</v-btn>
                        <v-btn color="primary" @click="currentStep = 8" :disabled="!validate.step7">Next</v-btn>
                    </div>
                </v-stepper-content>

                <!-- Step 8 -->
                <v-stepper-content step="8">
                    <v-form ref="form8" v-model="validate.step8" :disabled="!stepConfig.step8.enabled">
                        <h5 class="mt-2">18. Upload one of the following certifications: Cyber Essentials, Cyber Essentials Plus, ISO27001</h5>
                        <v-file-input
                            v-model="form.file_cyber"
                            accept=".csv, .xls, .xlsx"
                            label="Upload File"
                        ></v-file-input>
                        <div v-if="files.cyber">Existing File: <a :href="files.cyber.url" target="_blank">{{ files.cyber.name }}</a></div>

                        <h5 class="mt-2">19. Upload your SSIP certification</h5>
                        <v-file-input
                            v-model="form.file_ssip"
                            accept=".csv, .xls, .xlsx"
                            label="Upload File"
                        ></v-file-input>
                        <div v-if="files.ssip">Existing File: <a :href="files.ssip.url" target="_blank">{{ files.ssip.name }}</a></div>

                        <h5 class="mt-2">20. Upload your Certifire certification</h5>
                        <v-file-input
                            v-model="form.file_fire_products"
                            accept=".csv, .xls, .xlsx"
                            label="Upload File"
                        ></v-file-input>
                        <div v-if="files.fire_products">Existing File: <a :href="files.fire_products.url" target="_blank">{{ files.fire_products.name }}</a></div>

                        <h5 class="mt-2">21. Upload your BAFE Fire Safety Registration</h5>
                        <v-file-input
                            v-model="form.file_fire_competencies"
                            accept=".csv, .xls, .xlsx"
                            label="Upload File"
                        ></v-file-input>
                        <div v-if="files.fire_competencies">Existing File: <a :href="files.fire_competencies.url" target="_blank">{{ files.fire_competencies.name }}</a></div>

                        <h5 class="mt-2">22. Upload your CSCS summary template file</h5>
                        <v-file-input
                            v-model="form.file_cscs"
                            accept=".csv, .xls, .xlsx"
                            label="Upload File"
                        ></v-file-input>
                        <div v-if="files.cscs">Existing File: <a :href="files.cscs.url" target="_blank">{{ files.cscs.name }}</a></div>

                        <h5 class="mt-2">23. Upload your site Induction template file</h5>
                        <v-file-input
                            v-model="form.file_induction"
                            accept=".csv, .xls, .xlsx"
                            label="Upload File"
                        ></v-file-input>
                        <div v-if="files.induction">Existing File: <a :href="files.induction.url" target="_blank">{{ files.induction.name }}</a></div>

                        <h5 class="mt-2">24. Upload your MOD certificate file</h5>
                        <v-file-input
                            v-model="form.file_mod"
                            accept=".csv, .xls, .xlsx"
                            label="Upload File"
                        ></v-file-input>
                        <div v-if="files.mod">Existing File: <a :href="files.mod.url" target="_blank">{{ files.mod.name }}</a></div>
                    </v-form>
                    <div class="form-btn-group">
                        <v-btn @click="currentStep = 7">Back</v-btn>
<!--                        <v-btn color="primary" @click="submitForm">Submit</v-btn>-->
                    </div>
                </v-stepper-content>
            </v-stepper-items>
        </v-stepper>
        <div class="form-btn-group">
            <v-btn color="primary" @click="submitForm">Save</v-btn>
        </div>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";
import moment from "moment";
import OnboardingUsersTable from "../components/OnboardingUsersTable.vue";

export default {
    name: 'OnboardingForm',
    props: [],
    components: {
        OnboardingUsersTable
    },
    data() {
        return {
            currentStep: 1,
            onboardingUsers: [],
            menu1: false,
            menu2: false,
            selectedIssueDate: '',
            selectedExpiryDate: '',
            rules: {
                required: v => !!v || 'Required'
            },
            validate: {
                step1: true,
                step2: false,
                step3: false,
                step4: false,
                step5: false,
                step6: false,
                step7: false,
                step8: false,
            },
            stepConfig: {
                step2: {
                    slug: 'company_details',
                    enabled: false,
                },
                step3: {
                    slug: 'common_assessment_approved',
                    enabled: false,
                },
                step4: {
                    slug: 'users_and_roles',
                    enabled: false,
                },
                step5: {
                    slug: 'supply_chain',
                    enabled: false,
                },
                step6: {
                    slug: 'line_of_credit',
                    enabled: false,
                },
                step7: {
                    slug: 'esg_policies',
                    enabled: false,
                },
                step8: {
                    slug: 'company_certifications',
                    enabled: false,
                }
            },
            form: {
                id: null,
                company_name: '',
                company_address1: '',
                company_address2: '',
                company_city: '',
                company_postcode: '',
                company_registration_number: '',
                company_vat_number: '',
                developer: 0,
                framework: 0,
                main_contractor: 0,
                sub_contractor: 0,
                merchant: 0,
                manufacturer: 0,
                is_sme: 0,
                goods_supply: 0,
                cas_approved: 0,
                cas_certifying_body: null,
                cas_issue_date: null,
                cas_expiry_date: null,
                file_cas: null,
                file_cyber: null,
                file_ssip: null,
                file_fire_products: null,
                file_fire_competencies: null,
                file_cscs: null,
                file_induction: null,
                file_modern_slavery: null,
                file_bribery: null,
                file_conduct: null,
                file_mod: null,
                file_pref_suppliers: null,
                file_user_roles: null
            },
            files: {
                cas: null,
                cyber: null,
                ssip: null,
                fire_products: null,
                fire_competencies: null,
                cscs: null,
                induction: null,
                modern_slavery: null,
                bribery: null,
                conduct: null,
                mod: null,
                pref_suppliers: null,
                user_roles: null
            },
            certBodyOptions: [
                'CHAS',
                'ConstructionLine',
                'SMAS',
                'CQMS',
                'Compliance Chain',
                'Achilles',
                'SCCS'
            ]
        };
    },

    mounted() {
        const self = this;

        self.loadFormState();
    },

    watch: {
        selectedIssueDate: function (n, o) {
            this.form.cas_issue_date = n ? moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY') : null;
        },
        selectedExpiryDate: function (n, o) {
            this.form.cas_expiry_date = n ? moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY') : null;
        }
    },

    computed: {

    },

    methods: {
        onUsersLoaded(users) {
            const self = this;
            self.onboardingUsers = users;

            console.log(users);

            const filteredUsers = users.filter(user => user.id === self.user().id);

            console.log(filteredUsers);

            // enable form steps if current user is assigned to the task
            Object.keys(self.stepConfig).forEach(function(key, index) {
                if (self.isBillingUser()) {
                    self.stepConfig[key].enabled = true;
                } else if (filteredUsers.length > 0 && filteredUsers[0].tasks.includes(self.stepConfig[key].slug)) {
                    self.stepConfig[key].enabled = true;
                } else {
                    self.stepConfig[key].enabled = false;
                }
            });
        },
        aliasesForStep(taskKey) {
            return this.onboardingUsers
                .filter(user => user.tasks && user.tasks.includes(taskKey) && user.alias)
                .map(user => user.alias);
        },
        loadFormState() {
            const self = this;

            api.getContractorOnboarding()
                .then((response) => {
                    if (response.data.id) {
                        self.form.id = response.data.id;
                        self.form.company_name = response.data.company_name;
                        self.form.company_address1 = response.data.company_address1;
                        self.form.company_address2 = response.data.company_address2;
                        self.form.company_city = response.data.company_city;
                        self.form.company_postcode = response.data.company_postcode;
                        self.form.company_registration_number = response.data.company_registration_number;
                        self.form.company_vat_number = response.data.company_vat_number;
                        self.form.developer = + response.data.developer;
                        self.form.framework = + response.data.framework;
                        self.form.main_contractor = + response.data.main_contractor;
                        self.form.sub_contractor = + response.data.sub_contractor;
                        self.form.merchant = + response.data.merchant;
                        self.form.manufacturer = + response.data.manufacturer;
                        self.form.is_sme = + response.data.is_sme;
                        self.form.goods_supply = + response.data.goods_supply;
                        self.form.cas_approved = + response.data.cas_approved;
                        self.form.cas_certifying_body = response.data.cas_certifying_body;

                        self.form.cas_issue_date = response.data.cas_issue_date ? moment(response.data.cas_issue_date, 'YYYY-MM-DD').format('DD-MM-YYYY') : null;
                        self.form.cas_expiry_date = response.data.cas_expiry_date ? moment(response.data.cas_expiry_date, 'YYYY-MM-DD').format('DD-MM-YYYY') : null;

                        self.selectedIssueDate = response.data.cas_issue_date;
                        self.selectedExpiryDate = response.data.cas_expiry_date;

                        response.data.attachments.forEach(attachment => {
                            if (typeof self.files[attachment.description] !== 'undefined') {
                                self.files[attachment.description] = attachment;
                            }
                        });
                    }
                })
                .catch((error) => {
                    console.log(error);
                });
        },
        submitForm() {
            const self = this;

            let formData = new FormData();

            self.buildFormData(formData, self.form);

            api.saveContractorOnboardingForm(formData)
                .then((response) => {
                    self.$store.commit("showSnackbar", {
                        message: "Thankyou, your onboarding info has been saved successfully",
                        color: "success",
                    });

                    self.$router.push({name: 'dashboard'});
                })
                .catch((error) => {
                    self.$store.commit("showSnackbar", {
                        message: "An error occurred",
                        color: "error",
                    });
                    console.log(error);
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
        user() {
            return this.$store.getters.getSession.user;
        },
        isBillingUser() {
            return permissions.hasRole(permissions.role_billing_user);
        },
        goToStep(step) {
            this.currentStep = step;
        },
        getBillingUserId() {
            const self = this;

            if (self.isBillingUser()) {
                return self.user().id;
            } else {
                return self.user().billing_user_id ?? null;
            }
        }
    },
};
</script>

<style>
h5.disabled {
    color: #999;
}

.form-header {
    height: unset;
}

.form-header .v-stepper__step {
    cursor: pointer;
}

.form-btn-group {
    margin-top: 20px;
}

.step-chips {
    display: flex;
    flex-wrap: wrap;
    margin-top: 4px;
}

.step-header {
    text-align: center;
}
</style>
