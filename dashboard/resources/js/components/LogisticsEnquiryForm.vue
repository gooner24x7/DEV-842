<template>
    <v-card>
        <v-card-title>
            <span class="headline">Enquiries > Logistics</span>
        </v-card-title>

        <v-card-text>
            <v-container>
                <v-row>
                    <v-col cols="12" md="6">
                        <v-autocomplete
                            v-model="form.project_id"
                            :items="projects"
                            :search-input.sync="searchProject"
                            item-text="name"
                            item-value="id"
                            label="Project"
                            outlined
                            dense
                        ></v-autocomplete>
                        <v-autocomplete
                            v-model="form.type"
                            :items="typeOptions"
                            item-text="name"
                            item-value="id"
                            label="Type"
                            outlined
                            dense
                        ></v-autocomplete>
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-autocomplete
                            v-model="form.works_package_id"
                            :items="worksPackages"
                            :search-input.sync="searchWorksPackage"
                            item-text="name"
                            item-value="id"
                            label="Works Package"
                            outlined
                            dense
                        ></v-autocomplete>
                        <v-autocomplete
                            v-model="form.enquiry_type"
                            :items="['Logistics']"
                            label="Enquiry Type"
                            outlined
                            dense
                        ></v-autocomplete>
                    </v-col>
                </v-row>

                <v-row>
                    <v-col cols="12" md="6">
                        <v-menu
                            v-model="menu1"
                            :close-on-content-click="false"
                            max-width="290"
                        >
                            <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                    v-model="form.collect_date"
                                    label="Collection Date"
                                    v-bind="attrs"
                                    v-on="on"
                                ></v-text-field>
                            </template>
                            <v-date-picker
                                v-model="selectedCollectDate"
                                no-title
                                @change="menu1 = false"
                            ></v-date-picker>
                        </v-menu>
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-menu
                            v-model="menu2"
                            :close-on-content-click="false"
                            max-width="290"
                        >
                            <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                    v-model="form.delivery_date"
                                    label="Delivery Date"
                                    v-bind="attrs"
                                    v-on="on"
                                ></v-text-field>
                            </template>
                            <v-date-picker
                                v-model="selectedDeliveryDate"
                                no-title
                                @change="menu2 = false"
                            ></v-date-picker>
                        </v-menu>
                    </v-col>
                </v-row>

                <h5 class="mt-3">Collection Address</h5>
                <v-row>
                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.collect_postcode" label="Postcode"></v-text-field>
                        <v-text-field v-model="form.collect_address1" label="Address Line 1"></v-text-field>
                        <v-text-field v-model="form.collect_contact_name" label="Site Contact Name"></v-text-field>
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.collect_city" label="City/Town"></v-text-field>
                        <v-text-field v-model="form.collect_address2" label="Address Line 2"></v-text-field>
                        <v-text-field v-model="form.collect_contact_phone" label="Site Contact Number"></v-text-field>
                    </v-col>
                </v-row>

                <h5 class="mt-3">Delivery Address</h5>
                <v-row>
                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.delivery_postcode" label="Postcode"></v-text-field>
                        <v-text-field v-model="form.delivery_address1" label="Address Line 1"></v-text-field>
                        <v-text-field v-model="form.delivery_contact_name" label="Site Contact Name"></v-text-field>
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.delivery_city" label="City/Town"></v-text-field>
                        <v-text-field v-model="form.delivery_address2" label="Address Line 2"></v-text-field>
                        <v-text-field v-model="form.delivery_contact_phone" label="Site Contact Number"></v-text-field>
                    </v-col>
                </v-row>

                <v-text-field v-model="form.vehicle_type" label="Type of Vehicle Required (if known)"></v-text-field>
                <v-text-field v-model="form.load_details" label="Load Details (no of items, heights/weights of items"></v-text-field>

                <v-textarea
                    v-model="form.comments"
                    clearable
                    clear-icon="mdi-close-circle"
                    label="Comments"
                ></v-textarea>

                <v-textarea
                    v-model="form.notes"
                    clearable
                    clear-icon="mdi-close-circle"
                    label="Notes"
                ></v-textarea>

                <AttachmentsInput
                    v-model="form.attachment"
                    :attachKey="attachKey"
                ></AttachmentsInput>
            </v-container>
        </v-card-text>

        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="blue darken-1" text @click="save($event, 1)">Save Draft</v-btn>
            <v-btn color="blue darken-1" text @click="cancel">Cancel</v-btn>
            <v-btn color="blue darken-1" text @click="save($event, 0)">Publish</v-btn>
        </v-card-actions>

    </v-card>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";
import AttachmentsInput from "./AttachmentsInput.vue";
import moment from "moment/moment";

