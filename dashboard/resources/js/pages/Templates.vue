<template>
    <div style="margin: 1em">
<!--        <h2>Templates</h2>-->

        <v-dialog v-model="dialogCreate" max-width="800px">
            <template v-slot:activator="{ on, attrs }">
                <v-btn class="m-2"
                       color="primary"
                       style="left: 0"
                       v-bind="attrs"
                       v-on="on"
                >Add template
                </v-btn
                >
            </template>

            <create-template-form
                :visible="dialogCreate" v-on:cancel="closeCreate" v-on:saved="savedCreate"></create-template-form>
        </v-dialog>

        <v-data-table :headers="headers" :items="items" :loading="loader" :options.sync="options"
                      :server-items-length="serverItemsLength"
                      item-key="id" loading-text="Loading... Please wait" mobile-breakpoint="1000">
            <template v-slot:item.questions="{ item }">
                <div v-for="question in item.questionsParsed">
                    <b>Question: {{ question.text }}</b>
                    <div v-if="question.type === 'yes_no'">
                        Score Yes: {{ question.score_yes }}<br/>
                        Score No: {{ question.score_no }}
                    </div>
                    <div v-else>
                        Text answer
                    </div>
                </div>
            </template>

            <template v-slot:item.actions="{ item }">

                <div class="text-center">
                    <v-btn
                        v-on:click="deleteTemplate(item)"
                    >Delete
                    </v-btn>

                    <v-dialog v-model="dialog" max-width="800px">
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn class="m-2"
                                   color="primary"
                                   style="left: 0"
                                   v-bind="attrs"
                                   v-on="on"
                            >Edit
                            </v-btn
                            >
                        </template>

                        <edit-template-form :id="item.id"
                                            :templateQuestions="item.questionsParsed"
                                            v-on:cancel="close" v-on:saved="saved"></edit-template-form>
                    </v-dialog>
                </div>
            </template>

        </v-data-table>


    </div>
</template>

<script>
import api from "../common/api.js";
import EditTemplateForm from "../components/EditTemplateForm";
import CreateTemplateForm from "../components/CreateTemplateForm.vue";

export default {
    components: {CreateTemplateForm, EditTemplateForm},
    data() {
        return {
            loader: false,
            headers: [{
                text: "ID",
                value: "id",
                sortable: true
            },
                {
                    text: "Name",
                    value: "name",
                    sortable: true
                },
                {
                    text: "Questions",
                    value: "questions",
                    sortable: true
                },
                {
                    text: "",
                    value: "actions",
                    sortable: true,
                },
            ],
            items: [],
            dialog: false,
            dialogCreate: false,
            serverItemsLength: 0,
            options: {
                page: 1,
                itemsPerPage: 10,
                sortBy: ["id"],
                sortDesc: [true],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false,
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
    },

    methods: {
        close() {
            this.dialog = false;
        },

        closeCreate() {
            this.dialogCreate = false;
        },

        async savedCreate() {
            await this.load();
            this.dialogCreate = false;
        },

        async saved() {
            await this.load();
            this.dialog = false;
        },

        async deleteTemplate(item) {
            await api.deleteTemplate(item.id);

            await this.load();
        },

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

            try {
                const response = await api.loadTemplates(params)
                self.items = response.data.data.map((item) => {
                    item.questionsParsed = JSON.parse(item.questions);
                    return item;
                });
                self.serverItemsLength = response.data.total;
            } catch (e) {
            }

            self.loader = false;
        },
    },
};
</script>

<style>
</style>
