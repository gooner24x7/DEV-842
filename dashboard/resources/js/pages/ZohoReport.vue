<template>
    <div style="margin: 1em">
<!--        <h2>Zoho Report</h2>-->

        <v-container>
            <v-row>
                <v-col cols="12">
                    <form action="/zoho-report" method="POST">
                        <input type="hidden" name="_token" :value="getCsrf()">
                        <v-container>
                            <v-row>
                                <v-col cols="3">&nbsp;</v-col>
                                <v-col cols="3">Ideal Scenario</v-col>
                                <v-col cols="3">Minimum National Launch</v-col>
                                <v-col cols="3">Yorkshire Only Launch</v-col>
                            </v-row>
                            <v-row>
                                <v-col cols="3">National Suppliers</v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[0][0]" v-model="planToLaunch[0][0]"
                                                  dense outlined/>
                                </v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[0][1]" v-model="planToLaunch[0][1]"
                                                  dense outlined/>
                                </v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[0][2]" v-model="planToLaunch[0][2]"
                                                  dense outlined/>
                                </v-col>
                            </v-row>
                            <v-row>
                                <v-col cols="3">Regional Suppliers</v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[1][0]" v-model="planToLaunch[1][0]"
                                                  dense outlined/>
                                </v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[1][1]" v-model="planToLaunch[1][1]"
                                                  dense outlined/>
                                </v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[1][2]" v-model="planToLaunch[1][2]"
                                                  dense outlined/>
                                </v-col>
                            </v-row>
                            <v-row>
                                <v-col cols="3">Independent Suppliers</v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[2][0]" v-model="planToLaunch[2][0]"
                                                  dense outlined/>
                                </v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[2][1]" v-model="planToLaunch[2][1]"
                                                  dense outlined/>
                                </v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[2][2]" v-model="planToLaunch[2][2]"
                                                  dense outlined/>
                                </v-col>
                            </v-row>
                            <v-row>
                                <v-col cols="3">Contractors</v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[3][0]" v-model="planToLaunch[3][0]"
                                                  dense outlined/>
                                </v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[3][1]" v-model="planToLaunch[3][1]"
                                                  dense outlined/>
                                </v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[3][2]" v-model="planToLaunch[3][2]"
                                                  dense outlined/>
                                </v-col>
                            </v-row>
                            <v-row>
                                <v-col cols="3">Manufacturers</v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[4][0]" v-model="planToLaunch[4][0]"
                                                  dense outlined/>
                                </v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[4][1]" v-model="planToLaunch[4][1]"
                                                  dense outlined/>
                                </v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[4][2]" v-model="planToLaunch[4][2]"
                                                  dense outlined/>
                                </v-col>
                            </v-row>
                            <v-row>
                                <v-col cols="3">Buying Groups</v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[5][0]" v-model="planToLaunch[5][0]"
                                                  dense outlined/>
                                </v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[5][1]" v-model="planToLaunch[5][1]"
                                                  dense outlined/>
                                </v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[5][2]" v-model="planToLaunch[5][2]"
                                                  dense outlined/>
                                </v-col>
                            </v-row>
                            <v-row>
                                <v-col cols="3">Industry Bodies</v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[6][0]" v-model="planToLaunch[6][0]"
                                                  dense outlined/>
                                </v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[6][1]" v-model="planToLaunch[6][1]"
                                                  dense outlined/>
                                </v-col>
                                <v-col cols="3">
                                    <v-text-field type="text" name="planToLaunch[6][2]" v-model="planToLaunch[6][2]"
                                                  dense outlined/>
                                </v-col>
                            </v-row>

                            <h3>Allocate to staff</h3>

                            <div v-for="(users, type) in allocateToStaff">
                                <h4>{{ type }}</h4>
                                <v-row>
                                    <v-col v-for="(value, user) in users" cols="3" :key="user">{{ user }}
                                        <v-text-field type="text" :name="'allocateToStaff[' + type + '][' + user +']'" v-model="allocateToStaff[type][user]"></v-text-field>
                                    </v-col>
                                </v-row>
                            </div>

                            <v-btn type="submit">Get Zoho Report</v-btn>
                        </v-container>
                    </form>
                </v-col>
            </v-row>
        </v-container>

    </div>
</template>

<script>
import api from "../common/api.js";

export default {
    name: "ZohoReport.vue",
    data() {
        return {
            allocateToStaff: [],
            planToLaunch: [
                [0, 0, 0],
                [0, 0, 0],
                [0, 0, 0],
                [0, 0, 0],
                [0, 0, 0],
                [0, 0, 0],
                [0, 0, 0]
            ]
        }
    },
    mounted() {
        const self = this;
        api.zohoReportSettings().then(function (data) {
            self.planToLaunch = data.data.planToLaunch
            self.allocateToStaff = data.data.allocateToStaff
        })
    },
    methods: {
        getCsrf() {
            return document.head.querySelector('meta[name="csrf-token"]').content
        }
    }
}
</script>

<style scoped>

</style>
