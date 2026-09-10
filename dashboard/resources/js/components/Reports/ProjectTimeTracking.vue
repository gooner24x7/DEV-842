<template>
    <v-container fluid>

        <v-sheet class="pa-10" rounded>
            <v-row>
                <v-col>
                    <v-alert
                        v-if="targets.target_hours_ap === null || targets.target_hours_se === null"
                        type="warning"
                    >
                        Note: target hours have not been set for this project
                    </v-alert>
                </v-col>
            </v-row>
            <v-row>
                <v-col>
                    <div class="progress-header">
                        <div>Total Apprenticeship: {{ totals.total_hours_ap }} hours</div>
                        <div>Apprenticeship Target: {{ targets.target_hours_ap ?? 0 }} hours</div>
                    </div>
                    <v-progress-linear
                        v-model="percAp"
                        height="25"
                        :color="getProgressBarColor(percAp)"
                        style="pointer-events: none"
                    >
                        <strong>{{ Math.ceil(percAp) }}%</strong>
                    </v-progress-linear>
                </v-col>
            </v-row>
            <v-row>
                <v-col>
                    <div class="progress-header">
                        <div>Total Social Enterprise: {{ totals.total_hours_se }}</div>
                        <div>Social Enterprise Target: {{ targets.target_hours_se ?? 0 }}</div>
                    </div>
                    <v-progress-linear
                        v-model="percSe"
                        height="25"
                        :color="getProgressBarColor(percSe)"
                        style="pointer-events: none"
                    >
                        <strong>{{ Math.ceil(percSe) }}%</strong>
                    </v-progress-linear>
                </v-col>
            </v-row>
            <v-row>
                <v-col>
                    <div class="progress-header">
                        <div>Total Spend Social Enterprise: {{ totals.total_spend_se }}</div>
                        <div>Social Enterprise Budget: {{ targets.budget_se ?? 0 }}</div>
                    </div>
                    <v-progress-linear
                        v-model="percSpendSe"
                        height="25"
                        :color="getProgressBarColor(percSpendSe)"
                        style="pointer-events: none"
                    >
                        <strong>{{ Math.ceil(percSpendSe) }}%</strong>
                    </v-progress-linear>
                </v-col>
            </v-row>
        </v-sheet>

        <v-data-table
            item-key="id"
            :headers="headers"
            :items="items"
            :loading="loader"
            :options.sync="options"
            loading-text="Loading... Please wait"
            mobile-breakpoint="1000"
            hide-default-footer
        >
            <template v-slot:top>
                <v-dialog v-model="dialog" max-width="800px">
                    <template v-slot:activator="{ on, attrs }">
                        <v-btn
                            color="primary"
                            class="m-2"
                            v-bind="attrs" v-on="on"
                        >Update</v-btn>
                    </template>

                    <v-card>
                        <v-card-title>
                            Add Time
                        </v-card-title>
                        <v-card-text>
                            <v-text-field
                                v-model="form.hours_ap"
                                type="number"
                                label="Apprenticeship Hours"
                            ></v-text-field>

                            <v-text-field
                                v-model="form.hours_se"
                                type="number"
                                label="Social Enterprise"
                            ></v-text-field>

                            <v-text-field
                                v-model="form.spend_se"
                                type="number"
                                label="Social Enterprise Spend"
                                prefix="£"
                            ></v-text-field>

                            <v-text-field
                                v-model="form.name_se"
                                type="text"
                                label="Social Enterprise Name"
                            ></v-text-field>
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="blue darken-1" text @click="cancel()">Cancel</v-btn>
                            <v-btn color="blue darken-1" text @click="save()">Save</v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>
            </template>

            <template v-slot:item.actions="{ item }"></template>
        </v-data-table>

    </v-container>
</template>

<script>
import api from "../../common/api"

export default {
    name: "ProjectTimeTracking",
    props: ['projectId', 'targets'],
    components: {},

    data() {
        return {
            loader: false,
            dialog: false,
            items: [],
            totals: {
                total_hours_ap: 0,
                total_hours_se: 0,
                total_spend_se: 0
            },
            headers: [
                {
                    text: "Apprenticeship Hours",
                    value: "hours_ap",
                    sortable: true,
                },
                {
                    text: "Social Enterprise",
                    value: "hours_se",
                    sortable: true,
                },
                {
                    text: "Social Enterprise Spend",
                    value: "spend_se",
                    sortable: true,
                },
                {
                    text: "Social Enterprise Name",
                    value: "name_se",
                    sortable: true,
                },
                {
                    text: "Created By",
                    value: "first_name",
                    sortable: true,
                },
                {
                    text: "Created At",
                    value: "created_at",
                    sortable: true,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: false,
                },
            ],
            options: {
                page: 1,
                itemsPerPage: 10,
                sortBy: ["id"],
                sortDesc: [true],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false,
            },
            form: {
                hours_ap: null,
                hours_se: null,
                spend_se: null,
                name_se: null
            }
        }
    },

    computed: {
        percAp() {
            if (!this.targets.target_hours_ap) {
                return 0;
            }

            let perc = (this.totals.total_hours_ap / this.targets.target_hours_ap) * 100;

            return perc.toFixed(0);
        },
        percSe() {
            if (!this.targets.target_hours_se) {
                return 0;
            }

            let perc = (this.totals.total_hours_se / this.targets.target_hours_se) * 100;

            return perc.toFixed(0);
        },
        percSpendSe() {
            if (!this.targets.budget_se) {
                return 0;
            }

            let perc = (this.totals.total_spend_se / this.targets.budget_se) * 100;

            return perc.toFixed(0);
        }
    },

    watch: {
        options: {
            handler(n, o) {
                this.load();
            },
            deep: true,
        },
    },

    mounted() {
        const self = this;

        //self.load();
    },

    methods: {
        async load() {
            const self = this;
            self.loader = true;

            let params = {
                ...self.options,
                page: self.options.page,
                itemsPerPage: self.options.itemsPerPage,
                sortBy: self.options.sortBy[0],
                sortDesc: self.options.sortDesc[0] ? 1 : 0,
            };

            try {
                const response = await api.getProjectTimeTracking(self.projectId, params);

                self.items = response.data.items;
                self.totals = response.data.totals;
            } catch (e) {}

            self.loader = false;
        },
        async save() {
            const self = this;

            try {
                const response = await api.storeProjectTimeTracking(self.projectId, self.form);

                self.dialog = false;
                await self.load();
            } catch (e) {
                console.log(e.message);
            }
        },
        cancel() {
            const self = this;

            self.dialog = false;
        },
        getProgressBarColor(perc) {
            let colors = ['#F94144', '#F3722C', '#F8961E', '#F9C74F', '#90BE6D'];

            if (perc >= 80) {
                return colors[4];
            } else if (perc >= 60) {
                return colors[3];
            } else if (perc >= 40) {
                return colors[2];
            } else if (perc >= 20) {
                return colors[1];
            }

            return colors[0];
        }
    }
}
</script>

<style scoped>
.progress-header {
    display: flex;
    justify-content: space-between;
}
</style>
