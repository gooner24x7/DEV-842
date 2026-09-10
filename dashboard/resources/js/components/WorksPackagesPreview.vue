<template>
    <div>
        <v-card>
            <v-card-title>Review BOQ Data</v-card-title>
            <v-card-subtitle>
                {{ countPreviewItems() }} hierarchy items were generated from the uploaded file. <br>
                Please review the data and click save to add the selected hierarchy to the project.
            </v-card-subtitle>
            <v-card-text>
                <div v-if="metadata" class="wp-preview-summary mb-4">
                    <template v-if="metadata.allocated_records !== undefined">
                        <span>{{ metadata.allocated_records }} allocated bill lines</span>
                        <span>{{ metadata.packages_used }} {{ metadata.top_level_label }}</span>
                        <span v-if="metadata.work_items_used">{{ metadata.work_items_used }} {{ metadata.allocation_node_label }}</span>
                    </template>
                    <span v-else>{{ metadata.mapped_bills }} of {{ metadata.total_bills }} bills mapped</span>
                    <span>{{ metadata.overall_accuracy_score }} overall accuracy</span>
                    <span>{{ metadata.execution_time }}</span>
                </div>
                <v-treeview
                    :value="selectedPreviewItems"
                    @input="onPreviewSelectionChange"
                    v-if="previewData && previewData.length"
                    :items="previewData"
                    item-key="selection_key"
                    item-text="name"
                    item-children="children"
                    return-object
                    selectable
                    selection-type="independent"
                    dense
                >
                    <template v-slot:label="{ item }">
                        <div class="wp-preview-item">{{ item.name }}  {{ countItemChildren(item) }}</div>
                        <div class="wp-preview-attributes" v-if="item.attributes">
                            <div class="wp-preview-chip" v-for="(value, key) in item.attributes" :key="key" @click="showDialog(item)">
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
                <v-btn color="blue darken-1" text :disabled="!selectedPreviewItems.length" @click="save()">Save</v-btn>
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

                    <div v-if="dialogItem?.source_evidence?.length">
                        <h5 class="wp-attribute-heading">Source Evidence</h5>
                        <p
                            v-for="(evidence, index) in dialogItem.source_evidence"
                            :key="index"
                            class="wp-attribute-text wp-source-evidence"
                        >{{ evidence }}</p>
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
export default {
    name: "WorksPackagesPreview",
    props: ['previewData', 'metadata'],
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

    watch: {
        previewData: {
            handler() {
                this.selectAllPreviewItems();
            },
            immediate: true,
        },
    },

    methods: {
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
            const previousIds = new Set(this.selectedPreviewItems.map(item => item.selection_key));
            const selectionIds = new Set(selection.map(item => item.selection_key));
            const result = new Map(selection.map(item => [item.selection_key, item]));

            selection
                .filter(item => !previousIds.has(item.selection_key))
                .forEach(item => {
                    this.previewItemDescendants(item).forEach(child => result.set(child.selection_key, child));
                    this.previewItemAncestors(item).forEach(parent => result.set(parent.selection_key, parent));
                });

            this.selectedPreviewItems
                .filter(item => !selectionIds.has(item.selection_key))
                .forEach(item => {
                    this.previewItemDescendants(item).forEach(child => result.delete(child.selection_key));
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

                if (candidate.selection_key === item.selection_key) {
                    ancestors = trail;
                } else if (candidate.children && candidate.children.length) {
                    ancestors = this.previewItemAncestors(item, candidate.children, trail.concat(candidate));
                }
            });

            return ancestors;
        },

        countPreviewItems() {
            return this.flattenPreviewItems(this.previewData).length;
        },

        countItemChildren(item) {
            if (item.attributes?.allocated_lines !== undefined) {
                return '(' + item.attributes.allocated_lines + ' lines)';
            }

            if (item.children && item.children.length) {
                const label = this.metadata?.allocation_node_label || 'items';
                return '(' + item.children.length + ' ' + label + ')';
            }

            return '';
        },

        close() {
            this.$emit("close");
        },

        save() {
            this.$emit("save", this.selectedPreviewItems.map(item => item.selection_key));
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

.wp-preview-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 16px;
    color: #4f5964;
    font-size: 13px;
}

.wp-source-evidence {
    white-space: pre-line;
}
</style>
