<template>
    <div style="margin: 1em">
        <h2>User Roles</h2>

        <v-data-table mobile-breakpoint="1000" item-key="id" :headers="headers" :items="items"
                      :server-items-length="serverItemsLength"
                      :loading="loader" :options.sync="options" loading-text="Loading... Please wait"></v-data-table>
    </div>
</template>

<script>
import api from "../common/api.js";

export default {
    data() {
        return {
            loader: false,
            headers: [{
                text: "ID",
                value: "id",
                sortable: true
            },
                {
                    text: "Name",
                    value: "name",
                    sortable: true
                },
                {
                    text: "Code",
                    value: "slug",
                    sortable: true
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
    },

    methods: {
        async load() {
            const self = this;
            self.loader = true;

            let params = {
                ...self.options,
                page: self.options.page,
                itemsPerPage: self.options.itemsPerPage,
                sortBy: self.options.sortBy[0],
                sortDesc: self.options.sortDesc[0] ? 1 : 0,
            };

            try {
                const response = await api.loadRoles(params)
                self.items = response.data.data;
                self.serverItemsLength = response.data.total;
            } catch (e) {
            }

            self.loader = false;
        },
    },
};
</script>

<style>
</style>
