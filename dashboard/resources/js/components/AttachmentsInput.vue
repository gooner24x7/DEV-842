<template>
    <div>
        <div v-for="(item, index) in items">
            <v-row>
                <v-file-input cols="10" type="file" :key="attachKey ?? 0"
                              v-on:change="(f) => { value[index] = f; }" placeholder="Document Upload"/>
                <v-btn v-if="index>0" @click="() => { value.splice(index, 1); items.splice(index, 1) }" icon
                       class="float-right">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-row>
        </div>
        <v-btn v-on:click="() => { items.push('') }">Add input</v-btn>
    </div>
</template>

<script>
export default {
    name: "AttachmentInput",
    data() {
        return {
            items: [
                '',
            ],
        }
    },
    props: ["value", "attachKey"],
}
</script>

<style scoped>

</style>
