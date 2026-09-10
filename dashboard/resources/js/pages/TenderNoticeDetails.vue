<template>
    <div style="margin: 1em">
        <v-container v-if="notice !== null">

            <v-dialog v-model="newContactDialog" max-width="500px">
                <v-card>
                    <v-card-title>
                        <span class="headline">New Contact</span>
                    </v-card-title>
                    <v-card-text>
                        <v-row>
                            <v-col>
                                <v-text-field
                                    v-model="form.name"
                                    label="Name"
                                    dense
                                    outlined
                                ></v-text-field>
                                <v-text-field
                                    v-model="form.company"
                                    label="Company"
                                    dense
                                    outlined
                                ></v-text-field>
                                <v-text-field
                                    v-model="form.phone"
                                    label="Phone"
                                    dense
                                    outlined
                                ></v-text-field>
                            </v-col>
                            <v-col>
                                <v-text-field
                                    v-model="form.email"
                                    label="Email"
                                    dense
                                    outlined
                                ></v-text-field>
                                <v-text-field
                                    v-model="form.linkedin"
                                    label="LinkedIn"
                                    dense
                                    outlined
                                ></v-text-field>
                            </v-col>
                        </v-row>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="blue darken-1" text @click="saveContact()">Save</v-btn>
                        <v-btn color="blue darken-1" text @click="closeDialog()">Cancel</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <v-row>
                <v-col>
                    <v-btn
                        @click="convert()"
                        color="primary"
                        class="float-right"
                    >
                        Add to Workspace
                    </v-btn>
                </v-col>
            </v-row>

            <v-row>
                <v-col>
                    <v-card>
                        <v-card-title class="justify-space-between">
                            <div class="notice-title">Release Details</div>
                            <v-btn
                                class="notice-link"
                                :href="responseData.uri"
                                icon
                            >
                                <v-icon>mdi-link</v-icon>
                            </v-btn>
                        </v-card-title>
                        <v-card-text>
                            <v-row>
                                <v-col cols="12" md="4">
                                    <ul class="release-details-list">
                                        <li>
                                            <div class="item-header">OCID</div>
                                            <div class="item-value">{{ notice.ocid }}</div>
                                        </li>
                                        <li>
                                            <div class="item-header">ID</div>
                                            <div class="item-value">{{ notice.id }}</div>
                                        </li>
                                    </ul>
                                </v-col>
                                <v-col cols="12" md="4">
                                    <ul class="release-details-list">
                                        <li>
                                            <div class="item-header">Language</div>
                                            <div class="item-value">{{ notice.language }}</div>
                                        </li>
                                        <li>
                                            <div class="item-header">Date</div>
                                            <div class="item-value">{{ notice.date | formatDateTime }}</div>
                                        </li>
                                    </ul>
                                </v-col>
                                <v-col cols="12" md="4">
                                    <ul class="release-details-list">
                                        <li>
                                            <div class="item-header">Tags</div>
                                            <div class="item-value">
                                                <v-chip-group>
                                                    <v-chip v-for="(tag, index) in notice.tag" :key="index">{{ tag }}</v-chip>
                                                </v-chip-group>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="item-header">Initiation Type</div>
                                            <div class="item-value">{{ notice.initiationType }}</div>
                                        </li>
                                    </ul>
                                </v-col>
                            </v-row>
                        </v-card-text>

                    </v-card>
                </v-col>
            </v-row>
            <v-row>
                <v-col cols="12" md="6">
                    <v-card>
                        <v-card-title>Tender</v-card-title>
                        <v-card-text>
                            <template v-for="(value, key) in notice.tender">
                                <template v-if="typeof value === 'object' && value.constructor !== Array">
                                    <div class="sub-item">
                                        <h5 class="sub-heading">{{ formatFieldName(key) }}</h5>
                                        <template v-for="(subValue, subKey) in value">
                                            <v-row>
                                                <v-col sm="2">
                                                    <div class="field-label">{{ formatFieldName(subKey) }}</div>
                                                </v-col>
                                                <v-col sm="10">
                                                    <v-text-field
                                                        :value="subValue"
                                                        :type="getFieldType(subValue)"
                                                        disabled
                                                        dense
                                                        outlined
                                                        hide-details
                                                    >
                                                    </v-text-field>
                                                </v-col>
                                            </v-row>
                                        </template>
                                    </div>
                                </template>
                                <template v-else-if="Array.isArray(value) && key !== 'items'">
                                    <div class="sub-item">
                                        <h5 class="sub-heading">{{ formatFieldName(key) }}</h5>
                                        <template v-for="(arrayValue, arrayKey) in value">
                                            <template v-if="typeof arrayValue === 'object' && arrayValue.constructor !== Array">
                                                <template v-for="(subValue, subKey) in arrayValue">
                                                    <v-row>
                                                        <v-col sm="2">
                                                            <div class="field-label">{{ formatFieldName(subKey) }}</div>
                                                        </v-col>
                                                        <v-col sm="10">
                                                            <v-text-field
                                                                :value="subValue"
                                                                :type="getFieldType(subValue)"
                                                                disabled
                                                                dense
                                                                outlined
                                                                hide-details
                                                            >
                                                            </v-text-field>
                                                        </v-col>
                                                    </v-row>
                                                </template>
                                                <hr>
                                            </template>
                                        </template>
                                    </div>
                                </template>
                                <template v-else-if="typeof value === 'string' || typeof value === 'number'">
                                    <v-row>
                                        <v-col sm="2">
                                            <div class="field-label">{{ formatFieldName(key) }}</div>
                                        </v-col>
                                        <v-col sm="10">
                                            <v-textarea
                                                v-if="key === 'description'"
                                                :value="value"
                                                :type="getFieldType(value)"
                                                disabled
                                                dense
                                                outlined
                                                hide-details
                                            >
                                            </v-textarea>
                                            <v-text-field
                                                v-else
                                                :value="value"
                                                :type="getFieldType(value)"
                                                disabled
                                                dense
                                                outlined
                                                hide-details
                                            >
                                            </v-text-field>
                                        </v-col>
                                    </v-row>
                                </template>
                            </template>
                        </v-card-text>
                    </v-card>

                    <v-card class="mt-5">
                        <v-card-title>Buyer</v-card-title>
                        <v-card-text>
                            <template v-for="(value, key) in notice.buyer">
                                <template v-if="typeof value === 'object' && value.constructor !== Array">
                                    <h5 class="sub-heading">{{ formatFieldName(key) }}</h5>
                                    <template v-for="(subValue, subKey) in value">
                                        <v-row>
                                            <v-col sm="2">
                                                <div class="field-label">{{ formatFieldName(subKey) }}</div>
                                            </v-col>
                                            <v-col sm="10">
                                                <v-text-field
                                                    :value="subValue"
                                                    :type="getFieldType(subValue)"
                                                    disabled
                                                    dense
                                                    outlined
                                                    hide-details
                                                >
                                                </v-text-field>
                                            </v-col>
                                        </v-row>
                                    </template>
                                </template>
                                <template v-else-if="typeof value === 'string' || typeof value === 'number'">
                                    <v-row>
                                        <v-col sm="2">
                                            <div class="field-label">{{ formatFieldName(key) }}</div>
                                        </v-col>
                                        <v-col sm="10">
                                            <v-text-field
                                                :value="value"
                                                :type="getFieldType(value)"
                                                disabled
                                                dense
                                                outlined
                                                hide-details
                                            >
                                            </v-text-field>
                                        </v-col>
                                    </v-row>
                                </template>
                            </template>
                        </v-card-text>
                    </v-card>

                    <v-card class="mt-5">
                        <v-card-title class="justify-space-between">
                            <div class="notice-title">Contacts</div>
                            <v-btn id="newContactBtn" color="primary" @click="newContactDialog = true">Add Contact</v-btn>
                        </v-card-title>
                        <v-card-text>
                            <v-sheet border rounded>
                                <v-data-table
                                    id="contactsTable"
                                    :headers="headers"
                                    :items="contacts"
                                    :loading="loader"
                                    item-key="id"
                                    loading-text="Loading... Please wait"
                                    mobile-breakpoint="1000"
                                    :header-props="{ sortIcon: null }"
                                    hide-default-footer
                                >
                                    <template v-slot:item.linkedin="{ item }">
                                        <a :href="item.linkedin">{{ item.linkedin }}</a>
                                    </template>
                                </v-data-table>
                            </v-sheet>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12" md="6">
                    <v-card>
                        <v-card-title>Parties</v-card-title>
                        <v-card-text>
                            <div class="parties-item" v-for="(item, index) in notice.parties" :key="index">
                                <template  v-for="(value, key) in item">
                                    <template v-if="typeof value === 'object' && value.constructor !== Array">
                                        <div class="sub-item">
                                            <h5 class="sub-heading">{{ formatFieldName(key) }}</h5>
                                            <template v-for="(subValue, subKey) in value">
                                                <v-row>
                                                    <v-col sm="2">
                                                        <div class="field-label">{{ formatFieldName(subKey) }}</div>
                                                    </v-col>
                                                    <v-col sm="10">
                                                        <v-text-field
                                                            :value="subValue"
                                                            :type="getFieldType(subValue)"
                                                            disabled
                                                            dense
                                                            outlined
                                                            hide-details
                                                        >
                                                        </v-text-field>
                                                    </v-col>
                                                </v-row>
                                            </template>
                                        </div>
                                    </template>
                                    <template v-else-if="typeof value === 'string' || typeof value === 'number'">
                                        <v-row>
                                            <v-col sm="2">
                                                <div class="field-label">{{ formatFieldName(key) }}</div>
                                            </v-col>
                                            <v-col sm="10">
                                                <v-text-field
                                                    :value="value"
                                                    :type="getFieldType(value)"
                                                    disabled
                                                    dense
                                                    outlined
                                                    hide-details
                                                >
                                                </v-text-field>
                                            </v-col>
                                        </v-row>
                                    </template>
                                </template>
                                <v-divider v-if="(index + 1) < notice.parties.length"></v-divider>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>
        </v-container>
        <div v-else>
            Notice not found
        </div>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions.js";
