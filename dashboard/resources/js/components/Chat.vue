<template>
    <v-card>
        <v-card-title>
            <span class="headline">Conversation</span>
        </v-card-title>

        <v-card-text>
            <div>
                <div class="message" v-for="(item, index) in messages" :key="item.id">
                    <v-alert
                        :border="myMessage(item) ? 'right' : 'left'"
                        colored-border
                        :color="(myMessage(item) ? 'green' : getUserColor(item)) + ' accent-4'"
                        elevation="2"
                    >{{ item.message }}

                        <div class="caption date text--secondary">
                            {{ item.created_at | fromNow }}
                        </div>
                    </v-alert>
                    <div v-if="index === indexes[indexes.length - 1] && item.viewed_at" class="caption viewed-at">
                        Read at {{formatViewedAt(item.viewed_at)}}
                    </div>
                </div>
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
import moment from "moment/moment";

export default {
    props: ["answer", "question", "visibility"],
    data() {
        return {
            handler: null,
            userColors: {},
            options: {
                height: "200px",
            },
            messages: [],
            indexes: [],
            form: {
                message: "",
            },
            errors: {
                message: [],
            },
        };
    },

    watch: {
        visibility: function () {
            const self = this;

            clearTimeout(this.handler);

            if (this.visibility === true) {
                this.handler = setInterval(() => {
                    self.reload();
                }, 2000);
            }
        },
    },

    mounted() {
        const self = this;

        clearTimeout(this.handler);

        this.handler = setInterval(() => {
            self.reload();
        }, 2000);
    },

    beforeDestroy() {
        clearTimeout(this.handler);
    },

    methods: {
        formatViewedAt(datetime) {
            let viewed_at = moment(datetime, 'YYYY-MM-DD HH:mm:ss');

            if (viewed_at.isSame(new Date(), "day")) {
                return viewed_at.format('HH:mm');
            }

            return viewed_at.format('DD-MM-YYYY HH:mm');
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        getUserColor(item) {
            const self = this;

            const c = Object.keys(colors);
            if (typeof self.userColors[item.user_id] == 'undefined') {
                self.userColors[item.user_id] = c[Math.floor(Math.random()*c.length)];
            }

            return self.userColors[item.user_id];
        },

        myMessage(item) {
            let user = this.user();

            if (typeof user == "undefined" || typeof item == "undefined") {
                return false;
            }

            return item.user_id === user.id;
        },

        close() {
            clearTimeout(this.handler);

            this.$emit("close");
        },

        welcomeMessages() {
            const self = this;

            return [
                {
                    id: "question",
                    user_id: self.question.user_id,
                    created_at: self.question.created_at,
                    message: `Product "${self.question.product.name}", ${self.question.days} days, ${self.question.comment}`,
                },
                {
                    id: "answer",
                    user_id: self.answer.user_id,
                    created_at: self.answer.created_at,
                    message: `Price ${self.answer.price}, ${self.answer.comment}`,
                },
            ];
        },

        reload() {
            const self = this;
            api
                .loadMessages(this.answer.id, {paginate: 0})
                .then((response) => {
                    self.messages = self.welcomeMessages().concat(response.data);

                    self.indexes = [];

                    self.messages.forEach((item, index) => {
                        if (self.myMessage(item)) {
                            self.indexes.push(index);
                        }
                    });
                })
                .catch((error) => {
                    console.log(error);
                });
        },

        send(event) {
            const self = this;

            event.preventDefault();

            api
                .storeMessage(this.form, this.answer.id)
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
    bottom: 0;
}
.viewed-at {
    text-align: right;
    position: relative;
    top: -12px;
}
</style>