export default {
    components: {AttachmentsInput},
    props: ["value"],
    data() {
        return {
            editId: null,
            searchProject: '',
            searchWorksPackage: '',
            selectedCollectDate: '',
            selectedDeliveryDate: '',
            menu1: false,
            menu2: false,
            attachKey: 0,
            projects: [],
            worksPackages: [],
            form: {
                project_id: null,
                works_package_id: null,
                type: null,
                attachment: [],
                comments: '',
                notes: '',
                collect_date: '',
                delivery_date: '',
                collect_postcode: '',
                collect_city: '',
                collect_address1: '',
                collect_address2: '',
                collect_contact_name: '',
                collect_contact_phone: '',
                delivery_postcode: '',
                delivery_city: '',
                delivery_address1: '',
                delivery_address2: '',
                delivery_contact_name: '',
                delivery_contact_phone: '',
                vehicle_type: '',
                load_details: '',
                enquiry_type: ''
            },
            errors: {
                postcode: [],
                comment: [],
                product_id: [],
                project_id: [],
                works_package_id: [],
                type: [],
            },
            typeOptions: [
                {id: 0, name: 'Live'},
                {id: 1, name: 'Tender'},
            ]
        };
    },

    watch: {
        value: function (o, n) {
            const self = this;

            self.init();
        },
        selectedCollectDate:  function (n, o) {
            this.form.collect_date = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        },
        selectedDeliveryDate:  function (n, o) {
            this.form.delivery_date = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        },
        'form.project_id': function(n, o) {
            const self = this;

            let projectIds = [];

            if (n !== null) {
                projectIds.push(n);
            }

            self.loadWorksPackageOptions('', projectIds);
        }
    },

    async mounted() {
        const self = this;

        self.loadProjectOptions();
        self.loadWorksPackageOptions();

        self.init();
    },

    methods: {
        init() {
            this.editId = this.value.id;
            this.form.project_id = this.value.project_id;
            this.form.works_package_id = this.value.works_package_id;
            this.form.type = this.value.type;
            this.form.comments = this.value.comments;
            this.form.notes = this.value.notes;
            this.form.collect_date = this.value.collect_date;
            this.form.delivery_date = this.value.delivery_date;
            this.form.vehicle_type = this.value.vehicle_type;
            this.form.load_details = this.value.load_details;
            this.form.attachment = [];
            this.attachKey++;

            this.form.collect_postcode = this.value.contacts ? this.value.contacts[0].postcode : '';
            this.form.collect_city = this.value.contacts ? this.value.contacts[0].city : '';
            this.form.collect_address1 = this.value.contacts ? this.value.contacts[0].address1 : '';
            this.form.collect_address2 = this.value.contacts ? this.value.contacts[0].address2 : '';
            this.form.collect_contact_name = this.value.contacts ? this.value.contacts[0].contact_name : '';
            this.form.collect_contact_phone = this.value.contacts ? this.value.contacts[0].contact_phone : '';
            this.form.delivery_postcode = this.value.contacts ? this.value.contacts[1].postcode : '';
            this.form.delivery_city = this.value.contacts ? this.value.contacts[1].city : '';
            this.form.delivery_address1 = this.value.contacts ? this.value.contacts[1].address1 : '';
            this.form.delivery_address2 = this.value.contacts ? this.value.contacts[1].address2 : '';
            this.form.delivery_contact_name = this.value.contacts ? this.value.contacts[1].contact_name : '';
            this.form.delivery_contact_phone = this.value.contacts ? this.value.contacts[1].contact_phone : '';
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

        async save(event, status = 0) {
            const self = this;

            event.preventDefault();

            if (status === 0) {
                self.$store.commit("showRegFormLoader");
            }

            let formData = new FormData();
            self.buildFormData(formData, self.form, '');
            formData.append('status', status);

            try {
                await api.storeLogisticsEnquiry(formData, this.editId);

                this.form.attachment = [];
                this.attachKey++;

                self.$emit("saved", {});

            } catch (error) {
                self.$store.commit("hideRegFormLoader");

                let data = error.response.data;
                if (typeof data.errors != "undefined") {
                    for (let id in data.errors) {
                        self.errors.id = data.errors?.id;
                    }
                }
            }
        },

        cancel() {
            this.form.attachment = [];
            this.attachKey++;

            this.$emit("cancel", this.value);
        },

        async loadProjectOptions(search = '') {
            const self = this;

            self.filtersLoader = true;

            const resp = await api.getLogisticsProjectOptions(search);

            self.projects = resp.data
            self.filtersLoader = false;
        },

        async loadWorksPackageOptions(search = '', projectIds = []) {
            const self = this;

            self.filtersLoader = true;

            const resp = await api.getLogisticsWorksPackageOptions(search, projectIds);

            self.worksPackages = resp.data;
            self.filtersLoader = false;
        }
    },
};
</script>

<style>
</style>
