<template>
    <v-card>
        <v-card-title>
            <span class="headline">Questions</span>
        </v-card-title>

        <v-card-text>
            <v-container>
                <v-btn color="primary" v-on:click="templateQuestions.push({ ...emptyRow})">
                    Add Row
                </v-btn>
                <v-data-table :headers="headers" :items="templateQuestions">
                    <template v-slot:item.text="{item}">
                        <v-text-field v-model="item.text"
                                      :rules="[() => !!item.type || 'This field is required']"
                        ></v-text-field>
                    </template>
                    <template v-slot:item.type="{item}">
                        <v-autocomplete
                            v-model="item.type"
                            :items="types"
                            :rules="[() => !!item.type || 'This field is required']"
                            item-text="text"
                            item-value="id"
                            label="Type"
                        >
                        </v-autocomplete>
                    </template>
                    <template v-slot:item.score_yes="{item}">
                        <v-text-field v-model="item.score_yes"
                                      :rules="[() => !!item.score_yes || 'This field is required']"
                                      label="Yes (auto score)"
                        ></v-text-field>
                        <v-text-field v-model="item.score_no"
                                      :rules="[() => !!item.score_no || 'This field is required']"
                                      label="No (auto score)"
                        ></v-text-field>
                    </template>
                    <template v-slot:item.actions="{ item }">
                        <div class="text-center">
                            <v-btn
                                v-on:click="deleteQuestion(item)"
                            >Remove
                            </v-btn>
                        </div>
                    </template>
                </v-data-table>
            </v-container>
        </v-card-text>

        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="blue darken-1" text @click="cancel">Cancel</v-btn>
            <v-btn color="blue darken-1" text @click="save">Save</v-btn>
        </v-card-actions>
    </v-card>
</template>

<script>
import api from "../common/api.js";

export default {
    props: ["templateQuestions", "id"],
    data() {
        return {
            headers: [
                {
                    text: "Text",
                    value: "text",
                },
                {
                    text: 'Type',
                    value: 'type',
                },
                {
                    text: 'Auto Scores',
                    value: 'score_yes'
                },
                {
                    text: "",
                    value: "actions",
                    sortable: false,
                },
            ],
            emptyRow: {
                text: '',
                type: 'text',
                score_yes: 0,
                score_no: 0,
            },
            types: [
                {
                    id: 'yes_no',
                    text: 'Yes/No answer'
                },
                {
                    id: 'text',
                    text: 'Text answer'
                }
            ],
        };
    },

    watch: {
        'templateQuestions': function (o, n) {

        },
        'id': function (o, n) {

        },
    },

    mounted() {
    },

    methods: {
        deleteQuestion(item) {
            const self = this;
            let foundIndex = -1;
            for (let i in self.templateQuestions) {
                if (self.templateQuestions[i].text === item.text) {
                    foundIndex = i;
                    break;
                }
            }
            if (foundIndex >= 0) {
                self.templateQuestions.splice(foundIndex, 1)
            }
        },

        cancel() {
            this.$emit("cancel", this.value);
        },

        async save(event) {
            const self = this;

            event.preventDefault();

            await api.updateTemplate(self.id, {
                questions: this.templateQuestions
            });

            this.$emit("saved", {});
        },
    },
};
</script>

<style>
</style>
