<template>
    <v-card class="pqq_auto_scoring">
        <v-card-text>
            <h2>Edit Scores</h2>

            <v-row class="pdq_scoring_table">
                <v-col v-for="(v, i) in sessions.slice(0, 6)" :key="v.quote_id" :class="'pqq_score_' + i">

                    <template v-if="v.is_preferred_subcontractor">
                        <v-row style="background: #6df281;">
                            <v-col cols="8">
                                <div>Quote {{ v.quote_id }}</div>
                                <h2>{{ v.name }}</h2>
                            </v-col>
                            <v-col cols="4">
                                <div class="text-center" style="color: #171717; font-weight: bold;">
                                    Preferred<br>Subcontractor
                                </div>
                            </v-col>
                        </v-row>
                    </template>
                    <template v-else>
                        <v-row style="background: #f5f5f5;">
                            <v-col cols="8">
                                <div>Quote {{ v.quote_id }}</div>
                                <h2>{{ v.name }}</h2>
                            </v-col>
                            <v-col cols="4">
                            </v-col>
                        </v-row>
                    </template>

                    <v-row class="mb-1" style="background: #f5f5f5;">
                        <v-col cols="2.4">
                            <div class="score-number-sm">{{v.score}}</div>
                            <div class="score-footer">Questionnaire Score</div>
                        </v-col>
                        <v-col cols="2.4">
                            <div class="score-number-sm">{{ getRiskScore(v.user_id) }}</div>
                            <div class="score-footer">Risk Score</div>
                        </v-col>
                        <v-col cols="2.4">
                            <div class="score-number-sm">{{ getCertificateScore(v.user_id) }}</div>
                            <div class="score-footer">Compliance Score</div>
                        </v-col>
                        <v-col cols="2.4">
                            <div class="score-number-sm">{{ getProjectsAcceptedPercentage(v.user_id) }}</div>
                            <div class="score-footer">Projects Accepted on (%)</div>
                        </v-col>
                        <v-col cols="2.4">
                            <div class="score-number-sm">{{ getCheckboxValue(v.user_id, 'cas') ? 'Yes' : 'No' }}</div>
                            <div class="score-footer">Common Assessment Standard</div>
                        </v-col>
                    </v-row>

                    <v-row class="mb-1">
                        <v-col cols="12" md="6" v-if="creditsafeInfo[v.user_id]" style="border-right: 1px dashed #b5b5b5;">
                            <div class="es-header">Financial Summary:</div>
                            <ul class="es-info-list" style="list-style: none">
    <!--                        <li v-for="(v, i) in creditsafeInfo[v.user_id]" :key="i"><b>{{ i }}:</b> {{ v }}</li>-->
                                <li>Company Number: {{ creditsafeInfo[v.user_id]['Company Number'] }}</li>
                                <li>VAT Number: {{ creditsafeInfo[v.user_id]['VAT Number'] }}</li>
                                <li>Risk Score: {{ creditsafeInfo[v.user_id]['Risk Score'] }}</li>
                                <li>International Score: {{ creditsafeInfo[v.user_id]['International Score'] }}</li>
                                <li>Credit Limit: {{ creditsafeInfo[v.user_id]['Credit Limit'] }}</li>
                                <li>Contract Limit: {{ creditsafeInfo[v.user_id]['Contract Limit'] }}</li>
                                <li>Total CCJs: {{ creditsafeInfo[v.user_id]['Total CCJs'] }}</li>
                            </ul>
                        </v-col>
                        <v-col cols="12" md="6" v-if="reportData[v.user_id]">
                            <div class="es-header">Sub-contractor Summary:</div>
                            <div class="es-header mt-1">Live Projects:</div>
                            <ul class="es-info-list" style="list-style: none">
                                <li>
                                    Projects Accepted on:
                                    {{ reportData[v.user_id]['accepted_projects'] }}
                                     ({{ calculatePercentage(reportData[v.user_id]['accepted_projects'], reportData[v.user_id]['total_projects']) }})
                                </li>
                                <li>Total Live Projects: {{ reportData[v.user_id]['total_projects'] }}</li>
                            </ul>

                            <div class="es-header mt-1">Last 12 months performance:</div>
                            <ul class="es-info-list" style="list-style: none">
                                <li>Total Quotes: {{ reportData[v.user_id]['total_quotes'] }}</li>
                                <li>
                                    Total Accepted Quotes:
                                     {{ reportData[v.user_id]['accepted_quotes'] }}
                                      ({{ calculatePercentage(reportData[v.user_id]['accepted_quotes'], reportData[v.user_id]['total_quotes']) }})
                                </li>
                                <li>Total Quote Value: {{ reportData[v.user_id]['total_quotes_value'] | formatPrice }}</li>
                                <li>
                                    Total Accepted Quotes Value:
                                     {{ reportData[v.user_id]['accepted_quotes_value'] | formatPrice }}
                                      ({{ calculatePercentage(reportData[v.user_id]['accepted_quotes_value'], reportData[v.user_id]['total_quotes_value']) }})
                                </li>
                                <li>Average Quote Value Accepted: {{ reportData[v.user_id]['average_quote_value'] | formatPrice }}</li>
                            </ul>
                        </v-col>
                    </v-row>

                    <v-row class="mb-1">
                        <v-col>
                            <div class="es-header">Common Assessment Standard:</div>
                            <div class="es-cas">{{ getCheckboxValue(v.user_id, 'cas') ? 'Yes' : 'No' }}</div>
                        </v-col>
                    </v-row>

                    <v-row class="mb-1">
                        <v-col>
                            <div class="es-header">Compliance Summary:</div>
                            <div class="es-certificates" v-if="typeof certificateTypes !== 'undefined'">
                                <template v-for="(item, index) in certificateTypes">
                                    <v-checkbox
                                        :value="getCheckboxValue(v.user_id, item.slug)"
                                        :key="index"
                                        :ripple="false"
                                        dense
                                        readonly
                                    >
                                        <template v-slot:label>
                                            <div>
                                                {{ item.label }}
                                                <a
                                                    v-if="getCertificateUrl(v.user_id, item.slug)"
                                                    :href="getCertificateUrl(v.user_id, item.slug)"
                                                    target="_blank"
                                                    class="certificate-link"
                                                    @click="goToUrl(getCertificateUrl(v.user_id, item.slug))"
                                                >
                                                    <v-icon>mdi-open-in-new</v-icon>
                                                </a>
                                            </div>
                                        </template>
                                    </v-checkbox>
                                </template>
                            </div>
                        </v-col>
                    </v-row>
                </v-col>
            </v-row>

            <v-row class="pdq_scoring_table">
                <v-col v-for="(v, i) in sessions.slice(0, 6)" :key="v.quote_id" :class="'pqq_score_' + i">
                    <v-row>
                        <v-col>
                            <div v-if="v.is_answered !== 1">Not answered yet</div>
                            <div v-else>
                                <div v-for="q in answersWithScores[v.id]" :key="q.id" class="text-left">
                                    <span>{{ q.question_id }}. {{ q.question_text }}</span>

                                    <div v-if="q.question_type === 'yes_no'">
                                        <span class="answer-text">{{ q.is_yes ? 'Yes' : 'No' }}</span><br>
                                        <span class="answer-score">Score: {{ q.score }}</span>
                                    </div>

                                    <div v-if="q.question_type !== 'yes_no'" class="answer-box">
                                        <div class="answer-text">{{ q.answer_text }}</div>
                                        <span class="answer-input">
                                            Score: <v-text-field :value="q.score" v-on:change="(score) => updateScore(score, q.answer_id)"></v-text-field>
                                        </span>
                                    </div>
                                    <br/>
                                </div>

                                <div v-if="answersWithScores[v.id]?.[0]?.quote_id" style="border:none; background: none;">
                                    <b style="color: #264dec !important;">Price:</b>
                                    &pound;{{ parseFloat(answersWithScores[v.id][0]?.price).toFixed(2) }}<br/>
                                    <b style="color: #264dec !important;">Accepted:</b>
                                    <input :checked="!!answersWithScores[v.id][0]?.quote_accepted_at" onclick="return false;"
                                           readonly style="border: none;background:none;" type="checkbox">
                                </div>
                            </div>
                        </v-col>
                    </v-row>
                </v-col>
            </v-row>
        </v-card-text>
    </v-card>
