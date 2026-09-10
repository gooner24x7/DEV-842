<template>
    <v-card>
        <v-card-title>
            <span class="headline">{{title}} ID {{parentId}}</span>
        </v-card-title>
        <v-card-text>
            <ul v-if="attachments.length > 0" class="attachments-list mb-3">
                <li class="attachments-list-item" v-for="(file, index) in attachments" :key="index">
                    <div class="attachment-filename"><a :href="file.url" target="_blank">{{ file.name }}</a></div>
                    <div class="attachment-description" v-if="file.description">{{ file.description }}</div>
                    <v-btn v-if="canEdit()" class="btn-icon" icon small @click="deleteAttachment(file.id)">
                        <v-icon>mdi-delete</v-icon>
                    </v-btn>
                </li>
            </ul>
            <div class="mb-3" v-else>No documents attached</div>
            <v-spacer></v-spacer>
            <v-form ref="attachments_form" enctype="multipart/form-data">
                <v-file-input
                    v-if="canEdit()"
                    v-model="files"
                    accept="*"
                    label="Upload Files"
                    multiple
                ></v-file-input>
            </v-form>
        </v-card-text>
        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="blue darken-1" text @click="cancel">Cancel</v-btn>
            <v-btn v-if="canEdit()" color="blue darken-1" text @click="save">Save</v-btn>
        </v-card-actions>
    </v-card>
</template>

<script>
import api from "../common/api";

export default {
    props: ["parentId", "type", "title", "userCanEdit", "description"],
    name: "AttachmentsDialog",
    data() {
        return {
            files: [],
            attachments: [],
        }
    },
    mounted() {
        this.load();
    },
    watch: {
        parentId() {
            this.load();
        },
        description() {
            this.load();
        }
    },
    methods: {
        load() {
            const self = this;

            let params = {
                parent_id: self.parentId,
                type: self.type
            };

            if (typeof self.description !== 'undefined') {
                params.description = self.description;
            }

            api.loadAttachments(params)
                .then((response) => {
                    self.attachments = response.data;
                    console.log(self.attachments);
                })
                .catch((error) => {
                    console.log(error);
                });
        },
        save() {
            const self = this;
            let formData = new FormData();

            for (let i = 0; i < self.files.length; i++ ) {
                let file = self.files[i];
                formData.append('files[' + i + ']', file);
            }

            formData.append('type', self.type);
            formData.append('parent_id', self.parentId);

            if (self.description) {
                formData.append('description', self.description);
            }

            api.createAttachment(formData)
                .then((response) => {
                    self.load();
                })
                .catch((error) => {
                    console.log(error);
                });
        },
        deleteAttachment(id) {
            const self = this;

            if (confirm('Are you sure you want to delete this attachment?')) {
                api.deleteAttachment(id)
                    .then((response) => {
                        self.load();
                    })
                    .catch((error) => {
                        console.log(error);
                    });
            }
        },
        cancel() {
            this.$emit("close", this.value);
        },
        canEdit() {
            const self = this;

            if (typeof self.userCanEdit !== 'undefined') {
                return self.userCanEdit;
            }

            return false;
        }
    }
}
</script>

<style>
.btn__block .v-btn__content {
    display: block !important;
}

.attachments-list {
    padding-left: 0!important;
}

.attachments-list-item {
    display: flex;
    justify-content: space-between;
    background-color: #eeeeee;
    padding: 0 10px;
    margin-top: 5px;
    border-radius: 5px;
}

.attachment-filename, .attachment-description {
    line-height: 36px;
}

.btn-icon .v-icon{
    color: #606060 !important;
}
.btn-icon:hover .v-icon{
    color: #000000!important;
}
.btn-icon:after, .btn-icon:before {
    content: none;
}
</style>
