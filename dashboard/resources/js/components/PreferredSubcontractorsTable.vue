<template>
    <div>
        <v-row>
            <v-col cols="12" md="3">
                <v-autocomplete
                    v-model="filters.certType"
                    :items="options.certType"
                    :loading="loaders.certType"
                    item-text="label"
                    item-value="slug"
                    label="Certificate Type"
                    hide-details
                    outlined
                    dense
                ></v-autocomplete>
            </v-col>
            <v-col cols="12" md="3">
                <v-autocomplete
                    v-model="filters.certStatus"
                    :items="options.certStatus"
                    item-text="label"
                    item-value="value"
                    label="Status"
                    hide-details
                    outlined
                    dense
                ></v-autocomplete>
            </v-col>
            <v-col cols="12" md="3">
                <v-autocomplete
                    v-model="selectedProject"
                    :items="options.projects"
                    :loading="loaders.projects"
                    item-text="name"
                    item-value="id"
                    label="Project"
                    return-object
                    hide-details
                    outlined
                    dense
                ></v-autocomplete>
            </v-col>
            <v-col cols="12" md="3">
                <v-autocomplete
                    v-model="filters.worksPackage"
                    :items="options.worksPackages"
                    :loading="loaders.worksPackages"
                    item-text="name"
                    item-value="name"
                    label="Works Package"
                    hide-details
                    outlined
                    dense
                ></v-autocomplete>
            </v-col>
        </v-row>

        <v-row>
            <v-col cols="12" md="3">
                <v-autocomplete
                    v-model="filters.radius"
                    :items="options.radius"
                    item-text="label"
                    item-value="value"
                    label="Radius"
                    hide-details
                    outlined
                    dense
                ></v-autocomplete>
            </v-col>
            <v-col cols="12" md="3">
                <v-autocomplete
                    v-model="filters.products"
                    :items="options.products"
                    :loading="loaders.products"
                    item-text="name"
                    item-value="name"
                    label="Trades"
                    multiple
                    chips
                    small-chips
                    hide-details
                    outlined
                    dense
                ></v-autocomplete>
            </v-col>
            <v-col cols="12" md="3">
                <v-autocomplete
                    v-model="filters.region"
                    :items="options.regions"
                    :loading="loaders.regions"
                    label="Region"
                    hide-details
                    outlined
                    dense
                ></v-autocomplete>
            </v-col>
            <v-col cols="12" md="3">
                <v-text-field
                    v-model="filters.search"
                    label="Search"
                    hide-details
                    dense
                    outlined
                ></v-text-field>
            </v-col>
        </v-row>

        <v-row>
            <v-col>
                <v-btn
                    @click="applyFilters()"
                    color="primary"
                >
                    Apply Filters
                </v-btn>
                <v-btn
                    @click="clearFilters()"
                    color="primary"
                >
                    Clear Filters
                </v-btn>
            </v-col>
        </v-row>

        <v-data-table
            v-model="selectedRows"
            :headers="headers"
            :items="items"
            :loading="loaders.users"
            item-key="id"
            loading-text="Loading... Please wait"
            show-select
        >
            <template v-slot:top>

            </template>

            <template v-slot:item.distance="{ item }">
                {{ item.distance ? item.distance.toFixed(1) + ' miles' : '' }}
            </template>

            <template v-slot:item.cas="{ item }">
                <div class="text-center" style="font-weight: bold">
                    <div v-if="item.cas" style="color: green">Yes</div>
                    <div v-else style="color: red">No</div>
                </div>
            </template>

            <template v-slot:item.cert_list="{ item }">
                <v-row no-gutters>
                    <v-col cols="6">
                        <ul class="cert-list">
                            <li><span v-html="getCertIcon(item.cscs)"></span> CSCS</li>
                            <li><span v-html="getCertIcon(item.ssip)"></span> SSIP</li>
                            <li><span v-html="getCertIcon(item.cyber)"></span> Cyber Essentials</li>
                            <li><span v-html="getCertIcon(item.fire_products)"></span> Fire Products</li>
                            <li><span v-html="getCertIcon(item.fire_competencies)"></span> Fire Competencies</li>
                        </ul>
                    </v-col>
                    <v-col cols="6">
                        <ul class="cert-list">
                            <li><span v-html="getCertIcon(item.pref_suppliers)"></span> Sub-letting</li>
                            <li><span v-html="getCertIcon(item.modern_slavery)"></span> Modern Slavery</li>
                            <li><span v-html="getCertIcon(item.induction)"></span> Induction</li>
                            <li><span v-html="getCertIcon(item.goods_supply)"></span> Goods Supply</li>
                            <li><span v-html="getCertIcon(item.cas)"></span> CAS</li>
                        </ul>
                    </v-col>
                </v-row>
            </template>

            <template v-slot:item.products="{ item }">
                <template v-if="item.products">
                    <v-chip
                        v-for="(product, index) in (expandedProducts[item.id] ? getProductList(item) : getProductList(item).slice(0, 2))"
                        :key="index"
                        class="mr-1 mb-1"
                        small
                    >
                        {{ product }}
                    </v-chip>
                    <v-btn
                        v-if="getProductList(item).length > 2"
                        @click="toggleProducts(item.id)"
                        icon
                        x-small
                    >
                        <v-icon small>{{ expandedProducts[item.id] ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
                    </v-btn>
                </template>
            </template>

            <template v-slot:item.actions="{ item }">
                <div class="text-center">

                </div>
            </template>
        </v-data-table>
    </div>
</template>

<script>
import api from "../common/api";

export default {
    name: "PreferredSubcontractorsTable",
    components: {},
    props: ["inputFilters"],
    data() {
        return {
            selectedProject: null,
            selectedRows: [],
            items: [],
            expandedProducts: {},
            loaders: {
                users: false,
                certType: false,
                projects: false,
                worksPackages: false,
                products: false,
                regions: false,
            },
            headers: [
                {
                    text: "Name",
                    value: "first_name",
                    sortable: true,
                },
                {
                    text: "Distance",
                    value: "distance",
                    sortable: true,
                },
                {
                    text: "Postcode",
                    value: "postcode",
                    sortable: true,
                },
                {
                    text: "Trades",
                    value: "products",
                    sortable: true,
                },
                {
                    text: "CAS Approved",
                    value: "cas",
                    sortable: true,
                    align: 'center',
                },
                {
                    text: "Compliance Breakdown",
                    value: "cert_list",
                    sortable: true,
                    align: 'center',
                },
                {
                    text: "Risk Score",
                    value: "risk_score",
                    sortable: true,
                    align: 'center',
                },
                {
                    text: "Main Contractor Score",
                    value: "contractor_score",
                    sortable: true,
                    align: 'center',
                }
            ],
            filters: {
                search: null,
                region: null,
                project: null,
                worksPackage: null,
                products: [],
                radius: null,
                certType: null,
                certStatus: null,
            },
            options: {
                regions: [],
                projects: [],
                worksPackages: [],
                products: [],
                radius: [
                    {
                        value: 5,
                        label: '5 Miles',
                    },
                    {
                        value: 10,
                        label: '10 Miles',
                    },
                    {
                        value: 15,
                        label: '15 Miles',
                    },
                    {
                        value: 20,
                        label: '20 Miles',
                    },
                    {
                        value: 25,
                        label: '25 Miles',
                    },
                    {
                        value: 30,
                        label: '30 Miles',
                    },
                    {
                        value: 50,
                        label: '50 Miles',
                    }
                ],
                certType: [],
                certStatus: [
                    {
                        label: "Missing",
                        value: 0,
                    },
                    {
                        label: "Proofed",
                        value: 1,
                    }
                ],
            }
        };
    },
    async mounted() {
        const self = this;

        await self.loadTypeOptions();
        await self.loadProjectOptions();
        await self.loadWorksPackageOptions();
        await self.loadRegionOptions();
        await self.loadProductOptions();

        //await self.load();
    },
    watch: {
        inputFilters: {
            async handler(n, o) {
                const self = this;

                self.filters = {
                    ...self.filters,
                    ...n,
                    products: [...(n.products || [])],
                };

                await self.load();
            },
            deep: true,
            immediate: true,
        },
        selectedRows: {
            handler(n, o) {
                const self = this;

                if (n?.length > 0) {
                    self.$emit('selected', n);
                } else {
                    self.$emit('selected', []);
                }
            },
            deep: true,
        },
        selectedProject: {
            async handler(n, o) {
                const self = this;

                let projectIds = [];

                if (n !== null) {
                    projectIds.push(n.id);
                    self.filters.project = n.name;
                } else {
                    self.filters.project = null;
                }

                await self.loadWorksPackageOptions('', projectIds);
            },
            deep: true,
        },
    },
    methods: {
        async loadTypeOptions() {
            const self = this;
            const response = await api.getCertificateTypes();

            self.options.certType = response.data;
        },
        async loadProjectOptions() {
            const self = this;
            self.loaders.projects = true;

            const response = await api.getProjectOptions({grouped: 1});

            self.options.projects = response.data;

            if (self.filters.project) {
                let filteredProjects = self.options.projects.filter(p => p.name === self.filters.project);
                self.selectedProject = filteredProjects.length > 0 ? filteredProjects[0] : null;
            }

            self.loaders.projects = false;
        },
        async loadWorksPackageOptions(search = '', projectIds = []) {
            const self = this;
            self.loaders.worksPackages = true;

            const response = await api.getWorksPackageOptions({search: search, projectIds: projectIds});

            self.options.worksPackages = response.data;
            self.loaders.worksPackages = false;
        },
        async loadRegionOptions() {
            const self = this;
            self.loaders.regions = true;

            const response = await api.loadProjectRegions();

            self.options.regions = response.data;
            self.loaders.regions = false;
        },
        async loadProductOptions(typeIds = [3]) {
            const self = this;
            self.loaders.products = true;

            const response = await api.productOptions(typeIds);

            self.options.products = response.data;
            self.loaders.products = false;
        },
        async load() {
            const self = this;
            self.loaders.users = true;

            const response = await api.loadSupplyChainSubcontractors(self.filters);

            self.items = response.data;
            self.loaders.users = false;
        },
        getProductList(item) {
            if (!item.products) {
                return [];
            }
            return item.products.split(',');
        },
        toggleProducts(itemId) {
            this.$set(this.expandedProducts, itemId, !this.expandedProducts[itemId]);
        },
        async applyFilters() {
            const self = this;

            await self.load();
        },
        async clearFilters() {
            const self = this;

            self.filters = {
                search: null,
                region: null,
                project: null,
                worksPackage: null,
                products: [],
                radius: null,
                certType: null,
                certStatus: null,
            };

            await self.load();
        },

        getCertIcon(val) {
            if (val) {
                return `<i class="v-icon mdi mdi-check-circle-outline" style="color: green"></i>`;
            }

            return `<i class="v-icon mdi mdi-close-circle-outline" style="color: red"></i>`;
        }
    }
}
</script>

<style scoped>

</style>
