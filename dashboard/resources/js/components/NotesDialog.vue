<template>
 <div>
     <v-card>
         <v-card-title>
             <span class="headline">Notes</span>
         </v-card-title>

         <v-card-text>
             <v-container>
                 <v-row>
                     <v-col cols="12">
                         <v-data-table
                             item-key="id"
                             :headers="headers"
                             :items="items"
                             :server-items-length="serverItemsLength"
                             :loading="loader"
                             :options.sync="options"
                             loading-text="Loading... Please wait"
                         >
                             <template v-slot:item.message="{ item }">
                                 <v-text-field v-if="item.user_id === user().id" v-model="item.message" dense hide-details outlined
                                               @change="(e) => updateMessage(e, item)"></v-text-field>
                                 <span v-else>{{ item.message }}</span>
                             </template>

                             <template v-slot:item.created_at="{ item }">
                                <span>{{item.created_at | formatDate}}</span>
                             </template>
                         </v-data-table>
                     </v-col>
                 </v-row>

                 <v-row>

                     <v-col cols="12">
                         <v-form @submit="save">
                         <v-textarea
                             filled
                             v-model="form.message"
                             placeholder="Write your note here"
                             :error-messages="errors.message"
                             :rules="[() => !!form.message || 'This field is required']"
                         ></v-textarea>

                         <v-btn color="primary" small class="mr-2"  type="submit">Save Note</v-btn>
                         <v-btn small class="mr-2" @click="cancel">Cancel</v-btn>
                         </v-form>
                     </v-col>

                 </v-row>
             </v-container>
         </v-card-text>
     </v-card>
 </div>
</template>

<script>
import api from "../common/api";

export default {
    name: "NotesDialog",
    props: ["parentId", "type"],

    data() {
        return {
            form: {
                parentId: this.parentId,
                type: this.type,
                message: '',
            },

            items: [],
            errors: {
                message: []
            },

            loader: false,
            headers: [
                {
                    text: "ID",
                    value: "id",
                    sortable: true,
                },
                {
                    text: "Message",
                    value: "message",
                    sortable: true,
                },
                {
                    text: "Author",
                    value: "first_name",
                    sortable: true,
                },
                {
                    text: "Date",
                    value: "created_at",
                    sortable: true,
                },
            ],

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
        }
    },

    watch: {
        parentId: {
            handler(n, o) {
                const self = this;

                self.load();

                self.form.parentId = n;
                self.form.message = '';
            },
        },
        options: {
            handler(n, o) {
                this.load();
            },
            deep: true,
        },
    },

    methods: {
        user() {
            return this.$store.getters.getSession.user;
        },

        cancel() {
            this.$emit("cancel", this.value);
        },

        save(event) {
            const self = this;

            event.preventDefault();

            if (self.form.message === "") {
                return;
            }

            self.loader = true;

            api.storeNote(self.form).then((response) => {
                self.load();
            }).catch(err => {
                console.log(err);
            }).finally(() => {
                self.loader = true;
            })
        },

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
                .loadNotes(self.type, self.parentId, params)
                .then((response) => {
                    self.items = response.data.data;

                    self.serverItemsLength = response.data.total;
                    self.loader = false;
                    self.form.message = '';
                })
                .catch((err) => {
                    console.log(err);
                    self.loader = false;
                });
        },

        updateMessage(e, item) {
            const self = this;

            api
                .updateNote({id: item.id, message: item.message})
                .then((response) => {
                    self.$store.commit("showSnackbar", {
                        message: "Message updated",
                        color: "success",
                    });
                })
                .catch((err) => {
                    console.log(err);
                });
        }
    }
}
</script>

<style scoped>

</style>
