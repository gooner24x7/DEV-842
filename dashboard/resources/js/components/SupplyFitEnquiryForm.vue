<template>
    <div>
        <v-card>
            <v-card-title>
                <span class="headline">Enquiries > Marketplace</span>
            </v-card-title>

            <v-card-text>
                <v-container>
                    <v-row>
                        <v-col cols="6">
                            <v-autocomplete
                                v-model="selectedProject"
                                :hide-details="true"
                                :items="projectOptions"
                                :loading="loader"
                                :search-input.sync="searchProject"
                                clear-icon="mdi-close-circle"
                                clearable
                                dense
                                item-text="name"
                                item-value="id"
                                label="Project Name"
                                outlined
                                return-object
                            ></v-autocomplete>
                        </v-col>
                        <v-col>
                            <v-combobox
                                v-model="categoryType"
                                :hide-details="true"
                                :items="categoryTypes"
                                clear-icon="mdi-close-circle"
                                item-text="name"
                                item-value="id"
                                label="Select Enquiry Type"
                                outlined
                                dense
                            ></v-combobox>
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col>
                            <v-btn
                                @click="dialogWorksPackage = true"
                                :disabled="selectedProject === null"
                                class="mb-2"
                            >
                                Select Works Package
                            </v-btn>

                            <div v-if="selectedWorksPackages.length > 0" class="mb-2">
                                <div class="works-packages-table">
                                    <v-data-table
                                        :headers="[{text: 'Name', value: 'name'}, {text: 'Trade', value: 'product'}]"
                                        :items="selectedWorksPackages"
                                        class="data-table-mini"
                                        hide-default-footer
                                        dense
                                    >
                                        <template v-slot:item.name="{ item }">
                                            <div style="font-weight: bold;">{{ item?.name }}</div>
                                            <div v-if="item.assigned_users.length > 0">
                                                <ul style="list-style: none; padding: 0;">
                                                    <li v-for="user in item.assigned_users" :key="user.id">
                                                        <v-icon>mdi-account</v-icon>
                                                        {{ user?.first_name }}
                                                    </li>
                                                </ul>
                                            </div>
                                        </template>
                                        <template v-slot:item.product="{ item }">
                                            {{ item?.product?.name }}
                                        </template>
                                    </v-data-table>
                                </div>
                            </div>
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col>
                            <v-btn
                                @click="showAttachDialog()"
                                :disabled="selectedProject === null"
                                class="mb-2"
                            >
                                Attach Documents
                            </v-btn>

                            <div v-if="documentsList.length > 0" class="mb-2">
                                <div class="documents-table">
                                    <v-data-table
                                        :headers="[{text: 'Name', value: 'name'}, {text: 'Source', value: 'source'}]"
                                        :items="documentsList"
                                        class="data-table-mini"
                                        hide-default-footer
                                        dense
                                    >
                                        <template v-slot:item.name="{ item }">
                                            <div class="d-flex align-center">
                                                <v-icon size="24" :color="getFileTypeColor(item.name)">
                                                    {{ getFileTypeIcon(item.name) }}
                                                </v-icon>
                                                <span class="ml-2">{{ item.name }}</span>
                                            </div>
                                        </template>
                                    </v-data-table>
                                </div>
                            </div>
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col cols="12" md="6">
                            <v-autocomplete
                                v-model="form.product_id"
                                :items="productItems"
                                :disabled="true"
                                item-text="name"
                                item-value="id"
                                label="Trade selection"
                            >
                            </v-autocomplete>
                        </v-col>
                        <v-col cols="12" md="6">
                            <input :value="defaultPostcode" type="hidden"/>
                            <v-text-field
                                v-model="form.postcode"
                                :error-messages="errors.postcode"
                                :rules="[
                                    () => !!form.postcode || 'This field is required',
                                    (v) =>
                                      /^([A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}|GIR ?0A{2})$/i.test(v) ||
                                      'Must be a vaild UK postcode',
                                  ]"
                                label="Site Postcode"
                                placeholder="M60 1NW"
                                required
                            ></v-text-field>
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col cols="12" md="6">
                            <v-autocomplete
                                :items="['live', 'tender', 'pre tender']"
                                v-model="form.type"
                                label="Live/Tender"
                                :rules="[() => !!form.type || 'This field is required']"
                                :error-messages="errors.type"
                            ></v-autocomplete>
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-autocomplete
                                v-model="form.scope"
                                :items="scopeOptions"
                                label="Open/Closed"
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
                                        v-model="form.days"
                                        :error-messages="errors.days"
                                        :rules="[() => !!form.days || 'This field is required']"
                                        label="Assumed Start Date"
                                        type="text"
                                        v-bind="attrs"
                                        v-on="on"
                                    ></v-text-field>
                                </template>
                                <v-date-picker
                                    v-model="selectedDate"
                                    no-title
                                    @change="menu1 = false"
                                ></v-date-picker>
                            </v-menu>
                        </v-col>
                        <v-col cols=" 12" md="6">
                            <v-menu
                                v-model="menu2"
                                :close-on-content-click="false"
                                max-width="290"
                            >
                                <template v-slot:activator="{ on, attrs }">
                                    <v-text-field
                                        v-model="form.assumed_end_date"
                                        label="Assumed End Date"
                                        type="text"
                                        v-bind="attrs"
                                        v-on="on"
                                    ></v-text-field>
                                </template>
                                <v-date-picker
                                    v-model="selectedDate2"
                                    no-title
                                    @change="menu2 = false"
                                ></v-date-picker>
                            </v-menu>
                        </v-col>
                    </v-row>

                    <v-textarea
                        v-model="form.comment"
                        clear-icon="mdi-close-circle"
                        clearable
                        label="Comments"
                    ></v-textarea>
                </v-container>
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="blue darken-1" text @click="cancel">Cancel</v-btn>
                <v-btn color="blue darken-1" text @click="save($event, 2)">Save Draft</v-btn>
                <v-btn color="blue darken-1" text @click="save($event, 1)">Publish</v-btn>
            </v-card-actions>
        </v-card>

        <v-dialog v-model="dialogWorksPackage" max-width="600px">
            <works-package-selector
                :project-id="selectedProject ? selectedProject.id : null"
                :products="productItems"
                @save="selectWorksPackages"
                @close="dialogWorksPackage = false"
            ></works-package-selector>
        </v-dialog>

        <v-dialog v-model="dialogAttach" max-width="600px">
            <attach-documents
                :project="selectedProject"
                @save="saveAttachDialog"
                @close="dialogAttach = false"
            />
        </v-dialog>

        <v-overlay :value="saving" z-index="9999">
            <div class="text-center">
                <v-progress-circular indeterminate size="64"></v-progress-circular>
                <h3 class="mt-3">Creating enquiries</h3>
            </div>
        </v-overlay>
    </div>
