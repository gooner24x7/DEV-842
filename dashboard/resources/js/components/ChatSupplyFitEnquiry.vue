<template>
    <v-card>
        <v-card-title>
            <span class="headline">Conversation</span>
        </v-card-title>

        <v-card-text>
            <v-alert v-if="chats.length === 0 && (userId ?? 0)<=0">
              No initiated chats
            </v-alert>

            <div v-if="chats.length > 0">
                <v-card
                    class="mx-auto mb-2"
                    max-width="300"
                >
                    <v-list>
                        <v-list-item
                            :active="(userId === item.userId)"
                            v-on:click="userId = item.user_id; item.qty = 0;"
                            v-for="(item, i) in chats"
                            :key="i"
                        >

                            <v-badge
                                :value="item.qty > 0"
                                color="red"
                                :content="item.qty"
                                :overlap="true"
                            >
                                <v-list-item-title v-text="item.first_name + '' + item.last_name"></v-list-item-title>
                            </v-badge>
                        </v-list-item>
                    </v-list>
                </v-card>

            </div>
            <div v-if="userId && userId > 0">
                <div>
                    <v-alert
                        v-for="item in messages"
                        :key="item.id"
                        :border="myMessage(item) ? 'right' : 'left'"
                        colored-border
                        :color="(myMessage(item) ? 'green' : getUserColor(item)) + ' accent-4'"
                        elevation="2"
                    >{{ item.message }}

                        <div class="caption date text--secondary">
                            {{ item.created_at | fromNow }}
                        </div>
                    </v-alert>
                </div>

                <v-divider></v-divider>

                <v-container>
                    <v-textarea
                        v-model="form.message"
                        :error-messages="errors.message"
                        clearable
                        clear-icon="mdi-close-circle"
                        label="Write your message"
                    ></v-textarea>
                </v-container>
            </div>
        </v-card-text>

        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="blue darken-1" text @click="reload">Refresh</v-btn>
            <v-btn color="blue darken-1" text @click="close">Close</v-btn>
            <v-btn color="blue darken-1" text @click="send">Send</v-btn>
        </v-card-actions>
    </v-card>
</template>

<script>
import api from "../common/api.js";
import colors from 'vuetify/lib/util/colors'

export default {
    props: ["enquiry", "visibility", "interlocutorId", "isSelectChat"],
    data() {
        return {
            chats: [],
            handler: null,
            userColors: {},
            userId: '',
            options: {
                height: "200px",
            },
            messages: [],
            form: {
                message: "",
            },
            errors: {
                message: [],
            },
        };
    },

    watch: {
        userId() {
            const self = this;
            self.reload();
        },

        isSelectChat() {
          const self = this;
          self.userId = self.interlocutorId;
        },

        interlocutorId() {
          const self = this;
          self.userId = self.interlocutorId;
        },

        visibility() {
            const self = this;

            clearTimeout(this.handler);

            if (this.visibility === true) {
                self.userId = self.interlocutorId;
                self.reload();

                this.handler = setInterval(() => {
                    self.reload();
                }, 1000);
            } else {
              this.userId = 0;
            }
        },
    },

    mounted() {
        const self = this;

        self.userId = self.interlocutorId

        clearTimeout(this.handler);

        this.handler = setInterval(() => {
            self.reload();
        }, 1000);
    },

    beforeDestroy() {
        clearTimeout(this.handler);
    },

    methods: {
        user() {
            return this.$store.getters.getSession.user;
        },

        getUserColor(item) {
            const self = this;

            const c = Object.keys(colors);
            if (typeof self.userColors[item.user_id] == 'undefined') {
                self.userColors[item.user_id] = c[Math.floor(Math.random() * c.length)];
            }

            return self.userColors[item.user_id];
        },

        myMessage(item) {
            let user = this.user();
            if (typeof user == "undefined") {
                return false;
            }

            return item.user_id === user.id;
        },

        close() {
            clearTimeout(this.handler);
            this.userId = 0;

            this.$emit("close");
        },

        welcomeMessages() {
            const self = this;

            let days = !isNaN(parseInt(self.enquiry.days)) ? self.enquiry.days + ' days' : self.enquiry.days;

            return [
                {
                    id: "supplyFit",
                    user_id: self.enquiry.user_id,
                    created_at: self.enquiry.created_at,
                    message: `Product "${self.enquiry.product.name}", ${days}, ${self.enquiry.comment}`,
                },
            ];
        },

        loadChats() {
          const self = this;
          api.loadSupplyFitEnquiryChats(this.enquiry.id, {paginate: 0})
            .then((response) => {
                self.chats = response.data
            })
        },

        reload() {
            const self = this;

            self.loadChats();

            if (!self.userId) {
                return;
            }

            api
                .loadSupplyFitEnquiryMessages(this.enquiry.id, {paginate: 0, userId: self.userId})
                .then((response) => {
                    console.log(response.data)
                    self.messages = self.welcomeMessages().concat(response.data);
                })
                .catch((error) => {
                    console.log(error)
                });
        },

        send(event) {
            const self = this;

            event.preventDefault();

            self.form.interlocutorId = self.userId ? self.userId : self.enquiry.user_id

            api
                .storeSupplyFitEnquiryMessage(self.form, this.enquiry.id)
                .then((response) => {
                    self.reload();

                    self.form.message = "";
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

<style scoped>
.date {
    position: absolute;
    right: 15px;
    bottom: 0px;
}
</style>
