<template>
    <div style="margin: 1em">
        <div v-if="isContractor() || isAdmin()" class="mb-2">
            <v-btn class="primary" v-on:click="goToEnquiries()">Enquiries</v-btn>
            <v-btn :disabled=true class="primary">Projects</v-btn>
        </div>

<!--        <h2>Projects</h2>-->

        <v-dialog v-model="dialogConvert" max-width="900px">
            <v-card>
                <v-card-title>
                    Convert Project
                    <v-spacer></v-spacer>
                    <v-btn icon @click="dialogConvert = false"><v-icon>mdi-close</v-icon></v-btn>
                </v-card-title>
                <v-card-subtitle>
                    You are about to convert this project to the next stage.<br>
                    Please select the data you wish to copy below.
                </v-card-subtitle>
                <v-card-text>
                    <v-row>
                        <v-col>
                            <div class="ml-2">
                                <div class="text-subtitle-1 font-weight-bold mb-1">Copy Data</div>
                                <div class="text-body-2 mb-3">Copy the following data your new project:</div>
                                <div>
                                    <v-checkbox
                                        v-model="convertForm.copyWorksPackages"
                                        label="Copy works packages"
                                        :ripple="false"
                                        dense
                                        hide-details
                                    ></v-checkbox>
                                    <v-checkbox
                                        v-model="convertForm.copyUsers"
                                        label="Copy project assigned users"
                                        :ripple="false"
                                        dense
                                        hide-details
                                    ></v-checkbox>
                                    <v-checkbox
                                        v-model="convertForm.copyProjectInfo"
                                        label="Copy project information"
                                        :ripple="false"
                                        dense
                                        hide-details
                                    ></v-checkbox>
                                    <v-checkbox
                                        v-model="convertForm.copyDocuments"
                                        label="Copy project documents"
                                        :ripple="false"
                                        dense
                                        hide-details
                                    ></v-checkbox>
                                </div>
                            </div>
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn text @click="dialogConvert = false">Cancel</v-btn>
                    <v-btn color="primary" @click="submitConvert">
                        Continue <v-icon right>mdi-arrow-right</v-icon>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="dialogBoq" max-width="600px">
            <v-card>
                <v-card-title>Upload File</v-card-title>
                <v-card-text>
                    <p>Please upload your bill of quantities file</p>
                    <p>Accepted file types: .csv, .xls, .xlsx</p>
                    <v-file-input
                        v-model="boqFile"
                        name="boq_file"
                        id="boq_file"
                        accept=".csv, .xls, .xlsx"
                        label="Select File"
                    ></v-file-input>

                    <v-select
                        v-model="selectedBoqTemplate"
                        label="Select Template"
                        :items="boqTemplates"
                    ></v-select>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="blue darken-1" text @click="dialogBoq = false">Cancel</v-btn>
                    <v-btn color="blue darken-1" text @click="uploadBoqFile()">Upload</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="dialogPreview" max-width="900px">
            <works-packages-preview
                :preview-data="previewData"
                @close="dialogPreview = false"
                @save="createWorksPackages"
            ></works-packages-preview>
        </v-dialog>

        <v-dialog v-model="dialog" max-width="900px">
            <project-form
                :project="editedItem"
                :tab="tab"
                :key="projectFormKey"
                :type="projectType"
                v-on:cancel="close"
                v-on:saved="saved"
            />
        </v-dialog>

        <v-data-table
            :options="options"
            :expanded.sync="expanded"
            :headers="headers"
            :items="items"
            :loading="loader"
            item-key="id"
            loading-text="Loading... Please wait"
            show-expand
            @click:row="toggleExpanded"
        >
            <template v-slot:top>
                <v-row>
                    <v-col cols="12" md="3">
                        <v-btn
                            class="m-2"
                            color="primary"
                            @click="newProject()"
                            v-if="canEditProjects()"
                        >
                            New
                        </v-btn>
                    </v-col>
                    <v-col cols="12" md="3">

                    </v-col>
                    <v-col cols="12" md="3">

                    </v-col>
                    <v-col cols="12" md="3">
                        <v-autocomplete
                            v-model="filters.region"
                            :items="regionOptions"
                            label="Region"
                            dense
                            outlined
                            hide-details
                        ></v-autocomplete>
                    </v-col>
                </v-row>
            </template>

            <template v-slot:item.stage="{ item }">
                <span class="project-stage-badge" :class="stageBadgeClass(item)">
                    {{ stageBadgeLabel(item) }}
                </span>
            </template>

            <template v-slot:item.actions="{ item }">
                <div class="text-center" @click.stop>
                    <v-btn small class="primary" @click="view(item.id)">View</v-btn>
                    <v-btn small class="primary" @click="edit(item)" v-if="canEditProjects()">Edit</v-btn>
                    <v-btn small class="primary" @click="updateType(item.id)" v-if="canMoveProject(item)">Move to TBC Projects</v-btn>

                    <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                v-if="canEditProjects()"
                                class="mr-2"
                                color="black"
                                icon
                                small
                                v-bind="attrs"
                                @click="archiveItem(item)"
                                v-on="on"
                            >
                                <v-icon>
                                    mdi-folder-open
                                </v-icon>
                            </v-btn>
                        </template>
                        <span>Archive Project</span>
                    </v-tooltip>

                    <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                class="mr-2"
                                color="black"
                                icon
                                small
                                v-bind="attrs"
                                @click="showReport(item)"
                                v-on="on"
                            >
                                <v-icon>
                                    mdi-chart-pie
                                </v-icon>
                            </v-btn>
                        </template>
                        <span>Reports</span>
                    </v-tooltip>

                    <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                class="mr-2"
                                color="black"
                                icon
                                small
                                v-bind="attrs"
                                @click="showDocuments(item)"
                                v-on="on"
                            >
                                <v-icon>
                                    mdi-file-document
                                </v-icon>
                            </v-btn>
                        </template>
                        <span>Documents</span>
                    </v-tooltip>

                    <v-tooltip top v-if="canAssignUsers()">
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                class="mr-2"
                                color="black"
                                icon
                                small
                                v-bind="attrs"
                                @click="showUserAccessTab(item)"
                                v-on="on"
                            >
                                <v-icon>
                                    mdi-account
                                </v-icon>
                            </v-btn>
                        </template>
                        <span>Assign Users</span>
                    </v-tooltip>

                    <v-tooltip top v-if="canViewProgress()">
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                class="mr-2"
                                color="black"
                                icon
                                small
                                v-bind="attrs"
                                @click="showQuoteProgress(item)"
                                v-on="on"
                            >
                                <v-icon>
                                    mdi-text-box-search
                                </v-icon>
                            </v-btn>
                        </template>
                        <span>View Progress</span>
                    </v-tooltip>

                    <!--                    <v-icon large v-if="canArchive(item)" @click="archiveItem(item)">mdi-archive</v-icon>-->
                    <!--                    <v-icon large @click="showReport(item)">mdi-chart-pie</v-icon>-->
                    <!--                    <v-icon large @click="showDocuments(item)"></v-icon>-->
                </div>
            </template>

            <template v-slot:expanded-item="{ headers, item }">
                <td :colspan="headers.length" class="pa-0">
                    <div class="project-lifecycle-expanded" :class="stageExpandedClass(item)">
                        <div class="project-lifecycle-timeline">
                            <div
                                v-for="stage in lifecycleStages"
                                :key="stage.key"
                                class="project-lifecycle-timeline-item"
                                :class="timelineItemClass(item, stage)"
                            >
                                <div class="project-lifecycle-dot" :class="timelineDotClass(item, stage)">
                                    {{ lifecycleIcon(item, stage) }}
                                </div>
                                <div>
                                    <strong>
                                        {{ stage.title }}
                                    </strong>
                                    <p v-if="projectForLifecycleStage(item, stage)">
                                        Created: {{ projectForLifecycleStage(item, stage).created_at }}
                                    </p>
                                    <p v-else>
                                        Not yet created
                                    </p>
                                    <p v-if="isComplete(item) && isLastLifecycleStage(stage)">
                                        Completed: {{ item.completed_at }}
                                    </p>
                                    <p>
                                        Status:
                                        {{ lifecycleStatus(item, stage) }}
                                        <span
                                            v-if="isCurrentLifecycleStage(item, stage)"
                                            class="project-stage-badge project-stage-current"
                                            :class="currentBadgeClass(item)"
                                        >
                                            CURRENT
                                        </span>
                                        <span
                                            v-else-if="isComplete(item) && isLastLifecycleStage(stage)"
                                            class="project-stage-badge project-stage-badge-complete"
                                        >
                                            COMPLETE
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="project-lifecycle-progress">
                            <h3>Project Progress</h3>

                            <div class="project-lifecycle-progress-line">
                                <template v-for="(stage, index) in lifecycleStages">
                                    <div
                                        class="project-lifecycle-stage"
                                        :class="progressStageClass(item, stage)"
                                    >
                                        <div class="project-lifecycle-stage-icon">
                                            <v-icon>{{ progressIcon(item, stage) }}</v-icon>

                                        </div>
                                        <p>{{ stage.label }}</p>
                                        <small>{{ lifecycleStatus(item, stage) }}</small>
                                    </div>

                                    <div
                                        v-if="index < lifecycleStages.length - 1"
                                        :key="stage.key + '-connector'"
                                        class="project-lifecycle-connector"
                                        :class="connectorClass(item, stage)"
                                    ></div>
                                </template>
                            </div>

                            <div class="project-stage-card" :class="stageCardClass(item)">
                                <h4>{{ currentStageContent(item).title }}</h4>
                                <p>{{ currentStageContent(item).description }}</p>

                                <ul>
                                    <li
                                        v-for="point in currentStageContent(item).points"
                                        :key="point"
                                    >
                                        {{ point }}
                                    </li>
                                </ul>

                                <button
                                    v-if="currentStageContent(item).button"
                                    class="project-stage-action-btn"
                                    :class="stageButtonClass(item)"
                                    type="button"
                                    @click="onStageAction(item)"
                                >
                                    {{ currentStageContent(item).button }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <div class="project-lifecycle-side-card">
                                <h4>{{ currentStageContent(item).title }}</h4>
                                <p>{{ currentStageContent(item).sideDescription }}</p>
                                <p>{{ currentStageContent(item).sideHint }}</p>
                            </div>

                            <div class="project-lifecycle-side-card">
                                <h4>Import BOQ (Conquest)</h4>
                                <p>
                                    Use Estimation Solution Software (Conquest) to upload your BOQ and automatically
                                    create works packages in this project.
                                </p>
                                <button class="project-outline-btn" type="button" @click="showDialogBoq(item)">⬆ IMPORT BOQ</button>
                            </div>
                        </div>
                    </div>
                </td>
            </template>
        </v-data-table>

        <v-overlay :value="saving" z-index="9999">
            <div class="text-center">
                <v-progress-circular indeterminate size="64"></v-progress-circular>
                <h3 class="mt-3">Analysing file...</h3>
            </div>
        </v-overlay>

    </div>
</template>

<script>
import api from "../common/api.js";
import ProjectForm from "../components/ProjectForm";
import permissions from "../common/permissions";
import WorksPackagesPreview from "../components/WorksPackagesPreview.vue";

export default {
    props: [],
    components: {
        WorksPackagesPreview,
        ProjectForm
    },
    data() {
        return {
            loader: false,
            items: [],
            dialog: false,
            dialogBoq: false,
            dialogBoqItem: null,
            dialogConvert: false,
            dialogConvertItem: null,
            dialogPreview: false,
            dialogPreviewId: null,
            previewData: null,
            selectedPreviewItems: [],
            boqTemplates: [
                "NRM1 template.csv",
                "NRM2 template.csv",
                "WD template.csv",
            ],
            selectedBoqTemplate: null,
            expanded: [],
            reportData: [],
            editedIndex: -1,
            editedItem: null,
            tab: '',
            projectFormKey: 0,
            regionOptions: [],
            boqFile: null,
            saving: false,
            projectType: null,
            filters: {
                region: null
            },
            options: {
                page: 1,
                itemsPerPage: 10,
                sortBy: ["id"],
                sortDesc: [true],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false
            },
            headers: [
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
                    text: "Stage",
                    value: "stage",
                    sortable: true,
                },
                {
                    text: "Postcode",
                    value: "postcode",
                    sortable: true,
                },
                {
                    text: "Client name",
                    value: "client_name",
                    sortable: true,
                },
                {
                    text: "Framework",
                    value: "framework",
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
                    sortable: true,
                },
            ],
            lifecycleStages: [
                {
                    key: 1,
                    label: 'Pre-Tender',
                    title: 'PRE-TENDER PROJECT',
                    aliases: ['pre tender', 'pre-tender']
                },
                {
                    key: 2,
                    label: 'Tender',
                    title: 'TENDER PROJECT',
                    aliases: ['tender']
                },
                {
                    key: 3,
                    label: 'Live',
                    title: 'LIVE PROJECT',
                    aliases: ['live']
                }
            ],
            lifecycleContent: {
                1: {
                    title: 'Pre-Tender Stage',
                    description: 'You are in the Pre-Tender stage. Use this stage to engage your supply chain early, gather insights and prepare for tender.',
                    points: [
                        'Invite your supply chain to provide early budget pricing',
                        'Use insights and scores to shortlist the best performing subcontractors',
                        'Build stronger relationships and collaborate earlier',
                        'When you are ready, convert this project to the Tender stage'
                    ],
                    button: 'CONVERT PROJECT TO TENDER',
                    sideDescription: 'This project is currently in the Pre-Tender stage. Use this stage to run pre-tender enquiries, benchmark pricing and identify the best subcontractors.',
                    sideHint: 'When you are ready, convert this project to the Tender stage.'
                },
                2: {
                    title: 'Tender Stage',
                    description: 'You are in the Tender stage. Send tender enquiries to your shortlisted subcontractors, compare quotes and award the best value.',
                    points: [
                        'Send tender enquiries to your shortlisted supply chain',
                        'Compare quotes, scores and compliance',
                        'Award work packages to successful subcontractors',
                        'When you are ready, convert this project to the Live stage'
                    ],
                    button: '⚑ CONVERT PROJECT TO LIVE',
                    sideDescription: 'This project is currently in the Tender stage. Run tender enquiries, compare pricing, quality and compliance to award work packages.',
                    sideHint: 'When you are ready, convert this project to the Live stage.'
                },
                3: {
                    title: 'Live Stage',
                    description: 'You are in the Live stage. Manage your awarded work packages, track progress and collaborate with your supply chain.',
                    points: [
                        'Manage awarded work packages and subcontractors',
                        'Track progress, compliance and performance',
                        'Collaborate and share information in real time',
                        'Maintain records and monitor project delivery'
                    ],
                    button: '⚑ MARK PROJECT AS COMPLETE',
                    sideDescription: 'This project is currently in the Live stage. Manage your awarded work packages, track progress, compliance and performance.',
                    sideHint: 'Use the tools in this stage to deliver your project successfully.'
                }
            },
            completeContent: {
                title: 'Project Complete',
                description: 'This project has been completed. You can archive the project to keep your project list organised.',
                points: [
                    'All work packages have been delivered',
                    'All progress and compliance recorded',
                    'Project documentation complete',
                    'Ready for archiving'
                ],
                button: '▣ ARCHIVE PROJECT',
                sideDescription: 'This project is now complete. You can review final reports, performance and compliance records.',
                sideHint: 'When you are ready, archive this project.'
            },
            convertForm: {
                copyProjectInfo: false,
                copyWorksPackages: false,
                copyUsers: false,
                copyDocuments: false,
            }
        };
    },

    async mounted() {
        const self = this;

        self.projectType = self.$route.query.type ?? null;

        await self.load();
        await self.loadRegionOptions();
    },

    watch: {
        dialog(val) {
            val || this.close();
        },
        filters: {
            deep: true,
            handler() {
                this.load();
            }
        },
    },

    computed: {},

    methods: {
        async archiveItem(item) {
            const self = this;

            self.loader = true;
            try {
                await api.archiveProject(item.id);

                await self.load();
            } catch (e) {
                console.log(e);
            }

            self.loader = false;
        },

        canEditProjects() {
            return permissions.can(permissions.edit_projects);
        },

        canMoveProject(item) {
            const self = this;
            const userName = self.user().first_name;

            if (item.type !== 1) {
                return false;
            }

            if (item.client_name) {
                return userName === item.client_name;
            } else if (item.framework) {
                return userName === item.framework;
            } else {
                let filtered = item.assigned_users.filter((user) => {
                    return user.roles.indexOf('contractor') >= 0;
                });

                return filtered.length > 0;
            }
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        view(projectId) {
            this.$router.push({
                name: "works-packages",
                params: {
                    projectId: projectId
                }
            });
        },

        edit(project) {
            const self = this;

            self.editedItem = project;
            self.tab = 'general';
            self.projectFormKey = self.projectFormKey + 1;
            self.dialog = true;
        },

        showUserAccessTab(project) {
            const self = this;

            self.editedItem = project;
            self.tab = 'user-access'
            self.projectFormKey = self.projectFormKey + 1;
            self.dialog = true;
        },

        goToEnquiries() {
            this.$router.push({
                name: "supply-fit-enquiries",
            });
        },

        isContractor() {
            return permissions.hasRole(permissions.role_contractor);
        },

        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },

        isFrameworkUser() {
            return permissions.hasRole(permissions.role_framework);
        },

        isClientUser() {
            return permissions.hasRole(permissions.role_client);
        },

        canAssignUsers() {
            return permissions.can(permissions.project_assign_users);
        },

        canViewProgress() {
            return this.isFrameworkUser() || this.isClientUser() || this.isContractor() || this.isAdmin();
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
                region: self.filters.region,
                grouped: 1,
                type: self.projectType
            };

            const response = await api.loadProjects(params);

            self.items = response.data;
            self.loader = false;
        },

        async loadRegionOptions() {
            const self = this;

            const response = await api.loadProjectRegions();
            self.regionOptions = response.data;
        },

        async submitConvert() {
            const self = this;

            await self.updateProjectStage(self.dialogConvertItem, self.convertForm,false);

            self.dialogConvert = false;
            self.dialogConvertItem = null;
        },

        async onStageAction(item) {
            if (item.stage === 1 || item.stage === 2) {
                this.dialogConvert = true;
                this.dialogConvertItem = item;
                return;
            }

            if (this.isComplete(item)) {
                await this.archiveItem(item);
                return;
            }

            await this.updateProjectStage(item);
        },

        async updateProjectStage(item, params = {}, showConfirm = true) {
            const self = this;
            let message = "Are you sure you want to convert this project?";

            if (item.stage === 3) {
                message = "Are you sure you want to mark this project as complete?";
            }

            if (showConfirm) {
                if (!confirm(message)) {
                    return;
                }
            }

            const response = await api.updateProjectStage(item.id, params);
            self.dialogConvertItem = response.data ?? null;

            await self.load();
        },

        uploadBoqFile() {
            const self = this;
            const formData = new FormData();
            const projectId = self.dialogBoqItem.id ?? null;

            if (!projectId || !self.boqFile) {
                return;
            }

            formData.append('boq_file', self.boqFile);
            formData.append('project_id', projectId);
            formData.append('template', self.selectedBoqTemplate);

            self.dialogBoq = false;
            self.saving = true;

            api.uploadBoqFile(formData).then(response => {
                self.previewData = response.data;
                self.dialogPreview = true;
                self.dialogPreviewId = projectId;

                // self.$store.commit("showSnackbar", {
                //     message: response.data ?? 'File uploaded',
                //     color: "success",
                // });

                self.dialogBoqItem = null;
                self.boqFile = null;
                self.dialogBoq = false;
            }).catch(error => {
                console.error(error);

                self.$store.commit("showSnackbar", {
                    message: "An error occurred",
                    color: "error",
                });
            }).finally(() => {
                self.saving = false;
            });
        },

        async uploadBoqFileAsync(projectId, file) {
            const self = this;
            const formData = new FormData();

            formData.append('boq_file', file);
            formData.append('project_id', projectId);

            self.dialogConvert = false;
            self.saving = true;

            try {
                const response = await api.uploadBoqFile(formData);

                self.previewData = response.data;
                self.dialogPreviewId = projectId;
                self.dialogPreview = true;

                // self.$store.commit("showSnackbar", {
                //     message: response.data ?? 'File uploaded',
                //     color: "success",
                // });
            } catch(error) {
                console.error(error);
            }

            self.saving = false;
        },

        createWorksPackages(items) {
            const self = this;

            let data = {
                project_id: self.dialogPreviewId,
                boq_data: items,
            };

            api.createWorksPackagesFromBoq(data).then(response => {
                self.$store.commit("showSnackbar", {
                    message: response.data ?? 'Works packages created',
                    color: "success",
                });

                self.selectedPreviewItems = [];
                self.dialogPreview = false;
                self.dialogPreviewId = null;
                self.dialogBoq = false;
                self.dialogConvert = false;
                self.dialogBoqItem = null;
                self.dialogConvertItem = null;
                self.boqFile = null;
            }).catch(error => {
                console.error(error);

                self.$store.commit("showSnackbar", {
                    message: "An error occurred",
                    color: "error",
                });
            });
        },

        showDialogBoq(item) {
            const self = this;

            self.dialogBoqItem = item;
            self.dialogBoq = true;
        },

        close() {
            const self = this;

            self.dialog = false;
        },

        async saved() {
            const self = this;

            await self.load();
            self.dialog = false;
        },

        showReport(item) {
            this.$router.push({
                name: "contractor-report",
                query: {
                    id: item.id
                }
            });
        },

        showDocuments(item) {
            this.$router.push({
                name: "project-document-categories",
                query: {
                    projectId: item.id
                }
            });
        },

        showQuoteProgress(item) {
            this.$router.push({
                name: "project-quote-progress",
                query: {
                    id: item.id
                },
                params: {
                    projectName: item.name
                }
            });
        },

        newProject() {
            const self = this;

            self.editedItem = null;
            self.dialog = true;
        },

        toggleExpanded(item) {
            const index = this.expanded.findIndex((expandedItem) => expandedItem.id === item.id);

            if (index >= 0) {
                this.expanded.splice(index, 1);
                return;
            }

            this.expanded = [item];
        },

        stageLabel(project) {
            return project.stage_str || '';
        },

        stageKey(project) {
            return project.stage || 1;
        },

        stageIndex(project) {
            const key = this.stageKey(project);

            return this.lifecycleStages.findIndex((stage) => stage.key === key);
        },

        lifecycleProjects(item) {
            return Array.isArray(item.lifecycle_projects) && item.lifecycle_projects.length
                ? item.lifecycle_projects
                : [item];
        },

        projectForLifecycleStage(item, stage) {
            if (this.currentStage(item).key === stage.key) {
                return item;
            }

            return this.lifecycleProjects(item).find((project) => this.stageKey(project) === stage.key);
        },

        currentStage(item) {
            return this.lifecycleStages[this.stageIndex(item)] || this.lifecycleStages[0];
        },

        currentStageContent(item) {
            if (this.isComplete(item)) {
                return this.completeContent;
            }

            return this.lifecycleContent[this.currentStage(item).key] || this.lifecycleContent[1];
        },

        isComplete(item) {
            return !!item.completed_at;
        },

        isLastLifecycleStage(stage) {
            return this.lifecycleStages[this.lifecycleStages.length - 1].key === stage.key;
        },

        isCurrentLifecycleStage(item, stage) {
            if (this.isComplete(item)) {
                return false;
            }

            return this.currentStage(item).key === stage.key;
        },

        isCompletedLifecycleStage(item, stage) {
            if (this.isComplete(item)) {
                return true;
            }

            return this.lifecycleStages.findIndex((lifecycleStage) => lifecycleStage.key === stage.key) < this.stageIndex(item);
        },

        isUpcomingLifecycleStage(item, stage) {
            if (this.isComplete(item)) {
                return false;
            }

            return this.lifecycleStages.findIndex((lifecycleStage) => lifecycleStage.key === stage.key) > this.stageIndex(item);
        },

        lifecycleStatus(item, stage) {
            if (this.isCurrentLifecycleStage(item, stage)) {
                return 'Current';
            }

            if (this.isCompletedLifecycleStage(item, stage)) {
                return 'Completed';
            }

            return 'Upcoming';
        },

        lifecycleIcon(item, stage) {
            if (this.isUpcomingLifecycleStage(item, stage)) {
                return '';
            }

            return '✓';
        },

        progressIcon(item, stage) {
            if (stage.key === 1) {
                return 'mdi-file-document';
            }

            if (stage.key === 2) {
                return 'mdi-cube-outline';
            }

            if (stage.key === 3) {
                return 'mdi-flag-variant';
            }

            return 'mdi-file-document';
        },

        stageBadgeLabel(item) {
            return this.isComplete(item) ? 'COMPLETE' : this.stageLabel(item).toUpperCase();
        },

        stageBadgeClass(item) {
            if (this.isComplete(item)) {
                return { 'project-stage-badge-complete': true };
            }

            return {
                'project-stage-badge-pre': this.stageKey(item) === 1,
                'project-stage-badge-tender': this.stageKey(item) === 2,
                'project-stage-badge-live': this.stageKey(item) === 3
            };
        },

        currentBadgeClass(item) {
            return {
                'project-stage-current-pre': this.stageKey(item) === 1,
                'project-stage-current-tender': this.stageKey(item) === 2,
                'project-stage-current-live': this.stageKey(item) === 3
            };
        },

        stageExpandedClass(item) {
            if (this.isComplete(item)) {
                return { 'project-lifecycle-expanded-complete': true };
            }

            return {
                'project-lifecycle-expanded-pre': this.stageKey(item) === 1,
                'project-lifecycle-expanded-tender': this.stageKey(item) === 2,
                'project-lifecycle-expanded-live': this.stageKey(item) === 3
            };
        },

        timelineItemClass(item, stage) {
            return {
                active: this.isCurrentLifecycleStage(item, stage),
                completed: this.isCompletedLifecycleStage(item, stage)
            };
        },

        timelineDotClass(item, stage) {
            return {
                current: this.isCurrentLifecycleStage(item, stage),
                completed: this.isCompletedLifecycleStage(item, stage),
                grey: this.isUpcomingLifecycleStage(item, stage)
            };
        },

        progressStageClass(item, stage) {
            return {
                active: this.isCurrentLifecycleStage(item, stage),
                current: this.isCurrentLifecycleStage(item, stage),
                completed: this.isCompletedLifecycleStage(item, stage)
            };
        },

        connectorClass(item, stage) {
            if (this.isComplete(item)) {
                return { dashed: false };
            }

            const stageIndex = this.lifecycleStages.findIndex((lifecycleStage) => lifecycleStage.key === stage.key);

            return {
                dashed: stageIndex >= this.stageIndex(item)
            };
        },

        stageCardClass(item) {
            if (this.isComplete(item)) {
                return { 'project-stage-card-complete': true };
            }

            return {
                'project-stage-card-pre': this.stageKey(item) === 1,
                'project-stage-card-tender': this.stageKey(item) === 2,
                'project-stage-card-live': this.stageKey(item) === 3
            };
        },

        stageButtonClass(item) {
            if (this.isComplete(item)) {
                return { 'project-stage-action-complete': true };
            }

            return {
                'project-stage-action-pre': this.stageKey(item) === 1,
                'project-stage-action-tender': this.stageKey(item) === 2,
                'project-stage-action-live': this.stageKey(item) === 3
            };
        },

        updateType(projectId) {
            const self = this;

            if (!projectId) {
                return;
            }

            api.updateProjectType(projectId, {type: 2})
                .then(response => {

                    self.load();

                    self.$store.commit("showSnackbar", {
                        message: "Project type updated",
                        color: "success",
                    });
                })
                .catch(error => {
                    console.log(error);

                    self.$store.commit("showSnackbar", {
                        message: error.response?.data ?? "An error occurred",
                        color: "error",
                    });
                });
        }
    },
};
</script>

