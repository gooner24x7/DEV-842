<template>
<div style="margin: 1em">
    <login-form :message="message"/>
</div>
</template>

<script>
import LoginForm from "../components/LoginForm";

export default {
    name: "Login",
    components: {
        LoginForm
    },
    data() {
        return {
            message: null
        }
    },
    mounted() {
        this.message = this.$route.params.message ?? null;
    }
};
</script>

<style scoped>
</style>
