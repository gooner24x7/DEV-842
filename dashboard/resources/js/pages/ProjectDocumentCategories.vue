<template>
    <div style="margin: 1em">

        <h5>Project: {{ projectName }}</h5>

        <v-row>
            <v-col md="8">
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

        <v-card>
            <v-treeview
                :items="items"
                :active.sync="active"
                :open-all="expanded"
                :search="search"
                ref="worksPackagesTree"
                class="mt-3"
                dense
                hoverable
                open-on-click
                activatable
            >
                <template v-slot:prepend="{ item }">
                    <div><v-icon>mdi-folder</v-icon> {{ item.title }}</div>
                </template>
            </v-treeview>
        </v-card>

    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";

export default {
    name: 'ProjectDocumentCategories',
    props: [],
    components: {},
    data() {
        return {
            projectId: null,
            projectName: '',
            loader: false,
            expanded: false,
            search: null,
            items: [],
            active: [],
        };
    },

    async mounted() {
        this.projectId = this.$route.query.projectId ?? null;

        await this.load();
    },

    watch: {
        active(val) {
            if (val.length > 0) {
                this.viewCategory(val[0]);
            }
        }
    },

    computed: {},

    methods: {
        async load() {
            const self = this;

            api.getProjectDocumentCategories()
                .then((response) => {
                    self.items = response.data;
                })
                .catch((error) => {
                    console.log(error);
                });

        },

        close() {
            const self = this;

            self.dialog = false;
        },

        save(event) {
            event.preventDefault();

            const self = this;
            let formData = new FormData(event.target);

            api.createProjectDocument(self.projectId, formData)
                .then((response) => {
                    self.close();
                    self.load();
                })
                .catch((error) => {
                    console.log(error);
                });
        },

        viewCategory(categoryId) {
            const self = this;

            this.$router.push({
                name: "project-documents",
                query: {
                    projectId: self.projectId,
                    categoryId: categoryId
                },
            });
        },

        toggleTreeview() {
            this.expanded = !this.expanded;
            this.$refs.worksPackagesTree.updateAll(this.expanded);
        },

        getToggleBtnLabel() {
            return this.expanded ? 'Collapse Tree' : 'Expand Tree';
        },
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