<style>
#projectReportTable {
    th, td {
        padding: 5px 10px !important;
    }

    th:not(:first-child), td:not(:first-child) {
        text-align: center;
    }
}

.project-stage-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    margin-left: 8px;
    text-wrap-mode: nowrap;
}

.project-stage-badge-pre {
    background: #fff3cd;
    color: #f59e0b;
}

.project-stage-badge-tender {
    background: #264dec;
    color: #fff;
}

.project-stage-badge-live {
    background: #108a27;
    color: #fff;
}

.project-stage-badge-complete {
    background: #108a27;
    color: #fff;
}

.project-stage-current {
    margin-left: 10px;
    background: #fff;
}

.project-stage-current-pre {
    color: #f59e0b;
    border: 1px solid #f59e0b;
}

.project-stage-current-tender {
    color: #264dec;
    border: 1px solid #264dec;
}

.project-stage-current-live {
    color: #108a27;
    border: 1px solid #108a27;
}

.project-lifecycle-expanded {
    display: grid;
    grid-template-columns: 300px 1fr 360px;
    gap: 30px;
    padding: 32px;
    background: #fff;
}

.project-lifecycle-expanded-pre {
    background: #fffdf7;
}

.project-lifecycle-expanded-tender {
    background: #f8fbff;
}

