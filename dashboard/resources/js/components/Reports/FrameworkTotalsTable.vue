<template>
    <div>
        <table class="quotes-table">
            <tr>
                <th></th>
                <th>Framework</th>
                <th>Client</th>
            </tr>
            <tr>
                <td>Within 0-10 Miles</td>
                <td>{{ totals[0] | formatPrice }}</td>
                <td>{{ totals[0] | formatPrice }}</td>
            </tr>
            <tr>
                <td>Within 0-20 Miles</td>
                <td>{{ totals[1] | formatPrice }}</td>
                <td>{{ totals[1] | formatPrice }}</td>
            </tr>
            <tr>
                <td>Within 0-40 Miles</td>
                <td>{{ totals[2] | formatPrice }}</td>
                <td>{{ totals[2] | formatPrice }}</td>
            </tr>
            <tr>
                <td>Within 0-40+ Miles</td>
                <td>{{ totals[3] | formatPrice }}</td>
                <td>{{ totals[3] | formatPrice }}</td>
            </tr>
            <tr>
                <td>Total Price</td>
                <td>{{ totals[3] | formatPrice }}</td>
                <td>{{ totals[3] | formatPrice }}</td>
            </tr>
        </table>
    </div>
</template>

<script>
import api from "../../common/api"

export default {
    name: "FrameworkTotalsTable",
    props: ['totals'],
    components: {},

    data() {
        return {

        }
    },

    mounted() {
        const self = this;
    },

    methods: {

    }
}
</script>

<style scoped>

</style>
