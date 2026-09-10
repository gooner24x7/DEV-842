<template>
    <v-container>
        <v-row>
            <v-col cols="12">
                <v-data-table
                    item-key="id"
                    class="elevation-1"
                    :headers="headers"
                    :items="items"
                    :server-items-length="serverItemsLength"
                    :loading="loader"
                    :options.sync="options"
                    loading-text="Loading... Please wait"
                >
                    <template v-slot:item.actions="{ item }">
                        <div class="text-center">
                            <login-as-button :user-id="item.id"></login-as-button>
                            <v-btn color="primary" small @click="deleteItem(item)">Delete</v-btn>
                        </div>
                    </template>
                </v-data-table>
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="6">
                <v-autocomplete
                    class="m-2"
                    :loading="searchLoader"
                    :search-input.sync="searchUserName"
                    v-model="selectedUserId"
                    :items="userOptions"
                    outlined
                    dense
                    :label="getDropdownLabel()"
                    :item-text="getOptionText"
                    item-value="id"
                ></v-autocomplete>
            </v-col>
            <v-col cols="6">
                <v-btn color="primary" dark class="m-2" @click="addPreferred()">{{ getButtonLabel() }}</v-btn>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import api from "../common/api";
import LoginAsButton from "./LoginAsButton.vue";

export default {
    components: {LoginAsButton},
    props: ["user", "type"],
    data() {
        return {
            loader: false,
            searchLoader: false,
            selectedUserId: null,
            searchUserName: null,
            serverItemsLength: 0,
            items: [],
            userOptions: [],
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
                    text: "Postcode",
                    value: "postcode",
                    sortable: true,
                },
                {
                    text: "Email",
                    value: "email",
                    sortable: true,
                },
                {
                    text: "Phone Number",
                    value: "phone",
                    sortable: true,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: false,
                },
            ]
        }
    },
    mounted() {
        const self = this;

        self.load();
        self.loadOptions();
    },
    watch: {
        user(val) {
            const self = this;

            self.load();
        },
        searchCompanyUser() {
            const self = this;

            self.loadOptions();
        },
    },
    methods: {
        load() {
            const self = this;

            if (typeof self.user.id == 'undefined') {
                return;
            }

            self.loader = true;

            let params = {
                ...self.options,
                page: self.options.page,
                itemsPerPage: self.options.itemsPerPage,
                sortBy: self.options.sortBy[0],
                sortDesc: self.options.sortDesc[0] ? 1 : 0,
            };

            if (this.type === 1) {
                api.getPreferredSubcontractors(self.user.id, params)
                    .then(response => {
                        self.items = response.data.data;
                        self.serverItemsLength = response.data.total;
                        self.loader = false;
                    })
                    .catch(err => {
                        console.log(err);
                    });
            } else {
                api.getPreferredSuppliers(self.user.id, params)
                    .then(response => {
                        self.items = response.data.data;
                        self.serverItemsLength = response.data.total;
                        self.loader = false;
                    })
                    .catch(err => {
                        console.log(err);
                    });
            }

        },
        loadOptions() {
            const self = this;

            if (self.searchLoader) {
                return;
            }

            self.searchLoader = true;

            if (self.type === 1) {
                api.subcontractorsOptions(self.searchUserName)
                    .then((resp) => {
                        self.userOptions = resp.data;
                        self.searchLoader = false;
                    })
                    .catch(err => {
                        console.log(err)
                    });
            } else {
                api.suppliersOptions(self.searchUserName)
                    .then((resp) => {
                        self.userOptions = resp.data;
                        self.searchLoader = false;
                    })
                    .catch(err => {
                        console.log(err)
                    });
            }
        },
        addPreferred() {
            const self = this;

            if (this.type === 1) {
                api.storePreferredSubcontractor(self.user.id, self.selectedUserId)
                    .then(resp => {
                        self.load();
                        self.searchUserName = null;
                    })
                    .catch( err => {
                        console.log(err);
                    });
            } else {
                api.storePreferredSupplier(self.user.id, self.selectedUserId)
                    .then(resp => {
                        self.load();
                        self.searchUserName = null;
                    })
                    .catch( err => {
                        console.log(err);
                    });
            }
        },
        deleteItem(item) {
            const self = this;

            if (this.type === 1) {
                api.deletePreferredSubcontractor(self.user.id, item.id)
                    .then(resp => {
                        self.load();
                    })
                    .catch( err => {
                        console.log(err);
                    });
            } else {
                api.deletePreferredSupplier(self.user.id, item.id)
                    .then(resp => {
                        self.load();
                    })
                    .catch( err => {
                        console.log(err);
                    });
            }
        },
        getDropdownLabel() {
            if (this.type === 1) {
                return 'Find Preferred Subcontractor';
            }

            return 'Find Preferred Supplier';
        },
        getButtonLabel() {
            if (this.type === 1) {
                return 'Add Preferred Subcontractor';
            }

            return 'Add Preferred Supplier';
        },
        getOptionText(item) {
            return `${item.first_name} ${item.last_name}`;
        }
    }
}
</script>

<style scoped>

</style>