</template>

<script>
import api from "../common/api";
import _ from "lodash";
import moment from "moment";

export default {
    components: {},
    name: "EditScores",
    props: ["sessions", "visible"],
    data() {
        return {
            answersWithScores: [],
            creditsafeInfo: [],
            certificates: [],
            reportData: [],
            certificateTypes: []
        }
    },

    watch: {
        'visible': {
            async handler(n, o) {
                const self = this;
                if (n === false) {
                    return n;
                }

                await self.loadCreditsafeInfo();
                await self.loadCertificates();
                await self.loadAnswers();
                await self.loadSubcontractorReport();

                return n;
            }
        }
    },

    async mounted() {
        const self = this;

        await self.loadCertificateTypes();

        await self.loadCreditsafeInfo();
        await self.loadCertificates();
        await self.loadAnswers();
        await self.loadSubcontractorReport();
    },

    methods: {
        async loadCreditsafeInfo() {
            const self = this;

            let userIds = self.sessions.map((item) => {
                return item.user_id;
            });

            const response = await api.getCreditsafeInfo({user_ids: userIds});

            self.creditsafeInfo = response.data;
        },

        async loadCertificateTypes() {
            const self = this;
            const response = await api.getCertificateTypes();

            self.certificateTypes = response.data;
        },

        async loadCertificates() {
            const self = this;

            let userIds = self.sessions.map((item) => {
               return item.user_id;
            });

            const response = await api.getCertificates({user_ids: userIds});

            self.certificates = response.data;
        },

        async loadAnswers() {
            const self = this;

            const userIds = self.sessions.map((item) => {
                return item.id;
            });

            const response = await api.loadAnswersWithScores(userIds);

            self.answersWithScores = response.data;
        },

        async loadSubcontractorReport() {
            const self = this;

            const userIds = self.sessions.map((item) => {
                return item.user_id;
            });

            const response = await api.getSubcontractorReport({user_ids: userIds});

            self.reportData = response.data;
        },

        async updateScore(score, answerId) {
            const self = this;

            await api.updateScore(score, answerId);

            self.$store.commit("showSnackbar", {
                message: "Success!",
                color: "success",
            });
        },

        getCheckboxValue(userId, type) {
            const self = this;

            let keyFound = false;

            try {
                let certificate = self.certificates[userId][type][0];

                //let now = moment();
                //let expiry = moment(certificate.expiry_date);

                //keyFound = expiry > now;
                keyFound = typeof certificate.expiry_date !== 'undefined';

            } catch (e) {
                keyFound = false;
            }

            return keyFound;
        },

        getCertificateUrl(userId, type) {
            const self = this;

            let url = null;

            try {
                url = self.certificates[userId][type][0]['url'];
            } catch (e) {
                url = null;
            }

            return url;
        },

        goToUrl(url) {
            window.open(url);
        },

        calculatePercentage(val1, val2) {
            const self = this;
            let perc = 0;

            if (val2 > 0) {
                perc = (val1 / val2) * 100;
            }

            return perc.toFixed(0) + '%';
        },

        getRiskScore(userId) {
            const self = this;

            let riskScore = 0;

            if (self.creditsafeInfo[userId]) {
                riskScore = self.creditsafeInfo[userId]['Risk Score'];
            }

            return riskScore;
        },

        getCertificateScore(userId) {
            const self = this;

            let userCerts = 0;
            let totalCerts = self.certificateTypes.length;

            for (let i = 0; i < totalCerts; i++) {
                if (self.getCheckboxValue(userId, self.certificateTypes[i].slug)) {
                    userCerts++;
                }
            }

            if (totalCerts === 0) {
                return "0%";
            }

            return (userCerts / totalCerts * 100).toFixed(0) + '%';
        },

        getProjectsAcceptedPercentage(userId) {
            const self = this;

            let perc = "0%";

            if (self.reportData[userId]) {
                perc = self.calculatePercentage(
                    self.reportData[userId]['accepted_projects'],
                    self.reportData[userId]['total_projects']
                );
            }

            return perc;
        }
    }
}
</script>

<style scoped>
    .es-info-list {
        padding: 0;
        margin: 0;
    }

    .es-header {
        display: block;
        width: 100%;
        text-decoration: underline;
        font-weight: bold;
    }

    .score-wrapper {

    }

    .score-header {
        font-size: 1em;
        font-family: 'albula_probold';
        color: #5e5e5e;
        margin-bottom: 4px;
    }

    .score-number {
        font-size: 2em;
        font-family: 'albula_probold';
        color: #faa85c;
        display: block;
    }

    .score-number-sm {
        font-size: 1.5em;
        text-align: center;
        font-family: 'albula_probold';
        color: #171717;
        display: block;
        margin-bottom: 4px;
    }

    .score-footer {
        font-size: 0.8em;
        line-height: 1.0em;
        text-align: center;
        font-family: 'albula_probold';
        color: #171717;
    }

    .answer-text {
        color: #264dec;
    }

    .answer-score {
        color: #5e5e5e;
    }

    ::v-deep .es-certificates .v-label {
        margin-bottom: 0 !important;
    }

    .certificate-link:hover > i {
        color: #264dec !important;
    }
</style>