.project-lifecycle-expanded-live {
    background: #f5fff7;
}

.project-lifecycle-expanded-complete {
    background: #f5fff7;
}

.project-lifecycle-timeline {
    border-right: 1px solid #e5e7eb;
    padding-right: 25px;
}

.project-lifecycle-timeline-item {
    display: flex;
    gap: 16px;
    margin-bottom: 35px;
    position: relative;
}

.project-lifecycle-timeline-item:not(:last-child)::after {
    content: "";
    position: absolute;
    left: 11px;
    top: 28px;
    width: 2px;
    height: 55px;
    background: #d1d5db;
}

.project-lifecycle-timeline-item.completed:not(:last-child)::after {
    background: #16a34a;
}

.project-lifecycle-expanded-tender .project-lifecycle-timeline-item.completed:not(:last-child)::after {
    background: #264dec;
}

.project-lifecycle-expanded-pre .project-lifecycle-timeline-item.active:not(:last-child)::after {
    background: #f59e0b;
}

.project-lifecycle-expanded-tender .project-lifecycle-timeline-item.active:not(:last-child)::after {
    background: #d1d5db;
}

.project-lifecycle-expanded-live .project-lifecycle-timeline-item:not(:last-child)::after {
    background: #16a34a;
}

.project-lifecycle-dot {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #d1d5db;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    flex-shrink: 0;
}

