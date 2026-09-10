<template>
    <v-card>
        <v-card-title>
            <span class="headline">{{ dialogTitle }}</span>
        </v-card-title>

        <v-card-text>

            <v-tabs
                v-show="showTabs()"
                v-model="currentTab"
                background-color="transparent"
                grow
            >
                <v-tab href="#details">
                    Project Details
                </v-tab>
                <v-tab href="#general">
                    General
                </v-tab>
                <v-tab href="#user-access">
                    Assign Users
                </v-tab>
            </v-tabs>

            <v-tabs-items v-model="currentTab">
                <v-tab-item value="details">
                    <v-container>
                        <v-row>
                            <v-col>
                                <v-text-field
                                    v-model="form.sector"
                                    label="Sector"
                                    :disabled="disableInputs"
                                ></v-text-field>
                                <v-text-field
                                    v-model="form.region"
                                    label="Region"
                                    :disabled="disableInputs"
                                ></v-text-field>
                                <v-text-field
                                    v-model="form.frame_type"
                                    label="Frame Type"
                                    :disabled="disableInputs"
                                ></v-text-field>
                                <v-text-field
                                    v-model="form.procurement_route"
                                    label="Procurement Route"
                                    :disabled="disableInputs"
                                ></v-text-field>
                                <v-text-field
                                    v-model="form.boq_standard"
                                    label="BoQ Standard"
                                    :disabled="disableInputs"
                                ></v-text-field>
                                <v-autocomplete
                                    v-model="form.stage"
                                    :items="stageOptions"
                                    label="Project Stage"
                                    :disabled="projectId !== null"
                                ></v-autocomplete>
