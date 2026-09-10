<template>
    <v-row align="center" justify="center">
        <v-col cols="12" sm="8" md="4">
            <v-card class="elevation-1">
                <v-form @submit="doReset">
                    <v-card-title>Password Reset Form</v-card-title>
                    <v-card-text>
                        <v-alert
                            v-if="resetRequired"
                            type="info"
                            dense
                            outlined
                        >
                            <strong>Password reset required</strong><br>
                            Please help us to keep your account secure by resetting your password
                        </v-alert>

                        <div v-if="errors.length">
                            <v-alert dense outlined type="error">
                                <div v-bind:key="index" v-for="(error, index) in errors">
                                    {{ error }}
                                </div>
                            </v-alert>
                        </div>

                        <v-text-field
                            label="Username"
                            v-model="username"
                            name="username"
                            autcomplete="off"
                            prepend-icon="mdi-account"
                            type="text"
                        />

                        <v-spacer/>
                    </v-card-text>
                    <v-card-actions class="justify-space-between">
                        <v-btn type="submit" color="primary">Reset Password</v-btn>
                        <div>
                            Forgot username:
                            <a href="https://www.ntuk.co.uk/hire/support/">contact us</a>
                        </div>
                    </v-card-actions>
                </v-form>
            </v-card>
        </v-col>
    </v-row>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions.js";

export default {
    name: "PasswordResetForm",
    props: ["resetRequired"],
    components: {},
    data() {
        return {
            username: "",
            errors: [],
        }
    },
    methods: {
        redirectToFirstPage(message = null) {
            const self = this;

            if (permissions.isAuthorized()) {
                self.$router.push("dashboard");
                return;
            }

            self.$router.push({ name: 'login', params: { message: message }})
        },

        doReset(event) {
            const self = this;

            event.preventDefault();

            this.errors = [];

            if (!this.username) {
                this.errors.push("Username is required");
            }

            if (this.errors.length === 0) {
                api
                    .passwordReset(this.username)
                    .then(function (response) {
                        const message = "Password reset email sent. Please check your inbox for further details."
                        self.redirectToFirstPage(message);
                    })
                    .catch(function (error) {
                        self.errors.push(error.response.data.error || "Failed to reset password.");
                    });
            }
        },
    },
};
</script>

<style scoped>
</style>
