<template>
    <v-card>
        <v-card-title>
            <span class="headline">{{ getTitle() }}</span>
        </v-card-title>

        <v-card-text>
            <v-container>
                <v-row>
                    <v-col>
                        <v-text-field
                            v-model="form.name"
                            :error="errors.name"
                            clear-icon="mdi-close-circle"
                            clearable
                            label="Name"
                        ></v-text-field>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col>
                        <v-autocomplete
                            v-model="form.template_id"
                            :items="templates"
                            :loading="loader"
                            :search-input.sync="searchTemplate"
                            item-text="name"
                            item-value="id"
                            label="Select Template"
                            outlined
                            dense
                            chips
                            small-chips
                        ></v-autocomplete>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col>
                        <v-autocomplete
                            v-model="form.parent_id"
                            :items="worksPackages"
                            :loading="loader"
                            :search-input.sync="searchWorksPackage"
                            item-text="name"
                            item-value="id"
                            label="Select Parent"
                            outlined
                            dense
                        ></v-autocomplete>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col>
                        <v-autocomplete
                            v-model="form.high_risk"
                            :items="riskOptions"
                            label="High Risk"
                            outlined
                            dense
                        ></v-autocomplete>
                    </v-col>
                </v-row>
            </v-container>
        </v-card-text>

        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="blue darken-1" text @click="cancel">Cancel</v-btn>
            <v-btn color="blue darken-1" text @click="save">Save</v-btn>
        </v-card-actions>
    </v-card>
</template>

<script>
import api from "../common/api.js";

export default {
    props: ["value", "projectId"],
    data() {
        return {
            loader: false,
            templates: [],
            worksPackages: [],
            searchTemplate: null,
            searchWorksPackage: null,
            form: {
                id: null,
                name: null,
                template_id: null,
                high_risk: 0,
                parent_id: [],
            },
            errors: {
                name: null,
            },
            riskOptions: [
                {value: 0, text: 'No'},
                {value: 1, text: 'Yes'},
            ],
        };
    },

    async mounted() {
        const self = this;

        await this.loadTemplateOptions();
        await this.loadWorksPackageOptions();

        self.init();
    },

    watch: {
        value: {
            handler(n, o) {
                console.log(n, o);
                this.init();
            },
            deep: true,
        },
        async searchTemplate(val) {
            const self = this;

            await self.loadTemplateOptions(val);
        },
        async searchWorksPackage(val) {
            const self = this;

            await self.loadWorksPackageOptions(val);
        },
    },

    methods: {
        async init() {
            const self = this;

            self.form.id = self.value.id ?? null;
            self.form.name = self.value.name;
            self.form.template_id = self.value.template_id;
            self.form.parent_id = self.value.parent_id;
            self.form.high_risk = self.value.high_risk;

            await self.loadWorksPackageOptions('');

            if (self.form.id) {
                self.worksPackages = self.worksPackages.filter((item) => {
                    return item.id !== self.form.id;
                });
            }
        },

        cancel() {
            this.$emit("cancel", this.value);
        },

        async loadTemplateOptions(search = '') {
            const self = this;
            if (self.loader) {
                return;
            }
            self.loader = true;

            try {
                const resp = await api.questionnaireTemplateOptions(search);
                self.templates = resp.data;
            } catch (err) {
                console.log(err);
            }

            self.loader = false;
        },

        async loadWorksPackageOptions(search = '') {
            const self = this;
            if (self.loader) {
                return;
            }
            self.loader = true;

            let projectIds = [self.projectId];

            try {
                const resp = await api.getWorksPackageOptions({search: search, projectIds: projectIds});
                self.worksPackages = resp.data;
            } catch (err) {
                console.log(err);
            }

            self.loader = false;
        },

        async save(event) {
            const self = this;

            event.preventDefault();

            self.errors.name = !self.form.name;

            if (self.errors.name) {
                return;
            }

            try {
                if (self.form.id) {
                    const response = await api.updateWorksPackage(self.form.id, self.form);
                } else {
                    const response = await api.storeWorksPackage(this.projectId, self.form);
                }

                await self.loadWorksPackageOptions();
                self.$emit("saved", {});

                self.$store.commit("showSnackbar", {
                    message: "Success!",
                    color: "success",
                });
            } catch (error) {
                let data = error.response.data;
                if (typeof data.errors != "undefined") {
                    for (let id in data.errors) {
                        self.errors.id = data.errors?.id;
                    }
                }
            }
        },

        getTitle() {
            return this.value.id ? "Edit Works Package" : "New Works Package";
        }
    },
};
</script>

<style>
</style>