<!--                                <v-autocomplete-->
<!--                                    v-model="form.status"-->
<!--                                    :items="statusOptions"-->
<!--                                    label="Project Status"-->
<!--                                ></v-autocomplete>-->
                                <v-autocomplete
                                    v-model="form.level"
                                    :items="levelOptions"
                                    label="Data Confidence Level"
                                ></v-autocomplete>
                            </v-col>
                            <v-col>
                                <v-text-field
                                    v-model="form.project_value"
                                    label="Project Value"
                                    type="number"
                                    prefix="£"
                                    :disabled="disableInputs"
                                ></v-text-field>
                                <v-text-field
                                    v-model="form.area_sqm"
                                    label="Gross Internal Area"
                                    type="number"
                                    suffix="m2"
                                    :disabled="disableInputs"
                                ></v-text-field>
                                <v-text-field
                                    v-model="form.perc_services"
                                    label="Services Percentage"
                                    type="number"
                                    suffix="%"
                                    :disabled="disableInputs"
                                ></v-text-field>
                                <v-text-field
                                    v-model="form.perc_prelim"
                                    label="Preliminaries Percentage"
                                    type="number"
                                    suffix="%"
                                    :disabled="disableInputs"
                                ></v-text-field>
                                <v-text-field
                                    v-model="form.perc_labour"
                                    label="Labour Percentage"
                                    type="number"
                                    suffix="%"
                                    :disabled="disableInputs"
                                ></v-text-field>
                                <v-text-field
                                    v-model="form.perc_materials"
                                    label="Materials Percentage"
                                    type="number"
                                    suffix="%"
                                    :disabled="disableInputs"
                                ></v-text-field>
                            </v-col>
                        </v-row>

                        <h5 class="mt-2">Calculated Values</h5>
                        <v-row>
                            <v-col>
                                <v-text-field
                                    v-model="projectData.total_packages"
                                    label="Total Package Count"
                                    type="number"
                                    :disabled="true"
                                ></v-text-field>
                                <v-text-field
                                    v-model="projectData.total_quotes"
                                    label="Total Supplier Quotes"
                                    type="number"
                                    :disabled="true"
                                ></v-text-field>
                                <v-text-field
                                    v-model="projectData.min_quote_value"
                                    label="Lowest Tender Value"
                                    type="number"
                                    prefix="£"
                                    :disabled="true"
                                ></v-text-field>
                                <v-text-field
                                    v-model="projectData.awarded_value"
                                    label="Awarded Value"
                                    type="number"
                                    prefix="£"
                                    :disabled="true"
                                ></v-text-field>
                                <v-text-field
                                    v-model="projectData.cost_per_sqm"
                                    label="Benchmark Cost per m2"
                                    type="number"
                                    prefix="£"
                                    :disabled="true"
                                ></v-text-field>
                            </v-col>
                            <v-col>
                                <v-text-field
                                    v-model="projectData.created_by"
                                    label="Created By"
                                    :disabled="true"
                                ></v-text-field>
                                <v-text-field
                                    v-model="projectData.created_at"
                                    label="Created At"
                                    :disabled="true"
                                ></v-text-field>
                                <v-text-field
                                    v-model="projectData.updated_at"
                                    label="Updated At"
                                    :disabled="true"
                                ></v-text-field>
                            </v-col>
                        </v-row>
                    </v-container>
                </v-tab-item>
                <v-tab-item value="general">
                    <v-container>
                        <v-row>
                            <v-col>
                                <v-text-field
                                    v-model="form.name"
                                    :error="errors.name"
                                    label="Project Name"
                                    :disabled="disableInputs"
                                ></v-text-field>

                                <v-text-field
                                    v-model="form.client_name"
                                    label="Client name"
                                    :disabled="disableInputs"
                                ></v-text-field>

                                <v-text-field
                                    v-model="form.framework"
                                    label="Framework"
                                    :disabled="disableInputs"
                                ></v-text-field>

                                <v-text-field
                                    v-model="form.target_hours_ap"
                                    label="Apprenticeship Target Hours"
                                    type="number"
                                    :disabled="disableInputs"
                                ></v-text-field>

                                <v-menu
                                    v-model="menu1"
                                    :close-on-content-click="false"
                                    max-width="290"
                                >
                                    <template v-slot:activator="{ on, attrs }">
                                        <v-text-field
                                            v-model="form.date_start"
                                            label="Start Date"
                                            type="text"
                                            v-bind="attrs"
                                            v-on="on"
                                            :disabled="disableInputs"
                                        ></v-text-field>
                                    </template>
                                    <v-date-picker
                                        v-model="selectedStartDate"
                                        no-title
                                        @change="menu1 = false"
                                    ></v-date-picker>
                                </v-menu>

                                <v-menu
                                    v-model="menu2"
                                    :close-on-content-click="false"
                                    max-width="290"
                                >
                                    <template v-slot:activator="{ on, attrs }">
                                        <v-text-field
                                            v-model="form.date_end"
                                            label="End Date"
                                            type="text"
                                            v-bind="attrs"
                                            v-on="on"
                                            :disabled="disableInputs"
                                        ></v-text-field>
                                    </template>
                                    <v-date-picker
                                        v-model="selectedEndDate"
                                        no-title
                                        @change="menu2 = false"
                                    ></v-date-picker>
                                </v-menu>

                                <v-menu
                                    v-model="menu3"
                                    :close-on-content-click="false"
                                    max-width="290"
                                >
                                    <template v-slot:activator="{ on, attrs }">
                                        <v-text-field
                                            v-model="form.date_end_tender"
                                            label="Tender End Date"
                                            type="text"
                                            v-bind="attrs"
                                            v-on="on"
                                            :disabled="disableInputs"
                                        ></v-text-field>
                                    </template>
                                    <v-date-picker
                                        v-model="selectedEndDateTender"
                                        no-title
                                        @change="menu3 = false"
                                    ></v-date-picker>
                                </v-menu>

                                <v-text-field
                                    v-model="form.contractor_region"
                                    label="Main Contractor Region"
                                    :disabled="disableInputs"
                                ></v-text-field>

                                <v-text-field
                                    v-model="form.ocid"
                                    :error="errors.ocid"
                                    label="OCID"
                                    :disabled="disableInputs"
                                ></v-text-field>
                            </v-col>
                            <v-col>
                                <v-text-field
                                    v-model="form.postcode"
                                    label="Project Postcode"
                                    :disabled="disableInputs"
                                ></v-text-field>

                                <v-text-field
                                    v-model="form.postcode_districts"
                                    label="Postcode Districts"
                                    :disabled="disableInputs"
                                ></v-text-field>

                                <v-text-field
                                    v-model="form.target_miles_client"
                                    label="Client Target Miles"
                                    type="number"
                                    :disabled="disableInputs"
                                ></v-text-field>

                                <v-text-field
                                    v-model="form.target_miles_framework"
                                    label="Framework Target Miles"
                                    type="number"
                                    :disabled="disableInputs"
                                ></v-text-field>

                                <v-text-field
                                    v-model="form.target_hours_se"
                                    label="Social Enterprise Target"
                                    type="number"
                                    :disabled="disableInputs"
                                ></v-text-field>

                                <v-text-field
                                    v-model="form.budget_se"
                                    label="Social Enterprise Budget"
                                    type="number"
                                    prefix="£"
                                    :disabled="disableInputs"
                                ></v-text-field>

                                <v-text-field
                                    v-model="projectData.contractor_name"
                                    label="Contractor Name"
                                    type="text"
                                    :disabled="true"
                                ></v-text-field>

                                <v-text-field
                                    v-model="projectData.consultant_name"
                                    label="Consultant Name"
                                    type="text"
                                    :disabled="true"
                                ></v-text-field>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col>
                                <v-textarea
                                    v-model="form.description"
                                    label="Description"
                                ></v-textarea>
                            </v-col>
                        </v-row>
                    </v-container>
                </v-tab-item>
                <v-tab-item value="user-access">
                    <user-access-table :projectId="projectId" @assigned="userAssigned()"></user-access-table>
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
import moment from "moment/moment";
import UserAccessTable from "./UserAccessTable.vue";

