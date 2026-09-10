<template>
    <div style="margin: 1em">
        <div v-if="isContractor() || isAdmin()" class="mb-2">
            <v-btn :disabled=true class="primary">Enquiries</v-btn>
            <v-btn class="primary" v-on:click="goToProjects()">Projects</v-btn>
        </div>

<!--        <h2>Trade Package Tenders</h2>-->

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
                    <v-btn
                        v-if="canAskQuestion()"
                        class="m-2"
                        color="primary"
                        style="left: 0"
                        @click="newEnquiry()"
                    >
                        New Enquiry
                    </v-btn>

                    <v-row style="margin-top: 10px;">
                        <v-col>
                            <v-autocomplete
                                v-model="filters.appliedFor"
                                :items="[{value:true, text:'yes'}, {value:false, text:'no'}]"
                                chips
                                clearable
                                dense
                                item-text="text"
                                item-value="value"
                                label="Applied For"
                                outlined
                                small-chips
                            ></v-autocomplete>
                        </v-col>
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

                <template v-slot:item.id="{ item }">
                    <span>{{ item.id }}<span
                        v-if="item.ref_archived_enquiry_id"> (restored {{ item.ref_archived_enquiry_id }})</span></span>
                </template>

                <template v-slot:expanded-item="{ headers, item }">
                    <td :colspan="headers.length">
                        {{ item.comment }}
                    </td>
                </template>

                <template v-slot:item.comment="{ item }">
                    {{ item.comment | limit20Chars }}
                </template>

                <template v-slot:item.created_at="{ item }">
                    {{ item.created_at | formatDate }}
                </template>

                <template v-slot:item.published_at="{ item }">
                    {{ item.published_at | formatDate }}
                </template>

                <template v-slot:item.attachments="{ item }">
                    <a href="#" v-on:click="(e) => { e.preventDefault(); dialogUploadItem = item; dialogUploads = true; }">
                        <v-icon>mdi-download</v-icon>
                    </a>
                </template>

                <template v-slot:item.product_type="{ item }">
                    Marketplace
                </template>

                <template v-slot:item.type="{ item }">
                    <div class="type-chip" :style="getChipStyle(item.type)">
                        {{ item.type }}
                    </div>
                </template>

                <template v-slot:item.actual_starting_date="{item}">
                    <div v-if="canAskQuestion()">
                        <v-menu
                            v-model="menu[item.id]"
                            :close-on-content-click="false"
                            max-width="290"
                        >
                            <template v-slot:activator="{ on, attrs }">
                                <v-text-field v-model="item.actual_starting_date"
                                              :class="dateStateClass(item)"
                                              type="text"
                                              v-bind="attrs"
                                              v-on="on"></v-text-field>
                            </template>
                            <v-date-picker
                                v-model="selectedDate[item.id]"
                                no-title
                                @change="menu[item.id] = false"
                            ></v-date-picker>
                        </v-menu>
                    </div>
                    <div v-else :class="dateStateClass(item)">{{
                            item.actual_starting_date
                        }}
                    </div>
                </template>

                <template v-slot:item.actual_end_date="{item}">
                    <div v-if="canAskQuestion()">
                        <v-menu
                            v-model="menu2[item.id]"
                            :close-on-content-click="false"
                            max-width="290"
                        >
                            <template v-slot:activator="{ on, attrs }">
                                <v-text-field v-model="item.actual_end_date"
                                              :class="dateStateClass2(item)"
                                              type="text"
                                              v-bind="attrs"
                                              v-on="on"></v-text-field>
                            </template>
                            <v-date-picker
                                v-model="selectedDate2[item.id]"
                                no-title
                                @change="menu2[item.id] = false"
                            ></v-date-picker>
                        </v-menu>
                    </div>
                    <div v-else :class="dateStateClass(item)">{{
                            item.actual_end_date
                        }}
                    </div>
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
                                    v-if="canArchiveQuestion(item)"
                                    class="mr-2"
                                    color="black"
                                    icon
                                    small
                                    v-bind="attrs"
                                    @click="archiveItem(item)"
                                    v-on="on"
                                >
                                    <v-icon>
                                        mdi-folder-open
                                    </v-icon>
                                </v-btn
                                >
                            </template>
                            <span>Archive Enquiry</span>
                        </v-tooltip>

                        <v-tooltip top v-if="item.works_package_id">
                            <template v-slot:activator="{ on, attrs }">
                                <v-btn
                                    class="mr-2"
                                    color="black"
                                    icon
                                    small
                                    v-bind="attrs"
                                    @click="viewWorksPackageQuestions(item)"
                                    v-on="on"
                                >
                                    <v-icon>
                                        mdi-frequently-asked-questions
                                    </v-icon>
                                </v-btn
                                >
                            </template>
                            <span>Clarifications</span>
                        </v-tooltip>
                    </div>

                    <v-badge
                        :content="item.newQuotesQty"
                        :overlap="true"
                        :value="showUnreadAnswersIndicator(item)"
                        color="red"
                    >
                        <v-btn color="primary" small @click="showAnswers(item)">{{ showAnswersLabel() }}</v-btn>
                    </v-badge>

                    <div v-if="item.session_hash ?? false">
                        <v-btn
                            v-if="!item.session_answered"
                            :href="tenderForJob(item)"
                            class="primary"
                            target="_blank"
                        >
                            Tender for Job
                        </v-btn>
                        <v-btn v-else class="primary" target="_blank">
                            Applied
                        </v-btn>
                    </div>

                    <v-btn
                        v-if="canEditEnquiry(item)"
                        class="mr-2"
                        color="primary"
                        small
                        @click="editItem(item)"
                    >
                        {{ getEditButtonLabel(item) }}
                    </v-btn>
                </template>
            </v-data-table>
        </div>

        <v-dialog v-model="dialog" max-width="600px">
            <supply-fit-enquiry-form
                v-model="editedItem"
                @cancel="close"
                @saved="updated"
            />
        </v-dialog>

        <v-dialog v-model="dialogUploads" max-width="600">
            <attachments-dialog
                :parentId="dialogUploadItem.id"
                type="SupplyFitEnquiry"
                title="Enquiries > Marketplace"
                @close="dialogUploads=false"
                :userCanEdit="user().id === dialogUploadItem?.user_id"
            ></attachments-dialog>
        </v-dialog>

        <v-dialog v-model="dialogWpQuestions" max-width="900px">
            <works-package-questions-dialog
                :worksPackageId="selectedWorksPackageId"
                @cancel="dialogWpQuestions = false"
            />
        </v-dialog>

        <v-dialog v-model="chatDialog" max-width="600px">
            <chat-enquiry
                :enquiry="enquiry"
                :interlocutorId="(!isSelectChat && enquiry) ? enquiry.user_id: undefined"
                :isSelectChat="isSelectChat"
                :visibility="chatDialog"
                @close="chatDialog = false"
            />
        </v-dialog>
    </div>
