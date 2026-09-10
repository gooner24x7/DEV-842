<template>
    <v-dialog v-model="dialog" max-width="500px">
        <template v-slot:activator="{ on, attrs }">
            <v-btn color="primary" dark class="m-2" v-bind="attrs" v-on="on"
            >Select Category
            </v-btn
            >
        </template>
        <div>
            <v-card>
                <v-card-title>
                    <span class="headline">Select Category</span>
                </v-card-title>
                <v-card-text>
                    <v-treeview
                        v-model="tree"
                        :active.sync="active"
                        :items="items"
                        selected-color="indigo"
                        rounded
                        hoverable
                        activatable
                        return-object
                    >
                    </v-treeview>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="blue darken-1" text @click="cancel">Cancel</v-btn>
                    <v-btn color="blue darken-1" text @click="save">Apply</v-btn>
                </v-card-actions>
            </v-card>
        </div>
    </v-dialog>
</template>

<script>
import api from "../../common/api";

export default {
    name: "CategoryTreeSelector",

    data() {
        return {
            dialog: false,
            active: [],
            tree: [],
            items: [],
        }
    },
    methods: {
        async loadTree() {
            const self = this
            const resp = await api.loadTree()
            self.items = resp.data
        },

        cancel() {
            this.dialog = false
        },

        save() {
            this.$emit('select', this.active[0])
            this.dialog = false
        }
    },

    async mounted() {
        const self = this

        await self.loadTree()
    }
}
</script>

<style scoped>

</style>
