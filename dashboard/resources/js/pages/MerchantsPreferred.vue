<template>
    <div style="margin: 1em">
<!--        <h2>Preferred Merchants</h2>-->

        <v-btn v-on:click="nudgeSales()">Nudge Sales</v-btn>

        <div v-if="isAdmin()">
            <ul class="list-inline">
                <li><b>Enquiry ID:</b> {{ this.inquiryId }}</li>
                <li><b>Lat:</b> {{ this.lat }}</li>
                <li><b>Long:</b> {{ this.long }}</li>

                <v-btn color="primary" small @click="showMerchants()">Merchants</v-btn>
                <v-btn color="primary" small @click="showMerchantsPreferred()">Preferred Merchants</v-btn>
            </ul>
        </div>

        <v-data-table mobile-breakpoint="1000" item-key="id" :headers="headers" :items="items"
                      :server-items-length="serverItemsLength" :loading="loader" :options.sync="options"
                      loading-text="Loading... Please wait">
            <template v-slot:item.actions="{ item }">
                <login-as-button :user-id="item.id" v-if="isAdmin()"></login-as-button>
            </template>
        </v-data-table>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";
import LoginAsButton from "../components/LoginAsButton.vue";

export default {
    components: {LoginAsButton},
    data() {
        return {
            loader: false,
            inquiryId: 0,
            lat: 0,
            long: 0,
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
                    text: "Postcode",
                    value: "postcode",
                    sortable: true,
                },
                {
                    text: "Email",
                    value: "email",
                    sortable: true,
                },
                {
                    text: "Phone",
                    value: "phone",
                    sortable: true,
                },
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
        const self = this;

        self.inquiryId = self.$route.params.inquiry_id;
        self.lat = self.$route.params.lat;
        self.long = self.$route.params.long;
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
        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },

        async nudgeSales() {
            const self = this;
            await api.sendNudgeSalesEmail(self.inquiryId);

            alert('Email has been sent');
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
            };

            const response = await api.getMerchantsPreferred(self.inquiryId, params)

            try {
                self.items = response.data.data;

                self.serverItemsLength = response.data.total;
            } catch (e) {
            }

            self.loader = false;
        },

        showMerchants() {
            this.$router.push({
                name: "seen-by-merchants",
                params: {inquiry_id: this.inquiryId, lat: this.lat, long: this.long},
            });
        },

        showMerchantsPreferred() {
            this.$router.push({
                name: "merchants-preferred",
                params: {inquiry_id: this.inquiryId, lat: this.lat, long: this.long},
            });
        },
    },
};
</script>

<style>
    .list-inline {
        list-style: none;
        margin: 10px 0;
        padding-left: 4px;
    }

    .list-inline > li {
        display: inline;
        margin-right: 10px;
    }
</style>

