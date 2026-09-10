<template>
    <div style="margin: 1em">
        <h2>Video Tutorials</h2>
        <v-dialog v-model="dialog" max-width="500px">
            <template v-slot:activator="{ on, attrs }">
                <v-btn color="primary" dark class="m-2" v-bind="attrs" v-on="on" style="left: 0"
                >New Item
                </v-btn
                >
            </template>
            <tutorial-form
                v-model="editedItem"
                v-on:save="save"
                v-on:cancel="close"
                :title="formTitle"
            />
        </v-dialog>

        <div style="position: relative; padding-top: 1em">
            <v-data-table
                item-key="id"
                :headers="headers"
                :items="items"
                :server-items-length="serverItemsLength"
                :loading="loader"
                :options.sync="options"
                loading-text="Loading... Please wait"
            >
                <template v-slot:top>

                </template>
                <template v-slot:item.actions="{ item }">
                    <div class="text-center">
                        <v-btn color="primary" small class="mr-2" @click="editItem(item)"
                        >Edit
                        </v-btn
                        >

                        <v-btn color="primary" small @click="deleteItem(item)"
                        >Delete
                        </v-btn
                        >
                    </div>
                </template>
            </v-data-table>
        </div>
    </div>
</template>

<script>
import api from '../common/api.js';
import TutorialForm from '../components/TutorialForm';

export default {
    components: {
        TutorialForm,
    },
    data() {
        return {
            loader: false,
            headers: [
                {
                    text: 'ID',
                    value: 'id',
                    sortable: true,
                },
                {
                    text: 'Title',
                    value: 'title',
                    sortable: true,
                },
                {
                    text: '',
                    value: 'actions',
                    sortable: false,
                },
            ],
            items: [],
            serverItemsLength: 0,
            options: {
                page: 1,
                itemsPerPage: 10,
                sortBy: ['id'],
                sortDesc: [true],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false,
            },
            dialog: false,
            editedIndex: -1,
            editedItem: {
                title: '',
                embedCode: '',
            },
            defaultItem: {
                title: '',
                embedCode: '',
            },
        };
    },

    mounted() {
    },

    watch: {
        options: {
            handler(n, o) {
                this.load();
            },
            deep: true,
        },
        dialog(val) {
            val || this.close();
        },
    },

    computed: {
        formTitle() {
            return this.editedIndex === -1 ? 'New Video' : 'Edit Video';
        },
    },

    methods: {
        load() {
            const self = this;
            self.loader = true;

            let params = {
                ...self.options,
                page: self.options.page,
                itemsPerPage: self.options.itemsPerPage,
                sortBy: self.options.sortBy[0],
                sortDesc: self.options.sortDesc[0] ? 1 : 0,
            };

            api
                .loadTutorials(params)
                .then((response) => {
                    self.items = response.data.data;

                    self.serverItemsLength = response.data.total;

                    self.loader = false;
                })
                .catch((err) => {
                    self.loader = false;
                });
        },

        editItem(item) {
            this.editedIndex = this.items.indexOf(item);
            this.editedItem = Object.assign({}, item);
            this.dialog = true;
        },

        deleteItem(tutorial) {
            const self = this;

            if (confirm('Are you sure you want to delete this tutorial?')) {
                self.loader = true;
                api.deleteTutorial(tutorial.id).then((response) => {
                    self.load();

                    self.loader = false;
                });
            }
        },

        close() {
            this.dialog = false;
            this.$nextTick(() => {
                this.editedItem = Object.assign({}, this.defaultItem);
                this.editedIndex = -1;
            });
        },

        save(tutorial) {
            const self = this;

            self.loader = true;

            api.storeTutorial(tutorial, tutorial.id).then((response) => {
                self.load();

                self.loader = false;
            });

            this.close();
        },
    },
};
</script>

<style>
</style>
