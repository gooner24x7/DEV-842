<template>
    <v-card>
        <v-card-title>
            <span class="headline">{{ title }}</span>
        </v-card-title>

        <v-card-text>
            <v-container>
                <v-col cols="12">
                    <v-text-field v-model="value.title" label="Title" :error="errors.title"></v-text-field>
                </v-col>

                <v-textarea v-model="value.embedCode" label="Embed Code" :errors="errors.embedCode"></v-textarea>
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
import api from '../common/api.js';

export default {
    props: ['value', 'title'],
    data() {
        return {
            categoryItems: [],
            parentCategorySearch: '',
            formValid: true,
            errors: {
                title: false,
                embedCode: false,
            },
        };
    },

    mounted() {
        const self = this;
    },

    methods: {
        cancel() {
            this.$emit('cancel', this.value);
        },

        save() {
            this.formValid = true;
            for (let i in this.errors) {
                this.errors[i] = false;

                if (typeof this.value[i] === 'undefined' || this.value[i] === '') {
                    this.formValid = false;
                    this.errors[i] = true;
                }
            }

            if (this.formValid) {
                this.$emit('save', this.value);
            }
        },
    },
};
</script>

<style>
</style>
