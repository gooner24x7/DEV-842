<template>
    <div style="margin: 1em">
        <h2>Permissions Manager</h2>

        <div style="position: relative; padding-top: 1em">
            <v-row>
                <v-col cols="12" md="6">
                    <v-data-table
                        v-model="selectedRoles"
                        item-key="id"
                        :headers="headers"
                        :items="items"
                        :server-items-length="serverItemsLength"
                        :loading="loader"
                        :options.sync="options"
                        :item-class="itemRowBackground"
                        loading-text="Loading... Please wait"
                        show-select
                        single-select
                    >
                        <template v-slot:item.created_at="{ item }">
                            {{ item.created_at | formatDateTime }}
                        </template>

                        <template v-slot:item.actions="{ item }">
                            <div class="text-center">
<!--                                <v-btn color="primary" small class="mr-2" @click="editItem(item)">Edit</v-btn>-->
                            </div>
                        </template>
                    </v-data-table>
                </v-col>
                <v-col cols="12" md="6">
                    <v-card style="margin-top: 80px;" v-if="selectedRoles.length > 0">
                        <v-form
                            ref="permissions-form"
                            @submit.prevent="savePermissions"
                        >
                            <v-card-title>Edit Permissions</v-card-title>
                            <v-card-text>

                                    <div class="categories-container">
                                        <template v-for="(items, category) in permissionItems">
                                            <div class="category-item">
                                                <h5 class="category-header">{{ category }}</h5>
                                                <template v-for="(item, index) in items">
                                                    <v-switch
                                                        v-model="item.enabled"
                                                        :label="item.name"
                                                        :ripple="false"
                                                        hide-details
                                                        inset
                                                        dense
                                                    ></v-switch>
                                                </template>
                                            </div>
                                        </template>
                                    </div>

                            </v-card-text>
                            <v-card-actions>
                                <v-btn color="primary" small class="mr-2" type="submit">Save</v-btn>
                                <v-btn small class="mr-2" @click="resetPermissions">Reset</v-btn>
                            </v-card-actions>
                        </v-form>
                    </v-card>

                </v-col>
            </v-row>
        </div>

    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions.js";
import * as XLSX from 'xlsx';

export default {
    name: "PermissionsManager",
    props: [],
    components: {},
    data() {
        return {
            loader: false,
            selectedRoles: [],
            items: [],
            permissionItems: [],
            serverItemsLength: 0,
            headers: [
                {
                    text: "ID",
                    value: "id",
                    sortable: true,
                },
                {
                    text: "Name",
                    value: "name",
                    sortable: true,
                },
                {
                    text: "Date Created",
                    value: "created_at",
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
                itemsPerPage: 15,
                sortBy: ["id"],
                sortDesc: [false],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false,
            },
        };
    },

    mounted() {
        const self = this;
    },

    watch: {
        options: {
            handler(n, o) {
                this.load();
            },
            deep: true,
        },
        selectedRoles(val) {
            console.log(val);

            let roleId = this.selectedRoles[0]?.id;

            if (roleId === null) {
                return;
            }

            this.loadPermissions(roleId);
        }
    },

    computed: {

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
            };

            const response = await api.loadRoles(params);

            self.items = response.data.data;
            self.serverItemsLength = response.data.total;

            self.loader = false;

        },
        async loadPermissions(roleId = null) {
            const self = this;

            const response = await api.loadPermissions({role_id: roleId});

            self.permissionItems = response.data;
        },
        savePermissions() {
            const self = this;

            let roleId = self.selectedRoles[0]?.id;

            if (roleId === null) {
                return;
            }

            api.updateRolePermissions(roleId, self.permissionItems)
                .then(response => {
                    self.$store.commit("showSnackbar", {
                        message: "Permissions updated",
                        color: "success",
                    });
                })
                .catch(err => {
                    console.log(err);
                    self.$store.commit("showSnackbar", {
                        message: "An error occurred",
                        color: "error",
                    });
                });
        },
        resetPermissions() {
            const self = this;

            let roleId = self.selectedRoles[0]?.id;

            if (roleId === null) {
                return;
            }

            self.loadPermissions(roleId);
        },
        itemRowBackground: function (item) {
            return (this.selectedRoles[0]?.id === item.id) ? 'grey-row mnrow' : 'mnrow';
        },
        editItem(item) {

        },
    },
};
</script>

<style>
.categories-container {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    gap: 10px;
}

.category-header {
    margin-bottom: 0;
}

.category-item {
    padding: 20px;
    border: 1px solid lightgrey;
    border-radius: 5px;
    min-width: 300px;
}

.category-item .v-label {
    font-size: 14px;
    line-height: 16px;
}
</style>
