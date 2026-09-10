<template>
    <v-card class="elevation-1">
        <v-card-text>
            <div>{{ value.text }}</div>

            <v-textarea
                :rules="[() => !!value.text_answer || 'This field is required']"
                :name="'questions[' + value.id + '].text_answer'" v-if="value.type === 'text'" v-model="value.text_answer" required></v-textarea>

            <v-radio-group
                :rules="[() => !!value.isYes || 'This field is required']"
                :name="'questions[' + value.id + '].isYes'"  row v-if="value.type === 'yes_no'" v-model="value.isYes" required>
                <v-radio label="Yes" value="Y"></v-radio>
                <v-radio label="No" value="N"></v-radio>
            </v-radio-group>
        </v-card-text>
    </v-card>
</template>

<script>
export default {
    name: "question",
    props: ["value"],
}
</script>

<style scoped>

</style>
