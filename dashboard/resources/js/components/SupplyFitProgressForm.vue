<template>
    <v-card>
        <v-card-title>
            <span class="headline">Quote</span>
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

                <v-menu
                    v-model="menu1"
                    :close-on-content-click="false"
                    max-width="290"
                >
                    <template v-slot:activator="{ on, attrs }">
                        <v-text-field
                            v-model="form.date"
                            label="Select Date"
                            type="text"
                            v-bind="attrs"
                            v-on="on"
                        ></v-text-field>
                    </template>
                    <v-date-picker
                        v-model="selectedDate"
                        no-title
                        @change="menu1 = false"
                    ></v-date-picker>
                </v-menu>

                <v-textarea
                    v-model="form.comment"
                    clearable
                    clear-icon="mdi-close-circle"
                    label="Comments"
                ></v-textarea>

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
import moment from "moment/moment";

export default {
    components: {AttachmentsInput},
    props: ["value", "quoteId"],
    data() {
        return {
            editId: null,
            menu1: false,
            selectedDate: '',

            form: {
                date: "",
                comment: "",
                attachment: [],
            },
        };
    },

    watch: {
        value: function (o, n) {
            this.form.date = this.value.date;
            this.form.comment = this.value.comment;
            this.editId = this.value.id;
        },
        selectedDate: function (n, o) {
            this.form.date = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        },
    },

    mounted() {
        const self = this;

        this.form.date = this.value.date;
        this.form.comment = this.value.comment;
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

            formData.append('quoteId', self.quoteId);
            formData.append('type', 1);

            api
                .storeQuoteProgress(formData)
                .then((response) => {
                    self.$store.commit("showSnackbar", {
                        message: "Thank you! Your quote has been registered.",
                        color: "success",
                    });

                    this.$emit("saved", this.value);
                })
                .catch((error) => {
                    console.log(error);
                });
        },
    },
};
</script>

<style>
</style>
