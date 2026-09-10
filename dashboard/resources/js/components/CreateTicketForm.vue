<template>
    <div>
        <v-alert v-if="submitted"
                 type="success"
        >
            Successfully created a new ticket
        </v-alert>

        <v-form v-if="!submitted" @submit="submitCreateTicket" ref="create_task_form"  enctype="multipart/form-data">
            <v-card>
                <v-card-title>
                    <span class="headline">Create Ticket</span>
                </v-card-title>

                <v-card-text>
                    <v-container>
                        <v-row>
                            <v-col cols="12">
                                <v-text-field v-model="subject" name="subject" label="Subject" required></v-text-field>
                            </v-col>

                            <v-col cols="12">
                                <v-autocomplete
                                    v-model="category"
                                    :items="categories"
                                    outlined
                                    dense
                                    chips
                                    small-chips
                                    required
                                    label="Category"
                                    item-text="name"
                                    item-value="id"
                                ></v-autocomplete>

                                <input type="hidden" name="category" :value="category">
                            </v-col>

                            <v-col cols="12">
                                <v-text-field v-model="email" name="email" label="Email" required></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-textarea
                                    filled
                                    name="description"
                                    label="Description"
                                    v-model="description"
                                    required
                                ></v-textarea>
                            </v-col>
                        </v-row>
                    </v-container>
                </v-card-text>

                <v-card-actions>
                    <v-spacer class="d-none d-md-flex"></v-spacer>
                    <v-btn color="primary" type="submit">Save</v-btn>
                </v-card-actions>
            </v-card>

        </v-form>
    </div>
</template>

<script>
import api from "../common/api.js";

export default {
    name: "CreateTicket",
    data() {
        return {
            categories: [],
            submitted: false,
            subject: '',
            email: '',
            description: '',
            category: '',
        }
    },
    mounted() {
        const self = this;
        api.ticketCategoriesOptions().then((resp) => {
            self.categories = resp.data;
        });
    },
    methods: {
        submitCreateTicket(event) {
            event.preventDefault();

            const self = this;

            if (self.subject === '' || self.subject === '' || self.email === '' || self.description === '' || self.category === '') {
                return;
            }

            let formData = new FormData(event.target);

            api.saveTicket(formData).then(function (response) {
                self.submitted = true;
            });
        }
    }
}
</script>

<style scoped>

</style>
