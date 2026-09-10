<template>
    <div>
        <v-card>
            <v-card-title>
                <span class="headline">{{ enquiry ? 'Edit Enquiry' : 'New Enquiry' }}</span>
            </v-card-title>

            <v-card-text>
                <v-container>
                    <v-row>
                        <v-col cols="6">
                            <v-autocomplete
                                v-model="form.project_id"
                                :items="projectOptions"
                                clear-icon="mdi-close-circle"
                                item-text="name"
                                item-value="id"
                                label="Project"
                                hide-details
                                clearable
                                outlined
                                dense
                            ></v-autocomplete>
                        </v-col>
                        <v-col cols="6">
                            <v-autocomplete
                                v-model="form.works_package_id"
                                :items="worksPackageOptions"
                                clear-icon="mdi-close-circle"
                                item-text="name"
                                item-value="id"
                                label="Works Package"
                                hide-details
                                clearable
                                outlined
                                dense
                            ></v-autocomplete>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="6">
                            <v-text-field
                                v-model="form.business_name"
                                label="Business Name"
                            ></v-text-field>
                        </v-col>
                        <v-col cols="6">
                            <v-text-field
                                v-model="form.postcode"
                                label="Postcode"
                            ></v-text-field>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="6">
                            <v-autocomplete
                                v-model="form.trade"
                                :items="worksPackageOptions"
                                clear-icon="mdi-close-circle"
                                item-text="name"
                                item-value="name"
                                label="Trade"
                                clearable
                            ></v-autocomplete>
                        </v-col>
                        <v-col cols="6">
                            <v-autocomplete
                                v-model="form.trades_level"
                                :items="['Level 1', 'Level 2', 'Level 3']"
                                clear-icon="mdi-close-circle"
                                label="Trade Level"
                                clearable
                            ></v-autocomplete>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="6">
                            <v-menu
                                v-model="menu1"
                                :close-on-content-click="false"
                                max-width="290"
                            >
                                <template v-slot:activator="{ on, attrs }">
                                    <v-text-field
                                        v-model="form.start_date"
                                        label="Assumed Start Date"
                                        type="text"
                                        v-bind="attrs"
                                        v-on="on"
                                    ></v-text-field>
                                </template>
                                <v-date-picker
                                    v-model="selectedStartDate"
                                    no-title
                                    @change="menu1 = false"
                                ></v-date-picker>
                            </v-menu>
                        </v-col>
                        <v-col cols="6">
                            <v-menu
                                v-model="menu2"
                                :close-on-content-click="false"
                                max-width="290"
                            >
                                <template v-slot:activator="{ on, attrs }">
                                    <v-text-field
                                        v-model="form.end_date"
                                        label="Assumed End Date"
                                        type="text"
                                        v-bind="attrs"
                                        v-on="on"
                                    ></v-text-field>
                                </template>
                                <v-date-picker
                                    v-model="selectedEndDate"
                                    no-title
                                    @change="menu2 = false"
                                ></v-date-picker>
                            </v-menu>
                        </v-col>
                    </v-row>
                </v-container>
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="blue darken-1" text @click="cancel">Cancel</v-btn>
                <v-btn color="blue darken-1" text @click="save">Save</v-btn>
            </v-card-actions>
        </v-card>
    </div>
</template>

<script>
import api from "../common/api.js";
import utils from "../common/utils.js";
import moment from "moment/moment";

export default {
    components: {

    },
    props: ["enquiry"],
    data() {
        return {
            menu1: false,
            menu2: false,
            selectedStartDate: null,
            selectedEndDate: null,
            form: {
                id: null,
                business_name: null,
                postcode: null,
                trade: null,
                trades_level: null,
                start_date: null,
                end_date: null,
                project_id: null,
                works_package_id: null,
            },
            errors: {

            },
            projectOptions: [
                {
                    id: 1,
                    name: "Newtown Development",
                },
                {
                    id: 2,
                    name: "Steel Frame Project",
                },
                {
                    id: 3,
                    name: "Project Demo",
                }
            ],
            worksPackageOptions: [
                {
                    id: 1,
                    name: "Electrical",
                },
                {
                    id: 2,
                    name: "Carpentry",
                },
                {
                    id: 3,
                    name: "Masonry",
                },
                {
                    id: 4,
                    name: "Plumbing & Heating",
                },
                {
                    id: 5,
                    name: "Groundworks",
                }
            ],
        };
    },

    watch: {
        enquiry: function (o, n) {
            this.init();
        },
        selectedStartDate: function (n, o) {
            this.form.start_date = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        },
        selectedEndDate: function (n, o) {
            this.form.end_date = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        },
    },

    async mounted() {
        const self = this;

        self.init();
    },

    methods: {
        ...utils,

        init() {
            let exampleItem = {
                "id": 201,
                "date_created": "16-05-2026",
                "date_published": "16-05-2026",
                "business_name": "Main Contractor Demo",
                "postcode": "DN4 5HX",
                "trade": "Electrical",
                "trades_level": "Level 2",
                "paid_or_unpaid": "Paid",
                "is_meaningful": "Yes",
                "assumed_start_date": "09-07-2026",
                "assumed_end_date": "30-09-2026",
                "project": "Newtown Development",
                "works_package": "Electrical Works"
            };

            // todo: check if enquiry prop is provided and prefill form inputs (if editing)
        },

        save() {
            // todo: post data to server and store enquiry

            this.$emit("close");
        },

        cancel() {
            this.$emit("close");
        }
    },
};
</script>

<style>

</style>