.project-lifecycle-dot.completed {
    background: #16a34a;
}

.project-lifecycle-expanded-pre .project-lifecycle-dot.current {
    background: #f59e0b;
}

.project-lifecycle-expanded-tender .project-lifecycle-dot.completed {
    background: #264dec;
}

.project-lifecycle-expanded-tender .project-lifecycle-dot.current {
    background: #264dec;
}

.project-lifecycle-expanded-live .project-lifecycle-dot.current {
    background: #16a34a;
}

.project-lifecycle-dot.grey {
    background: #d1d5db;
}

.project-lifecycle-timeline strong {
    font-size: 14px;
}

.project-lifecycle-timeline p {
    margin: 8px 0;
    font-size: 13px;
}

.project-lifecycle-progress h3 {
    margin-top: 0;
}

.project-lifecycle-progress-line {
    display: flex;
    align-items: center;
    margin: 35px 0;
}

.project-lifecycle-stage {
    text-align: center;
    min-width: 100px;
}

.project-lifecycle-stage-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 3px solid #d1d5db;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    color: #6b7280;
    background: #fff;
}

.project-lifecycle-stage.completed .project-lifecycle-stage-icon {
    border-color: #16a34a;
    color: #16a34a;
}

.project-lifecycle-stage.completed .project-lifecycle-stage-icon .v-icon {
    color: #16a34a;
}

