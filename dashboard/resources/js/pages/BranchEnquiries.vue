<template>
    <div style="margin: 1em">
        <h2>Enquiries > Purchase & Hire</h2>

        <h3>Branch #{{ branchId }} {{ branchName }}</h3>

        <div style="position: relative; padding-top: 1em">
            <v-data-table
                item-key="id"
                :headers="computedHeaders"
                :items="items"
                :server-items-length="serverItemsLength"
                :loading="loader"
                :options.sync="options"
                loading-text="Loading... Please wait"
                :item-class="itemRowBackground"
                :single-expand="singleExpand"
                :expanded.sync="expanded"
                mobile-breakpoint="1000"
            >

                <template v-slot:top>
                    <v-dialog v-model="dialog" max-width="900px">
                        <question-form
                            v-model="editedItem"
                            v-on:saved="updated"
                            v-on:cancel="close"
                            :title="formTitle"
                        />
                    </v-dialog>

                    <v-dialog v-model="dialogUploads" max-width="500px">
                        <attachments-dialog
                            :parentId="dialogUploadItem.id"
                            type="Question"
                            title="Enquiry"
                            @close="dialogUploads=false"
                            :userCanEdit="user().id === dialogUploadItem?.user_id"
                        ></attachments-dialog>
                    </v-dialog>

                    <div class="row">
                        <div class="col-md-6 row">&nbsp;</div>
                        <div class="col-md-6 row">
                            <v-autocomplete
                                class="col-md-3"
                                item-text="name"
                                item-value="id"
                                :loading="filtersLoader"
                                :search-input.sync="searchProduct"
                                v-model="filters.productIds"
                                :items="products"
                                outlined
                                dense
                                chips
                                multiple
                                small-chips
                                label="Product"
                            ></v-autocomplete>

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

                            <v-checkbox label="Only unexpired" v-model="onlyUnexpired"></v-checkbox>
                        </div>
                    </div>
                </template>

                <template v-slot:expanded-item="{ headers, item }">
                    <td :colspan="headers.length">
                        <div class="expanded-comment">
                            <pre>{{ item.comment }}</pre>
                        </div>
                    </td>
                </template>

                <template v-slot:item.comment="{ item }">
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
                    <v-checkbox readonly :input-value="!!item.is_quoted"></v-checkbox>
                </template>

                <template v-slot:item.product_type="{ item }">
                    {{ (item.product_type === 1) ? 'Hire Enquiry' : 'Purchase Enquiry' }}
                </template>

                <template v-slot:item.id="{ item }">
                    <span>{{ item.id }}<span v-if="item.ref_archived_enquiry_id"> (restored {{item.ref_archived_enquiry_id}})</span></span>
                </template>

                <template v-slot:item.assigned="{ item }">
                    <ul>
                        <li v-for="user in (item.assigned || [])">{{
                                isCurrentUser(user.id) ? 'You' : user.username
                            }}
                        </li>
                    </ul>
                </template>

                <template v-slot:item.actions="{ item }">
                    <div class="text-center">
                        <v-btn
                            color="primary"
                            small
                            class="mr-2"
                            @click="addToFav(item)"
                        >Add to Favourites
                        </v-btn
                        >

                        <v-btn
                            color="primary"
                            v-if="canEditQuestion(item)"
                            small
                            class="mr-2"
                            @click="editItem(item)"
                        >Edit Enquiry
                        </v-btn
                        >

                        <v-tooltip top>
                            <template v-slot:activator="{ on, attrs }">
                                <v-btn
                                    small
                                    class="mr-2"
                                    @click="viewComments(item)"
                                    icon
                                    color="black"
                                    v-bind="attrs"
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
                                    small
                                    class="mr-2"
                                    @click="viewItem(item)"
                                    icon
                                    color="black"
                                    v-bind="attrs"
                                    v-on="on"
                                >
                                    <v-badge
                                        :value="item.newMsgQty > 0"
                                        color="red"
                                        :content="item.newMsgQty"
                                        :overlap="true"
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
                                    small
                                    class="mr-2"
                                    @click="archiveItem(item)"
                                    icon
                                    color="black"
                                    v-bind="attrs"
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
                    </div>
                    <v-btn color="primary" small @click="showAnswers(item)"
                    >{{ showAnswersLabel() }}
                    </v-btn
                    >

                    <v-btn color="primary" v-if="userCanBeAssigned(item)" small @click="toggleAssign(item)">
                        {{ assignToggleButtonLabel(item) }}
                    </v-btn>

                </template>
            </v-data-table>
        </div>

        <v-dialog v-model="chatDialog" max-width="500px">
            <chat-enquiry
                :isSelectChat="isSelectChat"
                :interlocutorId="(!isSelectChat && question) ? question.user_id: undefined"
                :visibility="chatDialog"
                :enquiry="question"
                v-on:close="chatDialog = false"
            />
        </v-dialog>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions.js";
