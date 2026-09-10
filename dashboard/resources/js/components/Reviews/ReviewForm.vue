<template>
    <v-card class="review-dialog">
        <v-card-title class="review-dialog__title">
            <span>Performance Review</span>
            <v-spacer></v-spacer>
            <v-btn icon @click="$emit('close')">
                <v-icon>mdi-close</v-icon>
            </v-btn>
        </v-card-title>

        <div class="review-dialog__info-bar">
            <div class="review-dialog__info-item">
                <v-icon small>mdi-account-outline</v-icon>
                <div class="ml-2">
                    <div class="review-dialog__info-label">Subcontractor</div>
                    <div class="review-dialog__info-value">{{ activeReview.quote_user_name }}</div>
                </div>
            </div>
            <div class="review-dialog__info-item" v-if="activeReview.project_name !== ''">
                <v-icon small>mdi-folder-outline</v-icon>
                <div class="ml-2">
                    <div class="review-dialog__info-label">Project</div>
                    <div class="review-dialog__info-value">{{ activeReview.project_name }}</div>
                </div>
            </div>
            <div class="review-dialog__info-item" v-if="activeReview.works_package_name !== ''">
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
        </div>

        <div v-if="!activeReview" class="text-center pa-8">
            <v-progress-circular indeterminate color="primary"></v-progress-circular>
        </div>

        <v-card-text v-else class="review-dialog__body pa-0">
            <v-row no-gutters style="min-height: 420px;">
                <v-col cols="4" class="review-dialog__sidebar">
                    <div class="review-dialog__sidebar-heading">Review Sections</div>
                    <div
                        v-for="(section, index) in activeReview.template.sections"
                        :key="section.id"
                        class="review-dialog__nav-item"
                        :class="{
                            'review-dialog__nav-item--active': currentSectionIndex === index,
                            'review-dialog__nav-item--done': isSectionComplete(section)
                        }"
                        @click="currentSectionIndex = index"
                    >
                        <v-icon
                            v-if="isSectionComplete(section) && currentSectionIndex !== index"
                            small color="success" class="mr-2"
                        >
                            mdi-check-circle-outline
                        </v-icon>
                        <v-icon
                            v-else-if="currentSectionIndex === index"
                            small color="primary" class="mr-2"
                        >
                            mdi-radiobox-marked
                        </v-icon>
                        <v-icon v-else small color="grey" class="mr-2">mdi-radiobox-blank</v-icon>
                        <span>{{ index + 1 }}. {{ section.title }}</span>
                    </div>
                    <div
                        class="review-dialog__nav-item"
                        :class="{ 'review-dialog__nav-item--active': currentSectionIndex === activeReview.template.sections.length }"
                        @click="currentSectionIndex = activeReview.template.sections.length"
                    >
                        <v-icon
                            v-if="currentSectionIndex === activeReview.template.sections.length"
                            small color="primary" class="mr-2"
                        >
                            mdi-radiobox-marked
                        </v-icon>
                        <v-icon v-else small color="grey" class="mr-2">mdi-radiobox-blank</v-icon>
                        <span>Overall Comments</span>
                    </div>

                    <div class="review-dialog__progress-box">
                        <div class="review-dialog__progress-label">Overall Progress</div>
                        <div class="review-dialog__progress-pct">{{ overallProgressPct }}%</div>
                        <div class="review-dialog__progress-sub">
                            {{ completedSectionsCount }} of {{ activeReview.template.sections.length }} sections completed
                        </div>
                        <v-progress-linear
                            :value="overallProgressPct"
                            color="primary"
                            rounded
                            height="6"
                            class="mt-2"
                        ></v-progress-linear>
                    </div>
                </v-col>

                <v-col cols="8" class="review-dialog__content">
                    <template v-if="currentSection">
                        <div class="review-dialog__section-header">
                            <div>
                                <div class="review-dialog__section-title">{{ currentSection.title }}</div>
                                <div class="review-dialog__section-desc">{{ currentSection.description }}</div>
                            </div>
                            <div class="review-dialog__section-number">
                                Section {{ currentSectionIndex + 1 }} of {{ activeReview.template.sections.length }}
                            </div>
                        </div>

                        <div
                            v-for="question in currentSection.questions"
                            :key="question.id"
                            class="review-dialog__question"
                        >
                            <div class="review-dialog__question-label">
                                {{ question.question }}
                            </div>
                            <div
                                v-for="option in question.options"
                                :key="option.id"
                                class="review-dialog__option"
                                :class="{ 'review-dialog__option--selected': reviewAnswers[question.id] && reviewAnswers[question.id].option_id === option.id }"
                                @click="selectReviewOption(question.id, option.id)"
                            >
                                <v-icon
                                    small
                                    :color="reviewAnswers[question.id] && reviewAnswers[question.id].option_id === option.id ? 'primary' : 'grey lighten-1'"
                                    class="mr-3 flex-shrink-0"
                                >
                                    {{ reviewAnswers[question.id] && reviewAnswers[question.id].option_id === option.id ? 'mdi-radiobox-marked' : 'mdi-radiobox-blank' }}
                                </v-icon>
                                <span class="review-dialog__option-text">{{ option.text }}</span>
                                <span class="review-dialog__option-score">Score: {{ option.score }}</span>
                            </div>

                            <div class="review-dialog__comment-label mt-4">
                                Comments
                            </div>
                            <v-textarea
                                v-model="reviewAnswers[question.id].comment"
                                outlined
                                dense
                                counter="1000"
                                maxlength="1000"
                                rows="4"
                                hide-details="auto"
                            ></v-textarea>
                        </div>
                    </template>

                    <template v-else>
                        <div class="review-dialog__section-header">
                            <div class="review-dialog__section-title">Overall Comments</div>
                        </div>
                        <div class="review-dialog__comment-label mt-4">
                            Add any overall comments for this review
                        </div>
                        <v-textarea
                            v-model="reviewOverallComment"
                            outlined
                            dense
                            counter="1000"
                            maxlength="1000"
                            rows="6"
                            hide-details="auto"
                        ></v-textarea>
                    </template>
                </v-col>
            </v-row>
        </v-card-text>

        <v-card-actions class="review-dialog__footer">
            <v-btn @click="reviewBack()" :disabled="currentSectionIndex === 0">
                <v-icon left small>mdi-arrow-left</v-icon> BACK
            </v-btn>
            <v-spacer></v-spacer>
            <v-btn color="primary" @click="reviewSaveAndNext()">
                {{ isLastReviewStep ? 'SUBMIT' : 'NEXT' }} <v-icon right small>mdi-arrow-right</v-icon>
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

