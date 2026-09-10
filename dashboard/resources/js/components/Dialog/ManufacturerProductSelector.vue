<template>
    <v-dialog v-model="dialog" max-width="1024px">
        <template v-slot:activator="{ on, attrs }">
            <v-btn color="primary" dark class="m-2" v-bind="attrs" v-on="on"
            >Select products
            </v-btn
            >
            Added: {{ total }}
        </template>
        <div>
            <v-card>
                <v-card-title>
                    <span class="headline">Select Product</span>
                </v-card-title>
                <v-card-text>

                    <span>Filter by:</span>
                    <v-row>
                        <v-col class="col-6">
                            <v-autocomplete
                                :items="manufacturerCategories"
                                v-model="selectedCategory"
                                label="Category"
                                clearable
                            >
                            </v-autocomplete>
                        </v-col>
                        <v-col class="col-6">
                            <v-autocomplete
                                v-if="selectedCategory"
                                :items="manufacturerSubcategories"
                                v-model="selectedSubcategory"
                                label="Subcategory"
                                clearable
                            >
                            </v-autocomplete>
                        </v-col>
                    </v-row>

                    <v-data-table
                        :headers="headers"
                        :items="filteredItems"
                        item-key="id"
                        loading-text="Loading... Please wait"
                    >
                        <template v-slot:item.image_url="{item}">
                            <a target="_blank" :href="'imported_products/' + item.image_url">
                                <v-img v-if="item.image_url" :src="'imported_products/' + item.image_url" width="50px" />
                            </a>
                        </template>
                        <template v-slot:item.actions="{ item }">
                            <div class="text-center">
                                <v-btn
                                    color="primary"
                                    small
                                    class="mr-2"
                                    @click="addItem(item)"
                                >Add
                                </v-btn
                                >
                                <span v-if="value[item.id]">Added: {{ value[item.id]?.qty ?? 0 }}</span>
                            </div>
                        </template>
                    </v-data-table>

                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="blue darken-1" text @click="cancel">Cancel</v-btn>
                    <v-btn color="blue darken-1" text @click="save">Apply</v-btn>
                </v-card-actions>
            </v-card>
        </div>
    </v-dialog>
</template>

<script>
import api from "../../common/api";

export default {
    name: 'ManufacturerProductSelector',

    props: ['value', 'merchantId'],

    data() {
        return {
            dialog: false,
            total: 0,
            items: [],
            filteredItems: [],
            selectedCategory: '',
            selectedSubcategory: '',
            manufacturerCategories: [],
            manufacturerSubcategories: [],
            allCategories: [],
            headers: [
                {
                    text: "Action",
                    value: "actions",
                },
                {
                    text: "ID",
                    value: "id",
                },
                {
                    text: 'Image',
                    value: 'image_url',
                },
                {
                    text: "Name",
                    value: "name",
                },
                {
                    text: "Deal Name",
                    value: "deal_name",
                },
                {
                    text: "Category Name",
                    value: "category_name",
                },
                {
                    text: "Subcategory Name",
                    value: "subcategory_name",
                },
                {
                    text: "Supplier Name",
                    value: "supplier_name",
                },
                {
                    text: "Supplier Product Code",
                    value: "supplier_product_code",
                },
                {
                    text: "MPN",
                    value: "mpn",
                },
                {
                    text: "Supplier Category",
                    value: "supplier_category_name",
                },
                {
                    text: "Supplier Subcategory",
                    value: "supplier_subcategory_name",
                },
                {
                    text: "Web Product Name",
                    value: "web_product_name",
                },
                {
                    text: "Colour",
                    value: "colour",
                },
                {
                    text: "Material",
                    value: "material",
                },
                {
                    text: "Finish",
                    value: "finish",
                },
            ],
        }
    },

    methods: {
        async loadItems() {
            const productResponse = await api.getManufacturerProductsOptions(this.merchantId);
            this.items = productResponse?.data?.data ?? [];
            this.filteredItems = this.items;

            for (let i in this.items) {
                if (!this.allCategories[this.items[i].category_name.trim()]) {
                    this.allCategories[this.items[i].category_name.trim()] = [];
                }
                this.allCategories[this.items[i].category_name.trim()].push(this.items[i].subcategory_name.trim())
            }

            this.manufacturerCategories = Object.keys(this.allCategories);

            for (let i in this.value) {
                this.total += parseInt(this.value[i].qty);
            }
        },

        cancel() {
            this.dialog = false
        },

        save() {
            this.$emit('select', this.value)
            this.dialog = false

            this.total = 0;
            for (let i in this.value) {
                this.total += parseInt(this.value[i].qty);
            }
        },

        async addItem(item) {
            const qty = prompt('Select quantity')
            const id = item.id
            if (parseInt(qty) > 0) {
                if (!this.value[id]) {
                    this.value[id] = {};
                }
                this.value[id].qty = parseInt(qty)
            }
            if (parseInt(qty) === 0) {
                delete this.value[id];
            }
            await this.loadItems();
        },
    },

    async mounted() {
        const self = this

        await self.loadItems();
    },

    watch: {
        merchantId: () => {
            this.loadItems();
        },

        selectedCategory: function (n, o) {
            this.manufacturerSubcategories = this.allCategories[n]

            if (!n) {
                this.filteredItems = this.items
                return
            }

            this.filteredItems = this.items.filter((item) => {
                return item.category_name.trim() === n;
            });
        },

        selectedSubcategory: function (n, o) {
            if (!n) {
                if (!this.selectedCategory) {
                    this.filteredItems = this.items
                } else {
                    this.filteredItems = this.items.filter((item) => {
                        return item.category_name.trim() === this.selectedCategory;
                    });
                }

                return
            }

            this.filteredItems = this.items.filter((item) => {
                return item.subcategory_name.trim() === n;
            });
        }
    }
}
</script>

<style scoped>

</style>
