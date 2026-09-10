<template>
    <v-card>
        <v-card-title>
            <span class="headline">Enquiries > Purchase & Hire
                <span v-if="value.ref_archived_enquiry_id">(restored {{ value.ref_archived_enquiry_id }})</span> </span>
        </v-card-title>

        <v-card-text>
            <v-container>
                <v-row>
                    <v-col cols="12" md="6">
                        <v-autocomplete
                            v-model="form.project_id"
                            :items="projects"
                            :search-input.sync="searchProject"
                            item-text="name"
                            item-value="id"
                            label="Project"
                        ></v-autocomplete>
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-autocomplete
                            v-model="form.works_package_id"
                            :items="worksPackages"
                            :search-input.sync="searchWorksPackage"
                            item-text="name"
                            item-value="id"
                            label="Works Package"
                        ></v-autocomplete>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col cols="12">
                        <v-autocomplete
                            item-text="name"
                            item-value="id"
                            :items="productItems"
                            v-model="form.product_id"
                            :error-messages="errors.product_id"
                            label="Product"
                            :rules="[() => !!form.product_id || 'This field is required']"
                        ></v-autocomplete>
                    </v-col>
                </v-row>

                <v-row>
                    <v-col cols="12">
                        <ManufacturerProductSelector
                            v-if="form.manufacturer_product_selected && foundManufacturer"
                            :merchantId="foundManufacturer.id"
                            v-model="form.manufacturer_product_selected"
                        />
                    </v-col>
                </v-row>

                <v-row>
                    <v-col cols="12" md="6">
                        <v-autocomplete
                            :items="['live', 'tender']"
                            v-model="form.type"
                            label="Live/Tender"
                            :rules="[() => !!form.type || 'This field is required']"
                            :error-messages="errors.type"
                        ></v-autocomplete>
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-autocomplete
                            v-model="form.scope"
                            :items="scopeOptions"
                            label="Open/Closed"
                        ></v-autocomplete>
                    </v-col>
                </v-row>

                <v-row>
                    <v-col cols=" 12" md="6">
                        <v-text-field
                            v-model="form.postcode"
                            :error-messages="errors.postcode"
                            label="Postcode"
                            required
                            placeholder="M60 1NW"
                            :rules="[
                                () => !!form.postcode || 'This field is required',
                                (v) => /^([A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}|GIR ?0A{2})$/i.test(v) || 'Must be a vaild UK postcode',
                            ]"
                        ></v-text-field>
                    </v-col>
                    <v-col cols="12" md="6">
                        <div v-if="form.product_type === 1">
                            <v-text-field
                                v-model="form.days"
                                :error-messages="errors.days"
                                type="number"
                                label="Days"
                                :rules="[() => !!form.days || 'This field is required']"
                            ></v-text-field>
                        </div>
                        <div v-else-if="form.product_type === 2">
                            <v-text-field
                                v-model="form.days"
                                :error-messages="errors.days"
                                type="text"
                                label="Due Date"
                                :rules="[() => !!form.days || 'This field is required']"
                            ></v-text-field>
                        </div>
                    </v-col>
                </v-row>

                <v-textarea
                    v-model="form.comment"
                    clearable
                    clear-icon="mdi-close-circle"
                    label="Comments box"
                ></v-textarea>

<!--                <v-checkbox-->
<!--                    v-model="form.send_to_national"-->
<!--                    label="Send to National"-->
<!--                ></v-checkbox>-->

                <template v-if="form.send_to_national">
                    <nationals-input v-model="value.nationals" :nationalsOptions="nationalsOptions"></nationals-input>
                </template>
            </v-container>
        </v-card-text>

        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="blue darken-1" text @click="save($event, 1)">Save Draft</v-btn>
            <v-btn color="blue darken-1" text @click="cancel">Cancel</v-btn>
            <v-btn color="blue darken-1" text @click="save($event, 0)">Publish</v-btn>
        </v-card-actions>

        <v-col cols="12" v-if="successCreated">
            <v-alert type="success">
                Please check your email for your log in details (check spam folder if required)
            </v-alert>
        </v-col>
    </v-card>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";
import ManufacturerProductSelector from "./Dialog/ManufacturerProductSelector";
import NationalsInput from "./NationalsInput.vue";

export default {
    components: {NationalsInput, ManufacturerProductSelector},
    props: ["value", "title", "projects", "worksPackages"],
    data() {
        return {
            productItems: [],
            successCreated: false,
            manufacturerProductItems: [],
            nationalsOptions: [],
            foundManufacturer: '',
            editId: null,
            searchProject: '',
            searchWorksPackage: '',
            form: {
                postcode: "",
                comment: "",
                product_id: 0,
                project_id: 0,
                works_package_id: 0,
                days: "",
                type: "",
                manufacturer_product_selected: '',
                send_to_national: false,
                nationals: [],
                product_type: null,
                status: null,
                scope: null,
            },
            errors: {
                postcode: [],
                comment: [],
                product_id: [],
                project_id: [],
                works_package_id: [],
                days: [],
                type: [],
                manufacturer_product_selected: [],
            },
            scopeOptions: [
                {text: 'open', value: 0},
                {text: 'closed', value: 1},
            ]
        };
    },

    watch: {
        value: function (o, n) {
            this.init();
        },
    },

    async mounted() {
        const self = this;

        self.init();

        let resp = await api.productOptions();
        self.productItems = resp.data;
        this.form.product_id = this.value.product_id;

        if (Object.keys(this.value.manufacturer_product_selected).length > 0) {
            const foundResponse = await api.getManufacturerByManufacturerProductId(Object.keys(this.value.manufacturer_product_selected)[0]);
            this.foundManufacturer = foundResponse.data;
        }

        resp = await api.nationalsOptions();
        self.nationalsOptions = resp.data;
    },

    methods: {
        init() {
            this.form.product_id = this.value.product_id;
            this.form.postcode = this.value.postcode;
            this.form.comment = this.value.comment;
            this.form.project_id = this.value.project_id;
            this.form.works_package_id = this.value.works_package_id;
            this.form.days = this.value.days;
            this.form.type = this.value.type;
            this.editId = this.value.id;
            this.form.product_id = this.value.product_id;
            this.form.manufacturer_product_selected = this.value.manufacturer_product_selected;
            this.form.send_to_national = this.value.send_to_national;
            this.form.nationals = this.value.nationals;
            this.form.product_type = this.value.product_type;
            this.form.status = this.value.status;
            this.form.scope = this.value.scope;

            for (let key in this.form.manufacturer_product_selected) {
                this.form.manufacturer_product_selected[key]['qty'] = parseInt(this.form.manufacturer_product_selected[key]['qty'])
            }
        },

        cancel() {
            this.$emit("cancel", this.value);
        },

        isUser() {
            return permissions.hasRole(permissions.role_user);
        },

        async save(event, status = 0) {
            const self = this;

            event.preventDefault();

            this.successCreated = false;

            if (self.isUser() && status === 0) {
                self.$store.commit("showRegFormLoader");
            }

            self.form.postcode = self.form.postcode.toUpperCase();
            self.form.status = status;

            try {
                await api.storeQuestion(this.form, this.editId);

                self.successCreated = true;
                self.$emit("saved", {});

                setTimeout(function () {
                    this.successCreated = false;
                }, 5000);

            } catch (error) {
                self.$store.commit("hideRegFormLoader");

                let data = error.response.data;
                if (typeof data.errors != "undefined") {
                    for (let id in data.errors) {
                        self.errors.id = data.errors?.id;
                    }
                }
            }
        },
    },
};
</script>

<style>
</style>
