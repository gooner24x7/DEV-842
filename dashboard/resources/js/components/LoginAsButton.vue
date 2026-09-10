<template>
    <v-btn color="primary" small @click="loginAs(userId)">Login As</v-btn>
</template>

<script>
import api from "../common/api.js";

export default {
    props: ['userId'],

    data: () => ({
        // userId: 0,
        // returnUrl: '',
    }),

    methods: {
        async loginAs(userId) {
            const self = this;
            //const returnUrl = window.location.pathname;
            const returnUrl = location.pathname.split('/')[1];
            const currentUserId = self.$store.getters.getSession.user.id ?? 0;
            const response = await api.loginAs(userId);

            self.$store.commit('setSession', response.data);
            self.$store.commit('setLoginAs', true);
            self.$store.commit('setLoginAsReturnId', currentUserId);
            self.$store.commit('setLoginAsReturnUrl', returnUrl);
            self.$eventBus.$emit('authorized', response.data);

            self.redirectToFirstPage();
        },

        redirectToFirstPage() {
            const self = this;
            self.$router.push({name: 'dashboard'});
        }
    }
};
</script>

<style scoped>
</style>
