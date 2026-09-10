<template>
    <div style="text-align: center" v-if="virtualExpos.length > 0">
        <h3 style="text-align: left">Latest exhibitions</h3>
        <v-slide-group
        >
            <v-slide-item
                v-for="(item,i) in virtualExpos"
                :key="i"
                reverse-transition="fade-transition"
                transition="fade-transition"
            >
                <v-card width="350" class="ma-4">
                    <v-card-text>
                        <v-img :src="item.banner_left_url ?? item.banner_url" style="width: 100%;"  v-on:click="viewItem(item)"
                       :alt="item.company_name" cover></v-img>
                    </v-card-text>
                </v-card>

            </v-slide-item>
        </v-slide-group>
    </div>
</template>

<script>
import api from "../common/api";

export default {
    name: "VirtualExpoGoldCarousel",
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
            'location': 'expo-gold-carousel',
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
                //matchingProducts: 1,
                onlyGold: 1
            })

            self.virtualExpos = response.data.data;

          self.virtualExpos.map((item) => {
            api.newAnalyticsEvent({
              'object_id': item.id,
              'object_type': 'virtual-expo',
              'action': 'view',
              'location': 'expo-gold-carousel',
              'url': window.location.pathname,
            });
          })
        }
    }
}
</script>

<style scoped>

</style>
