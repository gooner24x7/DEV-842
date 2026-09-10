<template>
    <div style="margin: 1em">

        <v-dialog v-model="dialog" max-width="900px">
            <review-template-form
                :editedItem="editedItem"
                v-on:cancel="close"
                v-on:saved="saved"
            />
        </v-dialog>

        <v-data-table
            :options="options"
            :headers="headers"
            :items="items"
            :loading="loader"
            item-key="id"
            loading-text="Loading... Please wait"
        >
            <template v-slot:top>
                <v-row>
                    <v-col cols="12">
                        <v-btn
                            class="m-2"
                            color="primary"
                            @click="newTemplate()"
                        >
                            New Template
                        </v-btn>
                    </v-col>
                </v-row>
            </template>

            <template v-slot:item.actions="{ item }">
                <div class="text-center">
                    <v-btn class="primary" v-on:click="edit(item)">Edit</v-btn>
                    <v-btn class="primary" v-on:click="deleteItem(item)">Delete</v-btn>
                </div>
            </template>
        </v-data-table>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";
import moment from "moment/moment";
import ReviewTemplateForm from "../components/Reviews/ReviewTemplateForm.vue";

export default {
    name: 'ReviewTemplates',
    props: [],
    components: {
        ReviewTemplateForm
    },
    data() {
        return {
            loader: false,
            items: [],
            dialog: false,
            editedIndex: -1,
            editedItem: null,
            options: {
                page: 1,
                itemsPerPage: 10,
                sortBy: ["id"],
                sortDesc: [true],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false
            },
            headers: [
                {
                    text: "ID",
                    value: "id",
                    sortable: true,
                },
                {
                    text: "Name",
                    value: "name",
                    sortable: true,
                },
                {
                    text: "Created At",
                    value: "created_at",
                    sortable: true,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: true,
                },
            ],
        };
    },

    async mounted() {
        await this.load();
    },

    computed: {

    },

    methods: {
        async load() {
            const self = this;
            self.loader = true;

            let params = {
                ...self.options,
                page: self.options.page,
                itemsPerPage: self.options.itemsPerPage,
                sortBy: self.options.sortBy[0],
                sortDesc: self.options.sortDesc[0] ? 1 : 0,
            };

            const response = await api.loadReviewTemplates(params);

            self.items = response.data;
            self.loader = false;
        },

        close() {
            const self = this;

            self.dialog = false;
        },

        async saved() {
            const self = this;

            await self.load();
            self.dialog = false;
        },

        newTemplate() {
            const self = this;

            self.editedItem = null;
            self.dialog = true;
        },

        edit(item) {
            const self = this;

            self.editedIndex = self.items.indexOf(item);
            self.editedItem = Object.assign({}, item);
            self.dialog = true;
        },

        deleteItem(item) {
            const self = this;

            if (confirm('Are you sure you want to delete this template?')) {
                api.deleteReviewTemplate(item.id)
                    .then(response => {
                        self.load();

                        self.$store.commit("showSnackbar", {
                            message: "Template deleted",
                            color: "success",
                        });
                    })
                    .catch(error => {
                        console.log(error);

                        self.$store.commit("showSnackbar", {
                            message: "An error occurred",
                            color: "error",
                        });
                    });
            }
        }
    },
};
</script>

<style>

</style>
