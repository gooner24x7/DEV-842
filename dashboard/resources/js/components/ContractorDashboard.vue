<template>
    <div>
        <div class="dashboard-header">
            <div class="header-left">Dashboard</div>
            <div class="header-right">Good Morning {{ user().first_name }} <br>Welcome to your Dashboard</div>
        </div>

        <v-row>
            <v-col cols="12" md="6">
                <v-card style="height: 400px; overflow-y: scroll;">
                    <v-card-title>Projects</v-card-title>
                    <v-card-text>
<!--                        <div style="overflow-y: scroll; max-height: 500px">-->
                            <v-data-table
                                :headers="headers.projects"
                                :items="projects"
                                :options.sync="options.projects"
                                :loading="loaders.projects"
                                item-key="id"
                                loading-text="Loading... Please wait"
                                class="data-table-mini"
                                hide-default-footer
                                dense
                            >
                                <template v-slot:top></template>

                                <template v-slot:item.wd_report="{ item }">
                                    <a href="#" v-on:click="(e) => { e.preventDefault(); goToReport(item) }">
                                        <v-icon>mdi-download</v-icon>
                                    </a>
                                </template>

                                <template v-slot:item.actions="{ item }">
                                    <div class="text-center">
                                        <v-btn class="primary" small @click="goToProject(item)">View Project</v-btn>
                                    </div>
                                </template>
                            </v-data-table>
<!--                        </div>-->
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="6">
                <v-card style="height: 400px; overflow-y: scroll;">
                    <v-card-title>Enquiries</v-card-title>
                    <v-card-text>
<!--                        <div style="overflow-y: scroll; max-height: 500px">-->
                            <v-data-table
                                :headers="headers.enquiries"
                                :items="enquiries"
                                :options.sync="options.enquiries"
                                :loading="loaders.enquiries"
                                item-key="id"
                                loading-text="Loading... Please wait"
                                class="data-table-mini"
                                hide-default-footer
                                dense
                            >
                                <template v-slot:top></template>

                                <template v-slot:item.published_at="{ item }">
                                    {{ item.published_at | formatDate }}
                                </template>

                                <template v-slot:item.actions="{ item }">
                                    <div class="text-center">
                                        <v-btn class="primary" small @click="goToQuotes(item)">View Quotes</v-btn>
                                    </div>
                                </template>
                            </v-data-table>
<!--                        </div>-->
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-row>
            <v-col cols="12" md="6">
                <v-card style="height: 400px;">
                    <v-card-title>Manage Supply Chain</v-card-title>
                    <v-card-text style="padding: 75px 40px 40px 40px;">
                        <v-row>
                            <v-col cols="12" md="4">
                                <stat-card
                                    title="Total Subcontractors"
                                    :value="scmData.total_subcontractors"
                                    card-class="stat-card-info"
                                />
                            </v-col>
                            <v-col cols="12" md="4">
                                <stat-card
                                    title="Compliant"
                                    :status="1"
                                    :value="calculatePercentage(scmData.total_compliant, scmData.total_subcontractors)"
                                    card-class="stat-card-yes"
                                />
                            </v-col>
                            <v-col cols="12" md="4">
                                <stat-card
                                    title="Not Compliant"
                                    :status="0"
                                    :value="calculatePercentage((scmData.total_subcontractors - scmData.total_compliant), scmData.total_subcontractors)"
                                    card-class="stat-card-no"
                                />
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="6">
                <v-card style="height: 400px; overflow-y: scroll;">
                    <v-card-title>
                        <v-row>
                            <v-col cols="12" md="6">
                                <h4>Live Chats</h4>
                            </v-col>
                            <v-col cols="12" md="6">
                                <h4>Clarifications</h4>
                            </v-col>
                        </v-row>
                    </v-card-title>
                    <v-card-text>
                        <v-row>
                            <v-col cols="12" md="6">
                                <v-data-table
                                    :headers="headers.messages"
                                    :items="messages"
                                    :loading="loaders.messages"
                                    item-key="id"
                                    loading-text="Loading... Please wait"
                                    class="data-table-mini"
                                    hide-default-footer
                                    dense
                                >
                                    <template v-slot:top></template>

                                    <template v-slot:item.created_at="{ item }">
                                        {{ item.created_at | formatDate }}
                                    </template>

                                    <template v-slot:item.actions="{ item }">
                                        <div class="text-center">
                                            <v-icon>mdi-forum</v-icon>
                                        </div>
                                    </template>
                                </v-data-table>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-data-table
                                    :headers="headers.wpQuestions"
                                    :items="wpQuestions"
                                    :loading="loaders.wpQuestions"
                                    item-key="id"
                                    loading-text="Loading... Please wait"
                                    class="data-table-mini"
                                    hide-default-footer
                                    dense
                                >
                                    <template v-slot:top></template>

                                    <template v-slot:item.created_at="{ item }">
                                        {{ item.created_at | formatDate }}
                                    </template>

                                    <template v-slot:item.actions="{ item }">
                                        <div class="text-center">
                                            <v-icon @click="showWorksPackageQuestionsDialog(item)">mdi-frequently-asked-questions</v-icon>
                                        </div>
                                    </template>
                                </v-data-table>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-row>
            <v-col>
                <virtual-expo-gold-carousel></virtual-expo-gold-carousel>
            </v-col>
        </v-row>

        <v-dialog v-model="worksPackageQuestionDialog" max-width="600px">
            <v-card>
                <v-card-title>
                    <span class="headline">Clarification Details</span>
                </v-card-title>

                <v-card-text>
                    <h5>Question from {{ selectedWorksPackageQuestion.user_name }}</h5>
                    <div>{{ selectedWorksPackageQuestion.created_at }}</div>
                    <v-textarea
                        v-model="selectedWorksPackageQuestion.question"
                        readonly
                    ></v-textarea>

                    <h5>Answered by {{ selectedWorksPackageQuestion.answer_user_name }}</h5>
                    <div>{{ selectedWorksPackageQuestion.answered_at }}</div>
                    <v-textarea
                        v-model="selectedWorksPackageQuestion.answer"
                        readonly
                    ></v-textarea>
                </v-card-text>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import api from "../common/api";