export default {
    components: {UserAccessTable},
    props: ['project', 'tab', 'type'],
    data() {
        return {
            menu1: false,
            menu2: false,
            menu3: false,
            selectedStartDate: '',
            selectedEndDate: '',
            selectedEndDateTender: '',
            currentTab: '',
            dialogTitle: 'New Project',
            projectId: null,
            isLoading: false,
            disableInputs: false,
            projectData: {
                consultant_name: null,
                contractor_name: null,
                created_by: null,
                created_at: null,
                updated_at: null,
                total_packages: null,
                total_quotes: null,
                min_quote_value: null,
                awarded_value: null,
                cost_per_sqm: null,
            },
            form: {
                stage: null,
                status: null,
                level: null,
                type: null,
                name: null,
                ocid: null,
                postcode: null,
                postcode_districts: null,
                sector: null,
                region: null,
                contractor_region: null,
                client_name: null,
                framework: null,
                frame_type: null,
                procurement_route: null,
                boq_standard: null,
                project_value: null,
                area_sqm: null,
                target_miles_client: null,
                target_miles_framework: null,
                target_hours_ap: null,
                target_hours_se: null,
                budget_se: null,
                perc_services: null,
                perc_prelim: null,
                perc_labour: null,
                perc_materials: null,
                date_start: null,
                date_end: null,
                date_end_tender: null,
                description: null,
            },
            stageOptions: [
                { value: 1, text: 'Pre Tender' },
                { value: 2, text: 'Tender' },
                { value: 3, text: 'Live' },
            ],
            statusOptions: [
                { value: 1, text: 'Published' },
                { value: 2, text: 'Draft' },
            ],
            levelOptions: [
                { value: 1, text: 'Bronze' },
                { value: 2, text: 'Silver' },
                { value: 3, text: 'Gold' },
            ],
            errors: {
                name: false,
                ocid: false,
            }
        };
    },

    async mounted() {
        const self = this;

        self.init();
    },

    // computed: {
    //     currentTab: {
    //         get() {
    //             console.log('getter');
    //             console.log(this.tab);
    //             return (this.tab === '') ? 'general' : this.tab;
    //         },
    //         set(val) {
    //             console.log('setter');
    //             console.log(val);
    //             return val;
    //         }
    //     }
    // },

    watch: {
        project: function(n, o) {
            this.init();
        },
        type: function(n, o) {
            console.log(n);
            this.form.type = n;
        },
        tab: function(n, o) {
            this.init();
        },
        selectedStartDate: function (n, o) {
            this.form.date_start = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        },
        selectedEndDate: function (n, o) {
            this.form.date_end = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        },
        selectedEndDateTender: function (n, o) {
            this.form.date_end_tender = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        },
    },

    methods: {
        init() {
            const self = this;

            self.currentTab = (this.tab === '') ? 'general' : this.tab;

            if (this.project !== null) {
                self.dialogTitle = 'Edit Project (ID: ' + this.project.id + ')';

                self.projectId = this.project.id;
                self.form.type = this.project.type;
                self.form.stage = this.project.stage;
                self.form.status = this.project.status;
                self.form.level = this.project.level;
                self.form.name = this.project.name;
                self.form.ocid = this.project.ocid;
                self.form.postcode = this.project.postcode;
                self.form.postcode_districts = this.project.postcode_districts;
                self.form.client_name = this.project.client_name;
                self.form.framework = this.project.framework;
                self.form.sector = this.project.sector;
                self.form.region = this.project.region;
                self.form.contractor_region = this.project.contractor_region;
                self.form.frame_type = this.project.frame_type;
                self.form.procurement_route = this.project.procurement_route;
                self.form.boq_standard = this.project.boq_standard;
                self.form.project_value = this.project.project_value;
                self.form.area_sqm = this.project.area_sqm;
                self.form.target_miles_client = this.project.target_miles_client;
                self.form.target_miles_framework = this.project.target_miles_framework;
                self.form.target_hours_ap = this.project.target_hours_ap;
                self.form.target_hours_se = this.project.target_hours_se;
                self.form.budget_se = this.project.budget_se;
                self.form.perc_services = this.project.perc_services;
                self.form.perc_prelim = this.project.perc_prelim;
                self.form.perc_labour = this.project.perc_labour;
                self.form.perc_materials = this.project.perc_materials;
                self.form.date_start = this.project.date_start ? moment(this.project.date_start, 'YYYY-MM-DD').format('DD-MM-YYYY') : null;
                self.form.date_end = this.project.date_end ? moment(this.project.date_end, 'YYYY-MM-DD').format('DD-MM-YYYY') : null;
                self.form.date_end_tender = this.project.date_end_tender ? moment(this.project.date_end_tender, 'YYYY-MM-DD').format('DD-MM-YYYY') : null;
                self.form.description = this.project.description;

                self.projectData.created_by = this.project.created_by;
                self.projectData.created_at = moment(this.project.created_at).format('DD-MM-YYYY HH:mm');
                self.projectData.updated_at = moment(this.project.updated_at).format('DD-MM-YYYY HH:mm');
                self.projectData.contractor_name = this.project.contractor_name ?? null;
                self.projectData.consultant_name = this.project.consultant_name ?? null;
                self.projectData.total_packages = this.project.total_packages ?? null;
                self.projectData.total_quotes = this.project.total_quotes ?? null;
                self.projectData.min_quote_value = this.project.min_quote_value.toFixed(2) ?? null;
                self.projectData.awarded_value = this.project.awarded_value.toFixed(2) ?? null;
                self.projectData.cost_per_sqm = this.project.cost_per_sqm.toFixed(2) ?? null;

                // disable inputs for consultant stage 3
                if (this.project?.assigned_users.length) {
                    this.project.assigned_users.forEach((item) => {
                        if (item.user_id === this.user().id && item.stage === 3) {
                            self.disableInputs = true;
                        }
                    });
                }
            } else {
                self.dialogTitle = 'New Project'

                for (const [key, value] of Object.entries(self.form)) {
                    self.form[key] = null;
                }

                for (const [key, value] of Object.entries(self.projectData)) {
                    self.projectData[key] = null;
                }

                self.projectId = null;
                self.disableInputs = false;
                self.form.type = self.type ?? null;

                self.fillInputs();
            }
        },

        cancel() {
            this.$emit("cancel");
        },

        userAssigned() {
            this.$emit("saved");
        },

        async save(event) {
            const self = this;

            event.preventDefault();

            self.errors.name = !this.form.name;
            self.errors.ocid = (this.form.type === 2) && !this.form.ocid;

            let projectId = (this.project !== null) ? this.project.id : null;

            let params = self.form;
            params.id = projectId;

            try {
                const response = await api.storeProject(params);

                self.$emit("saved");

                self.$store.commit("showSnackbar", {
                    message: "Success!",
                    color: "success",
                });
            } catch (error) {
                console.log(error);

                self.$store.commit("showSnackbar", {
                    message: error.response?.data ?? "An error occurred",
                    color: "error",
                });
            }
        },

        fillInputs() {
            const self = this;

            if (self.isFrameworkUser()) {
                self.framework = self.user().first_name;
            }

            if (self.isClientUser()) {
                self.client = self.user().first_name;
            }
        },

        showTabs() {
            return permissions.can(permissions.project_assign_users);
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        isFrameworkUser() {
            return permissions.hasRole(permissions.role_framework);
        },

        isClientUser() {
            return permissions.hasRole(permissions.role_client);
        }
    },
};
</script>

<style>
</style>