import tenderNotices from "./TenderNotices.vue";

export default {
    name: 'TenderNoticeDetails',
    components: {

    },
    data() {
        return {
            loader: false,
            noticeId: null,
            notice: null,
            responseData: null,
            newContactDialog: false,
            cpvCodes: [],
            form: {
                name: '',
                company: '',
                phone: '',
                email: '',
                linkedin: '',
            },
            headers: [
                {
                    text: "Name",
                    value: "name",
                    sortable: true,
                },
                {
                    text: "Company",
                    value: "company",
                    sortable: true,
                },
                {
                    text: "Phone",
                    value: "phone",
                    sortable: true,
                },
                {
                    text: "Email",
                    value: "email",
                    sortable: true,
                },
                {
                    text: "LinkedIn",
                    value: "linkedin",
                    sortable: true,
                },
            ],
            contacts: [
                {
                    id: 1,
                    name: "John Smith",
                    company: "Willmott Dixon",
                    phone: "+44 22 7049 9475",
                    email: "john@wilmottdixon.com",
                    linkedin: "https://uk.linkedin.com/company/willmott-dixon"
                },
                {
                    id: 2,
                    name: "Ronald Hughes",
                    company: "Moortown Group Ltd",
                    phone: "+44 75 8781 7096",
                    email: "ronald@moortowngroup.com",
                    linkedin: "https://uk.linkedin.com/company/moortown-group-limited"
                },
                {
                    id: 3,
                    name: "Stephen Petersen",
                    company: "Brebur Ltd",
                    phone: "+44 54 8653 1523",
                    email: "stephen@brebur.com",
                    linkedin: "https://uk.linkedin.com/company/brebur-ltd"
                },
                {
                    id: 4,
                    name: "Deborah Kline",
                    company: "Marlborough Construction Services Ltd",
                    phone: "+44 22 7049 9475",
                    email: "deborah@marlboroughcs.com",
                    linkedin: "https://uk.linkedin.com/company/marlborough-cs"
                },
            ]
        };
    },

    async mounted() {
        const self = this;

        self.noticeId = self.$route.query.id ?? null;

        if (self.noticeId !== null) {
            await self.load();
        }
    },

    watch: {

    },

    computed: {

    },

    methods: {
        async load() {
            const self = this;
            self.loader = true;

            try {
                const response = await api.getTenderNotice(self.noticeId);

                self.notice = response.data.releases[0];
                self.responseData = response.data;
            } catch (e) {
                console.log(e);
            }

            self.loader = false;
        },

        formatFieldName(key) {
            if (typeof key !== 'string') {
                return key;
            }

            let words = key.match(/[A-Za-z][a-z]*/g) || [];

            return words.map(function(word) {
                return word.charAt(0).toUpperCase() + word.substring(1);
            }).join(" ");
        },

        getFieldType(value) {
            if (typeof value === 'number') {
                return 'number';
            }

            return 'text';
        },

        ucFirst(val) {
            return String(val).charAt(0).toUpperCase() + String(val).slice(1);
        },

        saveContact() {
            const self = this;

            if (self.form.name !== '') {
                self.contacts.push(self.form);
            }

            self.closeDialog();
        },

        closeDialog() {
            const self = this;

            self.newContactDialog = false;
        },

        async convert() {
            const self = this;

            try {
                const response = await api.convertTenderNotice(self.notice);

                self.$store.commit("showSnackbar", {
                    message: "Workspace project created",
                    color: "success",
                });

            } catch (e) {
                console.log(e);

                self.$store.commit("showSnackbar", {
                    message: e.response?.data ?? "An error occurred",
                    color: "error",
                });
            }
        }
    }
};
</script>