.project-lifecycle-expanded-pre .project-lifecycle-stage.current .project-lifecycle-stage-icon {
    border-color: #f59e0b;
    color: #f59e0b;
}

.project-lifecycle-expanded-pre .project-lifecycle-stage.current .project-lifecycle-stage-icon .v-icon {
    color: #f59e0b;
}

.project-lifecycle-expanded-tender .project-lifecycle-stage.current .project-lifecycle-stage-icon {
    border-color: #264dec;
    color: #264dec;
}

.project-lifecycle-expanded-tender .project-lifecycle-stage.completed .project-lifecycle-stage-icon {
    border-color: #264dec;
    color: #264dec;
}

.project-lifecycle-expanded-tender .project-lifecycle-stage.current .project-lifecycle-stage-icon .v-icon {
    color: #264dec;
}

.project-lifecycle-expanded-tender .project-lifecycle-stage.completed .project-lifecycle-stage-icon .v-icon {
    color: #264dec;
}

.project-lifecycle-expanded-live .project-lifecycle-stage.current .project-lifecycle-stage-icon {
    border-color: #16a34a;
    color: #16a34a;
}

.project-lifecycle-expanded-live .project-lifecycle-stage.current .project-lifecycle-stage-icon .v-icon {
    color: #16a34a;
}

