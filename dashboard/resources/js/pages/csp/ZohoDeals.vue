<template>
    <div style="margin: 1em">
        <div style="position: relative; padding-top: 1em">

            <v-row>
                <v-col>
                    <v-text-field v-model="filters.search" dense outlined label="Search"></v-text-field>
                </v-col>
                <v-col>
                    <v-btn class="primary" :disabled="disableSearch" v-on:click="load()">Search</v-btn>
                </v-col>
            </v-row>

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

                <template v-slot:item.Contract_Sent="{ item }">
                    {{ item.Contract_Sent | formatDate }}
                </template>

                <template v-slot:item.Contract_Signed="{ item }">
                    {{ item.Contract_Signed | formatDate }}
                </template>

                <template v-slot:item.Description_1="{ item }">
                    <v-btn class="primary small" v-on:click="editItem(item)">Edit</v-btn>
                </template>

            </v-data-table>

            <v-dialog v-model="dialogEditNotes" max-width="800px">
                <v-card>
                    <v-card-title>
                        <span class="headline">Notes</span>
                    </v-card-title>

                    <v-card-text>
                        <v-textarea v-model="editDescription" label="Description" auto-grow></v-textarea>
                    </v-card-text>

                    <v-card-actions>
                        <v-btn color="blue darken-1" text @click="dialogEditNotes=false">Cancel</v-btn>
                        <v-btn color="blue darken-1" text @click="updateDescription">Save</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

        </div>
    </div>
</template>
<script>
import api from "../../common/api.js";
import permissions from "../../common/permissions.js";

export default {
    name: "ZohoDeals",
    props: [],
    components: {},
    data() {
        return {
            loader: false,
            items: [],
            serverItemsLength: 0,
            disableSearch: true,
            dialogEditNotes: false,
            editId: null,
            editDescription: '',
            filters: {
                search: null,
            },
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
                    text: "Company Name",
                    value: "Account_Name.Account_Name",
                    sortable: true,
                },
                {
                    text: "User Type",
                    value: "User_Type",
                    sortable: true,
                },
                {
                    text: "Contact Name",
                    value: "Contact_Name.Full_Name",
                    sortable: true,
                },
                {
                    text: "Mobile Number",
                    value: "Contact_Name.Mobile",
                    sortable: true,
                },
                {
                    text: "Email",
                    value: "Contact_Name.Email",
                    sortable: true,
                },
                {
                    text: "Contract Sent",
                    value: "Contract_Sent",
                    sortable: true,
                },
                {
                    text: "Contract Signed",
                    value: "Contract_Signed",
                    sortable: true,
                },
                {
                    text: "Notes",
                    value: "Description_1",
                    sortable: false,
                },
            ]
        }
    },

    watch: {
        'filters.search': {
            handler(n, o) {
                this.disableSearch = !(n.length >= 3);
            },
        },
        'options.page': {
            handler(n, o) {
                this.load();
            }
        },
        'options.itemsPerPage': {
            handler(n, o) {
                this.load();
            }
        },
        'options.sortBy': {
            handler(n, o) {
                if (n[0] === o[0]) {
                    return;
                }

                this.load();
            }
        },
        'options.sortDesc': {
            handler(n, o) {
                if (n[0] === o[0]) {
                    return;
                }

                this.load();
            }
        },
    },

    methods: {
        editItem(item) {
            const self = this;

            self.dialogEditNotes = true;
            self.editId = item.id;
            self.editDescription = item.Description_1;
        },
        updateDescription() {
            const self = this;

            let params = {
                id: self.editId,
                Description_1: self.editDescription
            }

            api.updateZohoDeals(params).then(response => {
                if (response.data) {
                    self.dialogEditNotes = false;
                }

                self.$store.commit("showSnackbar", {
                    message: "Success!",
                    color: "success",
                });

                self.load();
            }).catch(err => {
                console.log(err);
                self.$store.commit("showSnackbar", {
                    message: "An error occurred updating the deal",
                    color: "error",
                });
            });
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
                search: self.filters.search
            };

            api.getZohoDeals(params)
                .then(response => {
                    self.items = response.data.data;
                    self.serverItemsLength = response.data.total;
                    self.loader = false;
                })
                .catch(err => {
                    console.log(err);
                })
                .finally(() => {
                    self.loader = false;
                })
        }
    },

    mounted() {
        const self = this;

        self.load();
    },
}
</script>

<style scoped>

</style>
