<template>
    <v-card>
        <v-card-text>
            <h2>Score Comparison</h2>
            <v-row>
                <v-col cols="4" v-for="(v, index) in companiesWithScores" class="text-center" :key="index">
                    <h2>{{ v.company_name }}</h2>
                    <div class="largeScore">{{ v.totalScore || 0 }}</div>
                    <v-btn @click="accept(v.id)">Accept</v-btn>
                    <v-btn @click="decline(v.id)">Decline</v-btn>
                </v-col>
            </v-row>
        </v-card-text>
    </v-card>
</template>

<script>
import api from "../common/api"

export default {
    name: "CompareScores",
    props: ["companies", "visible"],
    watch: {
        'visible': {
            handler(n, o) {
                if (n === false) {
                    return n
                }

                this.loadScores(n)

                return n
            }
        }
    },
    data() {
        return {
            companiesWithScores: [],
        }
    },
    mounted() {
        this.loadScores();
    },
    methods: {
        loadScores() {
            const self = this;
            api.loadScores(this.companies.map((item) => {return item.id})).then((response) => {
                self.companiesWithScores = response.data;
            })
        },
        accept(id) {
            this.$emit('accept', id)
        },
        decline(id) {
            this.$emit('decline', id)
        }
    }
}
</script>

<style scoped>
.largeScore {
    font-size: 100px;
    line-height: 100px;
    margin: 20px 0;
}
</style>