</template>

<script>
import permissions from "../common/permissions";
import api from "../common/api";
import SupplyFitEnquiryForm from "../components/SupplyFitEnquiryForm";
import AttachmentsDialog from "../components/AttachmentsDialog";
import ChatEnquiry from "../components/ChatSupplyFitEnquiry";
import WorksPackageQuestionsDialog from "../components/WorksPackageQuestionsDialog.vue";
import AttachDocuments from "../components/AttachDocuments.vue";
import moment from "moment/moment";

export default {
    name: "SupplyFitEnquiries.vue",
    components: {
        AttachDocuments,
        WorksPackageQuestionsDialog,
        AttachmentsDialog,
        SupplyFitEnquiryForm,
        ChatEnquiry
    },
    computed: {
        computedHeaders() {
            const self = this;
            return this.headers.filter(function (item) {
                if (item.value === 'total_quotes' && !self.isAdmin()) {
                    return false;
                }

                return !(item.value === 'last_name' && permissions.can(permissions.create_enquiry_sf));
            });
        },
    },

    data() {
        return {
            selectedDate: [],
            selectedDate2: [],
            menu: [],
            menu2: [],
            chatDialog: false,
            loader: false,
            isSelectChat: false,
            singleExpand: true,
            dialogUploadItem: {},
            searchProduct: '',
            searchProject: '',
            searchWorksPackage: '',
            selectedWorksPackageId: null,
            enquiry: undefined,
            products: [],
            projects: [],
            worksPackages: [],
            filtersLoader: false,
            expanded: [],
            refreshHandler: undefined,
            dialog: false,
            dialogUploads: false,
            dialogWpQuestions: false,
            items: [],
            serverItemsLength: 0,
            options: {
                page: 1,
                itemsPerPage: 10,
                sortBy: ["published_at"],
                sortDesc: [true],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false,
            },
            filters: {
                productIds: [],
                projectIds: [],
                worksPackageIds: [],
                appliedFor: null,
            },
            editedIndex: -1,
            editedItem: {
                editId: null,
                postcode: '',
                comment: '',
                product_id: null,
                project_id: null,
                works_package_id: null,
                days: '',
                assumed_end_date: '',
                type: '',
                status: null,
                scope: null,
                attachment: [],
            },
            defaultItem: {
                editId: null,
                postcode: '',
                comment: '',
                product_id: null,
                project_id: null,
                works_package_id: null,
                days: '',
                assumed_end_date: '',
                type: '',
                status: null,
                scope: null,
                attachment: [],
            },
            headers: [
                {
                    text: "ID",
                    value: "id",
                    sortable: true,
                },
                {
                    text: "Date Created",
                    value: "created_at",
                    sortable: true,
                },
                {
                    text: "Date Published",
                    value: "published_at",
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
                    text: "Assumed starting date",
                    value: "days",
                    sortable: true,
                },
                {
                    text: 'Actual starting date',
                    value: 'actual_starting_date',
                    sortable: true,
                },
                {
                    text: 'Assumed end date',
                    value: 'assumed_end_date',
                    sortable: true,
                },
                {
                    text: 'Actual end date',
                    value: 'actual_end_date',
                    sortable: true,
                },
                {
                    text: "Live/Tender",
                    value: "type",
                    sortable: true,
                },
                {
                    text: "Open/Closed",
                    value: "scope_str",
                    sortable: true,
                },
                {
                    text: "Product",
                    value: "name",
                    sortable: true,
                },
                {
                    text: "Product Type",
                    value: "product_type",
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
                    text: 'Total Quotes',
                    value: 'total_quotes',
                    sortable: false,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: false,
                },
            ],
        }
    },

    async mounted() {
        const self = this;

        //await self.load();
        await self.loadProductOptions('');
        //await self.loadProjectOptions('');
        //await self.loadWorkPackageOptions('');
    },

    watch: {
        selectedDate: {
            async handler(n, o) {
                for (let id in n) {
                    let item = this.items.filter((item) => {
                        return +item.id === +id
                    })?.[0];
                    if (item) {
                        item.actual_starting_date = moment(n[id], 'YYYY-MM-DD').format('DD-MM-YYYY');

                        await this.saveEnquiry(item);
                        break;
                    }
                }
            },
            deep: true,
        },

        selectedDate2: {
            async handler(n, o) {
                for (let id in n) {
                    let item = this.items.filter((item) => {
                        return +item.id === +id
                    })?.[0];
                    if (item) {
                        item.actual_end_date = moment(n[id], 'YYYY-MM-DD').format('DD-MM-YYYY');

                        await this.saveEnquiry(item);
                        break;
                    }
                }
            },
            deep: true,
        },

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
        }
    },

    methods: {
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

        async loadProjectOptions(search = '') {
            const self = this;

            if (self.filtersLoader) {
                return;
            }

            self.filtersLoader = true;

            try {
                const resp = await api.projectOptionsSupplyFit(search)
                self.projects = resp.data;
            } catch (e) {
                console.log(e);
            }

            self.filtersLoader = false;
        },

        async loadWorkPackageOptions(search = '', projectIds = []) {
            const self = this;

            if (self.filtersLoader) {
                return;
            }

            self.filtersLoader = true;

            try {
                const resp = await api.worksPackageOptionsSupplyFit(search, projectIds);
                self.worksPackages = resp.data;
            } catch (e) {
                console.log(e);
            }

            self.filtersLoader = false;
        },

        tenderForJob(item) {
            if (item.session_hash) {
                return '/form/' + item.session_hash;
            }

            return false;
        },

        dateStateClass(item) {
            if (!item?.actual_starting_date) {
                return '';
            }
            return moment(item.actual_starting_date, 'DD-MM-YYYY') <= moment() ? 'force-red' : 'force-green';
        },

        dateStateClass2(item) {
            if (!item?.actual_end_date) {
                return '';
            }
            return moment(item.actual_end_date, 'DD-MM-YYYY') <= moment() ? 'force-red' : 'force-green';
        },

        async saveEnquiry(item) {
            await api.storeSupplyFitEnquiry(item, item.id);

            await this.load();
        },

        viewItem(enquiry) {
            const self = this;

            enquiry.newMsgQty = 0;
            self.enquiry = enquiry;
            self.enquiry.product = {name: self.enquiry.name}
            self.chatDialog = !self.chatDialog;
            self.isSelectChat = enquiry.user_id === self.user().id;
        },

        showAnswersLabel() {
            return (!permissions.can(permissions.create_enquiry_sf)) ? 'Quotes' : 'Quote';
        },

        goToProjects() {
            this.$router.push({
                name: "projects",
            });
        },

        showAnswers(item) {
            this.$router.push({
                name: "supply-fit-quotes",
                params: {enquiry_id: item.id},
            });
        },

        showUnreadAnswersIndicator(item) {
            if (!this.canAskQuestion()) {
                return false;
            }

            return item.newQuotesQty > 0;
        },

        close() {
            this.dialog = false;
            this.$nextTick(() => {
                this.editedItem = Object.assign({}, this.defaultItem);
                this.editedIndex = -1;
            });
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

        async archiveItem(item) {
            const self = this;

            self.loader = true;
            try {
                await api.archiveSupplyFitEnquiry(item.id);

                await self.load();
            } catch (e) {
                console.log(e)
            }

            self.loader = false;
        },

        canArchiveQuestion(question) {
            if (permissions.hasRole(permissions.role_admin)) {
                return true;
            }

            return question.user_id === this.user().id;
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },

        viewComments(item) {
            if (this.expanded.indexOf(item) > -1) {
                this.expanded = [];
                return;
            }

            this.expanded = [item];
        },

        viewWorksPackageQuestions(enquiry) {
            const self = this;

            self.selectedWorksPackageId = enquiry.works_package_id;
            self.dialogWpQuestions = true;
        },

        editItem(item) {
            const self = this;

            self.editedIndex = self.items.indexOf(item);
            self.editedItem = Object.assign({}, item);
            self.dialog = true;
        },

        newEnquiry() {
            const self = this;

            self.selectedWorksPackages = [];
            self.editedItem = self.defaultItem;
            self.dialog = true;
        },

        canEditEnquiry(enquiry) {
            const self = this;

            if ((enquiry.user_id === self.user().id) && (enquiry.status === 2)) {
                return true;
            }

            return permissions.hasRole(permissions.role_admin);
        },

        canAskQuestion() {
            return permissions.can(permissions.create_enquiry_sf);
        },

        isContractor() {
            return permissions.hasRole(permissions.role_contractor);
        },

        hideNewEnquiriesIndicator() {
            const self = this;
            setTimeout(() => self.$store.commit('setSession', {
                ...this.$store.getters.getSession,
                newSupplyFitEnquiries: 0,
            }), 3000);
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
                productIds: self.filters.productIds,
                projectIds: self.filters.projectIds,
                worksPackageIds: self.filters.worksPackageIds,
                isAnswered: self.filters.appliedFor,
            };

            try {
                const response = await api.loadSupplyFitEnquiries(params)
                self.items = response.data.data;
                self.serverItemsLength = response.data.total;
                self.hideNewEnquiriesIndicator();

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

        itemRowBackground: function (item) {
            if (item.status === 2) {
                return "grey-row mnrow";
            }

            return item.is_preferred_supplier ? "highlight-row mnrow" : "mnrow";
        },

        getEditButtonLabel(enquiry) {
            return enquiry.status === 2 ? "Edit Draft" : "Edit";
        },

        getChipStyle(type) {
            let style = 'background-color: #f0f0f0';

            if (type === 'live') {
                style = 'background-color: #75e673';
            } else if (type === 'tender') {
                style = 'background-color: #fffa86';
            } else if (type === 'pre tender') {
                style = 'background-color: #ffcc82';
            }

            return style;
        }
    }
}
</script>

<style>
.force-red input {
    color: red !important;
    border-color: red !important;;
    background-color: pink !important;;
}

.force-green input {
    color: green !important;;
    border-color: green !important;;
    background-color: #98dfb6 !important;;
}
</style>
