<template>
    <div style="margin: 1em">
        <h2>Manage House List</h2>

        <housebuilding-budgets-table category="Grand Totals" :houseNames="houseNames" :budgets="budgets['Grand Totals']"></housebuilding-budgets-table>

        <template v-for="category in categories">
            <housebuilding-budgets-table :category="category" :houseNames="houseNames" :budgets="budgets[category]"></housebuilding-budgets-table>
        </template>

        <v-data-table
            :headers="computedHeaders"
            :items="items"
            :server-items-length="serverItemsLength"
            :loading="loader"
            :options.sync="options"
            item-key="id"
            loading-text="Loading... Please wait"
        >
            <template v-slot:top>
                <v-row>
                    <v-col cols="8">

                    </v-col>
                    <v-col cols="4">
                        <v-text-field v-model="filters.search" dense outlined label="Search"></v-text-field>
                    </v-col>
                </v-row>
            </template>
        </v-data-table>

    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";
import HousebuildingBudgetsTable from "../components/HousebuildingBudgetsTable.vue";

export default {
    name: "HousebuildingProducts",
    props: [],
    components: {
        HousebuildingBudgetsTable
    },
    data() {
        return {
            loader: false,
            serverItemsLength: 0,
            items: [],
            categories: [],
            houseNames: [],
            budgets: [],
            defaultHeaders: [
                {
                    text: "Category",
                    value: "category",
                    sortable: true,
                },
                {
                    text: "Product Name",
                    value: "product_name",
                    sortable: true,
                },
                {
                    text: "Product Ref",
                    value: "product_ref",
                    sortable: true,
                },
                {
                    text: "Unit of Measurement",
                    value: "uom",
                    sortable: true,
                },
            ],
            houseHeaders: [],
            options: {
                page: 1,
                itemsPerPage: 10,
                sortBy: [],
                sortDesc: [true],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false,
            },
            filters: {
                search: null
            }
        };
    },

    async mounted() {
        //await this.load();
        await this.loadCategories();
        await this.loadHouseNames();
        await this.loadTotals();
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
        computedHeaders() {
            const self = this;

            let headers = [];

            self.defaultHeaders.forEach((headerObj) => {
                headers.push(headerObj);
            });

            self.houseHeaders.forEach((value) => {
                let headerObj = {
                    text: value,
                    value: value,
                    sortable: true,
                };

                headers.push(headerObj);
            });

            return headers;
        }
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
                search: self.filters.search
            };

            try {
                const response = await api.loadHousebuildingProducts(params);

                self.items = response.data.data;
                self.serverItemsLength = response.data.total;
                self.houseHeaders = response.data.headers ?? [];
            } catch (e) {
                console.log(e);
            }

            self.loader = false;
        },
        async loadCategories() {
            const self = this;

            let response = await api.getHousebuildingCategories();
            self.categories = response.data;
        },
        async loadHouseNames() {
            const self = this;

            let response = await api.getHouseNames();
            self.houseNames = response.data;
        },
        async loadTotals() {
            const self = this;

            let response = await api.getHousebuildingTotals();
            self.budgets = response.data;
        },
    },
};
</script>

<style>

</style>
