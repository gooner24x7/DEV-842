<template>
    <v-card>
        <v-card-title>
            <span class="headline">Find Other Merchants</span>
        </v-card-title>
        <v-card-text>
            <v-text-field
                v-model="searchTextMutable"
                label="Search places"
                placeholder="Enter postcode"
                type="text"
            ></v-text-field>
            <v-btn @click="findPlace">Search</v-btn>

            <div class="places-list">
                <div class="places-header">
                    <v-row>
                        <v-col cols="8"></v-col>
                        <v-col cols="2"><div>Called</div></v-col>
                        <v-col cols="2"><div>Onboarded</div></v-col>
                    </v-row>
                </div>
                <div class="place-item" v-for="(item, index) in places">
                    <v-row>
                        <v-col cols="8">
                            <h5 class="place-title">{{ item.displayName.text }}</h5>
                            <div class="place-address">{{ item.formattedAddress ?? '' }}</div>
                            <div class="place-phone">{{ item.nationalPhoneNumber ?? '' }}</div>
                        </v-col>
                        <v-col cols="2">
                            <v-checkbox v-model="item.called" @click="updateCheckbox(item, 'called')"></v-checkbox>
                        </v-col>
                        <v-col cols="2">
                            <v-checkbox v-model="item.onboarded" @click="updateCheckbox(item, 'onboarded')"></v-checkbox>
                        </v-col>
                    </v-row>
                </div>

                <div v-if="showNoResultsMessage">
                    No results found
                </div>
            </div>

        </v-card-text>
    </v-card>
</template>

<script>
import api from "../common/api";

export default {
    name: "SearchPlaces",
    props: ["searchText"],
    data() {
        return {
            places: [],
            searchTextMutable: this.searchText,
            showNoResultsMessage: false
        }
    },

    watch: {
        searchText: function(val) {
            if (val) {
                this.searchTextMutable = val;
                this.findPlace(val);
            }
        }
    },

    methods: {
        async findPlace() {
            const response = await api.findPlace(this.searchTextMutable);

            if (response.status === 200) {
                this.places = response.data;
            }

            if (this.places.length < 1) {
                this.showNoResultsMessage = true;
            }
        },

        updateCheckbox(item, key) {
            const data = { [key]: item[key] };
            const response = api.updateOtherMerchants(item.id, data)
        }
    },

    mounted() {
        if (this.searchTextMutable) {
            this.findPlace(this.searchTextMutable);
        }
    },
}
</script>

<style scoped>
.places-list {
    margin-top: 20px;
}

.place-item {
    padding: 10px 0;
    border-bottom: 3px dotted lightgrey;
}
</style>