<style scoped>
.release-details-list {
    list-style: none;
}

.release-details-list > li {
    margin-bottom: 20px;
}

.release-details-list .item-header {
    font-weight: bold;
}

.release-details-list .item-value {
    font-size: 16px;
}

.sub-item {
    border: 1px solid lightgrey;
    border-radius: 5px;
    padding: 20px;
    margin: 20px 0;
}

.sub-item > h5 {
    margin-bottom: 20px;
}

.notice-link:after, .notice-link:before {
    content: none!important;
}

.notice-link:hover, .notice-link:hover > span {
    color: #5876ee!important;
    text-decoration: none;
}

#contactsTable {
    margin-top: 0!important;
    background-color: #fff!important;
    border: 1px solid #cdcdcd;
}

.theme--light.v-data-table>.v-data-table__wrapper>table>tbody>tr:not(:last-child)>td:last-child, .theme--light.v-data-table>.v-data-table__wrapper>table>tbody>tr:not(:last-child)>td:not(.v-data-table__mobile-row), .theme--light.v-data-table>.v-data-table__wrapper>table>tbody>tr:not(:last-child)>th:last-child, .theme--light.v-data-table>.v-data-table__wrapper>table>tbody>tr:not(:last-child)>th:not(.v-data-table__mobile-row), .theme--light.v-data-table>.v-data-table__wrapper>table>thead>tr:last-child>th {
    border-bottom: thin solid rgba(0, 0, 0, .12)!important;
}

#newContactBtn {
    color: #fff!important;
}
</style>
