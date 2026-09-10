<template>
 <div>
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
                                     class="wp-answer-input"
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

     <v-card>
         <v-card-title>
             <span class="headline">Clarifications</span>
         </v-card-title>

         <v-card-text>
             <v-container>
                 <v-row>
                     <v-col cols="12">
                     <v-data-table
                         item-key="id"
                         :headers="headers"
                         :items="items"
                         :server-items-length="serverItemsLength"
                         :loading="loader"
                         :options.sync="options"
                         loading-text="Loading... Please wait"
                     >
                         <template v-slot:item.answered_at="{ item }">
                            <span>{{item.answered_at | formatDateTime}}</span>
                         </template>

                         <template v-slot:item.actions="{ item }">
                             <v-btn v-if="item.answered_at !== null" @click="showAnswer(item)">View</v-btn>
                             <v-btn v-else-if="isContractor()" @click="showAnswer(item, true)">Answer</v-btn>
                         </template>
                     </v-data-table>
                     </v-col>
                 </v-row>

                 <v-row>
                     <v-col cols="12">
                         <v-form @submit="save">
                             <v-textarea
                                 v-if="isSubContractor()"
                                 v-model="question"
                                 placeholder="Write your question here"
                                 :error-messages="errors.question"
                                 :rules="[() => !!question || 'This field is required']"
                                 filled
                             ></v-textarea>

                            <v-btn color="primary" small class="mr-2"  type="submit" v-if="isSubContractor()">Save</v-btn>
                            <v-btn small class="mr-2" @click="cancel">Close</v-btn>
                         </v-form>
                     </v-col>
                 </v-row>
             </v-container>
         </v-card-text>
     </v-card>
 </div>
</template>

<script>
import api from "../common/api";
import SupplyFitQuoteForm from "./SupplyFitQuoteForm.vue";
import permissions from "../common/permissions";
export default {
    name: "WorksPackageQuestionsDialog",
    components: {SupplyFitQuoteForm},
    props: ["worksPackageId"],

    data() {
        return {
            loader: false,
            answerDialog: false,
            disableAnswerInput: true,
            serverItemsLength: 0,
            question: null,
            answer: null,
            selectedQuestionId: null,
            items: [],
            errors: {
                question: [],
                answer: []
            },
            headers: [
                {
                    text: "Created By",
                    value: "user_name",
                    sortable: true,
                },
                {
                    text: "Question",
                    value: "question",
                    sortable: true,
                },
                {
                    text: "Answered At",
                    value: "answered_at",
                    sortable: true,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: false,
                }
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
        }
    },

    watch: {
        worksPackageId: {
            handler(n, o) {
                const self = this;

                self.load();
                self.question = null;
            },
        },
        options: {
            handler(n, o) {
                this.load();
            },
            deep: true,
        },
    },

    methods: {
        cancel() {
            this.$emit("cancel", this.value);
        },
        save(event) {
            const self = this;

            event.preventDefault();

            if (!self.question) {
                return;
            }

            self.loader = true;

            api.storeWorksPackageQuestion(self.worksPackageId, {question: self.question}).then((response) => {
                self.load();
                self.question = null;

                self.$store.commit("showSnackbar", {
                    message: "Success!",
                    color: "success",
                });
            }).catch(error => {
                console.log(error);

                self.$store.commit("showSnackbar", {
                    message: error?.response?.data ?? "An error occurred",
                    color: "error",
                });
            });

            self.loader = false;
        },
        load() {
            const self = this;
            self.loader = true;

            let params = {
                ...self.options,
                page: self.options.page,
                itemsPerPage: self.options.itemsPerPage,
                sortBy: self.options.sortBy[0],
                sortDesc: self.options.sortDesc[0] ? 1 : 0,
                worksPackageIds: [self.worksPackageId]
            };

            api
                .loadWorksPackageQuestions(params)
                .then((response) => {
                    self.items = response.data.data;
                    self.serverItemsLength = response.data.total;
                })
                .catch((err) => {
                    console.log(err);
                });

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
        isSubContractor() {
            return permissions.hasRole(permissions.role_user);
        },
    }
}
</script>

<style scoped>
::v-deep .wp-answer-input textarea {
    color: #000;
}
</style>
