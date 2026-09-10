<template>
    <v-card
        elevation="2"
    >
        <v-container>
            <v-btn v-if="index>0" class="float-right" icon @click="removeMe()">
                <v-icon>mdi-close</v-icon>
            </v-btn>

            <v-row>
                <v-col cols="12">
                    <AttachmentsInput
                        v-model="value.attachment"
                    ></AttachmentsInput>
                </v-col>
            </v-row>

            <v-row v-if="showProducts">
                <v-col cols="12">
                    <v-autocomplete
                        v-model="value.product_id"
                        :error-messages="errors.product_id"
                        :items="productItems"
                        :rules="[() => !!value.product_id || 'This field is required']"
                        item-text="name"
                        item-value="id"
                        label="Product"
                    >
                    </v-autocomplete>
                </v-col>
            </v-row>

            <v-row v-if="foundManufacturer?.first_name">
                <v-col cols="12">
                    <v-btn v-on:click="selectManufacturerProduct = !selectManufacturerProduct">Use our preferred
                        "{{ foundManufacturer?.first_name }}"
                    </v-btn>

                    <ManufacturerProductSelector
                        v-if="selectManufacturerProduct"
                        v-model="value.manufacturer_product_selected"
                        :merchantId="foundManufacturer.id"
                    />
                </v-col>
            </v-row>

            <v-row>
                <v-col cols="12" md="6">
                    <input :value="defaultPostcode" type="hidden"/>
                    <v-text-field
                        v-model="value.postcode"
                        :error-messages="errors.postcode"
                        :rules="[
                () => !!value.postcode || 'This field is required',
                (v) =>
                  /^([A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}|GIR ?0A{2})$/i.test(v) ||
                  'Must be a vaild UK postcode',
              ]"
                        label="Postcode"
                        placeholder="M60 1NW"
                        required
                    ></v-text-field>
                </v-col>
                <v-col cols="12" md="6">
                    <v-menu
                        v-model="menu1"
                        :close-on-content-click="false"
                        max-width="290"
                    >
                        <template v-slot:activator="{ on, attrs }">
                            <v-text-field
                                v-model="value.days"
                                :error-messages="errors.days"
                                :label="daysLabel()"
                                :rules="[() => !!value.days || 'This field is required']"
                                :type="(type === 1) ? 'number' : 'text'"
                                v-bind="attrs"
                                v-on="on"
                            ></v-text-field>
                        </template>
                        <v-date-picker
                            v-if="(type !== 1)"
                            v-model="selectedDate"
                            no-title
                            @change="menu1 = false"
                        ></v-date-picker>
                    </v-menu>
                </v-col>
            </v-row>

            <v-row>
                <v-col cols="12" md="6">
                    <v-autocomplete
                        v-model="value.type"
                        :items="['live', 'tender']"
                        label="Live/Tender"
                    ></v-autocomplete>
                </v-col>
                <v-col cols="12" md="6">
                    <v-autocomplete
                        v-model="value.scope"
                        :items="scopeOptions"
                        label="Open/Closed"
                    ></v-autocomplete>
                </v-col>
            </v-row>

            <v-textarea
                v-model="value.comment"
                clear-icon="mdi-close-circle"
                clearable
                label="Comments box"
                placeholder="Product information, type of task, delivery or collection, terms of agreement etc"
                rows="2"
            ></v-textarea>

<!--            <v-checkbox-->
<!--                v-model="value.send_to_national"-->
<!--                label="Send to National"-->
<!--            ></v-checkbox>-->

            <template v-if="value.send_to_national">
                <nationals-input v-model="value.nationals" :nationalsOptions="nationalsOptions"></nationals-input>
            </template>
        </v-container>
    </v-card>
</template>

<script>
import api from "../common/api";
import AttachmentsInput from "./AttachmentsInput";
import moment from 'moment';
import ManufacturerProductSelector from "./Dialog/ManufacturerProductSelector";
import NationalsInput from "./NationalsInput.vue";

export default {
    components: {AttachmentsInput, ManufacturerProductSelector, NationalsInput},
    props: ["productItems", "nationalsOptions", "value", "index", "type", 'defaultPostcode', 'showProducts'],
    data() {
        return {
            foundManufacturer: '',
            selectManufacturerProduct: false,
            selectedDate: '',
            menu1: false,
            attachment: [],
            errors: {
                postcode: [],
                comment: [],
                product_id: [],
                days: [],
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
        defaultPostcode: function (n, o) {
            this.value.postcode = n
        },

        selectedDate: function (n, o) {
            this.value.days = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        },

        'value.product_id': async function (n, o) {
            this.foundManufacturer = undefined;
            const response = await api.manufacturerUsersOptions('', [this.value.product_id])
            const manufacturer = response?.data?.[0] ?? ''

            if (manufacturer) {
                const productResponse = await api.getManufacturerProductsOptions(manufacturer.id);
                const items = productResponse?.data?.data ?? [];
                if (items.length > 0 && manufacturer.is_paid) {
                    this.foundManufacturer = manufacturer
                }
            }
        },
    },

    mounted() {
        const self = this;
    },

    methods: {
        daysLabel() {
            return (this.type === 1) ? 'Number Of Hire Days' : 'Estimated Delivery Date'
        },
        removeMe() {
            this.$emit("remove", this.index);
        }
    }
}
</script>

<style scoped>

</style>
