<template>
    <v-card>
        <v-card-title>
            <span class="headline">Enquiries > Purchase & Hire > Duplicate</span>
        </v-card-title>

        <v-card-text>
            <v-container>
                <v-row>
                    <v-col cols="12">
                        <v-menu
                            v-model="menu1"
                            :close-on-content-click="false"
                            max-width="290"
                        >
                            <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                    v-model="value.days"
                                    :error-messages="errors.days"
                                    :type="daysType()"
                                    :label="daysLabel()"
                                    :rules="[() => !!value.days || 'This field is required']"
                                    v-bind="attrs"
                                    v-on="on"
                                ></v-text-field>
                            </template>
                            <v-date-picker
                                v-model="selectedDate"
                                no-title
                                v-if="(value?.product_type !== 1)"
                                @change="menu1 = false"
                            ></v-date-picker>
                        </v-menu>

                    </v-col>
                </v-row>

                <v-autocomplete
                    :items="['live', 'tender']"
                    v-model="value.type"
                    label="Live/Tender"
                    :rules="[() => !!value.type || 'This field is required']"
                    :error-messages="errors.type"
                >
                </v-autocomplete>

                <v-textarea
                    v-model="value.comment"
                    clearable
                    clear-icon="mdi-close-circle"
                    label="Comments box"
                ></v-textarea>
            </v-container>
        </v-card-text>

        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="blue darken-1" text @click="cancel">Cancel</v-btn>
            <v-btn color="blue darken-1" text @click="save" :disabled="disabled">Save</v-btn>
        </v-card-actions>
    </v-card>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";
import moment from "moment";

export default {
    props: ["value", "title"],
    data() {
        return {
            productItems: [],
            disabled: false,
            successCreated: false,
            selectedDate: '',
            menu1: false,
            errors: {
                comment: [],
                days: [],
                type: [],
            },
        };
    },

    mounted() {
        const self = this;
    },

    watch: {
        selectedDate:  function (n, o) {
            this.value.days = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        }
    },

    methods: {
        cancel() {
            this.$emit("cancel", this.value);
        },

        isUser() {
            return permissions.hasRole(permissions.role_user);
        },

        daysType() {
            return (this.value?.product_type === 1) ? 'number' : 'text'
        },
        daysLabel() {
            return (this.value?.product_type === 1) ? 'Number Of Hire Days' : 'Estimated Delivery Date'
        },

        save(event) {
            const self = this;
            event.preventDefault();

            self.disabled = true
            api
                .duplicateEnquiry(this.value)
                .then((response) => {
                    self.$emit("saved", {});
                    self.disabled = false
                })
                .catch((error) => {
                    let data = error.response.data;
                    if (typeof data.errors != "undefined") {
                        for (let id in data.errors) {
                            self.errors[id] = data.errors[id];
                        }
                    }
                    self.disabled = false
                });
        },
    },
};
</script>

<style>
</style>
