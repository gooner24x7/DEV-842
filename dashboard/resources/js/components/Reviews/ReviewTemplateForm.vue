<template>
    <v-card>
        <v-card-title class="d-flex align-start justify-space-between pb-1">
            <div>
                <div class="headline">{{ dialogTitle }}</div>
                <div class="body-2 text--secondary font-weight-regular mt-1">
                    Build a review template to send to project managers when reviewing subcontractors.
                </div>
            </div>
            <v-btn icon @click="cancel">
                <v-icon>mdi-close</v-icon>
            </v-btn>
        </v-card-title>

        <v-card-text class="pt-2">
            <v-row>
                <v-col cols="12">
                    <v-text-field
                        v-model="form.name"
                        label="Template Name *"
                        outlined
                        dense
                        :error-messages="errors.name ? ['This field is required'] : []"
                    ></v-text-field>
                </v-col>
            </v-row>

            <v-row no-gutters>
                <!-- Sections sidebar -->
                <v-col cols="12" md="3" class="sections-panel pr-4">
                    <div class="overline mb-2">Review Sections</div>

                    <div
                        v-for="(section, index) in form.sections"
                        :key="index"
                        class="section-item d-flex align-center mb-1 pa-2 rounded"
                        :class="{ 'section-item--active': selectedSectionIndex === index }"
                        @click="selectedSectionIndex = index"
                        style="cursor: pointer;"
                    >
                        <v-icon small class="text--disabled mr-1">mdi-drag-vertical</v-icon>
                        <span class="body-2 flex-grow-1">{{ index + 1 }}. {{ section.title || 'Untitled' }}</span>
                        <v-btn icon x-small @click.stop="deleteSection(index)">
                            <v-icon small>mdi-close</v-icon>
                        </v-btn>
                    </div>

                    <v-btn text color="primary" small class="mt-2" @click="addSection">
                        <v-icon small left>mdi-plus</v-icon>
                        ADD SECTION
                    </v-btn>
                </v-col>

                <!-- Section editor -->
                <v-col cols="12" md="9" class="pl-4 section-editor">
                    <template v-if="selectedSection">
                        <div class="d-flex align-start">
                            <v-text-field
                                v-model="selectedSection.title"
                                label="Section Title *"
                                outlined
                                dense
                                class="flex-grow-1 mr-4"
                            ></v-text-field>
                            <v-btn text color="error" small class="mt-1" @click="deleteSection(selectedSectionIndex)">
                                <v-icon small left>mdi-delete-outline</v-icon>
                                DELETE SECTION
                            </v-btn>
                        </div>

                        <div class="mb-3">
                            <div class="subtitle-2 font-weight-bold">Questions</div>
                            <div class="caption text--secondary">Add questions and set scoring for each response option.</div>
                        </div>

                        <div
                            v-for="(question, qIndex) in selectedSection.questions"
                            :key="qIndex"
                            class="question-block mb-6"
                        >
                            <div class="caption font-weight-medium mb-1">Question *</div>
                            <v-textarea
                                v-model="question.text"
                                outlined
                                dense
                                rows="2"
                                auto-grow
                                class="mb-2"
                            ></v-textarea>

                            <div class="d-flex align-center mb-1">
                                <span class="caption text--secondary flex-grow-1 pl-6">Response Options</span>
                                <span class="caption text--secondary" style="width: 80px; text-align: center;">Score</span>
                                <span style="width: 36px;"></span>
                            </div>

                            <div
                                v-for="(option, oIndex) in question.options"
                                :key="oIndex"
                                class="mb-2"
                            >
                                <div class="d-flex align-center">
                                    <v-icon small class="text--disabled mr-1" style="flex-shrink:0;">mdi-drag-vertical</v-icon>
                                    <v-textarea
                                        v-model="option.text"
                                        outlined
                                        dense
                                        rows="1"
                                        auto-grow
                                        hide-details
                                        class="flex-grow-1 mr-2"
                                    ></v-textarea>
                                    <v-text-field
                                        v-model="option.score"
                                        type="number"
                                        outlined
                                        dense
                                        hide-details
                                        :rules="scoreRules"
                                        style="max-width: 72px; flex-shrink: 0;"
                                        class="mr-2"
                                    ></v-text-field>
                                    <v-btn icon small @click="deleteOption(qIndex, oIndex)" style="flex-shrink:0;">
                                        <v-icon small color="red darken-1">mdi-delete-outline</v-icon>
                                    </v-btn>
                                </div>
                                <div v-if="scoreError(option.score)" class="error--text text-caption mt-1">
                                    {{ scoreError(option.score) }}
                                </div>
                            </div>

                            <div class="d-flex align-center justify-space-between mt-2">
                                <v-btn text color="primary" x-small @click="addOption(qIndex)">
                                    <v-icon x-small left>mdi-plus</v-icon>
                                    ADD OPTION
                                </v-btn>
                                <v-btn icon x-small @click="deleteQuestion(qIndex)" title="Remove question">
                                    <v-icon x-small color="grey">mdi-delete-outline</v-icon>
                                </v-btn>
                            </div>

                            <v-divider class="mt-3"></v-divider>
                        </div>

                        <v-btn text color="primary" small class="mt-2" @click="addQuestion">
                            <v-icon small left>mdi-plus</v-icon>
                            ADD QUESTION
                        </v-btn>
                    </template>

                    <div v-else class="text--secondary text-center pa-8">
                        Select a section or add one to get started.
                    </div>
                </v-col>
            </v-row>
        </v-card-text>

        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn text @click="cancel">Cancel</v-btn>
            <v-btn color="primary" @click="save">Save</v-btn>
        </v-card-actions>
    </v-card>
