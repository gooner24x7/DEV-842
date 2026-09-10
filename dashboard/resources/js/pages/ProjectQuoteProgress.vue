<template>
    <div style="margin: 1em">
        <h2>View Progress</h2>
        <h2>Project: {{ projectName }}</h2>

        <v-data-table
            :headers="headers"
            :items="items"
            :loading="loader"
            class="elevation-1"
            item-key="id"
            loading-text="Loading... Please wait"
        >
            <template v-slot:top>

            </template>

            <template v-slot:item.actions="{ item }">
                <div class="text-center">
                    <v-btn class="primary" v-on:click="view(item)">View</v-btn>
                </div>
            </template>
        </v-data-table>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";

export default {
    name: 'ProjectQuoteProgress',
    props: [],
    components: {},
    data() {
        return {
            projectId: 0,
            projectName: '',
            loader: false,
            items: [],
            headers: [
                {
                    text: "Sub Contractor",
                    value: "contractor_name",
                    sortable: true,
                },
                {
                    text: "Enquiry ID",
                    value: "enquiry_id",
                    sortable: true,
                },
                {
                    text: "Quote ID",
                    value: "id",
                    sortable: true,
                },
                {
                    text: "Works Package",
                    value: "works_package_name",
                    sortable: true,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: true,
                },
            ],
        };
    },

    async mounted() {
        this.projectId = this.$route.query.id ?? 0;
        this.projectName = this.$route.params.projectName ?? '';

        await this.load();
    },

    watch: {

    },

    computed: {

    },

    methods: {
        isContractor() {
            return permissions.hasRole(permissions.role_contractor);
        },

        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },

        isFrameworkOrClient() {
            return this.isFrameworkUser() || this.isClientUser();
        },

        isFrameworkUser() {
            return permissions.hasRole(permissions.role_framework);
        },

        isClientUser() {
            return permissions.hasRole(permissions.role_client);
        },

        view(item) {
            this.$router.push({
                name: "supply-fit-quote-progress",
                query: {
                    enquiry_id: item.enquiry_id,
                    quote_id: item.id,
                }
            });
        },

        async load() {
            const self = this;
            self.loader = true;

            const response = await api.loadProjectSupplyFitQuotes(self.projectId);
            self.items = response.data;

            self.loader = false;
        },
    },
};
</script>

<style>

</style>
