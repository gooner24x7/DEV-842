<template>
    <div style="margin: 1em">
<!--        <h2>Enquiries > Logistics Archive</h2>-->

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
                    <logistics-enquiry-form
                        v-model="editedItem"
                        title="Edit Enquiry"
                        v-on:cancel="close"
                        v-on:saved="updated"
                    />
                </v-dialog>
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
                                v-bind="attrs"
                                v-on="on"
                                class="mr-2"
                                color="black"
                                icon
                                small
                                @click="viewComments(item)"
                            >
                                <v-icon>
                                    mdi-format-list-bulleted-square
                                </v-icon>
                            </v-btn>
                        </template>
                        <span>View Comments</span>
                    </v-tooltip>

                    <v-btn
                        v-if="canEditEnquiry(item)"
                        class="mr-2"
                        color="primary"
                        small
                        @click="restoreItem(item)"
                    >
                        Restore Enquiry
                    </v-btn>

                    <v-btn color="primary" smallsmall @click="showQuotes(item)">Quotes</v-btn>
                </div>
            </template>
        </v-data-table>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions.js";
import LogisticsEnquiryForm from "../components/LogisticsEnquiryForm.vue";

export default {
    components: {
        LogisticsEnquiryForm,
    },
    data() {
        return {
            loader: false,
            filtersLoader: false,
            searchProduct: '',
            searchProject: '',
            searchWorksPackage: '',
            projects: [],
            worksPackages: [],
            filters: {
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
                    text: "Works Package",
                    value: "works_package_name",
                    sortable: true,
                },
                {
                    text: "Project",
                    value: "project_name",
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
            editedIndex: -1,
            editedItem: {
                comment: "",
                product_id: 0,
                project_id: 0,
                works_package_id: 0,
                days: "",
                editId: null,
            },
            defaultItem: {
                comment: "",
                product_id: 0,
                project_id: 0,
                works_package_id: 0,
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
        // await self.loadWorksPackageOptions();
        // await self.loadProjectOptions();
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
                worksPackageIds: self.filters.worksPackageIds,
                projectIds: self.filters.projectIds,
            };

            const response = await api.loadLogisticsEnquiries(params);

            self.items = response.data.data;

            self.serverItemsLength = response.data.total;

            self.loader = false;
        },

        async loadWorksPackageOptions(search = '', projectIds = []) {
            const self = this;

            if (self.filtersLoader) {
                return;
            }

            self.filtersLoader = true;

            const resp = await api.worksPackageOptions(search, projectIds);

            self.worksPackages = resp.data;
            self.filtersLoader = false;
        },

        async loadProjectOptions(val) {
            const self = this;

            if (self.filtersLoader) {
                return;
            }

            self.filtersLoader = true;

            const resp = await api.projectOptions(val ?? '');

            self.projects = resp.data;
            self.filtersLoader = false;
        },

        editItem(item) {
            this.editedIndex = this.items.indexOf(item);
            this.editedItem = Object.assign({}, item);
            this.dialog = true;
        },

        async restoreItem(enquiry) {
            const self = this;

            self.loader = true;
            await api.restoreLogisticsEnquiry(enquiry.id);
            await self.load();
            self.loader = false;
        },

        viewComments: function (item) {
            if (this.expanded.indexOf(item) > -1) {
                this.expanded = [];
                return;
            }

            this.expanded = [item];
        },

        showQuotes(enquiry) {
            this.$router.push({
                name: "logistics-quotes",
                params: {enquiry_id: enquiry.id},
            });
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        canCreateEnquiry() {
            return permissions.can(permissions.create_enquiry_logistics);
        },

        canEditEnquiry(enquiry) {
            if (permissions.hasRole(permissions.role_admin)) {
                return true;
            }

            return enquiry.user_id === this.user().id;
        },

        async updated() {
            const self = this;

            this.dialog = false;
            this.$nextTick(() => {
                this.editedItem = Object.assign({}, this.defaultItem);
                this.editedIndex = -1;
            });

            await self.load();
        },

        close() {
            this.dialog = false;
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