import QuestionForm from "../components/QuestionForm";
import QuestionFormMulti from "../components/QuestionFormMulti";
import AttachmentsDialog from "../components/AttachmentsDialog";
import ChatEnquiry from "../components/ChatEnquiry";

export default {
    props: ["branchId", 'isQuoted', 'onlyUnexpiredDefault'],

    components: {
        AttachmentsDialog,
        QuestionForm, QuestionFormMulti,
        ChatEnquiry
    },
    data() {
        return {
            refreshHandler: null,
            loader: false,
            filtersLoader: false,
            dialogUploadItem: {},
            branchName: '',
            onlyUnexpired: false,
            searchProduct: '',
            searchProject: '',
            searchWorksPackage: '',
            searchQuoted: '',
            isSelectChat: false,
            products: [],
            projects: [],
            worksPackages: [],
            filters: {
                productIds: [],
                projectIds: [],
                worksPackageIds: [],
                is_quoted_by_branch: '',
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
                // {
                //     text: "Last Name",
                //     value: "last_name",
                //     sortable: true,
                //
                // },
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
                    text: "Live/Tender",
                    value: "type",
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
                // {
                //     text: "Comments",
                //     value: "comment",
                //     sortable: true,
                // },
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
                // {
                //     text: 'Uploaded Document',
                //     value: 'attachments',
                //     sortable: false,
                // },
                {
                    text: 'Assigned To',
                    value: 'assigned',
                    sortable: false,
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
            expanded: [],
            singleExpand: true,

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
            dialogMulti: false,
            dialogUploads: false,
            editedIndex: -1,
            editedItem: {
                postcode: "",
                comment: "",
                product_id: 0,
                project_id: 0,
                works_package_id: 0,
                days: "",
                editId: null,
                type: "",
            },
            defaultItem: {
                postcode: "",
                comment: "",
                product_id: 0,
                project_id: 0,
                works_package_id: 0,
                days: "",
                editId: null,
                type: "",
            },
            question: null,
            chatDialog: false,
        };
    },

    mounted() {
        const self = this;

        self.loadProductOptions();
        self.loadProjectOptions();
        self.loadWorksPackageOptions();

        self.load();

        api.getUser(this.branchId).then((user) => {
            self.branchName = user.data.first_name;
        });
    },

    watch: {
        onlyUnexpiredDefault() {
          this.onlyUnexpired = this.onlyUnexpiredDefault
        },
        onlyUnexpired() {
            this.load();
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
        },
        dialog(val) {
            val || this.close();
        },
        dialogMulti(val) {
            val || this.closeMulti();
        }
    },

    computed: {
        computedHeaders() {
            const self = this;
            return this.headers.filter(function (item) {
                if (item.value === 'total_quotes' && !self.isAdmin()) {
                    return false;
                }

                if (item.value === 'is_quoted' && permissions.can(permissions.create_quote_ph)) {
                    return true;
                }

                return !(item.value === 'last_name' && self.canAskQuestion());
            });
        },

        formTitle() {
            return this.editedIndex === -1 ? "New Enquiry" : "Edit Enquiry";
        },
    },

    methods: {
        viewItem(question) {
            const self = this;

            question.newMsgQty = 0;
            self.question = question;
            self.question.product = { name: self.question.name }
            self.chatDialog = !self.chatDialog;
            self.isSelectChat = question.user_id === self.user().id;
        },

        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },

        toggleAssign(item) {
            const self = this;
            api
                .toggleAssign(item.id)
                .then((response) => {
                    self.load();
                });
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

        userCanBeAssigned(item) {
            const self = this;
            if (!self.userHasBillingUser(item)) {
                return false;
            }

            return self.user().can_assign_to_enquiries;
        },

        assignToggleButtonLabel(item) {
            const self = this;

            let ids = item.assigned.map((item) => {
                return item.id;
            })

            return (ids.indexOf(self.user().id) < 0) ? "Assign" : "Remove assignment";
        },

        showUnreadAnswersIndicator(item) {
            if (!this.canAskQuestion()) {
                return false;
            }

            return item.newQuotesQty > 0;
        },

        showAnswersLabel() {
            return (!permissions.can(permissions.create_quote_ph)) ? 'Quotes' : 'Quote';
        },

        itemRowBackground: function (item) {
            return item.is_preferred_supplier ? "highlight-row mnrow" : "mnrow";
        },

        viewComments: function (item) {
            if (this.expanded.indexOf(item) > -1) {
                this.expanded = [];
                return;
            }

            this.expanded = [item];
        },

        load() {
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
                branchId: self.branchId,
                onlyUnexpired: self.onlyUnexpired
            };

            if (self.filters.is_quoted) {
                params.isQuoted = this.filters.is_quoted
            }

            if (self.isQuoted) {
                params.isQuotedByBranch = this.isQuoted;
            }

            api
                .loadQuestions(params)
                .then((response) => {
                    self.items = response.data.data;

                    self.serverItemsLength = response.data.total;

                    self.loader = false;

                    self.hideNewEnquiriesIndicator();
                })
                .catch((err) => {
                    self.loader = false;
                });
        },

        async loadProductOptions() {
            const self = this;

            self.filtersLoader = true;

            api.productOptions([1,2]).then((resp) => {
                self.products = resp.data;
            }).catch(err => {
                console.log(err)
            }).finally(() => {
                self.filtersLoader = false;
            });
        },

        async loadProjectOptions(searchVal = null) {
            const self = this;

            self.filtersLoader = true;

            const resp = await api.projectOptions(searchVal ?? '');

            self.projects = resp.data
            self.filtersLoader = false;
        },

        async loadWorksPackageOptions(search = '', projectIds = []) {
            const self = this;

            self.filtersLoader = true;

            const resp = await api.worksPackageOptions(search, projectIds);

            self.worksPackages = resp.data;
            self.filtersLoader = false;
        },

        hideNewEnquiriesIndicator() {
            const self = this;
            setTimeout(() => self.$store.commit('setSession', {
                ...this.$store.getters.getSession,
                newEnquiries: 0,
            }), 3000);
        },

        addToFav(item) {
            self.loader = true;
            api.addToFav(item.id).then((response) => {
                alert("Added to favourites");
                self.loader = false;
            });
        },

        editItem(item) {
            this.editedIndex = this.items.indexOf(item);
            this.editedItem = Object.assign({}, item);
            this.dialog = true;
        },

        deleteItem(question) {
            const self = this;

            if (confirm("Are you sure you want to delete this question?")) {
                self.loader = true;
                api.deleteQuestion(question.id).then((response) => {
                    self.load();

                    self.loader = false;
                });
            }
        },

        archiveItem(question) {
            const self = this;

            self.loader = true;
            api.archiveQuestion(question.id).then((response) => {
                self.load();

                self.loader = false;
            });
        },

        showAnswers(question) {
            this.$router.push({
                name: "branch-inquiries-quotes",
                params: {question_id: question.id, branchId: this.branchId},
            });
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        canAskQuestion() {
            return permissions.can(permissions.create_enquiry_ph);
        },

        canArchiveQuestion(question) {
            if (permissions.hasRole(permissions.role_admin)) {
                return true;
            }

            return question.user_id === this.user().id;
        },

        canEditQuestion(question) {
            if (permissions.hasRole(permissions.role_admin)) {
                return true;
            }

            return question.user_id === this.user().id;
        },

        updatedMulti() {
            const self = this;
            this.dialogMulti = false;
            self.load();
        },

        closeMulti() {
            this.dialogMulti = false;
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
    },
};
</script>

<style>
</style>
