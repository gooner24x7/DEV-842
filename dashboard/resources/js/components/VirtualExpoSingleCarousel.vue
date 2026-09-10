<template>
    <div style="text-align: center" v-if="virtualExpos.length > 0">
        <v-carousel
            hide-delimiters
            :continuous="true"
            :show-arrows="false"
            cycle
            style="width: 230px; height: 150px; "
        >
            <v-carousel-item
                v-for="(item,i) in virtualExpos"
                :key="i"
                reverse-transition="fade-transition"
                transition="fade-transition"
                v-on:click="viewItem(item)"
            >
                <v-img  :src="item.banner_left_url ?? item.banner_url"
                        :alt="item.company_name" style="max-height: 150px !important;"></v-img>
            </v-carousel-item>
        </v-carousel>
    </div>
</template>

<script>
import api from "../common/api";

export default {
    name: "VirtualExpoSingleCarousel.vue",
    data() {
        return {
            virtualExpos: [],
        };
    },
    async mounted() {
        const self = this;

        self.virtualExpos = [];

        await self.loadVirtualExpoAdd();
    },
    methods: {
        viewItem(item) {
            api.newAnalyticsEvent({
                'object_id': item.id,
                'object_type': 'virtual-expo',
                'action': 'click',
                'location': 'expo-single-banner',
                'url': window.location.pathname,
            });

            this.$router.push({
                name: 'virtualExpoItem',
                query: {
                    t: new Date().getTime()
                },
                params: {
                    'virtual_expo_id': item.id
                }
            });
        },

        async loadVirtualExpoAdd()
        {
            const self = this;
            const response = await api.getVirtualExpos({
                // matchingProducts: 1
            })

            self.virtualExpos = response.data.data;

            self.virtualExpos.map((item) => {
                api.newAnalyticsEvent({
                    'object_id': item.id,
                    'object_type': 'virtual-expo',
                    'action': 'view',
                    'location': 'expo-single-banner',
                    'url': window.location.pathname,
                });
            })
        }
    }
}
</script>

<style scoped>

</style>
