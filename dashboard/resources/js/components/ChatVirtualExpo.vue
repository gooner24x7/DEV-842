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
                    :color="(myMessage(item) ? 'green' : 'deep-purple') + ' accent-4'"
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

export default {
    props: ["sharedContact", "visibility"],
    data() {
        return {
            handler: null,
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

        reload() {
            const self = this;
            api
                .loadMessagesVirtualExpo(this.sharedContact.id, {paginate: 0})
                .then((response) => {
                    self.messages = response.data;
                })
                .catch((error) => {
                    console.log(error)
                });
        },

        send(event) {
            const self = this;

            event.preventDefault();

            api
                .storeMessageVirtualExpo(this.form, this.sharedContact.id)
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
