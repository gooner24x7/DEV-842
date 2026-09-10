<template>
    <v-row align="center" justify="center">
        <v-col cols="12" sm="8" md="4">
            <v-card class="elevation-1 p-2">
                <v-form @submit="doAuthorize">
                    <v-card-title>
                        Login Form
                    </v-card-title>
                    <v-card-text style="padding: 0 16px;">
                        <div v-if="message">
                            <v-alert dense outlined type="success">
                                {{ message }}
                            </v-alert>
                        </div>

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
                            prepend-icon="mdi-account"
                            type="text"
                        />

                        <v-text-field
                            id="password"
                            label="Password"
                            v-model="password"
                            name="password"
                            prepend-icon="mdi-lock"
                            type="password"
                        />

                        <v-spacer/>

                        <v-checkbox
                            v-model="rememberMe"
                            label="Remember Me"
                            :value=true
                        ></v-checkbox>
                    </v-card-text>
                    <v-card-actions style="padding: 0 16px; flex-wrap: wrap; gap: 10px;">
                        <v-btn type="submit" color="primary" class="m-0">Login</v-btn>
                        <v-btn type="button" color="primary" class="m-0" @click="loginWithMicrosoft()">
                            <v-icon left>mdi-microsoft</v-icon>
                            Login with Microsoft
                        </v-btn>
                        <v-btn type="button" class="m-0" @click="passwordReset()">Password Reset</v-btn>
                    </v-card-actions>
                    <div style="padding: 16px 16px 0 16px; font-size: 0.9em;">
                        <p>The data we collect helps power industry insights and benchmarking tools. All data is encrypted, anonymised and aggregated.</p>
                        <p>
                            <a href="https://www.thebuildchain.co.uk/privacy-policy/" target="_blank">Privacy Policy</a> |
                            <a href="https://www.thebuildchain.co.uk/terms-and-conditions/" target="_blank">Terms and Conditions</a>
                        </p>
                    </div>
                </v-form>
            </v-card>
        </v-col>
    </v-row>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions.js";

export default {
    name: "LoginForm",
    props: ["message"],
    components: {},
    data() {
        return {
            username: "",
            password: "",
            errors: [],
            rememberMe: false,
        }
    },

    mounted() {
        this.handleMicrosoftLogin();
    },

    methods: {
        handleMicrosoftLogin() {
            const self = this;
            if (this.$route.query.ms_data) {
                try {
                    const data = JSON.parse(atob(this.$route.query.ms_data));
                    if (data.access_token) {
                        self.$store.commit('setSession', data);
                        self.$store.commit('setLoginAs', false);
                        self.$eventBus.$emit('authorized', data);
                        self.redirectToFirstPage();
                    }
                } catch (e) {
                    this.errors.push("Failed to process Microsoft login data");
                }
            } else if (this.$route.query.error) {
                this.errors.push(this.$route.query.error);
            }
        },

        loginWithMicrosoft() {
            window.location.href = "/microsoft";
        },

        passwordReset(resetRequired = false) {
            const self = this;
            self.$router.push({name: 'password-reset', params: {resetRequired: resetRequired}});
        },

        redirectToFirstPage() {
            const self = this;

            self.$router.push("dashboard");
        },
        async doAuthorize(event) {
            const self = this;

            event.preventDefault();

            this.errors = [];

            if (!this.username) {
                this.errors.push("Username is required");
            }
            if (!this.password) {
                this.errors.push("Password is required");
            }

            if (this.errors.length === 0) {
                try {
                    const response = await api.login(this.username, this.password, this.rememberMe)

                    if (response.data.reset_required) {
                        self.passwordReset(true);
                        return;
                    }

                    if (typeof response.data.access_token != 'undefined') {
                        self.$store.commit('setSession', response.data);
                        self.$store.commit('setLoginAs', false);
                        self.$eventBus.$emit('authorized', response.data);
                        self.redirectToFirstPage();
                    } else {
                        self.errors.push('Wrong login or password');
                    }
                } catch (error) {
                    self.errors.push(error.response.data.error || "Wrong login or password");
                }
            }
        },
    },
};
</script>

<style scoped>
</style>
