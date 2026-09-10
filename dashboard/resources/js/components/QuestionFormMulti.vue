<template>
    <v-card>
        <v-card-title>
            <span class="headline">Enquiries > Purchase & Hire</span>
        </v-card-title>

        <v-card-text>
            <v-container>
                <v-row>
                    <v-col>
                        <v-autocomplete
                            v-model="selectedProject"
                            :hide-details="true"
                            :items="projects"
                            :loading="loader"
                            :search-input.sync="searchProject"
                            clear-icon="mdi-close-circle"
                            clearable
                            dense
                            item-text="name"
                            item-value="id"
                            label="Project"
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
                            dense
                            item-text="name"
                            item-value="id"
                            label="Select Enquiry Type"
                            outlined
                        ></v-combobox>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col cols="6">
                        <v-combobox
                            :key="worksPackageKey"
                            v-model="selectedWorksPackage"
                            :hide-details="true"
                            :items="worksPackages"
                            :loading="loader"
                            :search-input.sync="searchWorksPackage"
                            clear-icon="mdi-close-circle"
                            clearable
                            dense
                            item-text="name"
                            item-value="id"
                            label="Works Package"
                            outlined
                        ></v-combobox>
                    </v-col>
                    <v-col cols="6" v-if="showHouseNameInput()">
                        <v-combobox
                            v-model="selectedHouseName"
                            :hide-details="true"
                            :items="houseNames"
                            :loading="loader"
                            :search-input.sync="searchHouseName"
                            clear-icon="mdi-close-circle"
                            clearable
                            dense
                            label="House Name"
                            outlined
                        ></v-combobox>
                    </v-col>
                </v-row>

                <question-form-multi-row
                    v-for="(item, index) in items"
                    :key="index"
                    v-model="item.question"
                    :defaultPostcode="defaultPostcode"
                    :index="index"
                    :nationals-options="nationalsOptions"
                    :product-items="productItems"
                    :type="categoryType ? categoryType.id : ''"
                    :showProducts="selectedHouseName === null"
                    class="mb-2"
                    v-on:remove="removeRow"
                ></question-form-multi-row>

            </v-container>
        </v-card-text>

        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="blue darken-1" text @click="save($event, 1)">Save Draft</v-btn>
            <v-btn v-if="selectedHouseName === null" color="blue darken-1" text @click="oneMore">Add More Products</v-btn>
            <v-btn color="blue darken-1" text @click="cancel">Cancel</v-btn>
            <v-btn color="blue darken-1" text @click="save($event, 0)">Publish</v-btn>
        </v-card-actions>

        <v-col v-if="successCreated" cols="12">
            <v-alert type="success">Enquiry submitted</v-alert>
        </v-col>
    </v-card>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";
import QuestionFormMultiRow from "./QuestionFormMultiRow";

