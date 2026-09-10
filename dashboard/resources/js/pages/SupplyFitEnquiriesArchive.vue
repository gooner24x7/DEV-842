<template>
    <div style="margin: 1em">
        <div v-if="isContractor()" class="mb-2">
            <v-btn :disabled=true class="primary">Work Packages</v-btn>
            <v-btn class="primary" v-on:click="goToProjects()">Projects</v-btn>
        </div>

<!--        <h2>Enquiries > Marketplace Archive</h2>-->

        <v-dialog v-model="dialogDuplicate" max-width="500px">
            <supply-fit-enquiry-duplicate-form
                v-model="editedItem"
                title="Duplicate Enquiry"
                v-on:cancel="closeDuplicateDialog"
                v-on:saved="updatedDuplicatedDialog"
            />
        </v-dialog>

        <v-data-table
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
            <template v-slot:top>
                <v-dialog v-model="dialog" max-width="500px">
                    <question-form
                        v-model="editedItem"
                        title="Edit Question"
                        v-on:cancel="close"
                        v-on:saved="updated"
                    />
                </v-dialog>

                <v-row>
                    <v-col>
                        <v-autocomplete
                            v-model="filters.productIds"
                            :items="products"
                            :loading="filtersLoader"
                            :search-input.sync="searchProduct"
                            chips
                            dense
                            item-text="name"
                            item-value="id"
                            label="Product"
                            multiple
                            outlined
                            small-chips
                        ></v-autocomplete>
                    </v-col>
                    <v-col>
                        <v-autocomplete
                            v-model="filters.projectIds"
                            :items="projects"
                            :loading="filtersLoader"
                            :search-input.sync="searchProject"
                            chips
                            dense
                            item-text="name"
                            item-value="id"
                            label="Project Name"
                            multiple
                            outlined
                            small-chips
                        ></v-autocomplete>
                    </v-col>
                    <v-col>
                        <v-autocomplete
                            v-model="filters.worksPackageIds"
                            :items="worksPackages"
                            :loading="filtersLoader"
                            :search-input.sync="searchWorksPackage"
                            chips
                            dense
                            item-text="name"
                            item-value="id"
                            label="Works Package"
                            multiple
                            outlined
                            small-chips
                        ></v-autocomplete>
                    </v-col>
                </v-row>
            </template>

            <template v-slot:expanded-item="{ headers, item }">
                <td :colspan="headers.length">
                    <pre>{{ item.comment }}</pre>
                </td>
            </template>

            <template v-slot:item.comment="{ item }">
                {{ item.comment | limit20Chars }}
            </template>

            <template v-slot:item.created_at="{ item }">
                {{ item.created_at | formatDate }}
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

                    <v-btn
                        v-if="canEditQuestion(item)"
                        class="mr-2"
                        color="primary"
                        small
                        @click="restoreItem(item)"
                    >
                        Restore Enquiry
                    </v-btn>

                    <v-btn
                        v-if="canEditQuestion(item)"
                        class="mr-2"
                        color="primary"
                        small
                        @click="duplicateItem(item)"
                    >
                        Duplicate Enquiry
                    </v-btn>

                    <v-btn color="primary" small @click="showAnswers(item)">Quotes</v-btn>
                </div>
            </template>
        </v-data-table>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions.js";
import QuestionForm from "../components/QuestionForm";
import SupplyFitEnquiryDuplicateForm from "../components/SupplyFitEnquiryDuplicateForm";

