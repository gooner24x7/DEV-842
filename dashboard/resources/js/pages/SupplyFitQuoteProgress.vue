<template>
    <div style="margin: 1em">
        <h2>Enquiries > Marketplace > {{ pageTitle }}</h2>

        <supply-fit-enquiry-block :enquiryItems="enquiryItems"></supply-fit-enquiry-block>

<!--        <h2>Enquiry Quotes</h2>-->

        <supply-fit-quote-block :quoteItems="quoteItems"></supply-fit-quote-block>

        <div style="position: relative; padding-top: 1em">
            <v-dialog v-model="dialog" max-width="500px">
                <template v-slot:activator="{ on, attrs }">
                    <v-btn
                        v-if="isSubContractor()"
                        class="m-2"
                        color="primary"
                        dark
                        style="left: 0 !important; top: 0 !important;"
                        v-bind="attrs"
                        v-on="on"
                    >Upload</v-btn>
                </template>
                <supply-fit-progress-form
                    v-model="editedItem"
                    :quoteId="quoteId"
                    v-on:cancel="close"
                    v-on:saved="updated"
                />
            </v-dialog>

            <v-data-table
                :headers="headers"
                :items="items"
                :loading="loader"
                :options.sync="options"
                :server-items-length="serverItemsLength"
                item-key="id"
                loading-text="Loading... Please wait"
                mobile-breakpoint="1000"
            >
                <template v-slot:item.date="{ item }">
                    {{ item.date | formatDate }}
                </template>

                <template v-slot:item.attachments="{ item }">
                    <a href="#" v-on:click="(e) => { e.preventDefault(); dialogUploadItem = item; dialogUploads = true; }">
                        <v-icon>mdi-download</v-icon>
                    </a>
                </template>

                <template v-slot:item.completed_at="{ item }">
                    <v-checkbox
                        :input-value="!!item.completed_at"
                        :label="item.completed_at | formatDateTime"
                        readonly
                        v-on:click="switchCompleted(item)"
                    ></v-checkbox>
                </template>

                <template v-slot:item.actions="{ item }">
                    <v-btn
                        class="mr-2"
                        color="primary"
                        small
                        @click="showNotes(item)"
                    >Notes
                    </v-btn>
                </template>

            </v-data-table>
        </div>

        <v-dialog v-model="dialogUploads" max-width="500px">
            <attachments-dialog
                :parentId="dialogUploadItem.id"
                type="SupplyFitEnquiryQuote"
                title="Quote"
                @close="dialogUploads=false"
                :userCanEdit="user().id === dialogUploadItem?.user_id"
            ></attachments-dialog>
        </v-dialog>

        <v-dialog v-model="notesDialog" max-width="800px">
            <notes-dialog :parentId="notesDialogId" :type="0" v-on:cancel="closeNotes"></notes-dialog>
        </v-dialog>

    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions.js";
import AttachmentsDialog from "../components/AttachmentsDialog";
import SupplyFitEnquiryBlock from "../components/SupplyFitEnquiryBlock";
import SupplyFitQuoteBlock from "../components/SupplyFitQuoteBlock.vue";
import SupplyFitQuoteForm from "../components/SupplyFitQuoteForm.vue";
import SupplyFitProgressForm from "../components/SupplyFitProgressForm.vue";
import NotesDialog from "../components/NotesDialog.vue";

export default {
    name: 'SupplyFitQuoteProgress',
    components: {
        NotesDialog,
        SupplyFitProgressForm,
        SupplyFitQuoteForm,
        AttachmentsDialog,
        SupplyFitEnquiryBlock,
        SupplyFitQuoteBlock,
    },
    data() {
        return {
            enquiryId: 0,
            quoteId: 0,
            enquiry: null,
            quote: null,
            quoteItems: [],
            enquiryItems: [],
            items: [],
            expanded: [],
            serverItemsLength: 0,
            loader: false,
            singleExpand: true,
            dialog: false,
            dialogUploads: false,
            dialogUploadItem: {},
            notesDialog: false,
            notesDialogId: null,
            editedItem: {
                id: null,
                date: '',
                comment: '',
            },
            headers: [
                {
                    text: "ID",
                    value: "id",
                    sortable: true,
                },
                {
                    text: "Comments",
                    value: "comment",
                    sortable: true,
                },
                {
                    text: "Date",
                    value: "date",
                    sortable: true,
                },
                {
                    text: 'Documents',
                    value: 'attachments',
                    sortable: false,
                },
                {
                    text: 'Completed At',
                    value: 'completed_at',
                    sortable: true,
                },
                {
                    text: '',
                    value: 'actions',
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

        self.enquiryId = self.$route.query.enquiry_id;
        self.quoteId = self.$route.query.quote_id;

        if (self.enquiryId) {
            await self.loadEnquiry();
        }

        if (self.quoteId) {
            await self.loadQuote();
        }

        await self.load();
    },

    watch: {
        options: {
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
        pageTitle() {
            return this.isSubContractor() ? "Manage Progress" : "View Progress";
        }
    },

    methods: {
        isContractor() {
            return permissions.hasRole(permissions.role_contractor);
        },

        isSubContractor() {
            return permissions.hasRole(permissions.role_user);
        },

        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },

        canAskQuestion() {
            return permissions.can(permissions.create_enquiry_sf);
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        async loadEnquiry() {
            const self = this;
            self.loader = true;

            try {
                const response = await api.loadSupplyFitEnquiries({'id': self.enquiryId});

                self.enquiryItems = response.data.data;
            } catch (e) {
                console.log(e);
            }

            self.loader = false;
        },

        async loadQuote() {
            const self = this;
            self.loader = true;

            try {
                const response = await api.loadSupplyFitEnquiryQuotes(self.enquiryId,{'quoteId': self.quoteId, 'radius': 0});

                self.quoteItems = response.data.data;
            } catch (e) {
                console.log(e);
            }

            self.loader = false;
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
                radius: self.radius,
            };

            try {
                const response = await api.loadQuoteProgress(this.quoteId, 1, params);

                self.items = response.data.data;
                self.serverItemsLength = response.data.total;
            } catch (e) {
                console.log(e);
            }

            self.loader = false;
        },

        updated() {
            const self = this;

            this.dialog = false;

            self.load();
        },

        close() {
            this.dialog = false;
        },

        switchCompleted(item) {
            this.completeItem(item);
        },

        async completeItem(item) {
            const self = this;

            if (item.completed_at) {
                if (confirm("Are you sure you want to mark this item as not completed?")) {
                    self.loader = true;
                    await api.uncompleteQuoteProgress(item.id);
                    await self.load();
                    self.loader = false;
                }

                return;
            }

            if (confirm("Are you sure you want to complete this item?")) {
                self.loader = true;
                await api.completeQuoteProgress(item.id)
                await self.load();
                self.loader = false;
            }
        },

        showNotes(item) {
            this.notesDialogId = item.id;
            this.notesDialog = true;
        },

        closeNotes() {
            const self = this;

            self.notesDialog = false;
        },
    },
};
</script>

<style>
</style>
