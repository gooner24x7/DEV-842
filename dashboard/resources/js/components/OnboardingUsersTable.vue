<template>
    <div>
        <div class="out-header">
            <div class="out-header-left">
                <h4>{{ title }}</h4>
                <p>{{ subtitle }}</p>
            </div>
            <div class="out-header-right">
                <v-btn color="primary" @click="addItem" v-if="isBillingUser()">Add User</v-btn>
            </div>
        </div>

        <v-data-table
            :options="options"
            :headers="headers"
            :items="items"
            :loading="loader"
            item-key="id"
            loading-text="Loading... Please wait"
            class="data-table-mini"
            hide-default-footer
            dense
        >
            <template v-slot:item.tasks="{ item }">
                <v-chip x-small v-for="slug in item.tasks" :key="slug" class="mr-1">
                    {{ taskLabel(slug) }}
                </v-chip>
            </template>

            <template v-slot:item.actions="{ item }">
                <v-btn color="primary" small class="mr-2" @click="editItem(item)" v-if="isBillingUser()">Edit</v-btn>
<!--                <v-btn color="error" small @click="deleteItem(item)">Delete</v-btn>-->
            </template>
        </v-data-table>

        <v-dialog v-model="dialog" max-width="800px">
            <v-card>
                <v-card-title>
                    <h5 class="mb-3">{{ isEditing ? 'Edit User' : 'Add User' }}</h5>
                </v-card-title>
                <v-card-text>
                    <v-container>
                        <v-row>
                            <v-col md="6">
                                <v-text-field v-model="form.first_name" label="First Name" dense outlined hide-details></v-text-field>
                            </v-col>
                            <v-col md="6">
                                <v-text-field v-model="form.last_name" label="Last Name" dense outlined hide-details></v-text-field>
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col md="6">
                                <v-text-field v-model="form.job_title" label="Job Title" dense outlined hide-details></v-text-field>
                            </v-col>
                            <v-col md="6">
                                <v-text-field v-model="form.alias" label="Alias" dense outlined hide-details></v-text-field>
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col md="6">
                                <v-text-field v-model="form.email" label="Email" dense outlined hide-details></v-text-field>
                            </v-col>
                            <v-col md="6">
                                <v-text-field v-model="form.phone" label="Telephone" dense outlined hide-details></v-text-field>
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col>
                                <v-checkbox
                                    v-model="form.can_check_competency"
                                    label="Can check competency"
                                ></v-checkbox>
                            </v-col>
                        </v-row>

                        <v-row v-if="canAssign">
                            <v-col>
                                <v-select
                                    v-model="form.tasks"
                                    :items="taskOptions"
                                    item-text="label"
                                    item-value="key"
                                    label="Assign Tasks"
                                    multiple
                                    chips
                                    small-chips
                                    dense
                                    outlined
                                    hide-details
                                ></v-select>
                            </v-col>
                        </v-row>
                    </v-container>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="blue darken-1" text @click="cancel">Cancel</v-btn>
                    <v-btn class="primary" @click="save">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

    </div>
</template>

<script>
import api from "../common/api";
import permissions from "../common/permissions";
import UserForm from "./UserForm.vue";

const axios = require('axios');

export default {
    name: "OnboardingUsersTable",
    components: {UserForm},
    props: {
        canAssign: {
            type: Boolean,
            default: true,
        },
        title: {
            type: String,
            default: 'Create Users'
        },
        subtitle: {
            type: String,
            default: ''
        }
    },
    data() {
        return {
            loader: false,
            saving: false,
            dialog: false,
            isEditing: false,
            editingId: null,
            items: [],
            form: {
                billing_user_id: null,
                first_name: '',
                last_name: '',
                job_title: '',
                email: '',
                phone: '',
                alias: '',
                can_check_competency: false,
                tasks: [],
            },
            taskOptions: [
                { key: 'company_details',            label: 'Your Company Details' },
                { key: 'common_assessment_approved', label: 'Common Assessment Approved' },
                { key: 'users_and_roles',            label: 'Users and Roles' },
                { key: 'supply_chain',               label: 'Your Supply Chain' },
                { key: 'line_of_credit',             label: 'Line of Credit' },
                { key: 'esg_policies',               label: 'ESG Policies' },
                { key: 'company_certifications',     label: 'Company Certifications' },
            ],
            options: {
                page: 1,
                itemsPerPage: 50,
                sortBy: ["id"],
                sortDesc: [true],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false
            },
            headers: [
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
                    text: "Job Title",
                    value: "job_title",
                    sortable: true,
                },
                {
                    text: "Email",
                    value: "email",
                    sortable: true,
                },
                {
                    text: "Telephone",
                    value: "phone",
                    sortable: true,
                },
                {
                    text: "Alias",
                    value: "alias",
                    sortable: true,
                },
                {
                    text: "Tasks",
                    value: "tasks",
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

    mounted() {
        this.load();
    },

    watch: {

    },

    methods: {
        load() {
            const self = this;
            self.loader = true;

            api.getOnboardingUsers()
                .then((response) => {
                    self.items = response.data;
                    self.$emit('users-loaded', self.items);
                    self.loader = false;
                })
                .catch((error) => {
                    console.log(error);
                    self.loader = false;
                });
        },

        save() {
            const self = this;
            self.saving = true;

            self.form.billing_user_id = self.user().id;

            const request = self.isEditing
                ? api.updateOnboardingUser(self.editingId, self.form)
                : api.createOnboardingUser(self.form);

            request
                .then(() => {
                    self.$store.commit("showSnackbar", {
                        message: self.isEditing ? "User updated" : "User added",
                        color: "success",
                    });
                    self.resetForm();
                    self.load();
                    self.dialog = false;
                })
                .catch((error) => {
                    self.$store.commit("showSnackbar", {
                        message: error.response?.data ?? "An error occurred",
                        color: "error",
                    });
                })
                .finally(() => { self.saving = false; });
        },

        cancel() {
            const self = this;
            self.dialog = false;
            self.resetForm();
        },

        deleteItem(item) {
            const self = this;

            api.deleteOnboardingUser(item.id)
                .then(() => { self.load(); })
                .catch(console.log);
        },

        resetForm() {
            this.isEditing = false;
            this.editingId = null;
            this.form = {
                billing_user_id: null,
                first_name: '',
                last_name: '',
                job_title: '',
                email: '',
                phone: '',
                alias: '',
                can_check_competency: false,
                tasks: [],
            };
        },

        taskLabel(key) {
            const found = this.taskOptions.find(s => s.key === key);
            return found ? found.label : key;
        },

        addItem() {
            this.resetForm();
            this.dialog = true;
        },

        editItem(item) {
            this.isEditing = true;
            this.editingId = item.id;
            this.form = {
                billing_user_id: item.billing_user_id || this.user().id,
                first_name: item.first_name || '',
                last_name: item.last_name || '',
                job_title: item.job_title || '',
                email: item.email || '',
                phone: item.phone || '',
                alias: item.alias || '',
                can_check_competency: item.can_check_competency || false,
                tasks: item.tasks ? [...item.tasks] : [],
            };
            this.dialog = true;
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        isBillingUser() {
            return permissions.hasRole(permissions.role_billing_user);
        },
    }
}
</script>

<style scoped>
.out-header {
    display: flex;
    justify-content: space-between;
}

.out-header-right {
    padding: 12px;
}
</style>
