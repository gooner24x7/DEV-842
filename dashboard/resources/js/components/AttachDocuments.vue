<template>
    <v-card>
        <v-card-title>
            <span class="headline">Attach Documents</span>
        </v-card-title>

        <v-card-text>
            <v-text-field
                v-model="projectName"
                label="Selected Project"
                disabled
                hide-details
                outlined
                dense
            ></v-text-field>

            <v-tabs
                v-model="currentTab"
                background-color="transparent"
                grow
            >
                <v-tab href="#projectDocuments">
                    Project Documents
                </v-tab>
                <v-tab href="#uploadFromComputer">
                    Upload from Computer
                </v-tab>
            </v-tabs>

            <v-tabs-items v-model="currentTab">
                <v-tab-item value="projectDocuments">
                    <v-container>
                        <v-row>
                            <v-col md="6">
                                <v-autocomplete
                                    v-model="selectedCategory"
                                    :items="categories"
                                    item-text="title"
                                    item-value="id"
                                    label="Category"
                                    class="mb-2"
                                    return-object
                                    hide-details
                                    outlined
                                    dense
                                ></v-autocomplete>
                            </v-col>
                            <v-col md="6">
                                <v-autocomplete
                                    v-model="selectedSubCategory"
                                    :items="subCategories"
                                    item-text="title"
                                    item-value="id"
                                    label="Sub Category"
                                    class="mb-2"
                                    return-object
                                    hide-details
                                    outlined
                                    dense
                                ></v-autocomplete>
                            </v-col>
                        </v-row>

                        <v-text-field v-model="search" dense outlined label="Search"></v-text-field>

                        <v-data-table
                            v-model="selectedItems"
                            :headers="headers"
                            :items="items"
                            :loading="loader"
                            item-key="name"
                            loading-text="Loading... Please wait"
                            class="cb-table"
                            show-select
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
                        </v-data-table>
                    </v-container>
                </v-tab-item>
                <v-tab-item value="uploadFromComputer">
                    <v-container>
                        <div class="alert-info-grey mb-2">
                            <v-icon class="mr-1">fa-exclamation-circle</v-icon>
                            Note: Please select one or more files to upload
                        </div>

                        <v-file-input
                            v-model="files"
                            label="Upload Files"
                            accept="*"
                            multiple
                            show-size
                        ></v-file-input>
                    </v-container>
                </v-tab-item>
            </v-tabs-items>
        </v-card-text>

        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="blue darken-1" text @click="close">Cancel</v-btn>
            <v-btn color="blue darken-1" text @click="save">Save</v-btn>
        </v-card-actions>
    </v-card>
</template>

<script>
import api from "../common/api";
import utils from "../common/utils";
import _ from "lodash";

export default {
    name: "AttachDocuments",
    components: {},
    props: ["project"],
    data() {
        return {
            projectId: null,
            projectName: null,
            search: null,
            selectedCategory: null,
            selectedSubCategory: null,
            currentTab: 'projectDocuments',
            loader: false,
            items: [],
            categories: [],
            subCategories: [],
            selectedItems: [],
            files: [],
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
            ]
        }
    },
    async mounted() {
        const self = this;

        await self.loadCategories();
        //await self.load();
    },
    watch: {
        search: {
            handler: function(val) {
                if (val.length > 2) {
                    this.performSearch(val);
                } else if (val.length === 0) {
                    this.performSearch(val);
                }
            }
        },
        project: {
            handler: function(val) {
                if (val) {
                    this.projectId = val.id;
                    this.projectName = val.name;
                } else {
                    this.projectId = null;
                    this.projectName = null;
                }

                this.items = [];
            },
            deep: true,
            immediate: true
        },
        selectedCategory: {
            handler: function(val) {
                if (val) {
                    this.subCategories = val.children;
                } else {
                    this.subCategories = [];
                }
            }
        },
        selectedSubCategory: {
            handler: function(val) {
                if(val) {
                    this.load();
                } else {
                    this.items = [];
                }
            }
        }
    },
    methods: {
        ...utils,

        async load() {
            const self = this;
            self.loader = true;

            let params = {
                category_id: self.selectedSubCategory?.id,
                search: self.search,
            };

            const response = await api.getProjectDocuments(self.projectId, params);

            self.items = response.data.documents;
            self.loader = false;
        },

        async loadCategories() {
            const self = this;
            const response = await api.getProjectDocumentCategories();

            self.categories = response.data;
        },

        close() {
            this.$emit('close');
        },

        save() {
            const self = this;

            let data = {
                project: self.selectedItems,
                user: self.files
            }

            self.$emit('save', data);
        },

        performSearch: _.debounce(function(query) {
            this.load();
        }, 300),
    }
}
</script>

<style scoped>

</style>
