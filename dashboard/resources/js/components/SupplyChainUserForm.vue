<template>
    <v-card>
        <v-card-title>
            <span class="headline">{{ getFormTitle() }}</span>
        </v-card-title>

        <v-card-text>
            <v-container>
                <v-row>
                    <v-col cols="6">
                        <v-text-field
                            v-model="form.business_name"
                            :error="errors.business_name"
                            label="Business Name"
                        ></v-text-field>
                    </v-col>
                    <v-col cols="6">
                        <v-autocomplete
                            v-model="form.role_id"
                            :error="errors.role_id"
                            :items="roles"
                            item-text="name"
                            item-value="id"
                            label="User Type"
                        ></v-autocomplete>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col cols="6">
                        <v-text-field
                            v-model="form.first_name"
                            :error="errors.first_name"
                            label="First Name"
                        ></v-text-field>
                    </v-col>
                    <v-col cols="6">
                        <v-text-field
                            v-model="form.last_name"
                            :error="errors.last_name"
                            label="Last Name"
                        ></v-text-field>
                    </v-col>
                </v-row>

                <v-row>
                    <v-col cols="6">
                        <v-text-field
                            v-model="form.email"
                            :error="errors.email"
                            label="Email"
                        ></v-text-field>
                    </v-col>
                    <v-col cols="6">
                        <v-text-field
                            v-model="form.phone"
                            :error="errors.phone"
                            label="Telephone"
                        ></v-text-field>
                    </v-col>
                </v-row>

                <v-row>
                    <v-col md="6">
                        <v-text-field
                            v-model="form.postcode"
                            :error="errors.postcode"
                            label="Postcode"
                            placeholder="M60 1NW"
                        ></v-text-field>
                    </v-col>
                    <v-col md="6">
                        <v-text-field
                            id="city"
                            v-model="form.city"
                            :error="errors.city"
                            label="Town or City"
                            name="city"
                        />
                    </v-col>
                </v-row>

                <v-row>
                    <v-col md="6">
                        <v-text-field
                            id="addr_line_1"
                            v-model="form.addr_line_1"
                            :error="errors.addr_line_1"
                            label="Address line 1"
                            name="addr_line_1"
                        />
                    </v-col>
                    <v-col md="6">
                        <v-text-field
                            id="addr_line_2"
                            v-model="form.addr_line_2"
                            label="Address line 2"
                            name="addr_line_2"
                        />
                    </v-col>
                </v-row>
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
    name: "SupplyChainUserForm",
    components: {},
    props: ["value"],
    data() {
        return {
            isLoading: false,
            roles: [],
            editId: null,
            form: {
                role_id: null,
                first_name: "",
                last_name: "",
                business_name: "",
                email: "",
                phone: "",
                addr_line_1: "",
                addr_line_2: "",
                city: "",
                country: "",
                postcode: "",
            },
            errors: {
                role_id: false,
                first_name: false,
                last_name: false,
                business_name: false,
                email: false,
                phone: false,
                addr_line_1: false,
                city: false,
                postcode: false,
            },
        };
    },
    async mounted() {
        const self = this;

        await self.loadRoles();
        self.init();
    },
    computed: {

    },
    watch: {

    },
    methods: {
        init() {
            const self = this;

            self.editId = self.value.id;
            self.form.role_id = self.value.role_id;
            self.form.first_name = self.value.first_name;
            self.form.last_name = self.value.last_name;
            self.form.business_name = self.value.business_name;
            self.form.email = self.value.email;
            self.form.phone = self.value.phone;
            self.form.addr_line_1 = self.value.addr_line_1;
            self.form.addr_line_2 = self.value.addr_line_2;
            self.form.city = self.value.city;
            self.form.country = self.value.country;
            self.form.postcode = self.value.postcode;
        },

        async loadRoles() {
            const self = this;

            const response = await api.loadRoles({paginate: 0});
            self.roles = response.data.data;

            console.log(self.roles);

            let roleIds = [2, 3, 4, 14, 15];

            self.roles = self.roles.filter(function(item) {
                return roleIds.includes(item.id);
            });

            console.log(self.roles);
        },

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

            api.storeSupplyChainUser(self.form, self.editId).then(async (response) => {
                self.$store.commit("showSnackbar", {
                    message: "Success!",
                    color: "success",
                });

                this.$emit("saved", this.value);
            }).catch((error) => {
                console.log(error);
                self.$store.commit("showSnackbar", {
                    message: error.response.data,
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
            this.$emit("cancel", this.value);
        },

        getFormTitle() {
            const self = this;

            if (self.editId) {
                return 'Edit User';
            }

            return 'Add User';
        }
    },
};
</script>

<style></style>
