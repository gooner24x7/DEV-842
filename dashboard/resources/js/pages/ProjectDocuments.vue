<template>
    <div style="margin: 1em">

        <v-row>
            <v-col md="6">
                <h4 class="ml-1">
                    <v-icon size="30">mdi-folder</v-icon>
                    <span class="ml-1">{{ categoryTitle }}</span>
                </h4>
                <div class="subtitle-2 ml-1">Documents stored in this folder</div>
            </v-col>
            <v-col md="4">
                <v-text-field v-model="filters.search" dense outlined label="Search"></v-text-field>
            </v-col>
            <v-col md="2">
                <v-btn v-if="canEdit" color="primary" @click="dialog = true">Upload File</v-btn>
            </v-col>
        </v-row>

        <v-data-table
            :headers="headers"
            :items="items"
            :loading="loader"
            item-key="id"
            loading-text="Loading... Please wait"
        >
            <template v-slot:item.name="{ item }">
                <div class="d-flex align-center">
                    <v-icon size="24" :color="getFileTypeColor(item.name)">
                        {{ getFileTypeIcon(item.name) }}
                    </v-icon>
                    <span class="ml-2">{{ item.name }}</span>
                </div>
            </template>

            <template v-slot:item.total="{ item }">
                <div class="type-chip-sm" style="width: 60px; background: #f5f5f5;">
                    Rev {{ item.total }}
                </div>
            </template>

            <template v-slot:item.actions="{ item }">
                <div class="text-center">
                    <v-btn small class="primary" v-on:click="viewRevisions(item.group_id)">View</v-btn>
                    <v-btn small v-if="canEdit" class="primary" v-on:click="deleteDocument(item)">Delete</v-btn>
                </div>
            </template>
        </v-data-table>

        <v-dialog v-model="dialog" max-width="900px">
            <v-card>
                <v-form @submit="save" ref="project_documents_form" enctype="multipart/form-data">
                    <v-card-title>
                        <span class="headline">Upload File</span>
                    </v-card-title>

                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col>
                                    <div class="alert-info-grey">
                                        <v-icon class="mr-1">fa-exclamation-circle</v-icon>
                                        Note: Please select a single file to upload
                                    </div>

                                    <v-file-input
                                        name="file"
                                        id="file"
                                        accept="*"
                                        label="Upload File"
                                    ></v-file-input>
                                </v-col>
                            </v-row>
                        </v-container>
                    </v-card-text>

                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="blue darken-1" text @click="close">Cancel</v-btn>
                        <v-btn color="blue darken-1" text type="submit">Save</v-btn>
                    </v-card-actions>
                </v-form>
            </v-card>
        </v-dialog>

    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";
import utils from "../common/utils";
import _ from "lodash";

export default {
    name: 'ProjectDocuments',
    props: [],
    components: {},
    data() {
        return {
            projectId: null,
            categoryId: null,
            project: null,
            category: null,
            projectName: '',
            categoryTitle: '',
            loader: false,
            canEdit: false,
            filters: {
                search: null,
            },
            headers: [
                {
                    text: "Document Name",
                    value: "name",
                    sortable: true,
                },
                {
                    text: "Latest Revision",
                    value: "total",
                    sortable: true,
                },
                {
                    text: "Last Updated",
                    value: "created_at",
                    sortable: true,
                },
                {
                    text: "Updated By",
                    value: "created_by",
                    sortable: true,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: true,
                },
            ],
            items: [],
            dialog: false,
        };
    },

    async mounted() {
        const self = this;

        self.projectId = self.$route.query.projectId ?? null;
        self.categoryId = self.$route.query.categoryId ?? null;

        if (!self.projectId) { return; }

        await self.load();

        self.projectName = self.project.name ?? '';
        self.categoryTitle = self.category.title ?? '';

        self.canEdit = self.user().id === self.project.user_id;
    },

    watch: {
        'filters.search'(val) {
            if (val.length > 2) {
                this.performSearch(val);
            } else if (val.length === 0) {
                this.performSearch(val);
            }
        }
    },

    methods: {
        ...utils,

        async load() {
            const self = this;
            self.loader = true;

            let params = {
                category_id: self.categoryId,
                search: self.filters.search,
            };

            const response = await api.getProjectDocuments(self.projectId, params);

            self.items = response.data.documents;
            self.project = response.data.project;
            self.category = response.data.category;
            self.loader = false;
        },

        performSearch: _.debounce(function(query) {
            this.load();
        }, 300),

        close() {
            const self = this;

            self.dialog = false;
        },

        save(event) {
            event.preventDefault();

            const self = this;

            let formData = new FormData(event.target);
            formData.append('category_id', self.categoryId);

            api.createProjectDocument(self.projectId, formData)
                .then((response) => {
                    self.close();
                    self.load();
                })
                .catch((error) => {
                    console.log(error);
                });
        },

        viewRevisions(group_id) {
            const self = this;

            let params = {
                projectId: self.projectId,
                categoryId: self.categoryId,
                groupId: group_id,
            };

            self.$router.push({ name: 'project-document-revisions', query: params });
        },

        deleteDocument(document) {
            const self = this;

            if (confirm('Are you sure? This will delete all revisions of this document')) {
                api.deleteProjectDocument(self.projectId, {group_id: document.group_id})
                    .then((response) => {
                        self.load();
                    })
                    .catch((error) => {
                        console.log(error);
                    });
            }
        },

        user() {
            return this.$store.getters.getSession.user;
        }
    }
};
</script>

<style>
    .alert-info-grey {
        padding: 15px;
        background: #ececec;
        color: #494949;
        border-radius: 28px;
    }
</style>