<script>
import api from '../../common/api';

export default {
    name: 'ReviewForm',

    props: {
        id: {
            type: Number,
            required: true,
        },
    },

    data() {
        return {
            activeReview: null,
            currentSectionIndex: 0,
            reviewAnswers: {},
            reviewOverallComment: '',
        };
    },

    computed: {
        currentSection() {
            if (!this.activeReview) return null;
            return this.activeReview.template.sections[this.currentSectionIndex] || null;
        },

        completedSectionsCount() {
            if (!this.activeReview) return 0;
            return this.activeReview.template.sections.filter(s => this.isSectionComplete(s)).length;
        },

        overallProgressPct() {
            if (!this.activeReview || !this.activeReview.template.sections.length) return 0;
            return Math.round(this.completedSectionsCount / this.activeReview.template.sections.length * 100);
        },

        isLastReviewStep() {
            if (!this.activeReview) return false;
            return this.currentSectionIndex === this.activeReview.template.sections.length;
        },
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

            for (const section of this.activeReview.template.sections) {
                for (const question of section.questions) {
                    this.$set(this.reviewAnswers, question.id, { option_id: null, comment: '' });
                }
            }
        },

        selectReviewOption(questionId, optionId) {
            this.reviewAnswers[questionId].option_id = optionId;
        },

        isSectionComplete(section) {
            return section.questions.every(q =>
                this.reviewAnswers[q.id] &&
                this.reviewAnswers[q.id].option_id !== null &&
                this.reviewAnswers[q.id].option_id !== undefined
            );
        },

        reviewBack() {
            if (this.currentSectionIndex > 0) {
                this.currentSectionIndex--;
            }
        },

        reviewSaveAndNext() {
            if (this.isLastReviewStep) {
                this.submitReview();
            } else {
                this.currentSectionIndex++;
            }
        },

        submitReview() {
            const sections = this.activeReview.template.sections.map(section => ({
                id: section.id,
                questions: section.questions.map(q => ({
                    id: q.id,
                    selected_option_id: this.reviewAnswers[q.id].option_id,
                    comment: this.reviewAnswers[q.id].comment,
                })),
            }));

            let data = {
                id: this.activeReview.id,
                quote_id: this.activeReview.quote_id,
                template_id: this.activeReview.template.id,
                comment: this.reviewOverallComment,
                sections: sections,
            }

            api.storeReview(data)
                .then(() => {
                    this.$emit('submitted');
                })
                .catch((error) => {
                    console.log(error);
                    this.$store.commit('showSnackbar', { message: 'An error occurred', color: 'error' });
                });
        },
    },
};
</script>

