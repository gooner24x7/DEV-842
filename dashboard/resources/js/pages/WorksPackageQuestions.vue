<template>
    <div style="margin: 1em">
<!--        <h2>Clarifications</h2>-->

        <v-data-table
            :headers="headers"
            :items="items"
            :loading="loader"
            :options.sync="options"
            item-key="id"
            loading-text="Loading... Please wait"
        >
            <template v-slot:top>

            </template>

            <template v-slot:item.actions="{ item }">
                <v-btn class="primary" v-if="isContractor()" @click="showAnswer(item, true)">Answer</v-btn>
            </template>
        </v-data-table>

        <v-dialog v-model="answerDialog" max-width="600px">
            <v-card>
                <v-card-title>
                    <span class="headline">Answer</span>
                </v-card-title>

                <v-card-text>
                    <v-container>
                        <v-row>
                            <v-col cols="12">
                                <v-form @submit="saveAnswer">
                                    <v-textarea
                                        v-model="answer"
                                        placeholder="Write your answer here"
                                        :error-messages="errors.answer"
                                        :rules="[() => !!answer || 'This field is required']"
                                        :disabled="disableAnswerInput"
                                        filled
                                    ></v-textarea>

                                    <v-btn v-if="!disableAnswerInput" color="primary" small class="mr-2"  type="submit">Save</v-btn>
                                    <v-btn small class="mr-2" @click="answerDialog=false">Close</v-btn>
                                </v-form>
                            </v-col>
                        </v-row>
                    </v-container>
                </v-card-text>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";

export default {
    name: "WorksPackageQuestions",
    props: [],
    components: {

    },
    data() {
        return {
            loader: false,
            worksPackageId: null,
            selectedQuestionId: null,
            answerDialog: false,
            disableAnswerInput: true,
            answer: '',
            items: [],
            errors: {
                answer: []
            },
            headers: [
                {
                    text: "Question by",
                    value: "user_name",
                    sortable: true,
                },
                {
                    text: "Question",
                    value: "question",
                    sortable: true,
                },
                {
                    text: "Answer by",
                    value: "answer_user_name",
                    sortable: true,
                },
                {
                    text: "Answer",
                    value: "answer",
                    sortable: true,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: true,
                },
            ],
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
        };
    },

    async mounted() {
        const self = this;

        self.worksPackageId = self.$route.query.worksPackageId;
        await self.load();
    },

    watch: {

    },

    computed: {

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
                worksPackageIds: [self.worksPackageId],
            };

            const response = await api.loadWorksPackageQuestions(params);
            self.items = response.data.data;

            self.loader = false;
        },
        showAnswer(question, showInput = false) {
            const self = this;

            let user = self.user();

            if (user.id === question.answer_user_id) {
                self.disableAnswerInput = false;
            } else {
                self.disableAnswerInput = !showInput
            }

            self.answer = question.answer;
            self.selectedQuestionId = question.id;
            self.answerDialog = true;
        },
        saveAnswer(event) {
            const self = this;

            event.preventDefault();

            let data = {
                qid: self.selectedQuestionId,
                answer: self.answer
            };

            api.storeWorksPackageQuestion(self.worksPackageId, data).then((response) => {
                self.load();
                self.answerDialog = false;
            }).catch(err => {
                console.log(err);
            });
        },
        user() {
            return this.$store.getters.getSession.user;
        },
        isContractor() {
            return permissions.hasRole(permissions.role_contractor);
        },
    },
};
</script>

<style>

</style>
