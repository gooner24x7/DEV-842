<template>
<div style="margin: 1em">
    <password-reset-form :resetRequired="resetRequired" />
</div>
</template>

<script>
import PasswordResetForm from "../components/PasswordResetForm";

export default {
    name: "PasswordReset",
    components: {
        PasswordResetForm
    },
    data() {
        return {
            resetRequired: false,
        }
    },
    mounted() {
        this.resetRequired = this.$route.params.resetRequired ?? false;
    },
    methods: {}
};
</script>

<style scoped>
</style>
