<template>
    <div>
        <v-card>
            <v-card-title>Review BOQ Data</v-card-title>
            <v-card-subtitle>
                {{ countPreviewItems() }} works packages were generated from the uploaded file. <br>
                Please review the data and click save to add these works packages to the project.
            </v-card-subtitle>
            <v-card-text>
                <v-treeview
                    :value="selectedPreviewItems"
                    @input="onPreviewSelectionChange"
                    v-if="previewData && previewData.length"
                    :items="previewData"
                    item-key="id"
                    item-text="name"
                    item-children="children"
                    return-object
                    selectable
                    selection-type="independent"
                    dense
                >
                    <template v-slot:label="{ item, open }">
                        <div class="wp-preview-item">{{ item.name }}  {{ countItemChildren(item) }}</div>
                        <div class="wp-preview-attributes" v-if="item.attributes">
                            <div class="wp-preview-chip" v-for="(value, key) in item.attributes" @click="showDialog(item)">
                                {{ key + ': ' + value }}
                            </div>
                        </div>
                    </template>
                </v-treeview>
                <span v-else>No data to preview.</span>
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="blue darken-1" text @click="close()">Cancel</v-btn>
                <v-btn color="blue darken-1" text @click="save()">Save</v-btn>
            </v-card-actions>
        </v-card>
        <v-dialog
            v-model="dialog"
            max-width="800px"
        >
            <v-card>
                <v-card-title>
                    {{ dialogItem.name }}
                </v-card-title>
                <v-card-text>
                    <div v-if="dialogItem.attributes?.ai_rationale">
                        <h5 class="wp-attribute-heading">AI Reasoning</h5>
                        <p class="wp-attribute-text"> {{ dialogItem.attributes.ai_rationale }}</p>
                    </div>

                    <div v-if="dialogItem?.source_evidence">
                        <h5 class="wp-attribute-heading">Source Evidence</h5>
                        <div class="wp-attribute-text" v-html="getSourceEvidenceHtml(dialogItem)"></div>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="blue darken-1" text @click="dialog=false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";

export default {
    name: "WorksPackagesPreview",
    props: ['previewData'],
    components: {},
    data() {
        return {
            selectedPreviewItems: [],
            dialog: false,
            dialogItem: {
                id: null,
                name: "",
                attributes: {},
                source_evidence: [],
            },
        };
    },

    async mounted() {
        const self = this;
    },

    watch: {
        previewData: {
            handler(n, o) {
                this.selectAllPreviewItems();
            },
            immediate: true,
        },
    },

    computed: {

    },

    methods: {
        getSourceEvidenceHtml(dialogItem) {
            let html = "";

            if (dialogItem?.source_evidence) {
                html = dialogItem.source_evidence.map(evidence => `<p>${evidence}</p>`).join('');
            }

            return html;
        },

        showDialog(item) {
            if (item.attributes?.ai_rationale || item?.source_evidence) {
                this.dialogItem = item;
                this.dialog = true;
            }
        },

        selectAllPreviewItems() {
            this.selectedPreviewItems = this.flattenPreviewItems(this.previewData);
        },

        flattenPreviewItems(items) {
            let flattened = [];

            (items || []).forEach(item => {
                flattened.push(item);

                if (item.children && item.children.length) {
                    flattened = flattened.concat(this.flattenPreviewItems(item.children));
                }
            });

            return flattened;
        },

        // Selection is "independent" so a parent can stay ticked with none of its
        // children ticked, but we cascade toggles by hand to get leaf-like
        // behaviour: ticking/unticking a parent ticks/unticks its whole subtree,
        // and ticking a child ticks its ancestors so it is never orphaned.
        onPreviewSelectionChange(selection) {
            const previousIds = new Set(this.selectedPreviewItems.map(item => item.id));
            const selectionIds = new Set(selection.map(item => item.id));
            const result = new Map(selection.map(item => [item.id, item]));

            selection
                .filter(item => !previousIds.has(item.id))
                .forEach(item => {
                    this.previewItemDescendants(item).forEach(child => result.set(child.id, child));
                    this.previewItemAncestors(item).forEach(parent => result.set(parent.id, parent));
                });

            this.selectedPreviewItems
                .filter(item => !selectionIds.has(item.id))
                .forEach(item => {
                    this.previewItemDescendants(item).forEach(child => result.delete(child.id));
                });

            this.selectedPreviewItems = Array.from(result.values());
        },

        previewItemDescendants(item) {
            let descendants = [];

            (item.children || []).forEach(child => {
                descendants.push(child);
                descendants = descendants.concat(this.previewItemDescendants(child));
            });

            return descendants;
        },

        previewItemAncestors(item, items = null, trail = []) {
            let ancestors = [];

            (items || this.previewData || []).forEach(candidate => {
                if (ancestors.length) {
                    return;
                }

                if (candidate.id === item.id) {
                    ancestors = trail;
                } else if (candidate.children && candidate.children.length) {
                    ancestors = this.previewItemAncestors(item, candidate.children, trail.concat(candidate));
                }
            });

            return ancestors;
        },

        isPreviewItemSelected(item) {
            return this.selectedPreviewItems.some(selected => selected.id === item.id);
        },

        // Rebuilds the nested parent/child structure from the flat selection the
        // treeview gives us. A ticked parent is kept even when none of its
        // children are ticked, and an unticked parent is kept if it still has
        // ticked children.
        buildSelectedPreviewTree(items) {
            const selected = [];

            (items || []).forEach(item => {
                const children = item.children && item.children.length
                    ? this.buildSelectedPreviewTree(item.children)
                    : [];

                if (!this.isPreviewItemSelected(item) && !children.length) {
                    return;
                }

                const node = { ...item };

                if (item.children && item.children.length) {
                    node.children = children;
                }

                selected.push(node);
            });

            return selected;
        },

        countPreviewItems() {
            let total = 0;

            if (this.previewData) {
                total += this.previewData.length;

                this.previewData.forEach(item => {
                    total += item?.children?.length || 0;
                });
            }

            return total;
        },

        countItemChildren(item) {
            let total = '';

            if (item.children) {
                total = '(' + item.children.length + ')';
            }

            return total;
        },

        close() {
            this.$emit("close");
        },

        save() {
            let selectedItems = this.buildSelectedPreviewTree(this.previewData);

            this.$emit("save", selectedItems);
        },
    },
};
</script>

<style>
.wp-preview-item {
    font-weight: bold;
    padding: 2px;
}

.wp-preview-attributes {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.wp-preview-chip {
    text-align: center;
    padding: 2px 8px;
    border-radius: 22px;
    font-size: 11px;
    font-weight: bold;
    text-wrap-mode: nowrap;
    color: #5184ad;
    background: #e6f3ff;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
}
</style>
