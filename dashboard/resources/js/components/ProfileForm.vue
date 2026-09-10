<template>
    <v-row>
        <v-col cols="12">
            <v-card class="elevation-1">
                <v-form @submit="saveProfile" ref="profile_form" enctype="multipart/form-data">
                    <v-card-text>
                        <div v-if="errors.length">
                            <v-alert dense outlined type="error">
                                <div v-bind:key="index" v-for="(error, index) in errors">
                                    {{ error }}
                                </div>
                            </v-alert>
                        </div>

                        <v-text-field
                            label="Username"
                            v-model="username"
                            name="username"
                            prepend-icon="mdi-user"
                            type="text"
                            :readonly="true"
                        />

                        <v-row>
                            <v-col md="6">
                                <v-text-field
                                    label="Email"
                                    v-model="email"
                                    name="email"
                                    prepend-icon="mdi-email"
                                    type="text"
                                />
                            </v-col>
                            <v-col md="6">
                                <v-text-field
                                    id="phone"
                                    label="Telephone"
                                    v-model="phone"
                                    name="phone"
                                    prepend-icon="mdi-phone"
                                />
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col md="6">
                                <v-text-field
                                    label="First Name"
                                    v-model="first_name"
                                    name="first_name"
                                    prepend-icon="mdi-account"
                                    type="text"
                                />
                            </v-col>
                            <v-col md="6">
                                <v-text-field
                                    label="Last Name"
                                    v-model="last_name"
                                    name="last_name"
                                    type="text"
                                />
                            </v-col>
                        </v-row>

                        <v-text-field
                            autocomplete="off"
                            id="password"
                            label="Password"
                            v-model="password"
                            name="password"
                            prepend-icon="mdi-lock"
                            type="password"
                            :rules="passwordRule()"
                        />

                        <v-col cols="12" v-if="isCompany()">
                            <v-autocomplete
                                v-model="product_ids"
                                :items="productItems"
                                outlined
                                dense
                                chips
                                small-chips
                                label="Products"
                                item-text="name"
                                item-value="id"
                                multiple
                            ></v-autocomplete>
                        </v-col>

                        <hr/>

                        <v-row>
                            <v-col md="6">
                                <v-text-field
                                    v-model="postcode"
                                    :messages="(postcodeDisabled()) ? 'if you need to change contact customer services' : ''"
                                    :disabled="postcodeDisabled()"
                                    label="Postcode"
                                    name="postcode"
                                    type="text"
                                    prepend-icon="mdi-email"
                                    placeholder="M60 1NW"
                                    :rules="postcodeRule()"
                                ></v-text-field>
                            </v-col>
                            <v-col md="6">
                                <v-text-field
                                    id="city"
                                    label="Town or City"
                                    v-model="city"
                                    name="city"
                                    prepend-icon="mdi-city"
                                />
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col md="6">
                                <v-text-field
                                    id="addr_line_1"
                                    label="Address line 1"
                                    v-model="addr_line_1"
                                    name="addr_line_1"
                                    prepend-icon="mdi-map-marker"
                                />
                            </v-col>
                            <v-col md="6">
                                <v-text-field
                                    id="addr_line_2"
                                    label="Address line 2"
                                    v-model="addr_line_2"
                                    name="addr_line_2"
                                    prepend-icon="mdi-map-marker"
                                />
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col md="6">
                                <v-text-field
                                    id="locations"
                                    label="Locations"
                                    v-model="locations"
                                    name="locations"
                                    prepend-icon="mdi-map-marker"
                                    type="number"
                                />
                            </v-col>
                            <v-col md="6">
                                <v-text-field
                                    id="head_office_address"
                                    label="Head Office"
                                    v-model="head_office_address"
                                    name="head_office_address"
                                    prepend-icon="mdi-map-marker"
                                />
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col md="6">
                                <v-text-field
                                    id="customer_service"
                                    label="Customer Service"
                                    v-model="customer_service"
                                    name="customer_service"
                                />
                            </v-col>
                            <v-col md="6">
                                <v-text-field
                                    id="logo_url"
                                    label="Logo Url"
                                    v-model="logo_url"
                                    name="logo_url"
                                />
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col>
                                <v-textarea id="description" label="Description" v-model="description"
                                            name="description" :rules="[v => (v && v.length <= 500) || 'Description must be 500 characters or less']"></v-textarea>
                            </v-col>
                        </v-row>

                        <v-row v-if="isWithPreferredSuppliersUser()">
                            <v-col md="12">
                                <v-btn v-if="preferred_suppliers_file"
                                       :href="getPreferredSuppliersFile()" target="_blank">Download File
                                </v-btn>

                                <v-file-input
                                    name="preferred_suppliers_file"
                                    id="preferred_suppliers_file"
                                    accept=".xls,.xlsx,.csv"
                                    label="Upload preferred suppliers file"
                                ></v-file-input>
                            </v-col>
                        </v-row>

                        <v-spacer/>
                    </v-card-text>
                    <v-card-actions>
                        <v-btn type="submit" color="primary">Save</v-btn>
                    </v-card-actions>
                </v-form>
            </v-card>

            <v-card class="elevation-1 mt-2">
                <v-card-text>
                    <div class="expanded-user-info">
                        <img :src="logo_url" class="float-right" height="150">
                        <h4>{{ first_name }}</h4>
                        <div>
                            <b>Locations:</b> {{ locations }}<br>
                            <b>Head Office:</b> {{ head_office_address }}<br>
                            <b>Phone Number:</b> {{ phone }}<br>
                            <b>Customer Service:</b> {{ customer_service }}<br>
                            <b>Supplier Description:</b>
                            <p>{{ description }}</p>
                        </div>
                    </div>
                </v-card-text>
            </v-card>
        </v-col>
    </v-row>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";

