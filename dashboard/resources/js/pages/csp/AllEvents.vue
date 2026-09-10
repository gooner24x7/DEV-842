<template>
    <div>
        <v-data-table mobile-breakpoint="1000"
                      item-key="id" :headers="headers" :items="items" :server-items-length="serverItemsLength"
                      :loading="loader" :options.sync="options" loading-text="Loading... Please wait"></v-data-table>
    </div>
</template>

<script>
import api from "../../common/api";

export default {
    name: 'AllEvents',
    props: ['userId'],
    data() {
        return {
            headers: [
                {
                    text: "Inquiry Id",
                    value: "inquiry_id",
                    sortable: true
                },
                {
                    text: "Manager Name",
                    value: "author_name",
                    sortable: true
                },
                {
                    text: "Called At",
                    value: "called_at",
                    sortable: true,
                },
                {
                    text: "Comment",
                    value: "comment",
                    sortable: false,
                },
                {
                    text: "Created At",
                    value: "created_at",
                    sortable: true,
                }
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
            loader: false,
        }
    },
    mounted() {
        this.load()
    },
    watch: {
        'userId': function () {
            this.load();
        }
    },
    methods: {
        async load() {
            const self = this;
            self.loader = true;

            let params = {
                ...self.options,
                page: self.options.page,
                itemsPerPage: self.options.itemsPerPage,
                sortBy: self.options.sortBy[0],
                sortDesc: self.options.sortDesc[0] ? 1 : 0,
                merchantId: this.userId,
            };

            try {
                const response = await api.loadCalledMerchants(params)
                self.items = response.data.data;
                self.serverItemsLength = response.data.total;
            } catch (e) {
            }

            self.loader = false;
        }
    }
}
</script>

<style scoped>

</style>
