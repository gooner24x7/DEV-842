<template>
    <div style="margin: 1em">
<!--        <h2>Enquiries > Logistics</h2>-->

        <div style="position: relative;">
            <v-data-table
                :expanded.sync="expanded"
                :headers="computedHeaders"
                :item-class="itemRowBackground"
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
                    <v-dialog v-model="dialog" max-width="800px">
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                v-if="canCreateEnquiry()"
                                class="m-2"
                                color="primary"
                                style="left: 0"
                                v-bind="attrs"
                                v-on="on"
                            >New Enquiry
                            </v-btn
                            >

                        </template>
                        <logistics-enquiry-form
                            :value="editedItem"
                            @saved="updated()"
                            @cancel="close()"
                        ></logistics-enquiry-form>
                    </v-dialog>

                    <v-dialog v-model="dialogUploads" max-width="500px">
                        <attachments-dialog
                            :parentId="dialogUploadItem.id"
                            type="LogisticsEnquiry"
                            title="Enquiry"
                            @close="dialogUploads=false"
                            :userCanEdit="user().id === dialogUploadItem?.user_id"
                        ></attachments-dialog>
                    </v-dialog>

                    <v-row style="margin-top: 10px;">
                        <v-col >
                            <v-autocomplete
                                v-model="filters.isQuoted"
                                :items="['yes', 'no']"
                                clearable
                                dense
                                label="Already Quoted"
                                outlined
                                v-if="isLogisticsOrAdmin()"
                            ></v-autocomplete>
                        </v-col>
                        <v-col >
                            <v-autocomplete
                                v-model="filters.isIgnored"
                                :items="['yes', 'no']"
                                clearable
                                dense
                                label="Skipped"
                                outlined
                                v-if="isLogisticsOrAdmin()"
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
                                label="Project"
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
                        <div class="expanded-comment">
                            <pre>{{ item.comments }}</pre>
                        </div>
                    </td>
                </template>

                <template v-slot:item.comments="{ item }">
                    {{ item.comment | limit20Chars }}
                </template>

                <template v-slot:item.created_at="{ item }">
                    {{ item.created_at | formatDate }}
                </template>

                <template v-slot:item.attachments="{ item }">
                    <a href="#" v-on:click="(e) => { e.preventDefault(); dialogUploadItem = item; dialogUploads = true; }">
                        <v-icon>mdi-download</v-icon>
                    </a>
                </template>

                <template v-slot:item.is_quoted="{ item }">
                    <v-checkbox :input-value="!!item.is_quoted" readonly></v-checkbox>
                </template>

                <template v-slot:item.is_ignored="{ item }">
                    <v-checkbox :input-value="!!item.is_ignored" readonly></v-checkbox>
                </template>

                <template v-slot:item.actions="{ item }">
                    <div class="text-center">
                        <v-btn
                            v-if="canEditEnquiry(item)"
                            class="mr-2"
                            color="primary"
                            small
                            @click="editItem(item)"
                        >Edit Enquiry
                        </v-btn
                        >

                        <v-btn color="primary" small @click="showQuotes(item)">Quotes</v-btn>

                        <v-btn v-if="isLogisticsOrAdmin()" color="primary" small @click="toggleIgnore(item)">
                            {{ ignoreToggleButtonLabel(item) }}
                        </v-btn>

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
                                </v-btn
                                >
                            </template>
                            <span>View Comments</span>
                        </v-tooltip>

                        <v-tooltip top>
                            <template v-slot:activator="{ on, attrs }">
                                <v-btn
                                    class="mr-2"
                                    color="black"
                                    icon
                                    small
                                    v-bind="attrs"
                                    @click="viewItem(item)"
                                    v-on="on"
                                >
                                    <v-badge
                                        :content="item.newMsgQty"
                                        :overlap="true"
                                        :value="item.newMsgQty > 0"
                                        color="red"
                                    >
                                        <v-icon>
                                            mdi-forum
                                        </v-icon>
                                    </v-badge>
                                </v-btn
                                >
                            </template>
                            <span>Live Chat</span>
                        </v-tooltip>

                        <v-tooltip top>
                            <template v-slot:activator="{ on, attrs }">
                                <v-btn
                                    v-if="canArchiveEnquiry(item)"
                                    class="mr-2"
                                    color="black"
                                    icon
                                    small
                                    v-bind="attrs"
                                    @click="archiveItem(item)"
                                    v-on="on"
                                >
                                    <v-icon>mdi-folder-open</v-icon>
                                </v-btn>
                            </template>
                            <span>Archive Enquiry</span>
                        </v-tooltip>
                    </div>

                </template>
            </v-data-table>
        </div>

        <v-dialog v-model="chatDialog" max-width="500px">
            <chat-logistics-enquiry
                :enquiry="enquiry"
                :interlocutorId="(!isSelectChat && enquiry) ? enquiry.user_id : undefined"
                :isSelectChat="isSelectChat"
                :visibility="chatDialog"
                v-on:close="chatDialog = false"
            />
        </v-dialog>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions.js";
