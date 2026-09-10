<template>
    <div style="margin: 1em">
<!--        <h2>Users</h2>-->

        <div style="position: relative; padding-top: 1em">
            <v-data-table
                item-key="id"
                :headers="headers"
                :items="items"
                :server-items-length="serverItemsLength"
                :loading="loader"
                :options.sync="options"
                loading-text="Loading... Please wait"
                :item-class="itemRowBackground"
                mobile-breakpoint="1000"
            >
                <template v-slot:top>
                    <v-row>
                        <v-col>
                            <v-dialog v-model="dialog" max-width="800px">
                                <template v-slot:activator="{ on, attrs }">
                                    <v-btn
                                        color="primary"
                                        v-bind="attrs"
                                        v-on="on"
                                        @click="addUser()"
                                    >
                                        New User
                                    </v-btn>
                                </template>

                                <user-form
                                    :visible="dialog"
                                    :billing_user_id="editedItem.billing_user_id || billing_user_id"
                                    v-model="editedItem"
                                    v-on:save="save"
                                    v-on:cancel="close"
                                    :title="formTitle"
                                    :userList = "items"
                                    :key = "reloadFormKey"
                                    @update="updateEditedUser($event)"
                                    :isEdit="isEdit"
                                />
                            </v-dialog>
                        </v-col>

                        <v-col>
                            <v-text-field
                                v-model="filters.search"
                                label="Search"
                                dense
                                outlined
                                hide-details
                            ></v-text-field>
                        </v-col>

                        <v-col v-if="isAdmin()">
                            <v-autocomplete
                                :loading="filtersLoader"
                                :search-input.sync="searchBillingUser"
                                v-model="filters.billingUserId"
                                :items="billingUsers"
                                label="Filter by Billing User"
                                item-text="first_name"
                                item-value="id"
                                outlined
                                dense
                                hide-details
                            ></v-autocomplete>
                        </v-col>
                    </v-row>
                </template>
                <template v-slot:item.actions="{ item }">
                    <div class="text-center">
                        <v-btn color="primary" small class="mr-2" v-if="isBillingUser(item)"
                               @click="uploadUsersFromFile(item)">Import Users From CSV
                        </v-btn>

                        <v-btn color="primary" small class="mr-2" v-if="canSeeNotes(item)" @click="showNotes(item)">
                            Notes
                        </v-btn>

                        <v-btn color="primary" small class="mr-2" @click="editItem(item)">Edit</v-btn>

                        <login-as-button :user-id="item.id" v-if="isCurrentAdminOrBillingUser()"></login-as-button>

                        <v-btn color="primary" small @click="deleteItem(item)" v-if="allowDelete(item)">Delete</v-btn>

                        <v-btn color="primary" small class="mr-2" @click="downloadReport(item)"
                               v-if="isCurrentAdminOrBillingUser() && userIsSupplier(item)">Report</v-btn>

                        <v-btn color="primary" small class="mr-2" @click="resetPassword(item.username)" v-if="isAdmin()">Reset Password</v-btn>

                        <v-btn color="primary" small class="mr-2" @click="updateQuestionnaireSessions(item.id)" v-if="isAdmin()">Update Questionnaire Sessions</v-btn>
                    </div>
                </template>
            </v-data-table>
        </div>

        <v-dialog v-model="notesDialog" max-width="800px">
            <user-notes-dialog :userId="notesDialogUserId" v-on:cancel="closeNotes"></user-notes-dialog>
        </v-dialog>

        <v-dialog v-model="importUsersDialog" max-width="800px">
            <import-users-from-csv :billingUserId="importUsersDialogBillingId" v-on:cancel="importUsersDialog=false;"
                                   v-on:saved="load()"></import-users-from-csv>
        </v-dialog>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions.js";
import UserForm from "../components/UserForm";
import UserNotesDialog from "../components/UserNotesDialog";
import ImportUsersFromCsv from "../components/ImportUsersFromCsv";
import LoginAsButton from "../components/LoginAsButton.vue";
import * as XLSX from 'xlsx';

