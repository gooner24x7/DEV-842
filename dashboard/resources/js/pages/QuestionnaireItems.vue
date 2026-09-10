<template>
    <div style="margin: 1em">
<!--        <h2>Questionnaire</h2>-->

        <v-data-table
            :expanded.sync="expanded"
            :headers="computedHeaders"
            :items="items"
            :loading="loader"
            :options.sync="options"
            :server-items-length="serverItemsLength"
            :single-expand="singleExpand"
            item-key="id"
            loading-text="Loading... Please wait"
        >
            <template v-slot:top>

                <v-dialog v-model="dialog" max-width="900px">
                    <template v-slot:activator="{ on, attrs }">
                        <v-btn
                            v-if="canEditQuestion()"
                            class="m-2"
                            color="primary"
                            v-bind="attrs"
                            v-on="on"
                        >New
                        </v-btn
                        >
                    </template>
                    <questionnaire-form
                        v-model="editedItem"
                        :projectId="projectId"
                        :title="formTitle"
                        :worksPackageId="worksPackageId"
                        v-on:cancel="close"
                        v-on:saved="updated"
                    />
                </v-dialog>

                <v-btn
                    v-if="canEditQuestion()"
                    class="m-2"
                    color="primary"
                    v-on:click="saveTemplate()">
                    Save Template
                </v-btn>

                <v-dialog v-model="dialogSelectTemplate" max-width="900px">
                    <template v-slot:activator="{ on, attrs }">
                        <v-btn
                            v-if="canEditQuestion()"
                            class="m-2"
                            color="primary"
                            v-bind="attrs"
                            v-on="on"
                        >Select Template
                        </v-btn>
                    </template>
                    <v-card>
                        <v-card-title>
                            <span class="headline">Select Template</span>
                        </v-card-title>

                        <v-card-text>
                            <v-container>
                                <v-autocomplete
                                    v-model="templateId"
                                    :items="templates"
                                    :loading="loader"
                                    item-text="name"
                                    item-value="id"
                                    label="Select Template"
                                ></v-autocomplete>
                            </v-container>
                        </v-card-text>

                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="blue darken-1" text @click="dialogSelectTemplate = false">Cancel</v-btn>
                            <v-btn color="blue darken-1" text @click="applyTemplate(templateId)">Save</v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>

            </template>

            <template v-slot:item.created_at="{ item }">
                {{ item.created_at | formatDate }}
            </template>

            <template v-slot:item.actions="{ item }">
                <div class="text-center">
                    <v-btn
                        v-if="canEditQuestion(item)"
                        class="mr-2"
                        color="primary"
                        small
                        @click="editItem(item)"
                    >Edit
                    </v-btn
                    >

                    <v-btn
                        v-if="canEditQuestion(item)"
                        class="mr-2"
                        color="primary"
                        small
                        @click="deleteItem(item)"
                    >Delete
                    </v-btn
                    >
                </div>
            </template>
        </v-data-table>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions.js";
import QuestionnaireForm from "../components/QuestionnaireForm";

export default {
    components: {
        QuestionnaireForm,
    },
    data() {
        return {
            refreshHandler: null,
            loader: false,
            filtersLoader: false,
            worksPackageId: null,
            projectId: null,

            headers: [
                {
                    text: "ID",
                    value: "id",
                    sortable: true,
                },

                {
                    text: "Date",
                    value: "created_at",
                    sortable: true,
                },

                {
                    text: "Text",
                    value: "text",
                    sortable: true,
                },
                {
                    text: "Type",
                    value: "type",
                    sortable: true,
                },
                {
                    text: "Yes (default score)",
                    value: "score_yes",
                    sortable: true,
                },
                {
                    text: "No (default score)",
                    value: "score_no",
                    sortable: true,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: false,
                },
            ],
            expanded: [],
            singleExpand: true,

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
            dialogMulti: false,
            dialogUploads: false,
            dialogSelectTemplate: false,
            editedIndex: -1,
            editedItem: {
                text: "",
                type: "",
                score_yes: 0,
                score_no: 0,
                editId: null,
            },
            defaultItem: {
                text: "",
                type: "",
                score_yes: 0,
                score_no: 0,
                editId: null,
            },
            templates: [],
            templateId: 0,
        };
    },

    async mounted() {
        const self = this;

        self.worksPackageId = self.$route.params.worksPackageId;
        self.projectId = self.$route.params.projectId;

        await self.load();
        await self.loadTemplateOptions();
    },

    watch: {
        options: {
            async handler(n, o) {
                await this.load();
            },
            deep: true,
        },
        dialog(val) {
            val || this.close();
        },
    },

    computed: {
        computedHeaders() {
            const self = this;
            return this.headers;
        },

        formTitle() {
            return this.editedIndex === -1 ? "New Question" : "Edit Question";
        },
    },

    methods: {
        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },

        isContractor() {
            return permissions.hasRole(permissions.role_contractor);
        },

        isCurrentUser(userId) {
            const self = this;

            return self.user().id === userId;
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
                projectId: self.projectId,
                worksPackageId: self.worksPackageId,
            };

            const response = await api.loadQuestionnaireItems(params)

            self.items = response.data.data;
            self.serverItemsLength = response.data.total;
            self.loader = false;
        },

        async loadTemplateOptions(templateName = '') {
            const self = this;
            self.loader = true;

            try {
                const resp = await api.questionnaireTemplateOptions(templateName);
                self.templates = resp.data;
            } catch (err) {
                console.log(err);
            }

            self.loader = false;
        },

        editItem(item) {
            this.editedIndex = this.items.indexOf(item);
            this.editedItem = Object.assign({}, item);
            this.dialog = true;
        },

        async deleteItem(item) {
            const self = this;
            if (confirm("Are you sure you want to delete this question?")) {
                self.loader = true;
                await api.deleteQuestionnaireItem(item.id);

                await self.load();
                self.loader = false;
            }
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        canEditQuestion(question) {
            return this.isAdmin() || this.isContractor();
        },

        async saveTemplate() {
            const self = this;

            const templateName = prompt("New Template", "");
            if (templateName) {
                self.loader = true;

                await api.saveTemplate(templateName, self.projectId, self.worksPackageId);
                self.loader = false;

                alert("Saved template");
            }
        },

        async applyTemplate(templateId) {
            const self = this;
            if (self.loader) {
                return;
            }
            self.loader = true;

            try {
                const resp = await api.applyTemplate(templateId, self.projectId, self.worksPackageId);
                self.items = resp.data;

                await self.load();
                this.dialogSelectTemplate = false;
            } catch (err) {
                console.log(err);
            }

            self.loader = false;
        },

        async sendQuestionnaire() {
            const self = this;
            if (confirm("Are you sure you want to send the questionnaire link to all sub contractors with matching enquiries?")) {
                self.loader = true;
                await api.sendQuestionnaire(self.projectId, self.worksPackageId);
                self.loader = false;

                alert("Sent");
            }
        },

        async updated() {
            const self = this;

            this.dialog = false;
            this.$nextTick(() => {
                this.editedItem = Object.assign({}, this.defaultItem);
                this.editedIndex = -1;
            });

            await self.load();
        },

        close() {
            this.dialog = false;
            this.$nextTick(() => {
                this.editedItem = Object.assign({}, this.defaultItem);
                this.editedIndex = -1;
            });
        },
    },
};
</script>

<style>
</style>