import AttachmentsDialog from "../components/AttachmentsDialog";
import QuestionFormMulti from "../components/QuestionFormMulti.vue";
import LogisticsEnquiryForm from "../components/LogisticsEnquiryForm.vue";
import ChatLogisticsEnquiry from "../components/ChatLogisticsEnquiry.vue";

export default {
    name: 'LogisticsEnquiries',
    components: {
        ChatLogisticsEnquiry,
        LogisticsEnquiryForm,
        QuestionFormMulti,
        AttachmentsDialog,
    },
    data() {
        return {
            enquiry: null,
            refreshHandler: null,
            loader: false,
            filtersLoader: false,
            isSelectChat: false,
            serverItemsLength: 0,
            expanded: [],
            singleExpand: true,
            items: [],
            projects: [],
            worksPackages: [],
            dialog: false,
            dialogUploads: false,
            dialogUploadItem: {},
            chatDialog: false,
            searchProject: '',
            searchWorksPackage: '',
            editedIndex: -1,
            editedItem: {
                id: null,
                project_id: null,
                works_package_id: null,
                type: null,
                status: null,
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
                load_details: ''
            },
            defaultItem: {
                id: null,
            },
            filters: {
                projectIds: [],
                worksPackageIds: [],
                isQuoted: false,
                isIgnored: false,
            },
            headers: [
                {
                    text: "ID",
                    value: "id",
                    sortable: true,
                },
                {
                    text: "Date",
                    value: "created_at",
                    sortable: true,
                },
                {
                    text: "Business Name",
                    value: "first_name",
                    sortable: true,
                },
                {
                    text: "Type",
                    value: "type_str",
                    sortable: true,
                },
                {
                    text: "Open/Closed",
                    value: "scope_str",
                    sortable: true,
                },
                {
                    text: "Collection Postcode",
                    value: "contacts[0].postcode",
                    sortable: true,
                },
                {
                    text: "Delivery Postcode",
                    value: "contacts[1].postcode",
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
                    text: 'Documents',
                    value: 'attachments',
                    sortable: false,
                },
                {
                    text: 'Quoted',
                    value: 'is_quoted',
                    sortable: false,
                },
                {
                    text: 'Skipped',
                    value: 'is_ignored',
                    sortable: false,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: false,
                },
            ],
            options: {
                page: 1,
                itemsPerPage: 10,
                sortBy: ["id"],
                sortDesc: [true],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false,
            }
        };
    },

    async mounted() {
        const self = this;

        //await self.loadWorksPackageOptions();
        //await self.loadProjectOptions();

        // self.refreshHandler = setTimeout(() => {
        //     self.load();
        // }, 5 * 60 * 1000);
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
        },
    },

    computed: {
        computedHeaders() {
            const self = this;

            return this.headers.filter(function (item) {
                if (item.value === 'is_quoted' && !self.isLogisticsOrAdmin()) {
                    return false;
                }
                if (item.value === 'is_ignored' && !self.isLogisticsOrAdmin()) {
                    return false;
                }

                return true;
            });
        },

        formTitle() {
            return this.editedIndex === -1 ? "New Enquiry" : "Edit Enquiry";
        },
    },

    methods: {
        viewItem(item) {
            const self = this;

            item.newMsgQty = 0;
            self.enquiry = item;
            self.chatDialog = !self.chatDialog;
            self.isSelectChat = item.user_id === self.user().id;
        },

        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },

        isCompany() {
            return permissions.hasRole(permissions.role_company);
        },

        isLogisticsOrAdmin() {
            return permissions.hasRole(permissions.role_logistics) || permissions.hasRole(permissions.role_admin);
        },

        isCurrentUser(userId) {
            const self = this;

            return self.user().id === userId;
        },

        userHasBillingUser(item) {
            const self = this;

            let user = self.user()

            return user && user.billing_user_id > 0;
        },

        itemRowBackground: function (item) {
            if (item.status === 1) {
                return "grey-row mnrow";
            }

            return item.is_preferred_supplier ? "highlight-row mnrow" : "mnrow";
        },

        viewComments: function (item) {
            if (this.expanded.indexOf(item) > -1) {
                this.expanded = [];
                return;
            }

            this.expanded = [item];
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
                worksPackageIds: self.filters.worksPackageIds,
                projectIds: self.filters.projectIds,
            };

            if (self.filters.isQuoted) {
                params.isQuoted = self.filters.isQuoted
            }

            if (self.filters.isIgnored) {
                params.isIgnored = self.filters.isIgnored;
            }

            try {
                const response = await api.loadLogisticsEnquiries(params);

                self.items = response.data.data;
                self.serverItemsLength = response.data.total;

                self.projects = [];
                self.worksPackages = [];
                self.items.forEach(item => {
                    if (item.project_id && self.projects.findIndex(project => project.id === item.project_id) === -1) {
                        self.projects.push({id: item.project_id, name: item.project_name});
                    }
                    if (item.works_package_id && self.worksPackages.findIndex(wp => wp.id === wp.works_package_id) === -1) {
                        self.worksPackages.push({id: item.works_package_id, name: item.works_package_name});
                    }
                });
            } catch (e) {
                console.log(e);
            }

            self.loader = false;
        },

        async loadWorksPackageOptions(search = '', projectIds = []) {
            const self = this;

            self.filtersLoader = true;

            const resp = await api.getLogisticsWorksPackageOptions(search, projectIds);

            self.worksPackages = resp.data;
            self.filtersLoader = false;
        },

        async loadProjectOptions(search = '') {
            const self = this;

            self.filtersLoader = true;

            const resp = await api.getLogisticsProjectOptions(search);

            self.projects = resp.data
            self.filtersLoader = false;
        },

        editItem(item) {
            this.editedIndex = this.items.indexOf(item);
            this.editedItem = Object.assign({}, item);
            this.dialog = true;
        },

        async deleteItem(enquiry) {
            const self = this;

            if (confirm("Are you sure you want to delete this enquiry?")) {
                self.loader = true;
                await api.deleteLogisticsEnquiry(enquiry.id);
                await self.load();
                self.loader = false;
            }
        },

        async archiveItem(enquiry) {
            const self = this;

            self.loader = true;
            await api.archiveLogisticsEnquiry(enquiry.id)

            await self.load();
            self.loader = false;
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
            const self = this;

            if ((enquiry.user_id === self.user().id) && (enquiry.status === 1)) {
                return true;
            }

            return permissions.hasRole(permissions.role_admin);
        },

        canArchiveEnquiry(enquiry) {
            if (permissions.hasRole(permissions.role_admin)) {
                return true;
            }

            if (permissions.can(permissions.create_enquiry_logistics) && enquiry.user_id === this.user().id) {
                return true;
            }

            return false;
        },

        updated() {
            const self = this;

            self.dialog = false;
            this.$nextTick(() => {
                self.editedItem = Object.assign({}, self.defaultItem);
                self.editedIndex = -1;
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

        ignoreToggleButtonLabel(item) {
            const self = this;

            return (item.is_ignored === 1) ? 'Remove "Skipped"' : 'Skip';
        },

        async toggleIgnore(item) {
            const self = this;
            await api.toggleLogisticsEnquiryIgnore(item.id);
            await self.load();
        },

        getEditButtonLabel(enquiry) {
            return enquiry.status === 1 ? "Edit Draft" : "Edit";
        }
    },
};
</script>

<style>
</style>
