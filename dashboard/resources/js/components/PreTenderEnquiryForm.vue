<template>
    <v-card>
        <v-card-title>Send Pre Tender Enquiry</v-card-title>
        <v-card-text>
            <v-container>
                <v-row>
                    <v-col>
                        <v-autocomplete
                            v-model="form.project_id"
                            :items="projects"
                            item-text="name"
                            item-value="id"
                            label="Project"
                        ></v-autocomplete>

                        <v-autocomplete
                            v-model="form.works_package_id"
                            :items="worksPackages"
                            item-text="name"
                            item-value="id"
                            label="Works Package"
                        ></v-autocomplete>

                        <v-autocomplete
                            v-model="form.product_id"
                            :items="products"
                            item-text="name"
                            item-value="id"
                            label="Product Category"
                        ></v-autocomplete>

                        <v-autocomplete
                            v-model="form.type"
                            :items="['live', 'tender', 'pre tender']"
                            label="Stage"
                        ></v-autocomplete>

                        <v-autocomplete
                            v-model="form.scope"
                            :items="scopeOptions"
                            label="Open/Closed"
                        ></v-autocomplete>

                        <v-textarea
                            v-model="form.comment"
                            label="Comment"
                        ></v-textarea>
                    </v-col>
                </v-row>
            </v-container>
        </v-card-text>
        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="blue darken-1" text @click="$emit('cancel')">Cancel</v-btn>
            <v-btn color="blue darken-1" text @click="createEnquiry()">Save</v-btn>
        </v-card-actions>
    </v-card>
</template>

<script>
import AttachmentsInput from "./AttachmentsInput";
import api from "../common/api";

export default {
    components: {AttachmentsInput},
    props: ["userIds", "projects", "worksPackages", "products"],
    data() {
        return {
            form: {
                user_ids: this.userIds,
                project_id: null,
                works_package_id: null,
                product_id: null,
                type: 'pre tender',
                scope: 1,
                comment: '',
            },
            statusOptions: [
                {text: 'published', value: 0},
                {text: 'draft', value: 1},
            ],
            scopeOptions: [
                {text: 'open', value: 0},
                {text: 'closed', value: 1},
            ]
        }
    },

    watch: {

    },

    mounted() {
        const self = this;
    },

    methods: {
        createEnquiry() {
            const self = this;

            api.createPreTenderEnquiry(self.form)
                .then(function (response) {

                    self.$store.commit("showSnackbar", {
                        message: "Enquiry created",
                        color: "success",
                    });

                    self.$emit("saved");
                })
                .catch(function (error) {
                    self.$store.commit("showSnackbar", {
                        message: "An error occurred",
                        color: "error",
                    });

                    console.log(error);
                });

        }
    }
}
</script>

<style scoped>

</style>
