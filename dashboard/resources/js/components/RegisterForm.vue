<template>
  <v-col cols="12">
    <v-card class="elevation-1">
      <v-toolbar flat>
        <v-toolbar-title>Start Your Enquiry</v-toolbar-title>
        <v-spacer />
        <v-tooltip right></v-tooltip>
      </v-toolbar>
      <v-form @submit="doReg">
        <v-card-text>
          <v-row>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.first_name"
                :error-messages="errors.first_name"
                label="First Name"
                required
                :rules="[() => !!form.first_name || 'This field is required']"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.last_name"
                :error-messages="errors.last_name"
                label="Last Name"
                required
                :rules="[() => !!form.last_name || 'This field is required']"
              ></v-text-field>
            </v-col>
          </v-row>
          <v-row>
            <v-col cols="12" md="12">
              <v-text-field
                v-model="form.username"
                :error-messages="errors.username"
                label="Username"
                required
                :rules="[() => !!form.username || 'This field is required']"
              ></v-text-field>
            </v-col>
          </v-row>
          <v-row>
            <v-col cols=" 12" md="6">
              <v-text-field
                type="email"
                v-model="form.email"
                :error-messages="errors.email"
                label="Email Address"
                required
                :rules="[
                  () => !!form.email || 'This field is required',
                  (v) =>
                    !v ||
                    /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,10})+$/.test(v) ||
                    'E-mail must be valid',
                ]"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.postcode"
                :error-messages="errors.postcode"
                label="Postcode"
                placeholder="M60 1NW"
                required
                :rules="[
                  () => !!form.postcode || 'This field is required',
                  (v) =>
                    /^([A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}|GIR ?0A{2})$/i.test(v) ||
                    'Must be a vaild UK postcode',
                ]"
              ></v-text-field>
            </v-col>
          </v-row>

          <v-row>
            <v-col cols=" 12" md="6">
              <v-autocomplete
                item-text="name"
                item-value="id"
                :items="productItems"
                v-model="form.product_id"
                :error-messages="errors.product_id"
                label="Product"
                :rules="[() => !!form.product_id || 'This field is required']"
              >
              </v-autocomplete>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.days"
                :error-messages="errors.days"
                type="number"
                label="Days"
                :rules="[() => !!form.days || 'This field is required']"
              ></v-text-field>
            </v-col>
          </v-row>

          <v-textarea
            v-model="form.comment"
            clearable
            clear-icon="mdi-close-circle"
            label="Comments box"
            :rules="[() => !!form.comment || 'This field is required']"
            :error-messages="errors.comment"
          ></v-textarea>

          <v-spacer />
        </v-card-text>
        <v-card-actions>
          <v-btn type="submit" color="primary">Submit</v-btn>
        </v-card-actions>

        <v-col cols="12" v-if="successCreated">
          <v-alert type="success"
            >Please check your email for your log in details (check spam folder
            if required)</v-alert
          >
        </v-col>
      </v-form>
    </v-card>
  </v-col>
</template>

<script>
import api from "../common/api.js";

export default {
  data: () => ({
    productItems: [],
    successCreated: false,
    form: {
      username: "",
      first_name: "",
      last_name: "",
      postcode: "",
      comment: "",
      email: "",
      product_id: "",
      days: "",
    },
    errors: {
      username: [],
      first_name: [],
      last_name: [],
      postcode: [],
      comment: [],
      email: [],
      product_id: [],
      days: [],
    },
  }),
  mounted() {
    const self = this;

    api.productOptions().then((resp) => {
      self.productItems = resp.data;
    });
  },
  methods: {
    doReg(event) {
      const self = this;

      event.preventDefault();

      this.successCreated = false;

      self.$store.commit("showRegFormLoader");

      for (let id in self.errors) {
        self.errors[id] = [];
      }

      self.form.postcode = self.form.postcode.toUpperCase();

      api
        .register(this.form)
        .then((response) => {
          self.successCreated = true;
        })
        .catch((error) => {
          self.$store.commit("hideRegFormLoader");

          let data = error.response.data;
          if (typeof data.errors != "undefined") {
            for (let id in data.errors) {
              self.errors[id] = data.errors[id];
            }
          }
        });
    },
  },
};
</script>

<style scoped>
</style>
