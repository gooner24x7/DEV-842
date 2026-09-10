<template>
<div>
    <v-app-bar v-if="showToolbar" app>
        <v-btn icon @click="showleftMenu = !showleftMenu">
            <v-icon v-if="showleftMenu">fa-bars</v-icon>
            <v-icon v-else>fa-bars</v-icon>
        </v-btn>

        <v-spacer></v-spacer>

        <div class="nav-top">
            <div v-if="loginAs && user" style="display: inline-flex; border-radius: 3px; background-color: lightgrey; padding: 5px;">
                <div style="text-align: left; margin-right: 5px;">
                    You are currently logged in as: {{user.first_name}}
                    <br>Click the button to return to your own account
                </div>
                <v-btn @click="loginReturn">Return</v-btn>
            </div>

            <div v-if="isAuthorized" class="support-icon" @click="goToSupport()">
                <v-icon>fa-phone</v-icon>
                <div>Support</div>
            </div>

            <div v-if="isAuthorized" class="mn-user-icon">
                <b @click="routeCall('profile', {})" style="cursor: pointer">
                    <i class="fa fa-user-circle user-icon" aria-hidden="true"></i>
                    {{ user ? user.first_name : '' }}
                    <span v-if="user && user.last_name !== 'N/A'" class="mr-1">
                        {{ user ? user.last_name : '' }}
                    </span>
                </b>
                <div style="display: none">(as {{ joinNames(user ? user.roles : []) }})</div>
            </div>

            <div v-if="isAuthorized" class="text-center" style="line-height: 44px;">
                <v-menu offset-y>
                    <template v-slot:activator="{ on, attrs }">
                        <v-btn
                            class="btn-icon"
                            v-bind="attrs"
                            v-on="on"
                            :ripple="false"
                            icon
                        >
                            <v-icon dark>
                                mdi-arrow-down-bold-box-outline
                            </v-icon>
                        </v-btn>
                    </template>
                    <v-list dense>
                        <v-list-item @click="routeCall('profile', {})" v-if="isAuthorized">
                            <v-list-item-action>
                                <v-icon small>fa-cogs</v-icon>
                            </v-list-item-action>
                            <v-list-item-content class="text-xs-left">
                                Account Settings
                            </v-list-item-content>
                        </v-list-item>

                        <v-list-item @click="routeCall('onboarding-form', {})">
                            <v-list-item-action>
                                <v-icon small>fa-cogs</v-icon>
                            </v-list-item-action>
                            <v-list-item-content class="text-xs-left">
                                Company Settings
                            </v-list-item-content>
                        </v-list-item>

                        <v-list-item @click="routeCall('settings', {})" v-if="isAdmin">
                            <v-list-item-action>
                                <v-icon small>fa-cogs</v-icon>
                            </v-list-item-action>
                            <v-list-item-content class="text-xs-left">
                                Settings
                            </v-list-item-content>
                        </v-list-item>

                        <v-list-item @click="clickLogout" v-if="isAuthorized">
                            <v-list-item-action>
                                <v-icon small>fa-sign-out</v-icon>
                            </v-list-item-action>
                            <v-list-item-content>
                                <v-list-item-title class="text-xs-left">
                                    Log out
                                </v-list-item-title>
                            </v-list-item-content>
                        </v-list-item>
                    </v-list>
                </v-menu>
            </div>
        </div>
    </v-app-bar>
</div>
</template>

<script>
import api from "../../common/api";
import permissions from "../../common/permissions";

export default {
    props: ['loginAs'],
    data() {
        return {
            showToolbar: true
        };
    },
    async mounted() {
        const self = this;
    },
    methods: {
        joinNames: (value) => {
            let names = [];
            for (let i in value) {
                names.push(value[i].name);
            }

            return names.join(", ");
        },

        async clickLogout() {
            const self = this;

            await api.logout()

            self.$store.commit("setSession", []);

            await self.$router.push("login");
        },

        routeCall(name, params) {
            this.$router.push({
                name: name,
                params: params
            });
        },

        async loginReturn() {
            const self = this;
            const returnId = self.$store.getters.getLoginAsReturnId ?? 0;
            const returnUrl = self.$store.getters.getLoginAsReturnUrl ?? '';

            if (!returnId) {
                return false;
            }

            const response = await api.loginAs(returnId);

            self.$store.commit('setSession', response.data);
            self.$store.commit('setLoginAs', false);
            // todo: how to delete values from store?
            self.$store.commit('setLoginAsReturnId', false);
            self.$store.commit('setLoginAsReturnUrl', false);
            self.$eventBus.$emit('authorized', response.data);

            self.redirectToPage(returnUrl);
        },

        redirectToPage(url) {
            const self = this;

            url = url ? url : 'dashboard';
            self.$router.push({ name: url });
        },
    },
    computed: {
        isAuthorized() {
            return permissions.isAuthorized();
        },

        showleftMenu: {
            get() {
                return this.$store.getters.leftMenu;
            },
            set(val) {
                this.$store.commit("toggleleftMenu", val);
            }
        },

        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },

        isBillingUser() {
            return permissions.hasRole(permissions.role_billing_user);
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        goToSupport() {
            this.$router.push({ name: 'customer-support' });
        }
    }
};
</script>

<style>
.support-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: blue;
    cursor: pointer;
    text-align: center;
}

.support-icon > i {
    color: white !important;
    font-size: 16px !important;
    line-height: 40px !important;
}

.support-icon > div {
    display: block;
    font-size: 10px;
}
</style>
