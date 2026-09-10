<template>
    <div>
        <table class="quotes-table">
            <tr>
                <th>Postcode Area</th>
                <th>Total Materials</th>
                <th>Total Trades</th>
            </tr>
            <tr v-for="(value, key) in districts" :key="key">
                <td>{{ key }}</td>
                <td>{{ value.materials | formatPrice }}</td>
                <td>{{ value.trades | formatPrice }}</td>
            </tr>
        </table>
    </div>
</template>

<script>
import api from "../../common/api"
import permissions from "../../common/permissions";

export default {
    name: "PostcodeDistrictsTable",
    props: ['districts'],
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
