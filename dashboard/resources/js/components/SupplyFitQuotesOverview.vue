<template>
    <div>
        <h2>Overview</h2>
        <div class="row">
            <div class="col"><v-sheet class="pa-2">Price</v-sheet></div>
            <div class="col"><v-sheet class="pa-2">Actual Price</v-sheet></div>
            <div class="col text-right"><v-sheet class="pa-2">&pound;{{ stats.actualPrice }}</v-sheet></div>
            <div class="col"><v-sheet class="pa-2">Min. Price</v-sheet></div>
            <div class="col text-right"><v-sheet class="pa-2">&pound;{{ stats.minPrice }}</v-sheet></div>
            <div class="col"><v-sheet class="pa-2">Max. Price</v-sheet></div>
            <div class="col text-right"><v-sheet class="pa-2">&pound;{{ stats.maxPrice }}</v-sheet></div>
            <div class="col"><v-sheet class="pa-2">Total Saved Price</v-sheet></div>
            <div class="col text-right"><v-sheet class="pa-2">&pound;{{ stats.totalSavedPrice }}</v-sheet></div>
        </div>
        <div class="row">
            <div class="col"><v-sheet class="pa-2">ESG</v-sheet></div>
            <div class="col"><v-sheet class="pa-2">Actual ESG</v-sheet></div>
            <div class="col text-right"><v-sheet class="pa-2">%{{ stats.actualEsg }}</v-sheet></div>
            <div class="col"><v-sheet class="pa-2">Min. ESG</v-sheet></div>
            <div class="col text-right"><v-sheet class="pa-2">%{{ stats.minEsg }}</v-sheet></div>
            <div class="col"><v-sheet class="pa-2">Max. ESG</v-sheet></div>
            <div class="col text-right"><v-sheet class="pa-2">%{{ stats.maxEsg }}</v-sheet></div>
            <div class="col"><v-sheet class="pa-2">Total Saved ESG</v-sheet></div>
            <div class="col text-right"><v-sheet class="pa-2">%{{ stats.totalSavedEsg }}</v-sheet></div>
        </div>
    </div>
</template>

<script>
import api from "../common/api";

export default {
    name: "SupplyFitQuotesOverview",
    props: ['enquiryId'],
    data() {
        return {
            stats: {
                actualPrice: 0,
                minPrice: 0,
                maxPrice: 0,
                totalSavedPrice: 0,
                actualEsg: 0,
                minEsg: 0,
                maxEsg: 0,
                totalSavedEsg: 0,
            }
        }
    },
    watch: {
        'enquiryId': async function () {
            await this.getStats(this.enquiryId)
        },
    },
    async mounted() {
        await this.getStats(this.enquiryId)
    },
    methods: {
        async getStats(enquiryId) {
            if (!enquiryId) {
                return
            }

            const result = await api.getSupplyFitQuotesOverview(enquiryId)

            this.stats = result.data
        }
    }
}
</script>

<style scoped>

</style>
