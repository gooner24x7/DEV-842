<template>
    <div>
        <table id="projectReportTable">
            <tr>
                <th>Sub contractor name</th>
                <th>Work packages</th>
                <th>Enquiries</th>
                <th>Quotes</th>
            </tr>
            <tr v-for="(item, i) in reportData" :key="i">
                <td>{{ item.user_name }}</td>
                <td>{{ item.total_works_packages }}</td>
                <td>{{ item.total_questions }}</td>
                <td>{{ item.total_answers }}</td>
            </tr>
            <tr v-if="!reportData.length">
                <td colspan="4">No data available</td>
            </tr>
        </table>
    </div>
</template>

<script>
import api from "../../common/api"

export default {
    name: "ProjectReportTable",
    props: ['projectId'],
    components: {},

    data() {
        return {
            reportData: [],
        }
    },

    mounted() {
        const self = this;

        self.load();
    },

    methods: {
        async load() {
            const self = this;

            const response = await api.getProjectReport(self.projectId);
            self.reportData = response.data;
        }
    }
}
</script>

<style scoped>

</style>
