<template>
    <v-card>
        <v-card-title>Works Packages</v-card-title>

        <v-card-text>
            <v-row>
                <v-col md="8">
                    <v-btn class="m-2" color="primary" small @click="toggleTreeview()">{{ getToggleBtnLabel() }}</v-btn>
                    <v-btn class="m-2" color="primary" small @click="toggleSelectAll()">{{ getSelectAllBtnLabel() }}</v-btn>
                </v-col>
                <v-col md="4">
                    <v-text-field
                        v-model="search"
                        label="Search"
                        hide-details
                        outlined
                        clearable
                        dense
                        clear-icon="mdi-close-circle-outline"
                    ></v-text-field>
                </v-col>
            </v-row>

            <v-treeview
                v-model="selected"
                :items="items"
                :open-all="expanded"
                :search="search"
                ref="worksPackagesTree"
                class="mt-3"
                selection-type="independent"
                selectable
                return-object
                dense
                hoverable
            >
                <template v-slot:label="{ item, open }">
                    <div>{{ item.name }}</div>
                    <div :class="getAssignedUsersClass(item)">{{ getAssignedUsersText(item) }}</div>
                </template>

                <template v-slot:append="{ item, open }">
                    <v-autocomplete
                        v-model="item.product"
                        :items="products"
                        item-text="name"
                        item-value="id"
                        label="Select Trade"
                        return-object
                        hide-details
                        outlined
                        dense
                    >
                    </v-autocomplete>
<!--                    <v-btn class="mr-2" color="primary" small @click="select(item)">Select</v-btn>-->
                </template>
            </v-treeview>
        </v-card-text>

        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="blue darken-1" text @click="close">Cancel</v-btn>
            <v-btn color="primary" @click="save">Save</v-btn>
        </v-card-actions>
    </v-card>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";
import WorksPackageForm from "../components/WorksPackageForm.vue";

export default {
    name: "WorksPackageSelector",
    props: ['projectId', 'products'],
    components: {},
    data() {
        return {
            loader: false,
            dialog: false,
            expanded: false,
            search: null,
            items: [],
            selected: [],
        };
    },

    async mounted() {
        const self = this;

        await self.load();
    },

    watch: {
        'projectId': function (val) {
            const self = this;

            if (val !== null) {
                self.load();
            } else {
                self.items = [];
            }
        }
    },

    computed: {

    },

    methods: {
        isContractor() {
            return permissions.hasRole(permissions.role_contractor);
        },

        isAdmin() {
            if (typeof this.$store.getters.getSession.user.role_name != 'object') {
                return false;
            }

            return this.$store.getters.getSession.user.role_name.join().toLowerCase().includes("admin");
        },

        async load() {
            const self = this;
            self.loader = true;

            const response = await api.loadWorksPackages(this.projectId, {})
            self.items = response.data;

            self.loader = false;
        },

        select(item) {
            this.$emit("selected", item);
        },

        close() {
            this.$emit("close");
        },

        save() {
            this.$emit("save", this.selected);
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        toggleTreeview() {
            this.expanded = !this.expanded;
            this.$refs.worksPackagesTree.updateAll(this.expanded);
        },

        getToggleBtnLabel() {
            return this.expanded ? 'Collapse Tree' : 'Expand Tree';
        },

        flattenItems(items) {
            return items.reduce((acc, item) => {
                acc.push(item);
                if (item.children && item.children.length) {
                    acc.push(...this.flattenItems(item.children));
                }
                return acc;
            }, []);
        },

        toggleSelectAll() {
            const all = this.flattenItems(this.items);

            this.selected = this.selected.length === all.length ? [] : all;
        },

        getSelectAllBtnLabel() {
            const all = this.flattenItems(this.items);

            return all.length && this.selected.length === all.length ? 'Deselect All' : 'Select All';
        },

        getAssignedUsersClass(item) {
            return item.assigned_users.length > 0 ? 'text-success' : 'text-secondary';
        },

        getAssignedUsersText(item) {
            let total = item.assigned_users.length;
            let text = '';

            if (total === 0) {
                text = 'no users assigned';
            } else if (total === 1) {
                text = '1 user assigned';
            } else if (total > 1) {
                text = total + ' users assigned';
            }

            return text;
        }
    },
};
</script>

<style>
.v-treeview-node__root {
    padding: 4px 0;
}
</style>
