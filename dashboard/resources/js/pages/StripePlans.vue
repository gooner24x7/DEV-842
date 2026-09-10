<template>
    <div style="margin: 1em">
        <h2>Subscription Plans</h2>

        <v-layout row wrap>
            <stripe-plan v-for="item in plans" :key="item.id" :plan="item"></stripe-plan>
        </v-layout>
    </div>
</template>

<script>
import StripePlan from "../components/StripePlan";
import api from "../common/api.js";

export default {
    components: {
        StripePlan,
    },
    name: "StripePlans",
    data() {
        return {
            plans: [],
        }
    },
    mounted() {
        this.loadPlans()
    },
    methods: {
        loadPlans() {
            const self = this;

            api.getStripePlans({})
                .then((result) => {
                    self.plans = result.data
                })
                .catch(error => error.log(error))
        }
    }
}
</script>

<style scoped>

</style>
