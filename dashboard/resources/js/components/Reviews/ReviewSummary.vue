<template>
    <div>
        <v-card class="review-dialog">
            <v-card-title class="review-dialog__title">
                <span>Review Summary</span>
                <v-spacer></v-spacer>
                <v-btn icon @click="$emit('close')">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-card-title>

            <div class="review-dialog__info-bar">
                <div class="review-dialog__info-item" v-if="activeReview && activeReview.quote_user_name !== ''">
                    <v-icon small>mdi-account-outline</v-icon>
                    <div class="ml-2">
                        <div class="review-dialog__info-label">Subcontractor</div>
                        <div class="review-dialog__info-value">{{ activeReview.quote_user_name }}</div>
                    </div>
                </div>
                <div class="review-dialog__info-item" v-if="activeReview && activeReview.project_name !== ''">
                    <v-icon small>mdi-folder-outline</v-icon>
                    <div class="ml-2">
                        <div class="review-dialog__info-label">Project</div>
                        <div class="review-dialog__info-value">{{ activeReview.project_name }}</div>
                    </div>
                </div>
                <div class="review-dialog__info-item" v-if="activeReview && activeReview.works_package_name !== ''">
                    <v-icon small>mdi-package-variant</v-icon>
                    <div class="ml-2">
                        <div class="review-dialog__info-label">Works Package</div>
                        <div class="review-dialog__info-value">{{ activeReview.works_package_name }}</div>
                    </div>
                </div>
                <div class="review-dialog__info-item" v-if="activeReview">
                    <v-icon small>mdi-clipboard-text-outline</v-icon>
                    <div class="ml-2">
                        <div class="review-dialog__info-label">Review Template</div>
                        <div class="review-dialog__info-value">{{ activeReview.template.name }}</div>
                    </div>
                </div>
                <div class="review-dialog__info-item" v-if="activeReview">
                    <v-icon small>mdi-account-outline</v-icon>
                    <div class="ml-2">
                        <div class="review-dialog__info-label">Completed By</div>
                        <div class="review-dialog__info-value">{{ activeReview.user_name }}</div>
                    </div>
                </div>
                <div class="review-dialog__info-item" v-if="activeReview">
                    <v-icon small>mdi-update</v-icon>
                    <div class="ml-2">
                        <div class="review-dialog__info-label">Completed At</div>
                        <div class="review-dialog__info-value">{{ activeReview.completed_at }}</div>
                    </div>
                </div>
            </div>

            <v-card-text class="pa-0">
                <div v-if="!activeReview" class="text-center pa-8">
                    <v-progress-circular indeterminate color="primary"></v-progress-circular>
                </div>
                <div v-else class="review-summary__body">
                    <v-row no-gutters style="min-height: 420px;">
                        <v-col md="3" style="border-right: 1px solid #e0e0e0; background-color: #f8f9fa;">
                            <div class="review-summary__container">
                                <div class="review-summary__heading mb-2 text-center">Overall Score</div>
                                <div :class="getOverallPercentClass()" class="review-summary__percent mt-2 type-chip"> {{ getOverallPercent() }}</div>
                                <div class="review-summary__overall mt-1"> {{ `${overallScore}/${overallTotal}` }}</div>
                            </div>
                        </v-col>

                        <v-col md="9">
                            <div class="review-summary__container">
                                <div class="review-summary__heading mb-2">Sections</div>
                                <v-data-table
                                    :headers="headers"
                                    :items="sections"
                                    :expanded.sync="expanded"
                                    @click:row="toggleExpanded"
                                    class="data-table-mini review-summary__table"
                                    hide-default-footer
                                    show-expand
                                    dense
                                >
                                    <template v-slot:expanded-item="{ headers, item }">
                                        <td :colspan="headers.length" class="pa-0">
                                            <table class="review-summary__subtable" style="width: 100%;">
                                                <tbody>
                                                    <tr v-for="question in item.questions" :key="question.id">
                                                        <td>{{ question.question }}</td>
                                                        <td style="width: 60px">{{ getQuestionScore(question) }}</td>
                                                        <td style="width: 60px"><v-btn icon @click="showComment(question)"><v-icon>mdi-comment-text-outline</v-icon></v-btn></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </template>

                                    <template v-slot:item.title="{ index, item }">
                                        {{ index + 1 }}. {{ item.title}}
                                    </template>

                                    <template v-slot:item.score="{ item }">
                                        {{ getSectionScore(item) }}
                                    </template>
                                </v-data-table>

                                <div v-if="activeReview.comment" class="mt-2">
                                    <v-textarea
                                        v-model="activeReview.comment"
                                        label="Overall Comments"
                                        outlined
                                        readonly
                                    ></v-textarea>
                                </div>
                            </div>
                        </v-col>
                    </v-row>
                </div>
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn @click="$emit('close')">Close</v-btn>
            </v-card-actions>
        </v-card>

        <v-dialog v-model="dialog" width="800px">
            <v-card>
                <v-card-title class="review-dialog__title">Answer</v-card-title>
                <v-card-text>
                    <v-row>
                        <v-col md="10">
                            <v-text-field
                                v-model="selectedOption.text"
                                label="Selected Option"
                                dense
                                outlined
                                readonly
                            ></v-text-field>
                        </v-col>
                        <v-col md="2">
                            <v-text-field
                                v-model="selectedOption.score"
                                label="Score"
                                type="number"
                                dense
                                outlined
                                readonly
                            ></v-text-field>
                        </v-col>
                    </v-row>


                    <v-textarea
                        v-model="selectedComment"
                        label="Comment"
                        outlined
                        readonly
                    ></v-textarea>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="dialog=false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import api from '../../common/api';

