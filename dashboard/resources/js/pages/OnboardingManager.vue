<template>
    <div style="margin: 1em">
        <v-data-table
            :headers="headers"
            :items="items"
            :loading="loader"
            :options.sync="options"
            item-key="id"
            loading-text="Loading... Please wait"
        >
            <template v-slot:top>

            </template>

            <template v-slot:item.company_type="{ item }">
                <ul class="company-type-list">
                    <li v-for="type in getCompanyTypes(item)">
                        {{ type }}
                    </li>
                </ul>
            </template>

            <template v-slot:item.file_cas="{ item }">
                <a v-if="getItemAttachmentUrl(item, 'cas')" :href="getItemAttachmentUrl(item, 'cas')" target="_blank">
                    <v-icon>mdi-download</v-icon>
                </a>
            </template>

            <template v-slot:item.file_cyber="{ item }">
                <a v-if="getItemAttachmentUrl(item, 'cyber')" :href="getItemAttachmentUrl(item, 'cyber')" target="_blank">
                    <v-icon>mdi-download</v-icon>
                </a>
            </template>

            <template v-slot:item.file_ssip="{ item }">
                <a v-if="getItemAttachmentUrl(item, 'ssip')" :href="getItemAttachmentUrl(item, 'ssip')" target="_blank">
                    <v-icon>mdi-download</v-icon>
                </a>
            </template>

            <template v-slot:item.file_fire_products="{ item }">
                <a v-if="getItemAttachmentUrl(item, 'fire_products')" :href="getItemAttachmentUrl(item, 'fire_products')" target="_blank">
                    <v-icon>mdi-download</v-icon>
                </a>
            </template>

            <template v-slot:item.file_fire_competencies="{ item }">
                <a v-if="getItemAttachmentUrl(item, 'fire_competencies')" :href="getItemAttachmentUrl(item, 'fire_competencies')" target="_blank">
                    <v-icon>mdi-download</v-icon>
                </a>
            </template>

            <template v-slot:item.file_cscs="{ item }">
                <a v-if="getItemAttachmentUrl(item, 'cscs')" :href="getItemAttachmentUrl(item, 'cscs')" target="_blank">
                    <v-icon>mdi-download</v-icon>
                </a>
            </template>

            <template v-slot:item.file_induction="{ item }">
                <a v-if="getItemAttachmentUrl(item, 'induction')" :href="getItemAttachmentUrl(item, 'induction')" target="_blank">
                    <v-icon>mdi-download</v-icon>
                </a>
            </template>

            <template v-slot:item.file_modern_slavery="{ item }">
                <a v-if="getItemAttachmentUrl(item, 'modern_slavery')" :href="getItemAttachmentUrl(item, 'modern_slavery')" target="_blank">
                    <v-icon>mdi-download</v-icon>
                </a>
            </template>

            <template v-slot:item.file_bribery="{ item }">
                <a v-if="getItemAttachmentUrl(item, 'bribery')" :href="getItemAttachmentUrl(item, 'bribery')" target="_blank">
                    <v-icon>mdi-download</v-icon>
                </a>
            </template>

            <template v-slot:item.file_conduct="{ item }">
                <a v-if="getItemAttachmentUrl(item, 'conduct')" :href="getItemAttachmentUrl(item, 'conduct')" target="_blank">
                    <v-icon>mdi-download</v-icon>
                </a>
            </template>

            <template v-slot:item.file_mod="{ item }">
                <a v-if="getItemAttachmentUrl(item, 'mod')" :href="getItemAttachmentUrl(item, 'mod')" target="_blank">
                    <v-icon>mdi-download</v-icon>
                </a>
            </template>

            <template v-slot:item.file_pref_suppliers="{ item }">
                <a v-if="getItemAttachmentUrl(item, 'pref_suppliers')" :href="getItemAttachmentUrl(item, 'pref_suppliers')" target="_blank">
                    <v-icon>mdi-download</v-icon>
                </a>
            </template>

            <template v-slot:item.file_user_roles="{ item }">
                <a v-if="getItemAttachmentUrl(item, 'user_roles')" :href="getItemAttachmentUrl(item, 'user_roles')" target="_blank">
                    <v-icon>mdi-download</v-icon>
                </a>
            </template>

            <template v-slot:item.actions="{ item }">
                <div class="text-center">

                </div>
            </template>
        </v-data-table>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";