import StatCard from "./StatCard.vue";
import VirtualExpoGoldCarousel from "./VirtualExpoGoldCarousel.vue";

export default {
    name: 'ContractorDashboard',
    components: {VirtualExpoGoldCarousel, StatCard},
    props: [],
    data() {
        return {
            scmData: {
                total_subcontractors: 0,
                total_compliant: 0
            },
            projects: [],
            enquiries: [],
            messages: [
                {id: 1, project_name: 'Project Demo 1', works_package_name: 'Demo W1', created_at: '2025-03-19 12:00:00', message: 'Hello'},
                {id: 2, project_name: 'Project Demo 2', works_package_name: 'Demo W2', created_at: '2025-06-05 12:00:00', message: 'Hello'},
                {id: 3, project_name: 'Project Demo 3', works_package_name: 'Demo W3', created_at: '2025-11-16 12:00:00', message: 'Hello'},
                {id: 4, project_name: 'Project Demo 4', works_package_name: 'Demo W4', created_at: '2026-01-20 12:00:00', message: 'Hello'},
                {id: 5, project_name: 'Project Demo 5', works_package_name: 'Demo W5', created_at: '2026-02-11 12:00:00', message: 'Hello'}
            ],
            selectedWorksPackageQuestion: {
                id: null,
                project_id: null,
                works_package_id: null,
                user_id: null,
                user_name: '',
                answer_user_id: null,
                answer_user_name: '',
                question: '',
                answer: '',
                created_at: '',
                answered_at: '',
            },
            worksPackageQuestionDialog: false,
            worksPackageIds: [],
            wpQuestions: [],
            loaders: {
                projects: false,
                enquiries: false,
                messages: false,
                wpQuestions: false,
            },
            options: {
                projects: {
                    page: 1,
                    itemsPerPage: 10,
                    sortBy: ["created_at"],
                    sortDesc: [true],
                    groupBy: [],
                    groupDesc: [],
                    multiSort: false,
                    mustSort: false,
                },
                enquiries: {
                    page: 1,
                    itemsPerPage: 10,
                    sortBy: ["published_at"],
                    sortDesc: [true],
                    groupBy: [],
                    groupDesc: [],
                    multiSort: false,
                    mustSort: false,
                },
                wpQuestions: {
                    page: 1,
                    itemsPerPage: 10,
                    sortBy: ["created_at"],
                    sortDesc: [true],
                    groupBy: [],
                    groupDesc: [],
                    multiSort: false,
                    mustSort: false,
                },
            },
            headers: {
                projects: [
                    {
                        text: "ID",
                        value: "id",
                        sortable: true,
                    },
                    {
                        text: "Name",
                        value: "name",
                        sortable: true,
                    },
                    {
                        text: "Created",
                        value: "created_at",
                        sortable: true,
                    },
                    {
                        text: "WD Report",
                        value: "wd_report",
                        sortable: false,
                    },
                    {
                        text: "",
                        value: "actions",
                        sortable: false,
                    }
                ],
                enquiries: [
                    {
                        text: "ID",
                        value: "id",
                        sortable: true,
                    },
                    {
                        text: "Project",
                        value: "project_name",
                        sortable: true,
                    },
                    {
                        text: "Works Package",
                        value: "works_package_name",
                        sortable: true,
                    },
                    {
                        text: "Published",
                        value: "published_at",
                        sortable: true,
                    },
                    {
                        text: "",
                        value: "actions",
                        sortable: false,
                    }
                ],
                messages: [
                    {
                        text: "ID",
                        value: "id",
                        sortable: true,
                    },
                    {
                        text: "Project",
                        value: "project_name",
                        sortable: true,
                    },
                    {
                        text: "Works Package",
                        value: "works_package_name",
                        sortable: true,
                    },
                    {
                        text: "Created",
                        value: "created_at",
                        sortable: true,
                    },
                    {
                        text: "",
                        value: "actions",
                        sortable: false,
                    }
                ],
                wpQuestions: [
                    {
                        text: "ID",
                        value: "id",
                        sortable: true,
                    },
                    {
                        text: "Project",
                        value: "project_name",
                        sortable: true,
                    },
                    {
                        text: "Works Package",
                        value: "works_package_name",
                        sortable: true,
                    },
                    {
                        text: "Created",
                        value: "created_at",
                        sortable: true,
                    },
                    {
                        text: "",
                        value: "actions",
                        sortable: false,
                    }
                ]
            }
        };
    },
    async mounted() {
        const self = this;

        await self.loadProjects();
        await self.loadEnquiries();
        await self.loadWpQuestions();
        await self.getDashboardStats();
    },
    watch: {
        options: {
            async handler(n, o) {
                await this.loadProjects();
                await this.loadEnquiries();
                await this.loadWpQuestions();
            },
            deep: true,
        },
    },
    methods: {
        async loadProjects() {
            const self = this;

            let params = {
                ...self.options.projects,
                page: self.options.projects.page,
                itemsPerPage: self.options.projects.itemsPerPage,
                sortBy: self.options.projects.sortBy[0],
                sortDesc: self.options.projects.sortDesc[0] ? 1 : 0,
            };

            const response = await api.loadProjects(params);
            self.projects = response.data;
        },
        async loadEnquiries() {
            const self = this;

            let params = {
                ...self.options.enquiries,
                page: self.options.enquiries.page,
                itemsPerPage: self.options.enquiries.itemsPerPage,
                sortBy: self.options.enquiries.sortBy[0],
                sortDesc: self.options.enquiries.sortDesc[0] ? 1 : 0,
            };

            const response = await api.loadSupplyFitEnquiries(params);
            self.enquiries = response.data.data;

            self.worksPackageIds = [];

            for (let enquiry of self.enquiries) {
                if (enquiry.works_package_id && !self.worksPackageIds.includes(enquiry.works_package_id)) {
                    self.worksPackageIds.push(enquiry.works_package_id);
                }
            }
        },
        async loadWpQuestions() {
            const self = this;

            let params = {
                ...self.options.wpQuestions,
                page: self.options.wpQuestions.page,
                itemsPerPage: self.options.wpQuestions.itemsPerPage,
                sortBy: self.options.wpQuestions.sortBy[0],
                sortDesc: self.options.wpQuestions.sortDesc[0] ? 1 : 0,
                worksPackageIds: self.worksPackageIds,
            };

            const response = await api.loadWorksPackageQuestions(params);
            self.wpQuestions = response.data.data;
        },
        async getDashboardStats() {
            const self = this;

            const response = await api.getDashboardStats();
            self.scmData = response.data;
        },
        showWorksPackageQuestionsDialog(item) {
            const self = this;

            self.selectedWorksPackageQuestion = item;
            self.worksPackageQuestionDialog = true;
        },
        goToProject(project) {
            let url = '/projects/' + encodeURIComponent(project.id) + '/works-packages';
            this.$router.push(url);
        },
        goToReport(project) {
            this.$router.push({
                name: "contractor-report",
                query: {
                    id: project.id
                }
            });
        },
        goToQuotes(enquiry) {
            let url = '/supply-fit-enquiries/' + encodeURIComponent(enquiry.id) + '/quotes';
            this.$router.push(url);
        },
        user() {
            return this.$store.getters.getSession.user;
        },
        calculatePercentage(val1, val2) {
            if (val2 === 0) {
                return '0%';
            }

            return ((val1 / val2) * 100).toFixed(0) + '%';
        }
    }
}
</script>

<style scoped>
.dashboard-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.header-left {
    font-size: 2em;
}

.header-right {
    font-size: 1.3em;
    text-align: right;
}
</style>