export default {
    name: "SupplyFitEnquiriesArchive.vue",
    components: {
        SupplyFitEnquiryDuplicateForm,
        QuestionForm,
    },
    data() {
        return {
            loader: false,
            filtersLoader: false,
            products: [],
            projects: [],
            worksPackages: [],
            searchProduct: '',
            searchProject: '',
            searchWorksPackage: '',
            filters: {
                productIds: [],
                projectIds: [],
                worksPackageIds: []
            },
            headers: [
                {
                    text: "ID",
                    value: "id",
                    sortable: true,
                },
                {
                    text: "Business Name",
                    value: "first_name",
                    sortable: true,
                },
                {
                    text: "Postcode",
                    value: "postcode",
                    sortable: true,
                },
                {
                    text: "Days",
                    value: "days",
                    sortable: true,
                },
                {
                    text: "Product",
                    value: "name",
                    sortable: true,
                },
                {
                    text: "Project",
                    value: "project_name",
                    sortable: true,
                },
                {
                    text: "Works Package",
                    value: "works_package_name",
                    sortable: true,
                },
                {
                    text: "Date",
                    value: "created_at",
                    sortable: true,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: false,
                },
            ],
            items: [],
            serverItemsLength: 0,
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
            dialog: false,
            dialogDuplicate: false,
            editedIndex: -1,
            editedItem: {
                postcode: "",
                comment: "",
                product_id: "",
                project_id: "",
                works_package_id: "",
                days: "",
                editId: null,
            },
            defaultItem: {
                postcode: "",
                comment: "",
                product_id: "",
                project_id: "",
                works_package_id: "",
                days: "",
                editId: null,
            },
            expanded: [],
            singleExpand: true,
        };
    },

    async mounted() {
        const self = this;

        await self.load();
        await self.loadProductOptions();
        await self.loadProjectOptions();
        await self.loadWorksPackageOptions();
    },

    watch: {
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
        dialog(val) {
            val || this.close();
        }
    },

    methods: {
        goToProjects() {
            this.$router.push({
                name: "archived-projects",
            });
        },

        async load() {
            const self = this;
            self.loader = true;

            let params = {
                ...self.options,
                page: self.options.page,
                itemsPerPage: self.options.itemsPerPage,
                sortBy: self.options.sortBy[0],
                sortDesc: self.options.sortDesc[0] ? 1 : 0,
                archived: 1,
                productIds: self.filters.productIds,
                projectIds: self.filters.projectIds,
                worksPackageIds: self.filters.worksPackageIds
            };

            try {
                const response = await api.loadSupplyFitEnquiries(params);

                self.items = response.data.data;
                self.serverItemsLength = response.data.total;
            } catch (e) {
                console.log(e);
            }
            self.loader = false;
        },

        async loadProductOptions() {
            const self = this;

            if (self.filtersLoader) {
                return;
            }

            self.filtersLoader = true;

            try {
                const resp = await api.productOptions([3]);
                self.products = resp.data;
            } catch (e) {
                console.log(e);
            }

            self.filtersLoader = false;
        },

        async loadProjectOptions(val) {
            const self = this;
            if (self.filtersLoader) return;

            self.filtersLoader = true;

            try {
                const resp = await api.projectOptionsSupplyFit(val);
                self.projects = resp.data;
            } catch (err) {
                console.log(err)
            }

            self.filtersLoader = false;
        },

        async loadWorksPackageOptions(search, projectIds = []) {
            const self = this;

            if (self.filtersLoader) {
                return;
            }

            self.filtersLoader = true;

            try {
                const resp = await api.worksPackageOptionsSupplyFit(search, projectIds)
                self.worksPackages = resp.data;
            } catch (e) {
                console.log(e);
            }

            self.filtersLoader = false;
        },

        editItem(item) {
            this.editedIndex = this.items.indexOf(item);
            this.editedItem = Object.assign({}, item);
            this.dialog = true;
        },

        duplicateItem(item) {
            this.editedIndex = this.items.indexOf(item);
            this.editedItem = Object.assign({}, item);
            this.dialogDuplicate = true;
        },

        async restoreItem(question) {
            const self = this;

            self.loader = true;
            try {
                await api.restoreSupplyFitEnquiry(question.id);
                await self.load();
                self.loader = false;
            } catch (e) {
                console.log(e);
            }
        },

        viewComments: function (item) {
            if (this.expanded.indexOf(item) > -1) {
                this.expanded = [];
                return;
            }

            this.expanded = [item];
        },

        showAnswers(item) {
            this.$router.push({
                name: "supply-fit-quotes",
                params: {enquiry_id: item.id},
            });
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        canAskQuestion() {
            return permissions.can(permissions.create_enquiry_sf);
        },

        canEditQuestion(question) {
            if (permissions.hasRole(permissions.role_admin)) {
                return true;
            }

            return question.user_id === this.user().id;
        },

        updated() {
            const self = this;

            this.dialog = false;
            this.$nextTick(() => {
                this.editedItem = Object.assign({}, this.defaultItem);
                this.editedIndex = -1;
            });

            self.load();
        },

        close() {
            this.dialog = false;
            this.$nextTick(() => {
                this.editedItem = Object.assign({}, this.defaultItem);
                this.editedIndex = -1;
            });
        },

        updatedDuplicatedDialog() {
            const self = this;

            this.dialogDuplicate = false;
            this.$nextTick(() => {
                this.editedItem = Object.assign({}, this.defaultItem);
                this.editedIndex = -1;
            });

            self.load();
        },

        isContractor() {
            return permissions.hasRole(permissions.role_contractor);
        },

        closeDuplicateDialog() {
            this.dialogDuplicate = false;
            this.$nextTick(() => {
                this.editedItem = Object.assign({}, this.defaultItem);
                this.editedIndex = -1;
            });
        },
    },
};
</script>

<style>
</style>