</template>

<script>
import api from "../common/api.js";
import utils from "../common/utils.js";
import moment from "moment/moment";
import AttachmentsInput from "./AttachmentsInput.vue";
import WorksPackageSelector from "./WorksPackageSelector.vue";
import AttachDocuments from "./AttachDocuments.vue";

export default {
    components: {AttachDocuments, WorksPackageSelector, AttachmentsInput},
    props: ["value"],
    data() {
        return {
            defaultPostcode: '',
            selectedDate: '',
            selectedDate2: '',
            menu1: false,
            menu2: false,
            attachment: [],
            loader: false,
            saving: false,
            selectedProject: null,
            selectedWorksPackages: [],
            searchProject: '',
            dialogWorksPackage: false,
            dialogAttach: false,
            dialogAttachItems: {
                project: [],
                user: []
            },
            documentsList: [],
            projectOptions: [],
            worksPackageOptions: [],
            categoryType: null,
            categoryTypes: [
                {id: 1, name: 'Marketplace'}
            ],
            productItems: [],
            successCreated: false,
            editId: null,
            form: {
                id: null,
                type: "",
                postcode: "",
                comment: "",
                product_id: null,
                project_id: null,
                days: "",
                assumed_end_date: "",
                scope: 1,
                status: 1,
                attachment: [],
                project_attachment: [],
            },
            errors: {
                type: [],
                postcode: [],
                comment: [],
                days: [],
            },
            scopeOptions: [
                {text: 'open', value: 1},
                {text: 'closed', value: 2},
                {text: 'selected users', value: 3},
            ]
        };
    },

    watch: {
        value: function (o, n) {
            this.init();
        },

        selectedProject(project) {
            const self = this;

            if (project !== null) {
                self.defaultPostcode = project.postcode;
                self.form.type = project.stage_str ? project.stage_str.toLowerCase() : '';
                self.selectedDate = project.date_start ?? '';
                self.selectedDate2 = project.date_end ?? '';
            }

            if (project && project.id === self.form.project_id) {
                return;
            }

            self.form.project_id = project ? project.id : null;
            self.selectedWorksPackages = [];
        },

        defaultPostcode: function (n, o) {
            this.form.postcode = n
        },

        selectedDate: function (n, o) {
            this.form.days = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        },

        selectedDate2: function (n, o) {
            this.form.assumed_end_date = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        }
    },

    async mounted() {
        const self = this;

        await self.loadProductOptions();
        await self.loadProjectOptions();
        await self.loadWorksPackageOptions();

        self.init();
    },

    methods: {
        ...utils,

        init() {
            const self = this;

            self.form.id = self.value.id;
            self.form.project_id = self.value.project_id;
            self.form.product_id = self.value.product_id;
            self.form.postcode = self.value.postcode;
            self.form.comment = self.value.comment;
            self.form.days = self.value.days;
            self.form.assumed_end_date = self.value.assumed_end_date;
            self.form.type = self.value.type;
            self.form.status = self.value.status;
            self.form.scope = self.value.scope;

            self.categoryType = self.categoryTypes[0];

            if (self.value.project_id) {
                self.selectedProject = self.projectOptions.find(project => project.id === self.value.project_id);
            } else {
                self.selectedProject = null;
            }

            if (self.value.works_package_id) {
                let wps = self.worksPackageOptions.filter(wp => wp.id === self.value.works_package_id);
                let product = self.productItems.find(p => p.id === self.form.product_id);

                wps[0].product = {
                    id: self.form.product_id,
                    name: product?.name
                };

                self.selectedWorksPackages = wps;
            } else {
                self.selectedWorksPackages = [];
            }
        },

        async loadProductOptions(typeIds = [3]) {
            const self = this;

            const resp = await api.productOptions(typeIds);
            self.productItems = resp.data;
        },

        async loadProjectOptions(search = '') {
            const self = this;

            self.loader = true;

            try {
                const resp = await api.getProjectOptions({search: search, grouped: 1});
                self.projectOptions = resp.data;
            } catch (err) {
                console.log(err);
            }

            self.loader = false;
        },

        async loadWorksPackageOptions(search = '') {
            const self = this;

            try {
                const resp = await api.getWorksPackageOptions({search: search});
                self.worksPackageOptions = resp.data;
            } catch (err) {
                console.log(err);
            }
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

        async save(event, status = 1) {
            const self = this;

            event.preventDefault();

            self.form.postcode = self.form.postcode.toUpperCase();
            self.form.status = status;
            self.form.attachment = self.dialogAttachItems.user;
            self.form.project_attachment = self.dialogAttachItems.project;

            const formData = new FormData();
            self.buildFormData(formData, self.form);

            formData.append('works_packages', JSON.stringify(self.selectedWorksPackages));

            if (self.form.id) {
                await self.updateEnquiry(formData);
            } else {
                await self.storeEnquiry(formData);
            }
        },

        async storeEnquiry(formData) {
            const self = this;

            self.saving = true;

            try {
                await api.storeSupplyFitEnquiry(formData);

                self.$store.commit("showSnackbar", {
                    message: "Enquiry created",
                    color: "success",
                });

                self.$emit("saved");
            } catch (error) {
                self.$store.commit("showSnackbar", {
                    message: "An error occurred",
                    color: "error",
                });

                console.log(error);
            } finally {
                self.saving = false;
            }
        },

        async updateEnquiry(formData) {
            const self = this;

            try {
                await api.updateSupplyFitEnquiry(self.form.id, formData);

                self.$store.commit("showSnackbar", {
                    message: "Enquiry updated",
                    color: "success",
                });

                self.$emit("saved");
            } catch (error) {
                self.$store.commit("showSnackbar", {
                    message: "An error occurred",
                    color: "error",
                });

                console.log(error);
            }
        },

        selectWorksPackages(items) {
            const self = this;
            let scope = 1;

            self.selectedWorksPackages = items;

            if (items.length === 0) {
                self.dialogWorksPackage = false;
                return;
            }

            items.forEach(item => {
                if (item.assigned_users.length > 0) {
                    scope = 3;
                }
            });

            self.form.scope = scope;
            self.dialogWorksPackage = false;
        },

        cancel() {
            this.$emit("cancel");
        },

        showAttachDialog() {
            const self = this;

            self.dialogAttach = true;
        },

        saveAttachDialog(data) {
            const self = this;

            self.dialogAttachItems = data;
            self.documentsList = [];

            self.dialogAttachItems.project.forEach((item) => {
               self.documentsList.push({
                   name: item.name,
                   source: 'Project Files'
               });
            });

            self.dialogAttachItems.user.forEach((item) => {
               self.documentsList.push({
                   name: item.name,
                   source: 'From Computer'
               });
            });

            self.dialogAttach = false;
        }
    },
};
</script>

<style>

</style>
