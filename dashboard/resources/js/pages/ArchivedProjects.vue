<template>
    <div style="margin: 1em">
        <div v-if="isContractor() || isAdmin()" class="mb-2">
            <v-btn class="primary" v-on:click="goToEnquiries()">Enquiries</v-btn>
            <v-btn :disabled=true class="primary">Projects</v-btn>
        </div>

<!--        <h2>Archived Projects</h2>-->
        <v-data-table
            :headers="headers"
            :items="items"
            :loading="loader"
            item-key="id"
            loading-text="Loading... Please wait"
        >
            <template v-slot:top>

            </template>

            <template v-slot:item.actions="{ item }">
                <div class="text-center">
                    <v-btn
                        v-if="canEditProjects()"
                        class="primary"
                        @click="restoreItem(item)"
                    >
                        Restore Project
                    </v-btn
                    >
                    <v-btn class="primary" v-on:click="view(item.id)">View</v-btn>
                </div>
            </template>
        </v-data-table>

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
            projectType: null,
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
                    text: "Postcode",
                    value: "postcode",
                    sortable: true,
                },
                {
                    text: "Client name",
                    value: "client_name",
                    sortable: true,
                },
                {
                    text: "Framework",
                    value: "framework",
                    sortable: true,
                },
                {
                    text: "Created At",
                    value: "created_at",
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
            compareDialog: false,
            editDialog: false,
            editedIndex: -1,
            editedItem: {
                name: "",
            },
            defaultItem: {
                name: "",
            },
        };
    },

    async mounted() {
        const self = this;

        self.projectType = self.$route.query.type ?? null;

        await this.load();
    },

    watch: {
        dialog(val) {
            val || this.close();
        },
    },

    computed: {},

    methods: {
        canEditProjects() {
            return permissions.can(permissions.edit_projects);
        },

        async restoreItem(item) {
            const self = this;

            self.loader = true;
            try {
                await api.restoreProject(item.id);

                await self.load();
            } catch (e) {
                console.log(e);
            }

            self.loader = false;
        },

        view(projectId) {
            this.$router.push({
                name: "works-packages",
                params: {
                    projectId: projectId
                }
            });
        },

        goToEnquiries() {
            this.$router.push({
                name: "archived-supply-fit-enquiries",
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

        async load() {
            const self = this;
            self.loader = true;

            const response = await api.loadProjects({
                archived: 1,
                grouped: 1,
                type: self.projectType
            })
            self.items = response.data;
            self.loader = false;
        },
    },
};
</script>

<style>
</style>
