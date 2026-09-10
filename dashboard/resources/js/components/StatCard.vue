<template>
    <div>
         <v-card :class="cardClass" @click="goToSubcontractors(type, status)" hover>
             <v-card-text style="padding: 12px">
                 <div class="stat-card-title">{{ title }}</div>
                 <div class="stat-card-value">{{ value }}</div>
             </v-card-text>
         </v-card>
    </div>
</template>

<script>
import api from "../common/api";

export default {
    name: "StatCard",
    props: ["title", "value", "type", "status", "cardClass"],

    data() {
        return {

        }
    },

    watch: {

    },

    methods: {
        goToSubcontractors(type = null, status = 2) {
            console.log(type, status);
            this.$router.push({
                name: "supply-chain-subcontractors",
                query: {
                    type: type,
                    status: status
                }
            });
        }
    }
}
</script>

<style scoped>
.stat-card-title {
    text-align: center;
    font-size: 20px;
    font-weight: bold;
    line-height: 22px;
}

.stat-card-value {
    text-align: center;
    padding: 20px;
    font-size: 16px;
}

.stat-card-info {
    border: 1px solid #264dec !important;
    background-color: #f3f4ff !important;
}

.stat-card-yes {
    border: 1px solid green !important;
    background-color: #f3fff3 !important;
}

.stat-card-no {
    border: 1px solid red !important;
    background-color: #fff3f3 !important;
}
</style>
