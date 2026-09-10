<template>
    <div class="mn_expos" style="margin: 1em">
        <h2>Virtual Expo</h2>
        <v-dialog v-model="dialog" max-width="500px">
            <template v-slot:activator="{ on, attrs }">
                <v-btn v-if="isManufacturer() || isAdmin()" v-bind="attrs" v-on="on" class="m-2" color="primary" dark
                >New Item
                </v-btn
                >
            </template>

            <virtual-expo-form
                v-model="editedItem"
                :title="formTitle"
                v-on:cancel="close"
                v-on:save="save"
            ></virtual-expo-form>
        </v-dialog>

        <v-btn
            v-if="isManufacturer() || isAdmin()"
            class="m-2"
            color="primary"
            v-on:click="selectedExpoId = ''; showReportDialog = true"
        >Exhibition Reports
        </v-btn
        >

        <v-container class="expo-list">
            <v-row v-for="(item) in items" :key="item.id" class="expo-item">
                <v-col>
                    <v-row align="center" justify="center">
                        <v-col cols="12" md="1">
                            <v-checkbox v-model="item.is_active" style="padding: 0; margin: 0;" readonly></v-checkbox>
                        </v-col>
                        <v-col cols="12" md="2">
                            <h4 style="font-weight: bold">{{item.company_name}}</h4>
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-row style="padding: 10px">
                                <a :href="item.banner_image_left ?? ''" style="display: block; margin: auto;">
                                    <img :src="item.banner_image_left ?? ''" alt="Left Banner Image" class="expo-image"/>
                                </a>
                            </v-row>
                            <v-row style="padding: 10px; margin-top: 0;">
                                <div class="expo-buttons">
                                    <v-btn v-if="isViewActive()" class="mr-2" color="primary" small @click="viewItem(item)">View</v-btn>
                                    <v-btn v-if="isManufacturer() || isAdmin()" class="mr-2" color="primary" small @click="editItem(item)">Edit</v-btn>
                                    <v-btn v-if="isManufacturer() || isAdmin()" class="mr-2" color="primary" small @click="deleteItem(item)">Delete</v-btn>
                                    <v-btn v-if="isManufacturer() || isAdmin()" class="mr-2" color="primary" small v-on:click="selectedExpoId = item.id; showContactsDialog = true">Shared Contacts</v-btn>
                                    <v-btn v-if="isManufacturer() || isAdmin()" class="mr-2" color="primary" small v-on:click="selectedExpoId = item.id; showReportDialog = true">Reports</v-btn>
                                </div>
                            </v-row>
                        </v-col>
                        <v-col cols="12" md="3">
                            <h6>Description: </h6>
                            {{item.description}}
                        </v-col>
                        <v-col cols="12" md="3">
                            <ul class="expo-checklist">
                                <li v-for="(checkbox, index) in expoCheckboxes" :key="index">
                                    <v-checkbox
                                        class="expo-checkbox"
                                        :label="checkbox.label"
                                        hide-details="auto"
                                        style="padding: 0; margin: 0;"
                                        v-model="item[checkbox.slug]"
                                        @click="updateExpoCheckbox(item.id, checkbox.slug, item[checkbox.slug])"
                                        :disabled="!canEditChecklist(item.user_id)"
                                    ></v-checkbox>
                                </li>
                            </ul>
                        </v-col>
                    </v-row>
                </v-col>
            </v-row>
        </v-container>

        <v-dialog v-model="showContactsDialog" max-width="600px">
            <shared-contacts-dialog
                :id="selectedExpoId"
                v-on:close="selectedExpoId = ''; showContactsDialog = false"
            ></shared-contacts-dialog>
        </v-dialog>

        <v-dialog v-model="showReportDialog" max-width="600px">
            <virtual-expo-report
                :virtualExpoId="selectedExpoId"
                v-on:close="selectedExpoId = ''; showReportDialog = false"
            ></virtual-expo-report>
        </v-dialog>
</div>
</template>

<script>
import api from '../common/api.js';
import VirtualExpoForm from "../components/VirtualExpoForm";
import SharedContactsDialog from "../components/SharedContactsDialog";
import permissions from "../common/permissions";
import VirtualExpoReport from "../components/Dialog/virtualExpoReport"

