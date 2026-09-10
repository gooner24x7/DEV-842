<template>
    <v-card>
        <v-card-title>
            <span class="headline">Enquiries > Marketplace > Duplicate</span>
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
                                    label="Project Start Date"
                                    :rules="[() => !!value.days || 'This field is required']"
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
            selectedDate: '',
            successCreated: false,
            disabled: false,
            menu1: false,
            errors: {
                comment: [],
                days: [],
                type: [],
            },
        };
    },

    watch: {
        selectedDate:  function (n, o) {
            this.value.days = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        }
    },

    mounted() {
        const self = this;
    },

    methods: {
        cancel() {
            this.$emit("cancel", this.value);
        },

        isUser() {
            return permissions.hasRole(permissions.role_user);
        },

        async save(event) {
            const self = this;
            event.preventDefault();

            self.disabled = true
            try {
                await api.duplicateSupplyFitEnquiry(this.value);
                self.$emit("saved", {});
                self.disabled = false
            } catch(error) {
                let data = error.response.data;
                if (typeof data.errors != "undefined") {
                    for (let id in data.errors) {
                        self.errors[id] = data.errors[id];
                    }
                }
                self.disabled = false
            }
        },
    },
};
</script>

<style>
</style>
