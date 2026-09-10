<template>
    <div style="margin: 1em">
        <div v-if="isContractor() || isAdmin()" class="mb-2">
            <v-btn class="primary" v-on:click="toProjects()">Projects</v-btn>
        </div>

<!--        <h2>Works packages</h2>-->

        <v-row>
            <v-col md="8">
                <v-btn class="m-2" color="primary" @click="addWorksPackage()">New</v-btn>
                <v-btn class="m-2" color="primary" @click="openTemplates()">Templates</v-btn>
                <v-btn class="m-2" color="primary" @click="toggleTreeview()">{{ getToggleBtnLabel() }}</v-btn>
            </v-col>
            <v-col md="4">
                <v-text-field
                    v-model="search"
                    label="Search"
                    hide-details
                    outlined
                    clearable
                    dense
                    clear-icon="mdi-close-circle-outline"
                ></v-text-field>
            </v-col>
        </v-row>

<!--        <v-data-table-->
<!--            :headers="headers"-->
<!--            :items="items"-->
<!--            :loading="loader"-->
<!--            class="elevation-1"-->
<!--            item-key="id"-->
<!--            loading-text="Loading... Please wait"-->
<!--        >-->
<!--            <template v-slot:top>-->

<!--            </template>-->

<!--            <template v-slot:item.actions="{ item }">-->
<!--                <div class="text-center">-->
<!--                    <v-btn class="mr-2" color="primary" small @click="edit(item)">Edit</v-btn>-->
<!--                    <v-btn class="mr-2" color="primary" small @click="showQuestionnaires(item)">Questions</v-btn>-->
<!--                    <v-btn class="mr-2" color="primary" small @click="showReplies(item)">Replies</v-btn>-->
<!--                </div>-->
<!--            </template>-->
<!--        </v-data-table>-->

        <v-treeview
            :items="items"
            :open-all="expanded"
            :search="search"
            ref="worksPackagesTree"
            class="mt-3"
            dense
            hoverable
        >
            <template v-slot:label="{ item, open }">
                <div>{{ item.name }}</div>
                <div v-if="item.assigned_users.length > 0">
                    <div class="wp-user-list">
                        <ul>
                            <li v-for="user in item.assigned_users" :key="user.id">
                                <v-icon>mdi-account</v-icon>
                                {{ user.first_name }}
                            </li>
                        </ul>
                    </div>
                </div>
            </template>
            <template v-slot:append="{ item, open }">
                <v-btn class="mr-2" color="primary" small @click="assignUsers(item)">Assign Users</v-btn>
                <v-btn class="mr-2" color="primary" small @click="edit(item)">Edit</v-btn>
                <v-btn class="mr-2" color="primary" small @click="deleteWorksPackage(item)">Delete</v-btn>
                <v-btn class="mr-2" color="primary" small @click="showQuestionnaires(item)">Questions</v-btn>
                <v-btn class="mr-2" color="primary" small @click="showReplies(item)">Replies</v-btn>

                <v-tooltip top>
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
                        </v-btn>
                    </template>
                    <span>Clarifications</span>
                </v-tooltip>
            </template>
        </v-treeview>

        <v-dialog v-model="dialog" max-width="600px">
            <works-package-form
                v-model="editedItem"
                :projectId="projectId"
                v-on:cancel="close"
                v-on:saved="saved"
            />
        </v-dialog>

        <v-dialog v-model="dialogAssignUsers" max-width="1200px">
            <v-card>
                <v-card-title>Assign Sub-contractor</v-card-title>
                <v-card-text>
                    <preferred-subcontractors-table :input-filters="filters" @selected="userSelected"></preferred-subcontractors-table>
                </v-card-text>
                <v-card-actions>
                    <v-btn color="primary" v-if="assignedUsers.length > 0" @click="removeAssignedUsers()">Remove Assigned Users</v-btn>
                    <v-spacer></v-spacer>
                    <v-btn @click="dialogAssignUsers=false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveAssignedUsers()">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";
import WorksPackageForm from "../components/WorksPackageForm.vue";
import PreferredSubcontractorsTable from "../components/PreferredSubcontractorsTable.vue";