.project-lifecycle-connector {
    height: 3px;
    flex: 1;
    background: #16a34a;
    margin: 0 10px;
}

.project-lifecycle-expanded-pre .project-lifecycle-connector {
    background: #f59e0b;
}

.project-lifecycle-expanded-tender .project-lifecycle-connector {
    background: #264dec;
}

.project-lifecycle-connector.dashed {
    background: none;
    border-top: 2px dashed #cbd5e1;
}

.project-lifecycle-stage p {
    margin: 0;
    font-weight: 600;
}

.project-lifecycle-stage small {
    display: block;
    margin-top: 8px;
}

.project-stage-card {
    border-radius: 6px;
    padding: 22px;
}

.project-stage-card h4 {
    margin-top: 0;
}

.project-stage-card p,
.project-stage-card li {
    font-size: 14px;
    line-height: 1.6;
}

.project-stage-card ul {
    padding-left: 0;
    list-style: none;
}

.project-stage-card li::before {
    content: "✓";
    font-weight: bold;
    margin-right: 10px;
}

.project-stage-card-pre {
    background: #fff8e6;
    border: 1px solid #f5d28b;
}

.project-stage-card-pre li::before {
    color: #f59e0b;
}

.project-stage-card-tender {
    background: #f3f7ff;
    border: 1px solid #c7d7ff;
}