</template>

<script>
import api from "../../common/api";

export default {
    name: 'ReviewTemplateForm',
    components: {},
    props: ['editedItem'],
    data() {
        return {
            dialogTitle: 'Create Review Template',
            selectedSectionIndex: null,
            form: {
                name: null,
                sections: [],
            },
            errors: {
                name: false,
            },
            scoreRules: [
                v => (v !== null && v !== undefined && v !== '') || 'Score is required',
                v => Number.isInteger(Number(v)) || 'Score must be an integer',
                v => (Number(v) >= 1 && Number(v) <= 255) || 'Score must be between 1 and 255',
            ],
        };
    },

    computed: {
        selectedSection() {
            if (this.selectedSectionIndex === null) return null;
            return this.form.sections[this.selectedSectionIndex] || null;
        },
    },

    watch: {
        editedItem: function () {
            this.init();
        },
    },

    created() {
        this.init();
    },

    methods: {
        scoreError(score) {
            for (const rule of this.scoreRules) {
                const result = rule(score);
                if (result !== true) return result;
            }
            return null;
        },
        init() {
            if (this.editedItem) {
                this.dialogTitle = 'Edit Review Template';

                this.form = {
                    id: this.editedItem.id,
                    name: this.editedItem.name,
                    description: this.editedItem.description,
                    sections: (this.editedItem.sections || []).map(section => ({
                        title: section.title,
                        questions: (section.questions || []).map(question => ({
                            text: question.question,
                            options: (question.options || []).map(option => ({
                                text: option.text,
                                score: option.score,
                            })),
                        })),
                    })),
                };
                this.selectedSectionIndex = this.form.sections.length ? 0 : null;
            } else {
                this.dialogTitle = 'Create Review Template';

                this.form = {
                    name: null,
                    sections: [],
                };
                this.selectedSectionIndex = null;
            }

            this.errors.name = false;
        },

        addSection() {
            this.form.sections.push({ title: '', questions: [] });
            this.selectedSectionIndex = this.form.sections.length - 1;
        },

        deleteSection(index) {
            this.form.sections.splice(index, 1);
            if (this.selectedSectionIndex >= this.form.sections.length) {
                this.selectedSectionIndex = this.form.sections.length - 1;
            }
            if (this.form.sections.length === 0) {
                this.selectedSectionIndex = null;
            }
        },

        addQuestion() {
            if (!this.selectedSection) return;
            this.selectedSection.questions.push({ text: '', options: [] });
        },

        deleteQuestion(qIndex) {
            if (!this.selectedSection) return;
            this.selectedSection.questions.splice(qIndex, 1);
        },

        addOption(qIndex) {
            const question = this.selectedSection.questions[qIndex];
            const nextScore = question.options.length + 1;
            question.options.push({ text: '', score: nextScore });
        },

        deleteOption(qIndex, oIndex) {
            this.selectedSection.questions[qIndex].options.splice(oIndex, 1);
        },

        cancel() {
            this.$emit("cancel");
        },

        async save(event) {
            const self = this;

            event.preventDefault();

            if (!self.form.name) {
                self.errors.name = true;
                return;
            }

            self.errors.name = false;

            try {
                const response = await api.storeReviewTemplate(self.form);

                self.$emit("saved");

                self.$store.commit("showSnackbar", {
                    message: "Success!",
                    color: "success",
                });
            } catch (error) {
                console.log(error);

                self.$store.commit("showSnackbar", {
                    message: "An error occurred",
                    color: "error",
                });
            }
        },
    },
};
</script>

<style scoped>
.sections-panel {
    border-right: 1px solid #e0e0e0;
}

.section-item {
    border-radius: 6px;
    transition: background-color 0.15s;
}

.section-item:hover {
    background-color: #f5f5f5;
}

.section-item--active {
    background-color: #e3f0ff;
    color: #1565c0;
}

.section-editor {
    border-left: 1px solid #e0e0e0;
    min-height: 300px;
}

.question-block {
    background: #fafafa;
    border-radius: 6px;
    padding: 12px;
}
</style>
