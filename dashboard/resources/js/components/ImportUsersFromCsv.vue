<template>
    <v-card>
        <v-card-title>
            <span class="headline">Import Users From CSV for Billing User Id {{billingUserId}}</span>
        </v-card-title>

        <v-card-text>
            <v-file-input cols="10" type="file"
                          v-on:change="(f) => { attachment = f; }" placeholder="Upload CSV"/>
        </v-card-text>

        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="blue darken-1" text @click="cancel">Cancel</v-btn>
            <v-btn color="blue darken-1" text @click="save">Save</v-btn>
        </v-card-actions>
    </v-card>
</template>

<script>
import api from "../common/api";

export default {
    name: "ImportUsersFromCsv",
    props: ["billingUserId"],

    data() {
        return {
            attachment: null,
        }
    },

    methods: {
        cancel() {
            this.$emit("cancel", {});
        },
        save() {
            const self = this;
            if (self.attachment == null) {
                return
            }

            let formData = new FormData();
            formData.append('attachment', self.attachment);

            api
                .importUsersFromCsv(self.billingUserId, formData)
                .then((response) => {
                    self.$emit("saved", {});
                })
                .catch((error) => {
                    alert(error);
                });
        }
    }
}
</script>

<style scoped>

</style>