.project-stage-card-tender li::before {
    color: #264dec;
}

.project-stage-card-live {
    background: #f2fff4;
    border: 1px solid #b9e7c0;
}

.project-stage-card-live li::before {
    color: #108a27;
}

.project-stage-card-complete {
    background: #f2fff4;
    border: 1px solid #b9e7c0;
}

.project-stage-card-complete li::before {
    color: #108a27;
}

.project-stage-action-btn {
    border: none;
    padding: 11px 18px;
    border-radius: 4px;
    font-weight: 700;
    cursor: pointer;
}

.project-stage-action-pre {
    background: #f59e0b;
    color: #111827;
}

.project-stage-action-tender {
    background: #264dec;
    color: #fff;
}

.project-stage-action-live {
    background: #108a27;
    color: #fff;
}

.project-stage-action-complete {
    background: #108a27;
    color: #fff;
}

.project-lifecycle-side-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 24px;
    margin-bottom: 18px;
}

.project-lifecycle-side-card h4 {
    margin-top: 0;
}

.project-lifecycle-side-card p {
    font-size: 14px;
    line-height: 1.6;
}

.project-outline-btn {
    background: #fff;
    border: 1px solid #d1d5db;
    padding: 10px 16px;
    border-radius: 4px;
    font-weight: 700;
}
</style>
