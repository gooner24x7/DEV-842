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
<!--                            <login-as-button :user-id="item.id"></login-as-button>-->
                            <v-btn color="primary" small @click="deleteItem(item)" v-if="canDelete(item)">Delete</v-btn>
                        </div>
                    </template>
                </v-data-table>
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="4">
                <v-autocomplete
                    v-model="selectedRole"
                    :items="roleOptions"
                    label="Select Role"
                    item-text="name"
                    item-value="slug"
                    hide-details
                    outlined
                    dense
                ></v-autocomplete>
            </v-col>
            <v-col cols="4">
                <v-autocomplete
                    :loading="searchLoader"
                    :search-input.sync="searchUsers"
                    v-model="form.user_id"
                    :items="users"
                    :disabled="selectedRole == null"
                    label="Find User"
                    item-text="first_name"
                    item-value="id"
                    hide-details
                    outlined
                    dense
                ></v-autocomplete>
            </v-col>
            <v-col cols="4">
                <v-autocomplete
                    v-model="form.stage"
                    :items="stageOptions"
                    :disabled="selectedRole !== 'consultant'"
                    label="Select Stage"
                    hide-details
                    outlined
                    dense
                ></v-autocomplete>
            </v-col>
        </v-row>
        <v-row>
            <v-col>
                <v-btn color="primary" @click="addUser" :disabled="form.user_id === null">
                    Assign User
                </v-btn>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import api from "../common/api";
import LoginAsButton from "./LoginAsButton.vue";
import permissions from "../common/permissions";

export default {
    name: 'UserAccessTable',
    components: {LoginAsButton},
    props: ["projectId"],
    data() {
        return {
            loader: false,
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
            roleOptions: [
                {slug: permissions.role_contractor, name: 'Main Contractor'},
                {slug: permissions.role_consultant, name: 'Consultant'},
                {slug: permissions.role_framework, name: 'Framework'}
            ],
            stageOptions: [
                {value: 1, text: 'Stage 1'},
                {value: 3, text: 'Stage 3'},
            ],
            selectedRole: null,
            form: {
                user_id: null,
                stage: null,
                role: null,
            },
            users: [],
            searchUsers: "",
            searchLoader: false,
            headers: [
                {
                    text: "ID",
                    value: "id",
                    sortable: true,
                },
                {
                    text: "Username",
                    value: "username",
                    sortable: true,
                },
                {
                    text: "Name",
                    value: "first_name",
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
                    text: "Stage",
                    value: "stage",
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
        self.filterRoleOptions();
    },
    watch: {
        user(val) {
            const self = this;

            self.load();
        },
        selectedRole(val) {
            const self = this;

            self.form.role = val;
            self.loadUsers();
        },
    },
    methods: {
        load() {
            const self = this;

            if (self.projectId == null) {
                return;
            }

            self.loader = true;

            api.getProjectUsers(self.projectId)
                .then(response => {

                    self.items = response.data;
                    self.serverItemsLength = response.data.total;
                    self.loader = false;
                })
                .catch(err => {
                    console.log(err);
                })
                .finally(() => {
                    self.loader = false;
                })
        },
        loadUsers() {
            const self = this;

            api.getUsers({role: self.selectedRole})
                .then((resp) => {
                    self.users = resp.data;
                })
                .catch(err => {
                    console.log(err);
                });
        },
        addUser() {
            const self = this;

            api.createProjectUserAccess(self.projectId, self.form)
                .then(resp => {
                    self.load();
                    this.$emit('assigned');
                })
                .catch( err => {
                    console.log(err);

                    self.$store.commit("showSnackbar", {
                        message: err.response.data ?? "An error occurred",
                        color: "error",
                    });
                });
        },
        deleteItem(item) {
            const self = this;

            api.deleteProjectUserAccess(self.projectId, item.id)
                .then(resp => {
                    self.load();
                })
                .catch( err => {
                    console.log(err);
                });
        },
        canDelete(item) {
            return item.id !== this.$store.getters.getSession.user.id;
        },
        filterRoleOptions() {
            const self = this;

            self.roleOptions = self.roleOptions.filter(role => {
                return !permissions.hasRole(role.slug);
            });
        }
    }
}
</script>

<style scoped>

</style>