export default {
    props: ["billing_user_id"],
    components: {
        LoginAsButton,
        ImportUsersFromCsv,
        UserForm,
        UserNotesDialog
    },
    data() {
        return {

            loader: false,
            headers: [
                {
                    text: "ID",
                    value: "id",
                    sortable: true,
                },
                {
                    text: "First Name",
                    value: "first_name",
                    sortable: true,
                },
                {
                    text: "Last Name",
                    value: "last_name",
                    sortable: true,
                },
                {
                    text: "Email",
                    value: "email",
                    sortable: true,
                },
                {
                    text: "Role",
                    value: "role_name",
                    sortable: true,
                },
                {
                    text: "Subscription",
                    value: "subs_name",
                    sortable: false,
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
            notesDialog: false,
            notesDialogUserId: undefined,
            importUsersDialog: false,
            importUsersDialogBillingId: undefined,
            editedIndex: -1,
            searchBillingUser: "",
            filtersLoader: false,
            filters: {
                billingUserId: null,
                search: null,
            },
            billingUsers: [],
            reloadFormKey: 0,
            isEdit: false,
            editedItem: {
                is_preferred_supplier: false,
                billing_user_id: undefined,
                first_name: "",
                last_name: "",
                email: "",
                phone: "",
                city: "",
                country: "",
                addr_line_1: "",
                addr_line_2: "",
                role_ids: [],
                product_ids: [],
                password: "",
                postcode: "",
                is_billing_user: false,
                role_name: "",
                is_global: false,
                is_gold_account: false,
                is_test_account: false,
                is_sme: false,
                can_check_competency: false,
                manager_type: "",
                branch_id: "",
                trial_ends: "",
                pipeline_of_work: "",
                potential_users: "",
                turnover: "",
                number_of_employees: "",
                pain_points: "",
                onboarding_call: "",
            },
            defaultItem: {
                is_preferred_supplier: false,
                billing_user_id: null,
                first_name: "",
                last_name: "",
                email: "",
                role_ids: [],
                product_ids: [],
                password: "",
                postcode: "",
                is_billing_user: false,
                role_name: "",
                phone: "",
                city: "",
                country: "",
                addr_line_1: "",
                addr_line_2: "",
                is_global: false,
                is_gold_account: false,
                is_test_account: false,
                is_sme: false,
                can_check_competency: false,
                manager_type: "",
                branch_id: "",
                trial_ends: "",
                pipeline_of_work: "",
                potential_users: "",
                turnover: "",
                number_of_employees: "",
                pain_points: "",
                onboarding_call: "",
            },
        };
    },

    mounted() {
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

        async searchBillingUser() {
            const self = this;
            if (self.filtersLoader) return;

            self.filtersLoader = true;

            const resp = await api.billingUserOptions(self.searchBillingUser)
            self.billingUsers = resp.data;
            self.filtersLoader = false;
        },

        // filters: {
        //     handler(n, o) {
        //         this.load();
        //     },
        //     deep: true,
        // }

        'filters.billingUserId'() {
            this.load();
        },

        'filters.search'(val) {
            clearTimeout(this._searchTimeout);
            this._searchTimeout = setTimeout(() => {
                if (val.length > 2) {
                    this.load();
                } else if (val.length === 0) {
                    this.load();
                }
            }, 400);
        }
    },

    computed: {
        formTitle() {
            return this.editedIndex === -1 ? "New User" : "Edit User";
        },
    },

    methods: {
        updateEditedUser(userData) {
            this.editedItem = userData;
        },

        toSupportPage() {
            this.$router.push({name: 'customer-support', params: {}});
        },

        isBillingUser(item) {
            for (let el of item.permissions) {
                if (String(el.slug) === String(permissions.manage_subscriptions)) {
                    return true;
                }
            }

            return false;
        },

        canSeeNotes(item) {
            return this.$store.getters.getSession.user.id !== item.id;
        },

        userIsSupplier(item) {
            return item.roles.filter((p) => {
                return p?.slug === 'billing_user' || p?.slug === 'company';
            }).length > 0;
        },

        isCurrentAdminOrBillingUser() {
            const user = this.$store?.getters?.getSession?.user

            if (this.isAdmin()) {
                return true;
            }

            let roles = (user?.roles ?? []).map((item) => item?.slug)

            return roles.join().toLowerCase().includes("billing_user");
        },

        isPartner() {
            if (typeof this.$store?.getters?.getSession?.user?.role_name != 'object') {
                return false;
            }

            let roles = this.$store?.getters?.getSession?.user?.role_name ?? []

            return roles.join().toLowerCase().includes("partner");
        },

        isAdmin() {
            if (typeof this.$store?.getters?.getSession?.user?.role_name != 'object') {
                return false;
            }

            let roles = this.$store?.getters?.getSession?.user?.role_name ?? []

            return roles.join().toLowerCase().includes("admin");
        },

        allowDelete(item) {
            return item.id !== this.$store.getters.getSession.user.id;
        },

        itemRowBackground: function (item) {
            return item.is_preferred_supplier ? "highlight-row" : "";
        },

        async downloadReport(item) {
            const report = await api.supplierReport(item.id);

            const data = XLSX.utils.json_to_sheet(report.data.items, {})

            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, data, 'data')
            XLSX.writeFile(wb,'demo.xlsx')
        },

        async load() {
            const self = this;
            self.loader = true;

            let billUserId = self.billing_user_id
            if (self.filters.billingUserId) {
                billUserId = self.filters.billingUserId;
            }

            let params = {
                ...self.options,
                page: self.options.page,
                itemsPerPage: self.options.itemsPerPage,
                sortBy: self.options.sortBy[0],
                sortDesc: self.options.sortDesc[0] ? 1 : 0,
                billing_user_id: billUserId,
                search: self.filters.search,
            };

            // if searching then omit paging params
            if (self.filters.search && self.filters.search.length > 0) {
                params.page = 1;
                params.itemsPerPage = -1;
            }

            try {
                const response = await api.loadUsers(params)
                self.items = response.data.data;
                self.serverItemsLength = response.data.total;
            } catch (e) {}

            self.reloadForm();
            self.loader = false;
        },

        editItem(item) {
            const self = this;

            self.isEdit = true;

            self.editedIndex = self.items.indexOf(item);
            self.editedItem = Object.assign({}, item);
            self.editedItem.is_billing_user = self.isBillingUser(item)
            self.$nextTick(() => {
                self.dialog = true;
            });
        },

        addUser() {
            const self = this;

            if (!self.isAdmin() && !self.isPartner()) {
                self.toSupportPage();
                return;
            }

            self.isEdit = false;
        },

        uploadUsersFromFile(item) {
            const self = this;
            self.importUsersDialogBillingId = item.id;
            self.$nextTick(() => {
                self.importUsersDialog = true;
            });
        },

        showNotes(item) {
            this.notesDialogUserId = item.id;
            this.notesDialog = true;
        },

        async deleteItem(user) {
            const self = this;

            if (user.id === this.$store.getters.getSession.user.id) {
                alert("You can't delete your own account!");
                return;
            }

            if (confirm("Are you sure you want to delete this user?")) {
                self.loader = true;
                await api.deleteUser(user.id)
                await self.load();
                self.loader = false;
            }
        },

        close() {
            const self = this;

            self.dialog = false;
            self.$nextTick(() => {
                self.editedItem = Object.assign({}, self.defaultItem);
                self.editedIndex = -1;
            });
        },

        closeNotes() {
            const self = this;

            self.notesDialog = false;
            self.$nextTick(() => {
                self.notesDialogUserId = undefined;
            });
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

        reloadForm() {
            this.reloadFormKey++;
        },

        save(user) {
            const self = this;

            self.loader = true;

            let formData = new FormData();
            this.buildFormData(formData, user);

            api.storeUser(formData, user.id).then(async (response) => {
                self.$store.commit("showSnackbar", {
                    message: "Success!",
                    color: "success",
                });

                await self.load();

                self.close();
            }).catch((error) => {
                console.log(error);
                self.$store.commit("showSnackbar", {
                    message: error.response.data.message,
                    color: "error",
                });
            });

            self.loader = false;
        },

        resetPassword(username) {
            const self = this;

            if (confirm('Are you sure you want to reset this password?')) {
                api.passwordReset(username).then((response) => {
                    self.$store.commit("showSnackbar", {
                        message: `Success: ${response.data.success}`,
                        color: "success",
                    });
                }).catch((error) => {
                    self.$store.commit("showSnackbar", {
                        message: `Error: ${error.response.data.error}`,
                        color: "error",
                    });
                });
            }
        },

        updateQuestionnaireSessions(userId) {
            const self = this;

            api.updateQuestionnaireSessions(userId).then((response) => {
                self.$store.commit("showSnackbar", {
                    message: `Success: ${response.data.success}`,
                    color: "success",
                });
            }).catch((error) => {
                self.$store.commit("showSnackbar", {
                    message: `Error: ${error.response.data.error}`,
                })
            })
        }
    },
};
</script>

<style>
</style>
