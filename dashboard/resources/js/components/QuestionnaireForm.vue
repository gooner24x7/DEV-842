<template>
    <v-card>
        <v-card-title>
            <span class="headline">{{ title }}</span>
        </v-card-title>

        <v-card-text>
            <v-container>
                <v-row>
                    <v-col cols="12">
                        <v-autocomplete
                            v-model="form.type"
                            :error-messages="errors.type"
                            :items="types"
                            :rules="[() => !!form.type || 'This field is required']"
                            item-text="text"
                            item-value="id"
                            label="Type"
                        >
                        </v-autocomplete>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col cols="12">
                        <v-textarea
                            v-model="form.text"
                            :error-messages="errors.text"
                            :rules="[() => !!form.text || 'This field is required']"
                            clear-icon="mdi-close-circle"
                            clearable
                            label="Text"
                            required
                        ></v-textarea>
                    </v-col>
                </v-row>

                <v-row>
                    <v-col cols="12" md="6">
                        <v-text-field
                            v-model="form.score_yes"
                            :error-messages="errors.score_yes"
                            :rules="[() => !!form.score_yes || 'This field is required']"
                            label="Yes (auto score)"
                            type="number"
                        ></v-text-field>
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-text-field
                            v-model="form.score_no"
                            :error-messages="errors.score_no"
                            :rules="[() => !!form.score_no || 'This field is required']"
                            label="No (auto score)"
                            type="number"
                        ></v-text-field>
                    </v-col>
                </v-row>

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
    props: ["value", "title", "projectId", "worksPackageId"],
    data() {
        return {
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
            successCreated: false,

            editId: null,

            form: {
                type: '',
                text: '',
                score_yes: 0,
                score_no: 0,
            },
            errors: {
                type: [],
                text: [],
                score_yes: [],
                score_no: []
            },
        };
    },

    watch: {
        value: function (o, n) {
            this.form.text = this.value.text;
            this.form.type = this.value.type;
            this.form.score_yes = this.value.score_yes;
            this.form.score_no = this.value.score_no;
            this.editId = this.value.id;
        },
    },

    mounted() {
        this.form.text = this.value.text;
        this.form.type = this.value.type;
        this.form.score_yes = this.value.score_yes;
        this.form.score_no = this.value.score_no;
        this.editId = this.value.id;
    },

    methods: {
        cancel() {
            this.$emit("cancel", this.value);
        },

        async save(event) {
            const self = this;

            event.preventDefault();

            self.successCreated = false;
            self.form.projectId = self.projectId;
            self.form.worksPackageId = self.worksPackageId;

            try {
                const response = await api.storeQuestionnaireItem(self.form, self.editId)

                self.$emit("saved", self.form);

                self.$store.commit("showSnackbar", {
                    message: "Success!",
                    color: "success",
                });
            } catch (error) {
                let data = error.response.data;
                if (typeof data.errors != "undefined") {
                    for (let id in data.errors) {
                        self.errors[id] = data.errors[id];
                    }
                }
            }
        },
    },
};
</script>

<style>
</style>
