<template>
    <div>
        <v-data-table
            item-key="id"
            :headers="enquiryHeaders"
            :items="enquiryItems"
            :single-expand="true"
            :expanded.sync="expanded"
            hide-default-footer
            mobile-breakpoint="1000"
        >
            <template v-slot:expanded-item="{ headers, item }">
                <td :colspan="headers.length">
                    <div class="expanded-comment">{{ item.comment }}</div>

                    <ol class="mt-1" v-if="products && products.length > 0">
                        Selected products:
                        <li
                            v-for="item in products"
                            :key="item.id"
                            :title="item.name"
                        >Supplier: {{ item.supplier_name }}, Product Name: {{ item.web_product_name }}, MPN:
                            {{ item.mpn }}, Colour: {{ item.colour }} X {{ item.qty }}
                        </li>
                    </ol>

                    <v-btn class="blue darken-1" v-if="products && products.length > 0 && isMerchant()" v-on:click="shareContacts()">Dont Stock
                        these products - Speak to the manufacturer
                    </v-btn>
                </td>
            </template>

            <template v-slot:item.comment="{ item }">
                {{ item.comment | limit20Chars }}
            </template>

            <template v-slot:item.created_at="{ item }">
                {{ item.created_at | formatDate }}
            </template>

            <template v-slot:item.attachments="{ item }">
                <a
                    href="#"
                    v-if="showAttachmentsIcon(item)"
                    v-on:click="(e) => { e.preventDefault(); dialogUploadItem = item; dialogUploads = true; }"
                >
                    <v-icon>mdi-download</v-icon>
                </a>
            </template>

            <template v-slot:item.product_type="{ item }">
                {{ (item.product_type === 1) ? 'Hire Enquiry' : 'Purchase Enquiry' }}
            </template>

            <template v-slot:item.assigned="{ item }">
                <ul>
                    <li v-for="user in (item.assigned || [])">{{
                            isCurrentUser(user.id) ? 'You' : user.username
                        }}
                    </li>
                </ul>
            </template>

            <template v-slot:item.actions="{ item }">
                <div class="text-center">
                    <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                small
                                class="mr-2"
                                @click="viewComments(item)"
                                icon
                                color="black"
                                v-bind="attrs"
                                v-on="on"
                            >
                                <v-icon>
                                    mdi-format-list-bulleted-square
                                </v-icon>
                            </v-btn
                            >
                        </template>
                        <span>View Comments</span>
                    </v-tooltip>
                </div>
            </template>
        </v-data-table>

        <v-dialog v-model="dialogUploads" max-width="500px">
            <attachments-dialog
                :parentId="dialogUploadItem.id"
                type="Question"
                title="Enquiry"
                @close="dialogUploads=false"
                :userCanEdit="dialogUploadItem.user_id === user().id"
            ></attachments-dialog>
        </v-dialog>

    </div>
</template>

<script>
import AttachmentsDialog from "../components/AttachmentsDialog";
import api from "../common/api";
import permissions from "../common/permissions";

export default {
    name: "EnquiryBlock",

    props: [
        'enquiryItems'
    ],

    components: {
        AttachmentsDialog,
    },

    data() {
        return {
            expanded: [],
            dialogUploadItem: {},
            dialogUploads: false,
            products: [],

            enquiryHeaders: [
                {
                    text: "ID",
                    value: "id",
                    sortable: false,
                },

                {
                    text: "Date",
                    value: "created_at",
                    sortable: false,
                },

                {
                    text: "Business Name",
                    value: "first_name",
                    sortable: false,
                },

                {
                    text: "Postcode",
                    value: "postcode",
                    sortable: false,
                },
                {
                    text: "Days",
                    value: "days",
                    sortable: false,
                },
                {
                    text: "Live/Tender",
                    value: "type",
                    sortable: true,
                },
                {
                    text: "Product",
                    value: "name",
                    sortable: false,
                },
                {
                    text: "Product Type",
                    value: "product_type",
                    sortable: false,
                },
                {
                    text: "Comments",
                    value: "comment",
                    sortable: false,
                },
                {
                    text: "Project",
                    value: "project_name",
                    sortable: false,
                },
                {
                    text: "Works Package",
                    value: "works_package_name",
                    sortable: false,
                },
                {
                    text: 'Documents',
                    value: 'attachments',
                    sortable: false,
                },
                {
                    text: 'Assigned To',
                    value: 'assigned',
                    sortable: false,
                },
                {
                    text: 'Total Quotes',
                    value: 'total_quotes',
                    sortable: false,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: false,
                },
            ],
        }
    },

    watch: {
        enquiryItems: async function () {
            await this.loadProducts();
        }
    },

    methods: {
        isMerchant() {
            return permissions.hasRole(permissions.role_company);
        },

        async shareContacts() {
            const self = this;

            await api.storeVirtualExpoSharedContactToLastExpo(self.foundManufacturer.id);

            self.$store.commit("showSnackbar", {
                message: "Success!",
                color: "success",
            });
        },

        viewComments: function (item) {
            const self = this;

            if (self.expanded.indexOf(item) > -1) {
                self.expanded = [];
                return;
            }

            self.expanded = [item];
        },

        isCurrentUser(userId) {
            const self = this;

            return self.user().id === userId;
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        async loadProducts() {
            const item = this.enquiryItems[0] ?? null;

            if (!item) {
                return
            }

            if (Object.keys(item.manufacturer_product_selected).length <= 0) {
                return
            }

            const foundResponse = await api.getManufacturerByManufacturerProductId(Object.keys(item.manufacturer_product_selected)[0]);
            this.foundManufacturer = foundResponse.data

            const productResponse = await api.getManufacturerProductsOptions(this.foundManufacturer.id);
            const products = productResponse?.data?.data ?? [];
            const productsMap = {};
            for (const product of products) {
                productsMap[product.id] = product
            }

            this.products = Object.keys(item.manufacturer_product_selected).map((id) => {
                const qty = item.manufacturer_product_selected[id].qty
                const {supplier_name, web_product_name, name, deal_id, mpn, colour} = productsMap[id]
                return {
                    supplier_name,
                    web_product_name,
                    id,
                    name,
                    deal_id,
                    mpn,
                    colour,
                    qty
                }
            });
        },

        showAttachmentsIcon(item) {
            if (item.user_id === this.user().id) {
                return true;
            }

            return item.attachments.length > 0;
        }
    }
}
</script>

<style scoped>

</style>
