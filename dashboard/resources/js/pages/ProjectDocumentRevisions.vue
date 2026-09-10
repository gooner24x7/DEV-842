<template>
    <div style="margin: 1em">
        <v-row>
            <v-col md="10">
                <h4 class="ml-1">
                    <v-icon size="30" :color="getFileTypeColor(documentName)">{{ getFileTypeIcon(documentName) }}</v-icon>
                    <span class="ml-1">{{ documentName }}</span>
                </h4>
                <div class="subtitle-2 ml-1">All versions and revision history</div>
            </v-col>
            <v-col md="2">
                <v-btn v-if="canEdit" color="primary" @click="dialog = true">Update File</v-btn>
            </v-col>
        </v-row>

        <v-data-table
            :headers="headers"
            :items="items"
            :loading="loader"
            item-key="id"
            loading-text="Loading... Please wait"
        >
            <template v-slot:item.revision="{ item, index }">
                <div class="type-chip-sm" style="width: 60px; background: #f5f5f5;">
                    Rev {{ getRevisionNumber(index) }}
                </div>
            </template>

            <template v-slot:item.name="{ item }">
                <div class="d-flex align-center">
                    <v-icon size="24" :color="getFileTypeColor(item.filename)">
                        {{ getFileTypeIcon(item.filename) }}
                    </v-icon>
                    <span class="ml-2">{{ item.name }}</span>
                </div>
            </template>

            <template v-slot:item.actions="{ item }">
                <div class="text-center">
                    <v-btn small class="primary" v-on:click="viewDocument(item.filename)">
                        <v-icon color="white">mdi-download</v-icon>
                        Download
                    </v-btn>
                    <v-btn small v-if="canEdit" class="primary" v-on:click="deleteDocument(item)">Delete</v-btn>
                </div>
            </template>
        </v-data-table>

        <v-dialog v-model="dialog" max-width="900px">
            <v-card>
                <v-form @submit="save" ref="project_documents_form" enctype="multipart/form-data">
                    <v-card-title>
                        <span class="headline">Update File</span>
                    </v-card-title>

                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col>
                                    <div class="alert-info-grey">
                                        <v-icon class="mr-1">fa-exclamation-circle</v-icon>
                                        Note: Please upload the latest version of the file
                                    </div>

                                    <v-file-input
                                        name="file"
                                        id="file"
                                        accept="*"
                                        label="Upload File"
                                    ></v-file-input>

                                    <v-text-field
                                        name="description"
                                        id="description"
                                        label="Note"
                                        class="ml-8"
                                    ></v-text-field>
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
    name: 'ProjectDocumentRevisions',
    props: [],
    components: {},
    data() {
        return {
            projectId: null,
            categoryId: null,
            groupId: null,
            project: null,
            category: null,
            projectName: '',
            categoryName: '',
            documentName: '',
            loader: false,
            canEdit: false,
            filters: {
                search: null,
            },
            headers: [
                {
                    text: "Revision",
                    value: "revision",
                    sortable: true,
                },
                {
                    text: "File Name",
                    value: "name",
                    sortable: true,
                },
                {
                    text: "Note",
                    value: "description",
                    sortable: true,
                },
                {
                    text: "Created At",
                    value: "created_at",
                    sortable: true,
                },
                {
                    text: "Created By",
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
        self.groupId = self.$route.query.groupId ?? null;

        if (!self.projectId) { return; }

        await self.load();

        self.projectName = self.project.name ?? '';
        self.categoryName = self.category.name ?? '';
        self.documentName = self.items[0].name ?? '';

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
                group_id: self.groupId,
                search: self.filters.search
            }

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
            formData.append('group_id', self.groupId);

            api.createProjectDocument(self.projectId, formData)
                .then((response) => {
                    self.close();
                    self.load();
                })
                .catch((error) => {
                    console.log(error);
                });
        },

        viewDocument(filename) {
            const self = this;

            let url = '/storage/projects/' + self.projectId + '/' + filename;

            window.open(url, '_blank');
        },

        deleteDocument(document) {
            const self = this;

            if (confirm('Are you sure you want to delete ' + document.name + ' ?')) {
                api.deleteProjectDocument(self.projectId, {id: document.id})
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
        },

        getRevisionNumber(index) {
            const self = this;

            if (self.items.length === 1) {
                return 1;
            }

            return self.items.length - index;
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
