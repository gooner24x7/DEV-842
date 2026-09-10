<template>
    <v-card>
        <v-card-title>
            <span class="headline">{{ title }}</span>
        </v-card-title>

        <v-card-text>
            <v-container>
                <v-row>
                    <v-col cols="12">
                        <AttachmentsInput
                            v-model="form.attachment"
                        ></AttachmentsInput>
                    </v-col>
                </v-row>

                <v-text-field
                    v-model="form.price"
                    :error-messages="errors.price"
                    type="number"
                    label="Price (£)"
                    :rules="[() => !!form.price || 'This field is required']"
                ></v-text-field>

                <v-row>
                    <v-col cols="12">
                        <v-autocomplete
                            :items="['full', 'part']"
                            v-model="form.type"
                            :error-messages="errors.type"
                            label="Full/Part Quote"
                            :rules="[() => !!form.type || 'This field is required']"
                        >
                        </v-autocomplete>
                    </v-col>
                </v-row>

                <v-textarea
                    v-model="form.comment"
                    clearable
                    clear-icon="mdi-close-circle"
                    label="Comments"
                ></v-textarea>

                <v-checkbox
                    v-model="form.resources_available"
                    label="We have sufficient resources available"
                    :true-value="1"
                    :false-value="0"
                ></v-checkbox>

                <v-checkbox
                    v-model="form.is_competent"
                    label="We are competent to undertake this work"
                    :true-value="1"
                    :false-value="0"
                ></v-checkbox>
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
    props: ["value", "title", "enquiryId"],
    data() {
        return {
            productItems: [],
            editId: null,
            form: {
                price: "",
                comment: "",
                offers: "",
                type: "",
                resources_available: 0,
                is_competent: 0,
                attachment: [],
            },
            errors: {
                price: [],
                comment: [],
                offers: [],
                type: []
            },
        };
    },

    watch: {
        value: function (o, n) {
            this.init();
        },
    },

    mounted() {
        //this.init();
    },

    methods: {
        init() {
            const self = this;

            self.form.price = self.value.price;
            self.form.comment = self.value.comment;
            self.form.offers = self.value.offers;
            self.form.type = self.value.type;
            self.form.resources_available = self.value.resources_available;
            self.form.is_competent = self.value.is_competent;
            self.editId = self.value.id;
        },

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
                .storeSupplyFitEnquiryQuote(formData, this.editId, this.enquiryId)
                .then((response) => {
                    self.$store.commit("showSnackbar", {
                        message: "Thank you! Your quote has been registered.",
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
    },
};
</script>

<style>
</style>
