<template>
    <v-card>
        <v-card-title>
            <span class="headline">{{ title }}</span>
        </v-card-title>
        <v-card-text>
            <v-container>
                <v-col cols="12">
                    <v-text-field v-model="value.name" label="Name" :error="errors.name"></v-text-field>
                </v-col>
                <v-row>
                    <v-col cols="6" v-if="value.category_id <= 0 || typeof value.category_id === 'undefined'">
                        <v-combobox
                            v-model="categoryType"
                            :items="categoryTypes"
                            outlined
                            dense
                            item-text="name"
                            item-value="id"
                            clear-icon="mdi-close-circle"
                            label="Select Type"
                        >
                        </v-combobox>
                    </v-col>
                    <v-col cols="6">
                        <v-checkbox v-model="value.active" label="Active" :error="errors.active"></v-checkbox>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col cols="6">
                        <v-autocomplete
                            item-text="name"
                            item-value="id"
                            :search-input.sync="parentCategorySearch"
                            :items="categoryItems"
                            v-model="value.category_id"
                            :error="errors.category_id"
                            label="Category"
                        >
                        </v-autocomplete>
                    </v-col>
                    <v-col cols="6">
                        <category-tree-selector v-on:select="selectCategory"/>
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
import api from '../common/api.js';
import CategoryTreeSelector from "./Dialog/CategoryTreeSelector";

export default {
    components: {
        CategoryTreeSelector
    },
    props: ['value', 'title'],
    data() {
        return {
            categoryItems: [],

            categoryType: '',
            categoryTypes: [
                {
                    id: 1,
                    name: 'Hire Enquiry',
                },
                {
                    id: 2,
                    name: 'Purchase Enquiry',
                },
                {
                    id: 3,
                    name: 'Marketplace',
                }
            ],

            parentCategorySearch: '',
            formValid: true,
            errors: {
                name: false,
            },
        };
    },
    mounted() {
        const self = this;

        self.reloadCategories('');
    },
    watch: {
        parentCategorySearch(val) {
            const self = this;

            self.reloadCategories(val);
        },
        'value.type': (val) => {
            this.categoryType = val;
        },
        categoryType(val) {
            this.value.type = val.id;
        },
    },
    methods: {
        selectCategory(category) {
            this.reloadCategories(category.name);
            this.value.category_id = category.id;
        },
        reloadCategories(val) {
            const self = this;
            api.categoryOptions({
                'search': val,
            }).then((resp) => {
                self.categoryItems = resp.data;
            });
        },

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
