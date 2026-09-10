<template>
    <v-card>
        <v-card-title>
            <span class="headline">Conversation</span>
        </v-card-title>

        <v-card-text>
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
    props: ["supplyFitEnquiryQuote", "supplyFitEnquiry", "visibility"],
    data() {
        return {
            handler: null,
            userColors: {},
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
        visibility: function () {
            const self = this;

            clearTimeout(this.handler);

            if (this.visibility === true) {
                this.handler = setInterval(() => {
                    self.reload();
                }, 1000);
            }
        },
    },

    mounted() {
        const self = this;

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

            this.$emit("close");
        },

        welcomeMessages() {
            const self = this;

            return [
                {
                    id: "question",
                    user_id: self.supplyFitEnquiry.user_id,
                    created_at: self.supplyFitEnquiry.created_at,
                    message: `Product "${self.supplyFitEnquiry.product.name}", ${self.supplyFitEnquiry.days} days, ${self.supplyFitEnquiry.comment}`,
                },
                {
                    id: "answer",
                    user_id: self.supplyFitEnquiryQuote.user_id,
                    created_at: self.supplyFitEnquiryQuote.created_at,
                    message: `Price ${self.supplyFitEnquiryQuote.price}, ${self.supplyFitEnquiryQuote.comment}`,
                },
            ];
        },

        reload() {
            const self = this;
            api
                .loadSupplyFitQuoteMessages(this.supplyFitEnquiryQuote.id, {paginate: 0})
                .then((response) => {
                    self.messages = self.welcomeMessages().concat(response.data);
                })
                .catch((error) => {
                    console.log(error)
                });
        },

        send(event) {
            const self = this;

            event.preventDefault();

            api
                .storeSupplyFitQuoteMessage(this.form, this.supplyFitEnquiryQuote.id)
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
