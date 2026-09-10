<template>
    <div style="margin: 1em">
<!--        <h2>Users</h2>-->

        <v-dialog v-model="convertUserDialog" max-width="500px">
            <v-card>
                <v-card-title>
                    <span class="headline">Convert User</span>
                </v-card-title>

                <v-card-text>
                    <v-container>
                        <v-row>
                            <v-col>
                                <v-text-field
                                    v-model="convertForm.username"
                                    :error="convertErrors.username"
                                    label="Username"
                                    type="text"
                                    autocomplete="off"
                                    required
                                ></v-text-field>

                                <v-text-field
                                    v-model="convertForm.password"
                                    :error="convertErrors.password"
                                    label="Password"
                                    type="password"
                                    autocomplete="off"
                                    required
                                ></v-text-field>

                                <v-text-field
                                    v-model="convertForm.email"
                                    :error="convertErrors.email"
                                    label="Email"
                                    type="email"
                                    autocomplete="off"
                                    required
                                ></v-text-field>
                            </v-col>
                        </v-row>
                    </v-container>
                </v-card-text>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="blue darken-1" text @click="convertUserDialog = false">Cancel</v-btn>
                    <v-btn color="blue darken-1" text @click="convertUser()">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <div style="position: relative; padding-top: 1em">
            <v-data-table
                item-key="id"
                :headers="headers"
                :items="items"
                :server-items-length="serverItemsLength"
                :loading="loader"
                :options.sync="options"
                loading-text="Loading... Please wait"
                mobile-breakpoint="1000"
            >
                <template v-slot:top>
                    <v-row>
                        <v-col>
                            <v-dialog v-model="dialog" max-width="800px">
                                <template v-slot:activator="{ on, attrs }">
                                    <v-btn
                                           color="primary"
                                           class="m-2"
                                           v-bind="attrs" v-on="on"
                                           @click="addUser()"
                                    >Add User</v-btn>
                                </template>

                                <supply-chain-user-form
                                    :key="userFormKey"
                                    :value="editedItem"
                                    @saved="updated()"
                                    @cancel="close()"
                                />
                            </v-dialog>

                            <v-dialog v-model="importUsersDialog" max-width="800px">
                                <template v-slot:activator="{ on, attrs }">
                                    <v-btn
                                        color="primary"
                                        class="m-2"
                                        v-bind="attrs" v-on="on"
                                        @click="importUsers()"
                                    >Import Users</v-btn>
                                </template>

                                <supply-chain-import-users
                                    @saved="updated()"
                                    @cancel="close()"
                                ></supply-chain-import-users>
                            </v-dialog>
                        </v-col>

                        <v-col>
                            <v-autocomplete
                                v-model="filters.companyNames"
                                :items="companyNames"
                                :loading="filtersLoader"
                                :search-input.sync="searchCompanyName"
                                chips
                                dense
                                label="Business Name"
                                multiple
                                outlined
                                small-chips
                            ></v-autocomplete>
                        </v-col>

                        <v-col>
                            <v-text-field v-model="filters.search" dense outlined label="Search"></v-text-field>
                        </v-col>
                    </v-row>
                </template>

                <template v-slot:item.full_name="{ item }">
                    {{ getUserFullName(item) }}
                </template>

                <template v-slot:item.converted_at="{ item }">
                    {{ item.converted_at | formatDateTime }}
                </template>

                <template v-slot:item.actions="{ item }">
                    <div class="dropdown actions-dropdown">
                        <button class="btn" type="button" data-toggle="dropdown">
                            <v-icon>mdi-dots-horizontal</v-icon>
                        </button>
                        <ul class="dropdown-menu">
                            <li @click="editItem(item)">Edit</li>
                            <li v-if="item.converted_at === null" @click="showConvertUserDialog(item)">Convert</li>
                            <li @click="deleteItem(item)">Delete</li>
                        </ul>
                    </div>
                </template>
            </v-data-table>
        </div>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions.js";
import SupplyChainUserForm from "../components/SupplyChainUserForm.vue";
import LogisticsEnquiryForm from "../components/LogisticsEnquiryForm.vue";
import SupplyChainImportUsers from "../components/SupplyChainImportUsers.vue";

