<template>
    <div style="margin: 1em">
        <v-row>
            <v-col>
                <h2>Preferred Sub-contractors</h2>
            </v-col>
            <v-col>
                <v-btn
                    class="m-2 float-right"
                    color="primary"
                    @click="sendEmail()"
                >
                    Send Email
                </v-btn>
            </v-col>
        </v-row>

        <preferred-subcontractors-table :input-filters="filters" />
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";
import PreferredSubcontractorsTable from "../components/PreferredSubcontractorsTable.vue";

export default {
    name: 'SupplyChainSubcontractors',
    props: [],
    components: {PreferredSubcontractorsTable},
    data() {
        return {
            filters: {
                search: null,
                region: null,
                project: null,
                worksPackage: null,
                products: [],
                radius: null,
                certType: null,
                certStatus: null,
            },
        }
    },

    async mounted() {
        const self = this;

        self.filters.certType = self.$route.query.type ?? null;
        self.filters.certStatus = (self.$route.query.status !== 'undefined' && self.$route.query.status !== null) ? parseInt(self.$route.query.status) : null;
    },

    watch: {

    },

    computed: {

    },

    methods: {
        sendEmail() {
            console.log('send email to selected subcontractors');
        }
    },
};
</script>

<style>

</style>
