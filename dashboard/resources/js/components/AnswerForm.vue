<template>
    <v-card>
        <v-card-title>
            <span class="headline">{{ title }}</span>
        </v-card-title>

        <v-card-text>
            <v-container>
                <v-row>
                    <v-col>
                        <v-text-field
                            v-model="form.price"
                            :error-messages="errors.price"
                            type="number"
                            label="Price (ex VAT) (£)"
                            :rules="[() => !!form.price || 'This field is required']"
                        ></v-text-field>

                        <v-autocomplete
                            :items="['full', 'part']"
                            v-model="form.type"
                            :error-messages="errors.type"
                            label="Full/Part Quote"
                            :rules="[() => !!form.type || 'This field is required']"
                        >
                        </v-autocomplete>

                        <v-checkbox
                            v-model="form.has_substitution"
                            label="Have you quoted any substitute, alternative, equivalent or non-specified product?"
                            @change="showSubstitutionAlert"
                            :true-value="1"
                            :false-value="0"
                        ></v-checkbox>

                        <AttachmentsInput
                            v-model="form.attachment"
                        ></AttachmentsInput>

                        <v-textarea
                            v-model="form.comment"
                            clear-icon="mdi-close-circle"
                            label="Comments"
                            clearable
                        ></v-textarea>

                        <v-textarea
                            v-model="form.offers"
                            clear-icon="mdi-close-circle"
                            label="Offers"
                            clearable
                        ></v-textarea>
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
import AttachmentsInput from "./AttachmentsInput";

export default {
    components: {AttachmentsInput},
    props: ["value", "title", "questionId"],
    data() {
        return {
            productItems: [],

            editId: null,

            form: {
                price: "",
                comment: "",
                offers: "",
                attachment: [],
                type: "",
                has_substitution: 0,
            },
            errors: {
                price: [],
                comment: [],
                offers: [],
                type: [],
            },
        };
    },

    watch: {
        value: function (o, n) {
            this.form.price = this.value.price;
            this.form.comment = this.value.comment;
            this.form.offers = this.value.offers;
            this.form.type = this.value.type;
            this.form.has_substitution = this.value.has_substitution;
            this.editId = this.value.id;
        },
    },

    mounted() {
        const self = this;
        this.form.price = this.value.price;
        this.form.comment = this.value.comment;
        this.form.offers = this.value.offers;
        this.form.type = this.value.type;
        this.form.has_substitution = this.value.has_substitution;
        this.editId = this.value.id;
    },

    methods: {

        cancel() {
            this.$emit("cancel", this.value);
        },

        buildFormData(formData, data, parentKey) {
            const self = this;
            if (data && typeof data === 'object' && !(data instanceof Date) && !(data instanceof File)) {
                Object.keys(data).forEach(key => {
                    self.buildFormData(formData, data[key], parentKey ? `${parentKey}[${key}]` : key);
                });
            } else {
                const value = data == null ? '' : data;

                formData.append(parentKey, value);
            }
        },

        save(event) {
            const self = this;

            event.preventDefault();

            let formData = new FormData();
            self.buildFormData(formData, self.form, '');

            api
                .storeAnswer(formData, this.editId, this.questionId)
                .then((response) => {
                    self.$store.commit("showSnackbar", {
                        message: "Thank you! Your answer has been registered.",
                        color: "success",
                    });

                    this.$emit("saved", this.value);
                })
                .catch((error) => {
                    let data = error.response.data;
                    if (typeof data.errors != "undefined") {
                        for (let id in data.errors) {
                            self.errors[id] = data.errors[id];
                        }
                    }
                });
        },
        showSubstitutionAlert(value) {
            const message = `product datasheet
performance evidence
installation guidance
limitations of use
competency requirement

If your quote includes a product that differs from the BOQ, specification, named manufacturer,
system, performance requirement or requested item, please mark this as a substitution and upload
supporting product information. This helps the buyer identify whether technical approval,
competency evidence or compliance checks are required before order or installation.`

            if (value) {
                alert(message);
            }
        }
    },
};
</script>

<style>
</style>