<style>
.review-dialog__title {
    font-size: 18px !important;
    font-weight: 600;
    padding: 16px 20px;
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

.review-dialog__body {
    margin: 20px 0;
    border-top: 1px solid #e0e0e0;
    border-bottom: 1px solid #e0e0e0;
}

.review-dialog__sidebar {
    background: #f8f9fa;
    border-right: 1px solid #e0e0e0;
    padding: 16px 0;
    display: flex;
    flex-direction: column;
}

.review-dialog__sidebar-heading {
    font-size: 12px;
    font-weight: 600;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 20px;
}

.review-dialog__nav-item {
    display: flex;
    align-items: center;
    padding: 10px 16px;
    cursor: pointer;
    font-size: 14px;
    color: #555;
    transition: background 0.15s;
}

.review-dialog__nav-item:hover {
    background: #eef0f3;
}

.review-dialog__nav-item--active {
    background: #e8f0fe;
    color: #1565c0;
    font-weight: 600;
}

.review-dialog__progress-box {
    margin: auto 16px 16px;
    padding: 14px;
    background: white;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.review-dialog__progress-label {
    font-size: 12px;
    color: #888;
    margin-bottom: 4px;
}

.review-dialog__progress-pct {
    font-size: 30px;
    font-weight: 700;
    color: #222;
    line-height: 1.2;
}

.review-dialog__progress-sub {
    font-size: 12px;
    color: #888;
    margin-top: 4px;
}

.review-dialog__content {
    padding: 20px !important;
    overflow-y: auto;
    max-height: 600px;
}

.review-dialog__section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f0f0;
}

.review-dialog__section-title {
    font-size: 18px;
    font-weight: 600;
    color: #222;
}

.review-dialog__section-desc {
    font-size: 13px;
    color: #666;
    margin-top: 4px;
}

.review-dialog__section-number {
    white-space: nowrap;
    font-size: 12px;
    color: #888;
    margin-top: 6px;
}

.review-dialog__question-label {
    font-size: 14px;
    font-weight: 500;
    color: #333;
    margin-bottom: 10px;
}

.review-dialog__option {
    display: flex;
    align-items: center;
    padding: 12px 14px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: border-color 0.15s, background 0.15s;
}

.review-dialog__option:hover {
    border-color: #aaa;
    background: #fafafa;
}

.review-dialog__option--selected {
    border-color: #1565c0;
    background: #e8f0fe;
}

.review-dialog__option-text {
    flex: 1;
    font-size: 13px;
    color: #333;
}

.review-dialog__option-score {
    font-size: 12px;
    color: #555;
    font-weight: 500;
    padding: 3px 10px;
    background: #f0f0f0;
    border-radius: 4px;
    white-space: nowrap;
    margin-left: 8px;
}

.review-dialog__comment-label {
    font-size: 14px;
    font-weight: 500;
    color: #333;
    margin-bottom: 8px;
}

.review-dialog__footer {
    padding: 12px 20px;
}
</style>
