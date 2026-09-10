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
                    label="Price (ex VAT) (£)"
                    :rules="[() => !!form.price || 'This field is required']"
                ></v-text-field>

                <v-row>
                    <v-col cols="12">
                        <v-autocomplete
                            v-model="form.type"
                            :items="typeOptions"
                            item-value="id"
                            item-text="name"
                            :error-messages="errors.type"
                            label="Type"
                            :rules="[() => !!form.type || 'This field is required']"
                        >
                        </v-autocomplete>
                    </v-col>
                </v-row>

                <v-textarea
                    v-model="form.comments"
                    clearable
                    clear-icon="mdi-close-circle"
                    label="Comments"
                ></v-textarea>

                <v-textarea
                    v-model="form.offers"
                    clearable
                    clear-icon="mdi-close-circle"
                    label="Offers"
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

export default {
    components: {AttachmentsInput},
    props: ["value", "title", "enquiryId"],
    data() {
        return {
            editId: null,
            form: {
                type: "",
                price: "",
                comments: "",
                offers: "",
                attachment: [],
            },
            errors: {
                type: [],
                price: [],
                comments: [],
                offers: [],
            },
            typeOptions: [
                {id: 0, name: 'Full'},
                {id: 1, name: 'Part'},
            ]
        };
    },

    watch: {
        value: function (o, n) {
            this.editId = this.value.id;
            this.form.type = this.value.type;
            this.form.price = this.value.price;
            this.form.comments = this.value.comments;
            this.form.offers = this.value.offers;
        },
    },

    mounted() {
        const self = this;
        this.editId = this.value.id;
        this.form.type = this.value.type;
        this.form.price = this.value.price;
        this.form.comments = this.value.comments;
        this.form.offers = this.value.offers;
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
                .storeLogisticsQuote(formData, this.editId, this.enquiryId)
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
