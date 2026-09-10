<template>
    <div style="margin: 1em">
        <h2>Favourites</h2>

        <v-autocomplete
            class="col-md-3"
            v-model="branchIdSelected"
            :items="getBranchOptions()"
            outlined
            dense
            label="Branch Selected"
            clearable
            item-text="name"
            item-value="id"
        ></v-autocomplete>

        <favorite-purchase-hire-inquiries :branch-id="branchIdSelected"></favorite-purchase-hire-inquiries>

        <h2>Branches</h2>

        <div class="col-md-6 row">
            <v-autocomplete
                class="col-md-3 mr-2"
                :search-input.sync="searchQuoted"
                v-model="isQuoted"
                :items="['all', 'yes', 'no']"
                outlined
                dense
                label="Already Quoted"
                clearable

            ></v-autocomplete>

            <v-checkbox class="mr-2" label="Only unexpired" v-model="onlyUnexpired"></v-checkbox>

            <v-btn small v-on:click="downloadMerchantReport(user().id)">Get Report</v-btn>
        </div>

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
                <template v-slot:item.actions="{ item }">
                    <div class="text-center">
                        <v-btn color="primary" small @click="openItem(item)"
                        >Open inquiries
                        </v-btn
                        >

                        <v-btn color="primary" small v-on:click="downloadMerchantReport(item.id)">Get Report</v-btn>
                    </div>
                </template>
            </v-data-table>

        </div>
    </div>
</template>

<script>
import api from "../common/api";
import FavoritePurchaseHireInquiries from "../components/FavoritePurchaseHireInquiries";
import * as XLSX from "xlsx";

export default {
    name: "ManageBranches.vue",
    components: { FavoritePurchaseHireInquiries },
    data() {
        return {
            loader: false,
            searchQuoted: '',
            isQuoted: "no",
            headers: [
                {
                    text: "ID",
                    value: "id",
                    sortable: true,
                },
                {
                    text: "First Name",
                    value: "first_name",
                    sortable: true,
                },
                {
                    text: "Last Name",
                    value: "last_name",
                    sortable: true,
                },
                {
                    text: "Email",
                    value: "email",
                    sortable: true,
                },
                {
                    text: "Role",
                    value: "role_name",
                    sortable: true,
                },
                {
                    text: "Subscription",
                    value: "subs_name",
                    sortable: false,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: false,
                },
            ],
            onlyUnexpired: false,
            items: [],
            serverItemsLength: 0,
            branchIdSelected: 0,
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
        };
    },
    watch: {
        options: {
            handler(n, o) {
                this.load();
            },
            deep: true,
        },
    },
    mounted() {
        this.load();


    },
    methods: {
        async downloadMerchantReport(id) {
            const report = await api.supplierReport(id);

            const data = XLSX.utils.json_to_sheet(report.data.items, {})

            const wb = XLSX.utils.book_new()
            XLSX.utils.book_append_sheet(wb, data, 'data')
            XLSX.writeFile(wb,'demo.xlsx')
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        getBranchOptions() {
          return this.items.map( (item) => { return { id: item.id, name: item.first_name } } )
        },
        openItem(item) {
            this.$router.push({
                name: 'branch-inquiries',
                params: {
                    branchId: item.id,
                    isQuoted: this.isQuoted,
                    onlyUnexpiredDefault: this.onlyUnexpired
                }
            });
        },
        load() {
            const self = this;
            self.loader = true;

            let billUserId = self.$store.getters.getSession.user.id

            let params = {
                ...self.options,
                page: self.options.page,
                itemsPerPage: self.options.itemsPerPage,
                sortBy: self.options.sortBy[0],
                sortDesc: self.options.sortDesc[0] ? 1 : 0,
            };



            api
                .loadBranches(billUserId, params)
                .then((response) => {
                    self.items = response.data.data;

                    self.serverItemsLength = response.data.total;

                    self.loader = false;

                    self.branchIdSelected = self.getBranchOptions()[0].id ?? 0
                })
                .catch((err) => {
                    self.loader = false;
                });
        },
    }
}
</script>

<style scoped>

</style>