export default {
    props: [],
    components: {
        SupplyChainImportUsers,
        LogisticsEnquiryForm,
        SupplyChainUserForm
    },
    data() {
        return {
            loader: false,
            items: [],
            companyNames: [],
            billingUsers: [],
            serverItemsLength: 0,
            dialog: false,
            importUsersDialog: false,
            convertUserDialog: false,
            editedIndex: -1,
            searchCompanyName: "",
            searchBillingUser: "",
            filtersLoader: false,
            userFormKey: 0,
            isEdit: false,
            headers: [
                {
                    text: "ID",
                    value: "id",
                    sortable: true,
                },
                {
                    text: "User Type",
                    value: "role_name",
                    sortable: true,
                },
                {
                    text: "Full Name",
                    value: "full_name",
                    sortable: true,
                },
                {
                    text: "Business Name",
                    value: "business_name",
                    sortable: true,
                },
                {
                    text: "Email",
                    value: "email",
                    sortable: true,
                },
                {
                    text: "Phone",
                    value: "phone",
                    sortable: true,
                },
                {
                    text: "Postcode",
                    value: "postcode",
                    sortable: true,
                },
                {
                    text: "Town/City",
                    value: "city",
                    sortable: true,
                },
                {
                    text: "Address 1",
                    value: "addr_line_1",
                    sortable: true,
                },
                {
                    text: "Address 2",
                    value: "addr_line_2",
                    sortable: true,
                },
                {
                    text: "Converted At",
                    value: "converted_at",
                    sortable: true,
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
            },
            filters: {
                search: null,
                companyNames: [],
            },
            editedItem: {
                id: null,
                role_id: null,
                first_name: "",
                last_name: "",
                business_name: "",
                email: "",
                phone: "",
                addr_line_1: "",
                addr_line_2: "",
                city: "",
                country: "",
                postcode: "",
            },
            defaultItem: {
                id: null,
                role_id: null,
                first_name: "",
                last_name: "",
                business_name: "",
                email: "",
                phone: "",
                addr_line_1: "",
                addr_line_2: "",
                city: "",
                country: "",
                postcode: "",
            },
            convertForm: {
                id: null,
                username: null,
                password: null,
                email: null
            },
            convertErrors: {
                username: false,
                password: false,
                email: false
            }
        };
    },

    async mounted() {
        const self = this;

        //await self.load();
        await self.loadCompanyNames();
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

        'filters.search'(val) {
            if (val.length > 2) {
                this.load();
            } else if (val.length === 0) {
                this.load();
            }
        },

        'filters.companyNames'(val) {
            this.load();
        }
    },

    computed: {
        formTitle() {
            return this.editedIndex === -1 ? "New User" : "Edit User";
        },
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
                search: self.filters.search,
                companyNames: self.filters.companyNames
            };

            try {
                const response = await api.loadSupplyChainUsers(params);

                self.items = response.data.data;
                self.serverItemsLength = response.data.total;
            } catch (e) {}

            self.loader = false;
        },

        async loadCompanyNames() {
            const self = this;
            self.loader = true;

            const response = await api.loadSupplyChainCompanyNames();
            self.companyNames = response.data;

            self.loader = false;
        },

        editItem(item) {
            const self = this;

            self.isEdit = true;

            self.editedIndex = self.items.indexOf(item);
            self.editedItem = Object.assign({}, item);
            self.$nextTick(() => {
                self.userFormKey++;
                self.dialog = true;
            });
        },

        addUser() {
            const self = this;

            self.editedItem = self.defaultItem;
            self.userFormKey++;

            self.isEdit = false;
            self.dialog = true;
        },

        importUsers() {
            const self = this;

            self.importUsersDialog = true;
        },

        async deleteItem(user) {
            const self = this;

            if (confirm("Are you sure you want to delete this user?")) {
                self.loader = true;
                await api.deleteSupplyChainUser(user.id);
                await self.load();
                self.loader = false;
            }
        },

        close() {
            const self = this;

            self.dialog = false;
            self.importUsersDialog = false;

            self.$nextTick(() => {
                self.editedItem = Object.assign({}, self.defaultItem);
                self.editedIndex = -1;
            });
        },

        async updated() {
            const self = this;

            self.dialog = false;
            self.importUsersDialog = false;

            this.$nextTick(() => {
                self.editedItem = Object.assign({}, self.defaultItem);
                self.editedIndex = -1;
            });

            await self.load();
            await self.loadCompanyNames();
        },

        getUserFullName(item) {
            let firstName = item.first_name ?? '';
            let lastName = item.last_name ?? '';

            return `${firstName} ${lastName}`;
        },

        showConvertUserDialog(item) {
            const self = this;

            self.convertForm.id = item.id;
            self.convertForm.email = item.email;
            self.convertUserDialog = true;
        },

        convertUser(id) {
            const self = this;

            api.convertSupplyChainUser(self.convertForm).then(async (response) => {
                self.$store.commit("showSnackbar", {
                    message: `Success! Account details sent to ${self.convertForm.email}`,
                    color: "success",
                });

                await self.load();

                self.convertUserDialog = false;
            }).catch((error) => {
                console.log(error);
                let errorMessage = (typeof error.response?.data === 'string') ? error.response.data : 'An error occurred';

                self.$store.commit("showSnackbar", {
                    message: errorMessage,
                    color: "error",
                });
            });
        }
    },
};
</script>

<style>
</style>