export default {
    props: [],
    components: {
        PreferredSubcontractorsTable,
        WorksPackageForm,
    },
    data() {
        return {
            loader: false,
            dialog: false,
            dialogAssignUsers: false,
            projectId: null,
            expanded: true,
            search: null,
            items: [],
            selectedUserIds: [],
            selectedWorksPackage: null,
            assignedUsers: [],
            headers: [
                {
                    text: "Name",
                    value: "name",
                    sortable: true,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: true,
                },
            ],
            editedItem: {
                id: null,
                name: null,
                template_id: null,
                parent_id: null,
                high_risk: 0,
            },
            filters: {
                search: null,
                region: null,
                project: null,
                worksPackage: null,
                products: [],
                radius: null,
                certType: null,
                certStatus: null,
            },
        };
    },

    async mounted() {
        const self = this;

        self.projectId = self.$route.params.projectId;
        await self.load();
    },

    watch: {
        dialog(val) {
            val || this.close();
        },
    },

    computed: {},

    methods: {
        toProjects() {
            this.$router.push({
                name: 'projects',
                params: {}
            });
        },

        isContractor() {
            return permissions.hasRole(permissions.role_contractor);
        },

        isAdmin() {
            if (typeof this.$store.getters.getSession.user.role_name != 'object') {
                return false;
            }

            return this.$store.getters.getSession.user.role_name.join().toLowerCase().includes("admin");
        },

        showReplies(item) {
            this.$router.push({
                name: 'questionnaire-replies',
                params: {projectId: this.projectId, worksPackageId: item.id}
            });
        },

        showQuestionnaires(item) {
            this.$router.push({name: 'questionnaires', params: {projectId: this.projectId, worksPackageId: item.id}});
        },

        openTemplates() {
            this.$router.push({name: 'templates', params: {}});
        },

        async load() {
            const self = this;
            self.loader = true;

            const response = await api.loadWorksPackages(this.projectId, {})
            self.items = response.data;

            self.loader = false;
        },

        close() {
            const self = this;

            self.dialog = false;
        },

        async saved() {
            const self = this;

            await self.load();
            self.dialog = false;
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        edit(item) {
            const self = this;

            self.editedItem.id = item.id;
            self.editedItem.name = item.name;
            self.editedItem.template_id = item.template_id;
            self.editedItem.parent_id = item.parent_id;
            self.editedItem.high_risk = item.high_risk;
            self.dialog = true;
        },

        deleteWorksPackage(item) {
            const self = this;

            if (confirm('Are you sure you want to delete this works package?')) {
                api.deleteWorksPackage(item.id)
                    .then(response => {
                        self.load();

                        self.$store.commit("showSnackbar", {
                            message: "Success",
                            color: "success",
                        });
                    })
                    .catch(error => {
                        console.error(error);

                        self.$store.commit("showSnackbar", {
                            message: "An error occurred",
                            color: "error",
                        });
                    })
            }
        },

        addWorksPackage() {
            const self = this;

            self.editedItem.id = null;
            self.editedItem.name = null;
            self.editedItem.template_id = null;
            self.editedItem.parent_id = null;
            self.editedItem.high_risk = 0;
            self.dialog = true;
        },

        toggleTreeview() {
            this.expanded = !this.expanded;
            this.$refs.worksPackagesTree.updateAll(this.expanded);
        },

        getToggleBtnLabel() {
            return this.expanded ? 'Collapse Tree' : 'Expand Tree';
        },

        viewWorksPackageQuestions(item) {
            this.$router.push({
                name: "works-package-questions",
                query: {
                    worksPackageId: item.id
                }
            });
        },

        async assignUsers(item) {
            const self = this;

            let products = [];

            let productMap = [
                {wp: 'MEP', product: 'Electrical'},
                {wp: 'Dry Lining', product: 'Dry Lining'},
                {wp: 'Bricks', product: 'Bricklaying'},
                {wp: 'Groundworks', product: 'Groundwork'},
            ];

            productMap.forEach(function(product) {
               if (item.name.toLowerCase() === product.wp.toLowerCase()) {
                   products.push(product.product);
               }
            });

            self.filters = {
                search: null,
                region: null,
                project: item.project_name,
                worksPackage: item.name,
                products: products,
                radius: 20,
                certType: 'cas',
                certStatus: 1,
            };

            self.assignedUsers = item.assigned_users;
            self.selectedWorksPackage = item;
            self.dialogAssignUsers = true;
        },

        saveAssignedUsers() {
            const self = this;

            api.storeWorksPackageAssignedUsers(self.selectedWorksPackage.id, {user_ids: self.selectedUserIds})
                .then(response => {
                    self.$store.commit("showSnackbar", {
                        message: "Success",
                        color: "success",
                    });

                    self.dialogAssignUsers = false;
                    self.selectedUserIds = [];
                    self.load();
                })
                .catch(error => {
                    self.$store.commit("showSnackbar", {
                        message: "An error occurred",
                        color: "error",
                    });

                    console.error(error);
                });
        },

        userSelected(users) {
            const self = this;

            if (users.length > 0) {
                users.forEach(function(user) {
                    self.selectedUserIds.push(user.id);
                });
            } else {
                self.selectedUserIds = [];
            }
        },

        removeAssignedUsers() {
            const self = this;

            if (confirm('This will remove all assigned users. Are you sure?')) {
                self.selectedUserIds = [];
                self.saveAssignedUsers();
            }
        }
    },
};
</script>

<style>
/*
.v-treeview {
    background: white;
    border: 1px solid lightgrey;
    border-radius: 5px;
}
 */

.wp-user-list {
    padding: 5px;
    border: 1px solid lightgrey;
    border-radius: 5px;
    background-color: white;
    width: min-content;
}

.wp-user-list ul {
    list-style: none;
    padding-left: 0;
    margin: 0;
}
</style>
