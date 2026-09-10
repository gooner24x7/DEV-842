<template>
    <v-card>
        <v-card-title>
            <span class="headline">Import Users</span>
        </v-card-title>

        <v-card-text>
            <v-container>
                <v-btn href="supply_chain_users_template.csv" download>Download Template</v-btn>
                <v-file-input placeholder="Upload CSV" type="file" v-on:change="(file) => { importedFile = file; }"/>
            </v-container>
        </v-card-text>

        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="blue darken-1" text @click="cancel()">Cancel</v-btn>
            <v-btn color="blue darken-1" text @click="save()">Save</v-btn>
        </v-card-actions>
    </v-card>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";

export default {
    name: "SupplyChainImportUsers",
    components: {},
    props: [],
    data() {
        return {
            importedFile: null
        };
    },
    async mounted() {
        const self = this;

    },
    computed: {

    },
    watch: {

    },
    methods: {
        buildFormData(formData, data, parentKey) {
            const self = this;
            if (data && typeof data === 'object' && !(data instanceof Date) && !(data instanceof File)) {
                Object.keys(data).forEach(key => {
                    self.buildFormData(formData, data[key], parentKey ? `${parentKey}[${key}]` : key);
                });
            } else {
                const value = data == null ? '' : data;

                formData.append(parentKey, value);
            }
        },

        save() {
            const self = this;
            self.loader = true;

            let formData = new FormData();
            formData.append('file', self.importedFile);

            api.importSupplyChainUsers(formData).then(async (response) => {
                let message = "";

                if (typeof response.data === 'string' || response.data instanceof String) {
                    message = response.data;
                }

                message = message.replace(/(?:\r\n|\r|\n)/g, '<br>');

                self.$store.commit("showSnackbar", {
                    message: message,
                    color: "success",
                });

                this.$emit("saved", this.value);
            }).catch((error) => {
                console.log(error);

                let message = "An error occurred";

                if (typeof error.response.data === 'string' || error.response.data instanceof String) {
                    message = error.response.data;
                }

                self.$store.commit("showSnackbar", {
                    message: message,
                    color: "error",
                });
            });

            self.loader = false;
        },

        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },

        isClient() {
            return permissions.hasRole(permissions.role_client);
        },

        cancel() {
            this.$emit("cancel");
        },
    },
};
</script>

<style></style>
