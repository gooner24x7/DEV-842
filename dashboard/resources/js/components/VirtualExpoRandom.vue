<template>
<div>
    <v-card>
        <v-card-title>
            {{item.company_name}}
        </v-card-title>
        <v-card-text>
            <v-img v-on:click="viewItem(item)" :src="item.banner_left_url ?? item.banner_url"></v-img>
        </v-card-text>
        <v-card-actions>
            <v-btn v-on:click="viewItem(item)" class="primary">View exhibition</v-btn>
            <v-btn v-on:click="close(item)">Close</v-btn>
        </v-card-actions>
    </v-card>
</div>
</template>

<script>

import api from "../common/api";

export default {
    name: "VirtualExpoRandom",
    props: ['item'],
    data() {
        return {

        }
    },
    methods: {
        close() {
            this.$emit("close", {});
        },
        viewItem(item) {
            api.newAnalyticsEvent({
                'object_id': item.id,
                'object_type': 'virtual-expo',
                'action': 'click',
                'location': 'expo-random',
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
    }
}
</script>

<style scoped>

</style>
