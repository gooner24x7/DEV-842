<template>
 <div>
     <v-card>
         <v-card-title>
             <span class="headline">Shared Contacts</span>
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
                             <template v-slot:item.actions="{ item }">
                                 <div class="text-center">
                                     <v-tooltip top>
                                         <template v-slot:activator="{ on, attrs }">
                                             <v-btn
                                                 small
                                                 class="mr-2"
                                                 @click="viewItem(item)"
                                                 icon
                                                 color="black"
                                                 v-bind="attrs"
                                                 v-on="on"
                                             >
                                                 <v-badge
                                                     :value="item.newMsgQty > 0"
                                                     color="red"
                                                     :content="item.newMsgQty"
                                                     :overlap="true"
                                                 >
                                                     <v-icon>
                                                         mdi-forum
                                                     </v-icon>
                                                 </v-badge>
                                             </v-btn
                                             >
                                         </template>
                                         <span>Live Chat</span>
                                     </v-tooltip>
                                 </div>
                             </template>
                         </v-data-table>
                     </v-col>

                 </v-row>
             </v-container>
         </v-card-text>
     </v-card>

     <v-dialog v-model="chatDialog" max-width="500px">
         <chat
             :visibility="chatDialog"
             :sharedContact="sharedContact"
             v-on:close="chatDialog = false"
         />
     </v-dialog>
 </div>
</template>

<script>
import api from "../common/api";
import Chat from "../components/ChatVirtualExpo";

export default {
    name: "SharedContactsDialog",
    props: ["id", "show"],
    components: {
        Chat,
    },

    data() {
        return {
            items: [],
            loader: false,
            chatDialog: false,
            sharedContact: undefined,
            headers: [
                {
                    text: "ID",
                    value: "id",
                    sortable: true,
                },
                {
                    text: "Company Name",
                    value: "company_name",
                    sortable: true,
                },
                {
                    text: "Username",
                    value: "username",
                    sortable: true,
                },
                {
                    text: "Date",
                    value: "created_at",
                    sortable: true,
                },
                {
                    text: '',
                    value: 'actions',
                    sortable: false,
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
        show: {
            handler(n, o) {
                const self = this;

                if (!self.show) {
                    return;
                }

                self.load();
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
        viewItem(item) {
            const self = this;
            self.sharedContact = item;
            self.chatDialog = !this.chatDialog;
        },

        cancel() {
            this.$emit("cancel", this.value);
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
                virtual_expo_id: self.id,
            };

            try {
                const response = await api.loadVirtualExpoSharedContacts(params)

                self.items = response.data.data;
                self.serverItemsLength = response.data.total;
                self.form.message = '';
            } catch (e) {
                console.log(e)
            }

            self.loader = false;
        },
    }
}
</script>

<style scoped>

</style>