export default {
    name: 'ReviewSummary',

    props: {
        id: {
            type: Number,
            required: true,
        }
    },

    data() {
        return {
            activeReview: null,
            dialog: false,
            selectedComment: '',
            selectedOption: {text: '', score: 0},
            overallTotal: 0,
            overallScore: 0,
            reviewAnswers: {},
            expanded: [],
            sections: [],
            headers: [
                { text: 'Section', value: 'title', sortable: false },
                { text: 'Score', value: 'score', sortable: false, width: '60px' },
                { text: '', value: 'comment', sortable: false, width: '60px' },
            ]
        };
    },

    computed: {

    },

    watch: {
        id: {
            immediate: true,
            handler(id) {
                if (id) {
                    this.loadReview(id);
                }
            },
        },
    },

    methods: {
        async loadReview(id) {
            this.activeReview = null;
            this.currentSectionIndex = 0;
            this.reviewAnswers = {};
            this.reviewOverallComment = '';

            const resp = await api.getReview(id);

            this.activeReview = resp.data;

            const answersByQuestionId = {};
            (this.activeReview.answers || []).forEach(answer => {
                answersByQuestionId[answer.question_id] = answer;
            });

            this.activeReview.template.sections.forEach(section => {
                section.questions.forEach(question => {
                    const answer = answersByQuestionId[question.id];
                    question.comment = answer ? answer.comment : null;
                    question.selected_option_id = answer ? answer.option_id : null;
                });
            });

            this.sections = this.activeReview.template.sections;

            this.calculateOverall();
        },

        getSectionScore(item) {
            let total = 0;
            let score = 0;

            item.questions.forEach(question => {
                question.options.forEach(option => {
                    total += option.score;
                    if (question?.selected_option_id === option.id) score += option.score;
                });
            });

            return `${score}/${total}`;
        },

        getQuestionScore(question) {
            let total = 0;
            let score = 0;

            question.options.forEach(option => {
                total += option.score;
                if (question?.selected_option_id === option.id) score += option.score;
            });

            return `${score}/${total}`;
        },

        toggleExpanded(item) {
            const index = this.expanded.findIndex((expandedItem) => expandedItem.id === item.id);

            if (index >= 0) {
                this.expanded.splice(index, 1);
                return;
            }

            this.expanded = [item];
        },

        showComment(question) {
            this.selectedOption = question.options.find(option => option.id === question.selected_option_id);
            this.selectedComment = question.comment;
            this.dialog = true;
        },

        calculateOverall() {
            let total = 0;
            let score = 0;

            this.sections.forEach(section => {
                section.questions.forEach(question => {
                    question.options.forEach(option => {
                        total += option.score;
                        if (question?.selected_option_id === option.id) score += option.score;
                    });
                });
            });

            this.overallTotal = total;
            this.overallScore = score;
        },

        getOverallPercent() {
            if (this.overallTotal === 0) return '0%';
            return `${Math.round((this.overallScore / this.overallTotal) * 100)}%`;
        },

        getOverallPercentClass() {
            let color = 'chip-orange';
            let percent = Math.round((this.overallScore / this.overallTotal) * 100);

            if (percent > 60) {
                color = 'chip-green';
            } else if (percent > 30) {
                color = 'chip-yellow';
            }

            return color;
        }
    },
};
</script>

<style>
.review-dialog__title {
    font-size: 18px !important;
    font-weight: 600;
    padding: 16px 20px;
}

.review-summary__heading {
    font-size: 16px !important;
    color: #222;
}

.review-dialog__info-bar {
    display: flex;
    align-items: center;
}

.review-dialog__info-item {
    display: flex;
    align-items: center;
    padding: 10px 20px;
    max-width: 200px;
}

.review-dialog__info-item:not(:last-child) {
    border-right: 1px solid #e0e0e0;
}

.review-dialog__info-label {
    font-size: 11px;
    color: #888;
    line-height: 1.3;
}

.review-dialog__info-value {
    font-size: 13px;
    font-weight: 600;
    color: #222;
}

.review-summary__body {
    margin-top: 20px;
    border-top: 1px solid #e0e0e0;
    border-bottom: 1px solid #e0e0e0;
}

.review-summary__container {
    padding: 12px;
    //border: 1px solid #e0e0e0;
    //border-radius: 4px;
}

.review-summary__percent {
    text-align: center;
    font-size: 30px;
    line-height: 30px;
    width: 90px;
    margin: 0 auto;
    border-radius: 24px;
}

.review-summary__overall {
    text-align: center;
    font-size: 14px;
    font-weight: bold;
    color: #656565;
}

.review-summary__table > .v-data-table__wrapper > table > tbody > tr:not(.v-data-table__expanded__content) {
    cursor: pointer;
}

.review-summary__subtable {
    width: 100%;
    border-collapse: unset !important;
    border-spacing: unset !important;
    margin-top: 0 !important;
}

</style>