export default {
    components: {VirtualExpoReport, VirtualExpoForm, SharedContactsDialog},
    data() {
        return {
            loader: false,
            dialogSharedContacts: [],
            showReportDialog: false,
            showContactsDialog: false,
            selectedExpoId: '',
            items: [],
            serverItemsLength: 0,
            options: {
                page: 1,
                itemsPerPage: 10,
                sortBy: ['id'],
                sortDesc: [true],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false,
            },
            dialog: false,
            editedIndex: -1,
            editedItem: {
                company_name: '',
                description: '',
                date_start: '',
                date_end: '',
                is_active: false,
                videos: [],
            },
            defaultItem: {
                company_name: '',
                description: '',
                date_start: '',
                date_end: '',
                is_active: false,
                videos: [''],
            },
            expoCheckboxes: [
                {
                    slug: 'expo_live',
                    label: 'Expo Live'
                },
                {
                    slug: 'product_categories',
                    label: 'Downloadable Product Categories'
                },
                {
                    slug: 'tech_support',
                    label: 'Tech Support'
                },
                {
                    slug: 'pim_uploaded',
                    label: 'PIM Uploaded'
                },
                {
                    slug: 'epd_info',
                    label: 'EPD Information'
                }
            ],
        };
    },

    mounted() {
        const self = this;

        self.load();
    },

    watch: {
        options: {
            handler(n, o) {
                this.load();
            },
            deep: true,
        },
        dialog(val) {
            val || this.close();
        },
    },

    computed: {
        formTitle() {
            return this.editedIndex === -1 ? 'New Virtual Expo' : 'Edit Virtual Expo';
        },
    },

    methods: {
        isManufacturer() {
            return permissions.hasRole(permissions.role_manufacturer);
        },

        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },

        isCompany() {
            return permissions.hasRole(permissions.role_company);
        },

        isViewActive() {
            return permissions.isAuthorized();
        },

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
                const response = await api.loadVirtualExpo(params)

                self.items = response.data.data;
                self.serverItemsLength = response.data.total;
            } catch (e) {
                console.log(e);
            }

            self.loader = false;
        },

        editItem(item) {
            this.editedIndex = this.items.indexOf(item);
            this.editedItem = Object.assign({}, item);
            this.dialog = true;
        },

        async viewItem(item) {
            await this.$router.push({
                name: 'virtualExpoItem',
                query: {
                    t: new Date().getTime()
                },
                params: {
                    'virtual_expo_id': item.id
                }
            });
        },

        async deleteItem(item) {
            const self = this;

            if (confirm('Are you sure you want to delete this virtual expo?')) {
                self.loader = true;

                await api.deleteVirtualExpo(item.id)
                await self.load();

                self.loader = false;
            }
        },

        close() {
            this.dialog = false;
            this.$nextTick(() => {
                this.editedItem = Object.assign({}, this.defaultItem);
                this.editedIndex = -1;
            });
        },

        async save(item) {
            const self = this;

            self.loader = true;

            let formData = new FormData();
            formData.append('banner_image', item.banner_image);
            formData.append('banner_image_left', item.banner_image_left);

            for (let file of item.attachments) {
                formData.append('attachments[]', file);
            }

            for (let file of item.attachments_epd) {
                formData.append('attachments_epd[]', file);
            }

            for (let id of item.remove_attachments) {
                formData.append('remove_attachments[]', id);
            }

            for (let video of item.videos) {
                formData.append('videos[]', video);
            }

            for (let role of item.roles) {
                formData.append('roles[]', role);
            }

            formData.append('company_name', item.company_name);
            formData.append('description', item.description);
            formData.append('is_active', (item.is_active ? '1' : '0'));
            formData.append('date_start', item.date_start);
            formData.append('date_end', item.date_end);

            try {
                await api.storeVirtualExpo(formData, item.id)
            } catch (e) {
                console.log(e);
                self.loader = false;
                alert('Error storing virtual expo: ' + e.message)

                return
            }

            await self.load();

            self.loader = false;
            self.close();
        },

        async updateExpoCheckbox(expoId, key, value) {
            try {
                await api.updateExpoCheckbox(expoId, key, value);
            } catch (e) {
                console.log(e);
            }
        },

        canEditChecklist(expoUserId) {
            return this.isAdmin() || this.$store.getters.getSession.user.id === expoUserId;
        }
    },
};
</script>

<style>
    .expo-list {
        margin-top: 10px;
        max-width: 100%;
    }

    .expo-item {
        margin-bottom: 10px;
        background: white;
        border-radius: 2px;
        border: 1px solid lightgrey;
    }

    .expo-buttons {
        max-width: 276px;
        text-align: center;
        display: block;
        margin: 0 auto;
    }

    .expo-buttons > .v-btn {
        margin-bottom: 8px;
    }

    .expo-image {
        max-width: 100%;
        object-fit: contain;
    }

    .expo-checklist {
        list-style: none;
    }

    .expo-checklist > li {
        padding-top: 12px;
    }

    .expo-checkbox label {
        margin: 0;
        font-size: 18px;
    }

    .expo-checkbox .v-icon {
        font-size: 30px;
        margin-top: 2px;
    }
</style>