export default {
    components: {
        QuestionFormMultiRow
    },
    props: ['visible'],
    data() {
        return {
            worksPackageKey: 1,
            loader: false,
            defaultPostcode: '',
            productItems: [],
            projects: [],
            worksPackages: [],
            houseNames: [],
            selectedProject: null,
            selectedWorksPackage: null,
            selectedHouseName: null,
            searchProject: '',
            searchWorksPackage: '',
            searchHouseName: '',
            nationalsOptions: [],
            successCreated: false,
            categoryType: '',
            categoryTypes: [
                {
                    id: 1,
                    name: 'Hire Enquiry',
                },
                {
                    id: 2,
                    name: 'Purchase Enquiry',
                }
            ],

            items: [
                {
                    question: {
                        attachment: [],
                        nationals: [
                            {
                                email: '',
                                supplier_id: 0,
                                contact_name: '',
                                account_number: '',
                            }
                        ],
                        manufacturer_product_selected: {},
                    }
                }
            ],
        };
    },
    watch: {
        'visible': function () {
            if (this.visible) {
                this.items = [
                    {
                        question: {
                            postcode: this.defaultPostcode,
                            manufacturer_product_selected: {},
                            attachment: [],
                        }
                    }
                ];
            }
        },
        selectedProject: {
            async handler(n, o) {
                const self = this;
                let projectIds = [];

                if (n !== null) {
                    projectIds.push(n.id);
                }

                await self.loadWorksPackageOptions(self.searchWorksPackage, projectIds);
            },
            deep: true,
        },
        selectedWorksPackage: {
            handler(n, o) {
                const self = this;

                self.defaultPostcode = n?.postcode ?? '';
            },
            deep: true,
        },
        categoryType(val) {
            const self = this;
            self.selectByType(val);
        }
    },
    async mounted() {
        const self = this;

        await self.loadWorksPackageOptions();
        await self.loadProjectOptions();
        await self.loadNationalsOptions();
        await self.loadHouseNameOptions();
    },

    methods: {

        async loadNationalsOptions() {
            const self = this;
            const resp = await api.nationalsOptions();
            self.nationalsOptions = resp.data;
        },

        async loadProjectOptions(val = '') {
            const self = this;

            if (self.loader) {
                return;
            }

            self.loader = true;

            try {
                const resp = await api.projectOptions(val);
                self.projects = resp.data;
            } catch (err) {
                console.log(err);
            }

            self.loader = false;
        },

        async loadWorksPackageOptions(search = '', projectIds = []) {
            const self = this;

            if (self.loader) {
                return;
            }

            self.loader = true;

            try {
                const resp = await api.worksPackageOptions(search, projectIds);
                self.worksPackages = resp.data;
            } catch (err) {
                console.log(err);
            }

            self.loader = false;
        },

        async loadHouseNameOptions(search = '') {
            const self = this;

            if (self.loader) {
                return;
            }

            self.loader = true;

            try {
                const resp = await api.getHouseNames(search);
                self.houseNames = resp.data;
            } catch (err) {
                console.log(err);
            }

            self.loader = false;
        },

        async selectByType(type) {
            const self = this;
            const resp = await api.productOptions([type.id]);

            self.productItems = resp.data;
        },

        removeRow(key) {
            this.items.splice(key, 1);
        },

        oneMore() {
            this.items = [...this.items, {
                question: {
                    attachment: [],
                    manufacturer_product_selected: {},
                    postcode: this.defaultPostcode
                }
            }];
        },

        cancel() {
            this.$emit("cancel", this.value);
        },

        isUser() {
            return permissions.hasRole(permissions.role_user);
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        showHouseNameInput() {
            return permissions.hasRole(permissions.role_admin) || permissions.hasRole(permissions.role_keepmoat);
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

            this.successCreated = false;

            if (self.isUser() && status === 0) {
                self.$store.commit("showRegFormLoader");
            }

            let submitItems = self.items.map((item) => {
                if (typeof item.question.postcode != 'undefined') {
                    item.question.postcode = item.question.postcode.toUpperCase();
                }
                return item.question;
            });

            let formData = new FormData();

            let projectId = self.selectedProject != null ? self.selectedProject.id : 0;
            let worksPackageId = self.selectedWorksPackage != null ? self.selectedWorksPackage.id : 0;

            formData.append('project_id', projectId);
            formData.append('works_package_id', worksPackageId);
            formData.append('house_name', self.selectedHouseName);
            formData.append('status', status);

            if (!worksPackageId && self.searchWorksPackage !== '' && self.searchWorksPackage !== null) {
                formData.append('works_package_name', self.searchWorksPackage);
            }

            if (self.showHouseNameInput() && self.selectedHouseName) {
                formData.append('postcode', self.items[0].question.postcode ?? '');
                formData.append('days', self.items[0].question.days ?? '');
                formData.append('type', self.items[0].question.type ?? '');
                formData.append('comment', self.items[0].question.comment ?? '');
                if (self.items[0].question.attachment[0]) {
                    formData.append('attachment[]', self.items[0].question.attachment[0]);
                }
            } else {
                self.buildFormData(formData, submitItems, 'items');
            }

            try {
                if (self.showHouseNameInput() && self.selectedHouseName) {
                    await api.storeQuestionsByHouseName(formData);
                } else {
                    await api.storeQuestions(formData);
                }

                self.successCreated = true;

                setTimeout(() => {
                    self.$emit("saved", {items: self.items});
                }, 1000);
            } catch (error) {
                console.log(error);
                self.$store.commit("hideRegFormLoader");
            }
        },
    },
};
</script>

<style>
</style>
