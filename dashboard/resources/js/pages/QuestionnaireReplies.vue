<template>
    <div style="margin: 1em">
<!--        <h2>Company Questionnaires</h2>-->

        <v-row>
            <v-col v-if="isAdmin() || isContractor()">
                <!--                <v-btn color="primary" dark class="m-2" @click="compareCompanies()">Compare Scores</v-btn>-->
                <v-btn class="m-2" color="primary" dark @click="editCompanies()">Edit Scores</v-btn>
            </v-col>
        </v-row>

        <v-data-table
            v-model="selectedCompanies"
            :headers="headers"
            :items="items"
            :loading="loader"
            :options.sync="options"
            :server-items-length="serverItemsLength"
            class="elevation-1"
            item-key="id"
            loading-text="Loading... Please wait"
            show-select
        >

            <template v-slot:item.hash="{ item }">
                <a :href="'/form/' + item.hash" target="_blank">{{ item.hash }}</a>
            </template>

            <template v-slot:item.is_answered="{ item }">
                <v-checkbox v-model="item.is_answered" readonly/>
            </template>

            <template v-slot:item.is_accepted="{ item }">
                <v-checkbox v-model="item.is_accepted" readonly/>
            </template>

            <template v-slot:item.is_declined="{ item }">
                <v-checkbox v-model="item.is_declined" readonly/>
            </template>

            <template v-slot:item.actions="{ item }">
                <div class="text-center">
                </div>
            </template>
        </v-data-table>

        <!--        <v-dialog v-model="compareDialog" max-width="800px">-->
        <!--            <CompareScores :companies="selectedCompanies" :visible="compareDialog" v-on:accept="acceptCompany" v-on:decline="declineCompany"></CompareScores>-->
        <!--        </v-dialog>-->

        <v-dialog v-model="editDialog" max-width="1200px">
            <EditScores :companies="selectedCompanies" :visible="editDialog"></EditScores>
        </v-dialog>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions.js";
import CompareScores from "../components/CompareScores"
import EditScores from "../components/EditScores"

export default {
    props: [],
    components: {
        EditScores,
        CompareScores,
    },
    data() {
        return {
            selectedCompanies: [],
            loader: false,
            headers: [
                {
                    text: "ID",
                    value: "id",
                    sortable: true,
                },
                {
                    text: "Company Name",
                    value: "company_name",
                    sortable: true,
                },
                {
                    text: 'Enquiry ID',
                    value: 'inquiry_id',
                    sortable: true,
                },
                {
                    text: "Email",
                    value: "email",
                    sortable: true,
                },
                {
                    text: "External link",
                    value: "hash",
                    sortable: true,
                },
                {
                    text: "Total Score",
                    value: "totalScore",
                    sortable: true,
                },
                {
                    text: "Submitted",
                    value: "is_answered",
                    sortable: true,
                },
                // {
                //     text: 'Accepted',
                //     value: 'is_accepted',
                //     sortable: true,
                // },
                // {
                //     text: 'Declined',
                //     value: 'is_declined',
                //     sortable: true,
                // },
                {
                    text: "",
                    value: "actions",
                    sortable: false,
                },
            ],
            items: [],
            serverItemsLength: 0,
            options: {
                page: 1,
                itemsPerPage: 10,
                sortBy: ["is_answered"],
                sortDesc: [true],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false,
            },
            dialog: false,
            compareDialog: false,
            editDialog: false,

            editedIndex: -1,
            filtersLoader: false,
            filters: {},
            editedItem: {
                company_name: "",
                email: "",
            },
            defaultItem: {
                company_name: "",
                email: "",
            },
        };
    },

    mounted() {
    },

    watch: {
        options: {
            handler(n, o) {
                this.load();
            },
            deep: true,
        },
        dialog(val) {
            val || this.close();
        },

        filters: {
            handler(n, o) {
                this.load();
            },
            deep: true,
        },
    },

    computed: {
        formTitle() {
            return this.editedIndex === -1 ? "New" : "Edit";
        },
    },

    methods: {
        editCompanies() {
            this.editDialog = true;
        },

        compareCompanies() {
            this.compareDialog = true;
        },

        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },

        isContractor() {
            return permissions.hasRole(permissions.role_contractor);
        },

        allowDelete(item) {
            return true;
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
                projectId: self.$route.params.projectId,
                worksPackageId: self.$route.params.worksPackageId,
            };

            const response = await api.loadCompanyQuestionnaires(params)
            self.items = response.data.data;
            self.serverItemsLength = response.data.total;
            self.loader = false;
        },

        close() {
            const self = this;
            self.$nextTick(() => {
                self.editedItem = Object.assign({}, self.defaultItem);
                self.editedIndex = -1;
            });
        },

        async save(item) {
            const self = this;

            self.loader = true;

            item.projectId = self.$route.params.projectId;
            item.worksPackageId = self.$route.params.worksPackageId;
            await api.storeCompanyQuestionnaire(item, item.id);
            await self.load();

            self.loader = false;

            this.close();
        },

    },
};
</script>

<style>
</style>