export default {
    data: () => ({
        errors: [],
        username: null,
        email: null,
        first_name: null,
        last_name: null,
        password: null,
        postcode: null,
        phone: null,
        country: null,
        city: null,
        addr_line_1: null,
        addr_line_2: null,
        product_ids: [],
        productItems: [],
        preferred_suppliers_file: null,
        description: null,
        logo_url: null,
        customer_service: null,
        head_office_address: null,
        locations: 0
    }),
    mounted() {
        const self = this;

        api.me().then((resp) => {
            self.$store.commit('setSession', {
                ...self.$store.getters.getSession,
                user: resp.data.user
            });

            setTimeout(()=> {
                let user = self.$store.getters.getSession.user
                self.preferred_suppliers_file = user.preferred_suppliers_file;
                self.username = user.username;
                self.email = user.email;
                self.first_name = user.first_name;
                self.last_name = user.last_name;
                self.phone = user.phone;
                self.country = user.country;
                self.city = user.city;
                self.addr_line_1 = user.addr_line_1;
                self.addr_line_2 = user.addr_line_2;
                self.postcode = user.postcode;
                self.description = user.description;
                self.logo_url = user.logo_url;
                self.customer_service = user.customer_service;
                self.head_office_address = user.head_office_address;
                self.locations = user.locations;
                self.product_ids = self.$store.getters.getSession.user.product_ids;

                api.productOptions().then((resp) => {
                    self.productItems = resp.data;
                });
            }, 500);
        }).catch((err) => {
        });
    },
    methods: {
        postcodeDisabled() {
            return true;
        },

        postcodeRule() {
            return [
                (v) => !!v || 'This field is required',
                (v) =>
                    /^([A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}|GIR ?0A{2})$/i.test(v) ||
                    'Must be a vaild UK postcode',
            ];
        },

        passwordRule() {
            // password is optional; only validate when a value is entered
            return [
                (v) => !v || v.length >= 12 || 'Password must be at least 12 characters',
                (v) => !v || /[A-Z]/.test(v) || 'Password must contain at least 1 uppercase letter',
                (v) => !v || /[a-z]/.test(v) || 'Password must contain at least 1 lowercase letter',
                (v) => !v || /[0-9]/.test(v) || 'Password must contain at least 1 number',
                (v) => !v || /[^A-Za-z0-9]/.test(v) || 'Password must contain at least 1 special character',
            ];
        },

        isValidPassword(password) {
            return password.length >= 12
                && /[A-Z]/.test(password)
                && /[a-z]/.test(password)
                && /[0-9]/.test(password)
                && /[^A-Za-z0-9]/.test(password);
        },

        isWithPreferredSuppliersUser() {
            const self = this;

            if (permissions.can(permissions.manage_subscriptions)) {
                return true;
            }

            return self.isUser();
        },

        isUser() {
            return permissions.hasRole(permissions.role_user);
        },

        isCompany() {
            return permissions.hasRole(permissions.role_company);
        },

        isManufacturer() {
            return permissions.hasRole(permissions.role_manufacturer);
        },

        getPreferredSuppliersFile() {
            return api.baseUrl + '/downloadFile?file=' + encodeURIComponent(this.preferred_suppliers_file);
        },

        saveProfile(event) {
            const self = this;

            event.preventDefault();

            self.errors = [];

            if (!self.email) {
                self.errors.push("Email is required");
            }

            if (!self.first_name) {
                self.errors.push("First Name is required");
            }

            if (!self.last_name) {
                self.errors.push("Last Name is required");
            }

            if (!self.postcode) {
                self.errors.push("Postcode is required");
            }

            if (self.password && !self.isValidPassword(self.password)) {
                self.errors.push("Password must be at least 12 characters and include an uppercase letter, a lowercase letter, a number and a special character");
            }

            if (self.errors.length === 0) {
                self.postcode = self.postcode.toUpperCase();

                let formData = new FormData(event.target);

                //formData.append('first_name', self.first_name ?? '');
                //formData.append('last_name', self.last_name ?? '');
                formData.append('postcode', self.postcode ?? '');
                // formData.append('email', self.email ?? '');
                // formData.append('phone', self.phone ?? '');
                formData.append('country', self.country ?? '');
                // formData.append('city', self.city ?? '');
                // formData.append('addr_line_1', self.addr_line_1 ?? '');
                // formData.append('addr_line_2', self.addr_line_2);

                // formData.append('description', self.description || '');
                // formData.append('logo_url', self.logo_url || '');
                // formData.append('customer_service', self.customer_service || '');
                // formData.append('head_office_address', self.head_office_address || '');
                // formData.append('locations', self.locations || 0);

                if (self.password) {
                    formData.append('password', self.password ?? '');
                }

                for (let prodId in self.product_ids) {
                    formData.append('product_ids[]', self.product_ids[prodId]);
                }

                api
                    .saveProfile(formData)
                    .then(function (response) {
                        let session = self.$store.getters.getSession;

                        session.user.email = self.email ?? '';
                        session.user.first_name = self.first_name ?? '';
                        session.user.last_name = self.last_name ?? '';
                        session.user.postcode = self.postcode ?? '';
                        session.user.product_ids = self.product_ids ?? [];
                        session.user.phone = self.phone ?? '';
                        session.user.country = self.country ?? '';
                        session.user.city = self.city ?? '';
                        session.user.addr_line_1 = self.addr_line_1 ?? '';
                        session.user.addr_line_2 = self.addr_line_2 ?? '';
                        session.user.preferred_suppliers_file = response.data.preferred_suppliers_file ?? '';
                        session.user.description = response.data.description;
                        session.user.logo_url = response.data.logo_url;
                        session.user.customer_service = response.data.customer_service;
                        session.user.head_office_address = response.data.head_office_address;
                        session.user.locations = response.data.locations;

                        self.$store.commit("setSession", session);

                        self.$store.commit("showSnackbar", {
                            message: "Success!",
                            color: "success",
                        });
                    })
                    .catch(function (e) {
                        self.errors.push("Error. Please, try again later.");
                    });
            }
        },
    },
};
</script>

<style>
</style>
