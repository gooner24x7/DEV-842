<template>
    <v-flex md6 p-2>
        <v-card class="partner-block">
            <div class="text-center m-2 mx-md-10 my-md-2 text-md-left">
                <h3>{{ plan.product.name }}</h3>

                <v-card-text class="px-0">
                    State: {{ plan.active }}<br>
                    Price: {{ plan.amount_decimal }} {{ plan.currency }}<br>
                    Interval: {{ plan.interval }}
                </v-card-text>
            </div>
        </v-card>
    </v-flex>
</template>

<script>
export default {
    props: ["plan"],
    name: "StripePlan"
}
</script>

<style scoped>

</style>
