<template>
    <div style="margin: 1em">
<!--        <h2>Tickets</h2>-->

        <create-ticket></create-ticket>
    </div>
</template>

<script>
import CreateTicket from "../components/CreateTicketForm";
export default {
    name: "Tickets",
    components: {CreateTicket}
}
</script>

<style scoped>

</style>
