<template>
    <div style="margin: 1em">
    <!--<h2>Works packages</h2>-->

        <v-row>
            <v-col md="8">
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

        <v-treeview
            :items="items"
            :open-all="expanded"
            :search="search"
            ref="worksPackagesTree"
            class="mt-3"
            dense
            hoverable
        >
            <template v-slot:append="{ item, open }">
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
                        </v-btn
                        >
                    </template>
                    <span>Clarifications</span>
                </v-tooltip>
            </template>
        </v-treeview>

    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";

export default {
    props: [],
    components: {},
    data() {
        return {
            loader: false,
            dialog: false,
            projectId: null,
            expanded: true,
            search: null,
            items: [],
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
</style>
