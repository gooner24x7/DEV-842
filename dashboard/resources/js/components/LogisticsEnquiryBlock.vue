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
                    <div class="expanded-comment">{{ item.comments }}</div>
                </td>
            </template>

            <template v-slot:item.comments="{ item }">
                {{ item.comments | limit20Chars }}
            </template>

            <template v-slot:item.created_at="{ item }">
                {{ item.created_at | formatDate }}
            </template>

            <template v-slot:item.attachments="{ item }">
                <a href="#" v-on:click="(e) => { e.preventDefault(); dialogUploadItem = item; dialogUploads = true; }">
                    <v-icon>mdi-download</v-icon>
                </a>
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
                type="LogisticsEnquiry"
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
    name: "LogisticsEnquiryBlock",

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
                    text: "Type",
                    value: "type_str",
                    sortable: true,
                },
                {
                    text: "Comments",
                    value: "comments",
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
                    text: "",
                    value: "actions",
                    sortable: false,
                },
            ],
        }
    },

    watch: {

    },

    methods: {
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
    },
}
</script>

<style scoped>

</style>
