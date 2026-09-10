<template>
    <div>
        <div v-for="(item, index) in value">
            <div class="nationals-input-group" :data-index="index">
                <hr>
                <div>
                    Nationals {{index + 1}}
                    <v-btn v-if="index>0" @click="() => { value.splice(index, 1) }" icon class="float-right">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </div>

                <v-text-field
                    v-model="value[index].email"
                    :rules="[() => !!value[index].email || 'This field is required']"
                    label="Email"
                ></v-text-field>

                <v-autocomplete
                    item-text="name"
                    item-value="id"
                    :items="nationalsOptions"
                    :rules="[() => !!value[index].supplier_id || 'This field is required']"
                    v-model="value[index].supplier_id"
                    label="Select National"
                ></v-autocomplete>

                <v-text-field
                    v-model="value[index].contact_name"
                    label="Contact Name (optional)"
                ></v-text-field>

                <v-text-field
                    v-model="value[index].account_number"
                    label="Account Number (optional)"
                ></v-text-field>
            </div>
        </div>
        <v-btn v-on:click="addInput()">Add input</v-btn>
    </div>
</template>

<script>
export default {
    name: "NationalsInput",
    props: ["value", "nationalsOptions"],
    data() {
        return {
            items: [],
        }
    },
    methods: {
        addInput() {
            const self = this;

            let newItem = {
                email: '',
                contact_name: '',
                supplier_id: 0
            };

            self.value.push(newItem);
        }
    }
}
</script>

<style scoped>

</style>
