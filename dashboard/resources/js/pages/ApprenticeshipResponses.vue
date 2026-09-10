<template>
    <div style="margin: 1em">
        <v-data-table
            :headers="enquiryHeaders"
            :items="[enquiry]"
            disable-sort
            hide-default-footer
            item-key="enquiry_id"
        ></v-data-table>

        <h2 class="cb-title mt-4 mb-2">Apprenticeships Responses</h2>

        <v-btn
            class="m-2 mb-3"
            color="primary"
            style="left: 0"
            @click="upload()"
        >
            Upload
        </v-btn>

        <v-data-table
            v-model="selected"
            :headers="responsesHeaders"
            :items="responses"
            :loading="loader"
            :options.sync="options"
            item-key="id"
            loading-text="Loading... Please wait"
            mobile-breakpoint="1000"
            show-select
        >
            <template v-slot:item.apprentice_name="{ item }">
                <div class="d-flex align-center">
                    <v-avatar color="primary" size="36">
                        <span class="white--text">{{ initials(item.apprentice_name) }}</span>
                    </v-avatar>
                    <a
                        class="ml-2"
                        href="#"
                        @click.prevent="viewApprentice(item)"
                    >{{ item.apprentice_name }}</a>
                </div>
            </template>

            <template v-slot:item.postcode_location="{ item }">
                <div class="d-flex align-center">
                    <v-icon size="18">mdi-map-marker</v-icon>
                    <span class="ml-1">{{ item.postcode_location.postcode }}</span>
                </div>
                <div class="text-no-wrap">
                    ({{ item.postcode_location.latitude }}, {{ item.postcode_location.longitude }})
                </div>
            </template>

            <template v-slot:item.availability="{ item }">
                <div>Available from</div>
                <div>{{ item.availability }}</div>
            </template>

            <template v-slot:item.neet_before_recruitment="{ item }">
                <div class="text-center">
                    <div :class="yesNoChipClass(item.neet_before_recruitment)" class="type-chip-sm d-inline-block px-4">
                        {{ item.neet_before_recruitment }}
                    </div>
                </div>
            </template>

            <template v-slot:item.care_leaver="{ item }">
                <div class="text-center">
                    <div :class="yesNoChipClass(item.care_leaver)" class="type-chip-sm d-inline-block px-4">
                        {{ item.care_leaver }}
                    </div>
                </div>
            </template>

            <template v-slot:item.actions="{ item }">
                <div class="d-flex align-center justify-center">
                    <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                class="mr-2"
                                color="black"
                                icon
                                small
                                v-bind="attrs"
                                @click="viewChat(item)"
                                v-on="on"
                            >
                                <v-icon>
                                    mdi-forum
                                </v-icon>
                            </v-btn>
                        </template>
                        <span>Live Chat</span>
                    </v-tooltip>

                    <v-btn color="primary" small @click="acceptApprentice(item)">
                        Accept Apprentice
                    </v-btn>
                </div>
            </template>
        </v-data-table>

    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions.js";

