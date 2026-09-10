<template>
    <v-app id="inspire" :dark="false" baseline>
        <v-main>
            <v-form v-if="!submitted" @submit.prevent="submit">
                <div class="p-2">
                    <question v-for="(v, i) in questions" v-model="questions[i]" class="mb-2" :key="i"></question>
                </div>

                <v-btn v-if="questions.length > 0" :disabled="notReady" class="ml-2 primary" type="submit">Submit
                </v-btn>
            </v-form>

            <v-alert v-else
                     dense
                     text
                     type="success"
            >{{ getSuccessfulMessage() }}
            </v-alert>
        </v-main>
    </v-app>
</template>

<script>
import Question from "./components/question";
import api from "../common/api";

export default {
    name: "index",
    components: {Question},

    async mounted() {
        await this.checkHash();
    },

    methods: {
        getSuccessfulMessage() {
            return `Thank you for completing the questionnaire!
The next step is to create a Purchase & Hire enquiry and obtain your tender prices.
Once you have those, you can proceed to submit your quote to ${this.companyName}.`
        },
        async submit() {
            const self = this;
            let emptyItems = this.questions.filter((item) => {
                return item.text_answer === null && item.isYes === null;
            });

            if (emptyItems.length > 0) {
                alert("Please, answer all questions");
                return;
            }

            let answers = this.questions.map((item) => {
                return {
                    item_id: item.id,
                    session_id: self.session.id,
                    text_answer: item.text_answer,
                    is_yes: item.isYes,
                    type: item.type
                }
            });

            try {
                const response = await api.submitAnswers(window.formHash, answers);

                this.companyName = response.data.companyName;
            } catch (e) {
                alert("Error. Please, try again later");
                return
            }

            self.submitted = true
            alert('Successfully submitted');
        },
        async checkHash() {
            const self = this;

            let response = undefined
            try {
                response = await api.checkHash(window.formHash)
            } catch (e) {
                alert('Forbidden');
                return
            }

            self.session = response.data;
            if (self.session.is_answered === 1) {
                return
            }

            await self.loadQuestions()
        },

        async loadQuestions() {
            const self = this;
            const response = await api.loadQuestionnaireItems({
                'worksPackageId': self.session.works_package_id
            })

            self.questions = response.data.data;

            self.questions = self.questions.map((item) => {
                item.text_answer = null
                item.isYes = null
                return item
            });
        }
    },
    data() {
        return {
            notReady: false,
            session: null,
            questions: [],
            submitted: false,
            companyName: '',
        }
    }
}
</script>

<style scoped>

</style>