export default {
    name: 'OnboardingManager',
    props: [],
    components: {

    },
    data() {
        return {
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
                    text: "Address 1",
                    value: "company_address1",
                    sortable: true,
                },
                {
                    text: "Address 2",
                    value: "company_address2",
                    sortable: true,
                },
                {
                    text: "City",
                    value: "company_city",
                    sortable: true,
                },
                {
                    text: "Postcode",
                    value: "company_postcode",
                    sortable: true,
                },
                {
                    text: "Company Reg No",
                    value: "company_registration_number",
                    sortable: true,
                },
                {
                    text: "Company Vat No",
                    value: "company_vat_number",
                    sortable: true,
                },
                {
                    text: "Company Type",
                    value: "company_type",
                    sortable: true,
                },
                {
                    text: "CAS Certifying Body",
                    value: "cas_certifying_body_str",
                    sortable: true,
                },
                {
                    text: "CAS",
                    value: "file_cas",
                    sortable: false,
                },
                {
                    text: "Cyber",
                    value: "file_cyber",
                    sortable: false,
                },
                {
                    text: "SSIP",
                    value: "file_ssip",
                    sortable: false,
                },
                {
                    text: "Fire Products",
                    value: "file_fire_products",
                    sortable: false,
                },
                {
                    text: "Fire Competencies",
                    value: "file_fire_competencies",
                    sortable: false,
                },
                {
                    text: "CSCS",
                    value: "file_cscs",
                    sortable: false,
                },
                {
                    text: "Induction",
                    value: "file_induction",
                    sortable: false,
                },
                {
                    text: "Modern Slavery",
                    value: "file_modern_slavery",
                    sortable: false,
                },
                {
                    text: "Bribery & Corruption",
                    value: "file_bribery",
                    sortable: false,
                },
                {
                    text: "Code of Conduct",
                    value: "file_conduct",
                    sortable: false,
                },
                {
                    text: "MOD",
                    value: "file_mod",
                    sortable: false,
                },
                {
                    text: "Preferred Suppliers",
                    value: "file_pref_suppliers",
                    sortable: false,
                },
                {
                    text: "User Roles",
                    value: "file_user_roles",
                    sortable: false,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: false,
                },
            ],
            options: {
                page: 1,
                itemsPerPage: 20,
                sortBy: ["id"],
                sortDesc: [true],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false,
            },
            items: [],
        };
    },

    mounted() {
        //this.load();
    },

    watch: {
        options: {
            handler(n, o) {
                this.load();
            },
            deep: true,
        },
    },

    computed: {

    },

    methods: {
        load() {
            const self = this;

            let params = {
                ...self.options,
                page: self.options.page,
                itemsPerPage: self.options.itemsPerPage,
                sortBy: self.options.sortBy[0],
                sortDesc: self.options.sortDesc[0] ? 1 : 0,
            };

            api.loadContractorOnboarding(params)
                .then((response) => {
                    self.items = response.data.data;
                })
                .catch((error) => {
                    console.log(error);
                });
        },
        getItemAttachmentUrl(item, type) {
            let filtered = item.attachments.filter((file) => {
                return file.description === type;
            });

            if (filtered.length > 0) {
                return filtered[0].url;
            }

            return null;
        },
        getCompanyTypes(item) {
            let types = [
                'developer',
                'framework',
                'main_contractor',
                'sub_contractor',
                'merchant',
                'manufacturer',
            ];

            let result = [];

            types.forEach((type) => {
               if (item[type]) {
                   result.push(type);
               }
            });

            return result;
        }
    },
};
</script>

<style>
.company-type-list {
    list-style: none;
    font-size: 13px;
    padding: 0;
    margin: 0;
}
</style>
