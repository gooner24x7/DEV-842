<template>
    <div style="margin: 1em">
<!--        <h2>Preferred Suppliers</h2>-->

        <div style="position: relative; padding-top: 1em">
            <v-data-table
                item-key="id"
                :headers="headers"
                :items="items"
                :server-items-length="serverItemsLength"
                :loading="loader"
                :options.sync="options"
                loading-text="Loading... Please wait"
                mobile-breakpoint="1000"
            >
                <template v-slot:top>
                    <v-row>
                        <v-col>
                            <v-text-field v-model="filters.search" dense outlined label="Search"></v-text-field>
                        </v-col>
                    </v-row>
                </template>

                <template v-slot:item.attachment_url="{ item }">
                    <a
                        v-if="item.preferred_suppliers_file"
                        :href="getPreferredSuppliersFile(item)"
                        target="_blank"
                    >
                        <v-icon>mdi-download</v-icon>
                    </a>
                </template>

                <template v-slot:item.file_uploaded="{ item }">
                    <v-checkbox :input-value="!!item.preferred_suppliers_file" readonly></v-checkbox>
                </template>

            </v-data-table>
        </div>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions.js";

export default {
    props: [],
    components: {},
    data() {
        return {

            loader: false,
            headers: [
                {
                    text: "User ID",
                    value: "id",
                    sortable: true,
                },
                {
                    text: "Company Name",
                    value: "first_name",
                    sortable: true,
                },
                {
                    text: "Preferred Suppliers",
                    value: "attachment_url",
                    sortable: true,
                },
                {
                    text: "Uploaded",
                    value: "file_uploaded",
                    sortable: true,
                },
            ],
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
            filtersLoader: false,
            filters: {
                search: null,
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

        'filters.search'(val) {
            if (val.length > 2) {
                this.load();
            } else if (val.length === 0) {
                this.load();
            }
        }
    },

    computed: {

    },

    methods: {
        getPreferredSuppliersFile(item) {
            return api.baseUrl + '/downloadFile?file=' + encodeURIComponent(item.preferred_suppliers_file);
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
                search: self.filters.search,
                role_id: 2,
            };

            // if searching then omit paging params
            if (self.filters.search && self.filters.search.length > 0) {
                params.page = 1;
                params.itemsPerPage = -1;
            }

            // change sort fields
            if (params.sortBy === 'attachment_url' || params.sortBy === 'file_uploaded') {
                params.sortBy = 'preferred_suppliers_file';
            }

            try {
                const response = await api.loadUsers(params)
                self.items = response.data.data;
                self.serverItemsLength = response.data.total;
            } catch (e) {}

            self.loader = false;
        },

        async save(user) {
            const self = this;

            self.loader = true;

            // let formData = new FormData();
            // this.buildFormData(formData, user)

            // await api.storeUser(formData, user.id)
            // await self.load();

            self.loader = false;
        },
    },
};
</script>

<style>

</style>