export default {
    name: 'ApprenticeshipResponses',
    components: {

    },
    data() {
        return {
            loader: false,
            selected: [],
            options: {
                page: 1,
                itemsPerPage: 10,
                sortBy: [],
                sortDesc: [],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false,
            },
            filters: {

            },
            enquiryHeaders: [
                {
                    text: "Enquiry ID",
                    value: "enquiry_id",
                },
                {
                    text: "Project",
                    value: "project",
                },
                {
                    text: "Works Package",
                    value: "works_package",
                },
                {
                    text: "Trade",
                    value: "trade",
                },
                {
                    text: "Required Level",
                    value: "required_level",
                },
                {
                    text: "Assumed Start Date",
                    value: "assumed_start_date",
                },
                {
                    text: "Assumed End Date",
                    value: "assumed_end_date",
                },
                {
                    text: "Postcode Location",
                    value: "postcode_location",
                },
                {
                    text: "Date Created",
                    value: "date_created",
                },
            ],
            enquiry: {
                "enquiry_id": 201,
                "project": "Newtown Development",
                "works_package": "Electrical Works",
                "trade": "Electrical",
                "required_level": "Level 2",
                "assumed_start_date": "01-07-2026",
                "assumed_end_date": "30-09-2026",
                "postcode_location": "DN4 5HX",
                "date_created": "16-05-2026"
            },
            responsesHeaders: [
                {
                    text: "Apprentice Name",
                    value: "apprentice_name",
                    sortable: true,
                },
                {
                    text: "Company Name",
                    value: "company_name",
                    sortable: true,
                },
                {
                    text: "Trade",
                    value: "trade",
                    sortable: true,
                },
                {
                    text: "Level",
                    value: "level",
                    sortable: true,
                },
                {
                    text: "Postcode Location",
                    value: "postcode_location",
                    sortable: false,
                },
                {
                    text: "Availability",
                    value: "availability",
                    sortable: true,
                },
                {
                    text: "NEET Before Recruitment?",
                    value: "neet_before_recruitment",
                    sortable: true,
                    align: "center",
                },
                {
                    text: "Care Leaver?",
                    value: "care_leaver",
                    sortable: true,
                    align: "center",
                },
                {
                    text: "Comments",
                    value: "comments",
                    sortable: false,
                },
                {
                    text: "Actions",
                    value: "actions",
                    sortable: false,
                    align: "center",
                },
            ],
            responses: [
                {
                    "id": 1,
                    "apprentice_name": "Jack Davidson",
                    "company_name": "Meridian Site Services",
                    "trade": "Electrical Installation",
                    "level": "Level 2",
                    "postcode_location": {
                        "postcode": "DN4 5HX",
                        "latitude": 53.5221,
                        "longitude": -1.1334
                    },
                    "availability": "01-07-2026",
                    "neet_before_recruitment": "Yes",
                    "care_leaver": "No",
                    "comments": "Enthusiastic and keen to learn."
                },
                {
                    "id": 2,
                    "apprentice_name": "Liam Smith",
                    "company_name": "Apex Structural Solutions",
                    "trade": "Electrical Installation",
                    "level": "Level 2",
                    "postcode_location": {
                        "postcode": "DN4 5HX",
                        "latitude": 53.5221,
                        "longitude": -1.1334
                    },
                    "availability": "15-06-2026",
                    "neet_before_recruitment": "No",
                    "care_leaver": "Yes",
                    "comments": "Strong potential, needs experience."
                },
                {
                    "id": 3,
                    "apprentice_name": "Ethan Brown",
                    "company_name": "Northern Shield Construction",
                    "trade": "Electrical Installation",
                    "level": "Level 2",
                    "postcode_location": {
                        "postcode": "DN4 5HX",
                        "latitude": 53.5221,
                        "longitude": -1.1334
                    },
                    "availability": "01-07-2026",
                    "neet_before_recruitment": "Yes",
                    "care_leaver": "No",
                    "comments": "Previously unemployed, motivated to succeed."
                },
                {
                    "id": 4,
                    "apprentice_name": "Oliver Stewart",
                    "company_name": "Volt Electrical Services",
                    "trade": "Electrical Installation",
                    "level": "Level 2",
                    "postcode_location": {
                        "postcode": "DN4 5HX",
                        "latitude": 53.5221,
                        "longitude": -1.1334
                    },
                    "availability": "10-07-2026",
                    "neet_before_recruitment": "Yes",
                    "care_leaver": "Yes",
                    "comments": "Gained interest through college workshop."
                },
                {
                    "id": 5,
                    "apprentice_name": "Callum Harris",
                    "company_name": "Bright Future Training Ltd",
                    "trade": "Electrical Installation",
                    "level": "Level 2",
                    "postcode_location": {
                        "postcode": "DN4 5HX",
                        "latitude": 53.5221,
                        "longitude": -1.1334
                    },
                    "availability": "01-08-2026",
                    "neet_before_recruitment": "No",
                    "care_leaver": "No",
                    "comments": "Reliable and punctual."
                }
            ],
        };
    },

    async mounted() {

    },

    watch: {

    },

    computed: {

    },

    methods: {
        initials(name) {
            return (name || '')
                .split(' ')
                .filter((part) => part.length)
                .map((part) => part.charAt(0).toUpperCase())
                .slice(0, 2)
                .join('');
        },

        yesNoChipClass(value) {
            return value === 'Yes' ? 'chip-green' : 'chip-red';
        },

        newEnquiry() {
            // TODO: open the new enquiry dialog once the backend is in place
        },

        upload() {
            // TODO: open the upload dialog once the backend is in place
        },

        viewApprentice(item) {
            // TODO: open the apprentice profile once the backend is in place
        },

        viewChat(item) {
            // TODO: open the live chat dialog once the backend is in place
        },

        acceptApprentice(item) {
            // TODO: accept the apprentice once the backend is in place
        },
    },
};
</script>

<style>

</style>
