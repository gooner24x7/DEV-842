<template>
    <v-card>
        <v-card-title>
            <span class="headline">{{ title }}</span>
        </v-card-title>

        <v-card-text>
            <v-container>
                <v-row>
                    <v-col cols="12">
                        <v-text-field v-model="value.company_name" label="Name"
                                      :error="errors.company_name"></v-text-field>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col cols="12">
                        <v-textarea v-model="value.description" label="Description"
                                      :error="errors.description"></v-textarea>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col cols="6">
                        <v-menu
                            v-model="menu1"
                            :close-on-content-click="false"
                            max-width="290"
                        >
                            <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                    v-model="value.date_start"
                                    :error="errors.date_start"
                                    type="text"
                                    label="Date Start"
                                    v-bind="attrs"
                                    v-on="on"
                                ></v-text-field>
                            </template>
                            <v-date-picker
                                v-model="value.date_start"
                                no-title
                                @change="menu1 = false"
                            ></v-date-picker>
                        </v-menu>
                    </v-col>
                    <v-col cols="6">
                        <v-menu
                            v-model="menu2"
                            :close-on-content-click="false"
                            max-width="290"
                        >
                            <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                    v-model="value.date_end"
                                    :error="errors.date_end"
                                    type="text"
                                    label="Date End"
                                    v-bind="attrs"
                                    v-on="on"
                                ></v-text-field>
                            </template>
                            <v-date-picker
                                v-model="value.date_end"
                                no-title
                                @change="menu2 = false"
                            ></v-date-picker>
                        </v-menu>
                    </v-col>
                </v-row>

                <v-row>
                    <v-col cols="12">
                        <v-file-input type="file" id="banner_image" ref="banner_image" v-on:change="handleFileUpload"
                                      :error="errors.banner_image" placeholder="Top Banner Image"/>
                    </v-col>
                </v-row>

                <v-row>
                    <v-col cols="12">
                        <v-file-input type="file" id="banner_image_left" ref="banner_image_left"
                                      v-on:change="handleFileUploadLeft"
                                      :error="errors.banner_image_left" placeholder="Left Banner Image"/>
                    </v-col>
                </v-row>


                <v-row>
                    <v-col cols="12">
                        <v-autocomplete
                            v-model="value.roles"
                            :items="roles"
                            outlined
                            dense
                            chips
                            small-chips
                            label="Roles"
                            item-text="name"
                            item-value="id"
                            multiple
                            :error="errors.roles"
                        ></v-autocomplete>
                    </v-col>
                </v-row>

                <v-row>
                    <v-col cols="12" :id="id" v-for="(video, id) in value.videos" :key="id">
                        <v-btn @click="value.videos.splice(id, 1)" icon class="float-right">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>

                        <v-textarea v-model="value.videos[id]" label="Video link"></v-textarea>
                    </v-col>

                    <v-btn v-on:click="value.videos.push('')" :class="errors['videos'] ? 'redBorder': '' ">Add Video
                    </v-btn>
                </v-row>

                <v-row>
                    <div class="expo-input-group">
                        <h6>Attach Files</h6>
                        <v-file-input
                            multiple
                            v-model="currAttachments"
                            small-chips
                            show-size
                            clearable
                            label="Add files (up to 10)"
                            @change="inputChanged"
                        >
                            <template v-slot:selection="{ text, index, file }">
                                <v-chip
                                    small
                                    text-color="white"
                                    color="#295671"
                                    close
                                    @click:close="removeAttachment(index)"
                                >
                                    {{ text }}
                                </v-chip>
                            </template>
                        </v-file-input>

                        <div>
                            <template v-for="(file, index) in value.attachments">
                                <div class="attachment-group">
                                    <v-btn :href="file.url" style="margin-right: 0">
                                        {{ file.filename.substring(0, 15) }}
                                    </v-btn>
                                    <v-btn @click="removeExistingAttachment(index, file.id)" icon style="margin-left: 0; padding: 18px;">
                                        <v-icon>mdi-close</v-icon>
                                    </v-btn>
                                </div>
                            </template>
                        </div>
                    </div>
                </v-row>

                <v-row>
                    <div class="expo-input-group">
                        <h6>EPD Information</h6>
                        <v-file-input
                            multiple
                            v-model="currAttachmentsEpd"
                            small-chips
                            show-size
                            clearable
                            label="Add files (up to 10)"
                            @change="inputChangedEpd"
                        >
                            <template v-slot:selection="{ text, index, file }">
                                <v-chip
                                    small
                                    text-color="white"
                                    color="#295671"
                                    close
                                    @click:close="removeAttachmentEpd(index)"
                                >
                                    {{ text }}
                                </v-chip>
                            </template>
                        </v-file-input>

                        <div>
                            <template v-for="(file, index) in value.attachments_epd">
                                <div class="attachment-group">
                                    <v-btn :href="file.url" style="margin-right: 0">
                                        {{ file.filename.substring(0, 15) }}
                                    </v-btn>
                                    <v-btn @click="removeExistingAttachment(index, file.id, true)" icon style="margin-left: 0; padding: 18px;">
                                        <v-icon>mdi-close</v-icon>
                                    </v-btn>
                                </div>
                            </template>
                        </div>
                    </div>
                </v-row>

                <v-row v-if="isAdmin()">
                    <v-col cols="12">
                        <v-checkbox v-model="value.is_active" label="Active" :error="errors.is_active"></v-checkbox>
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
import permissions from "../common/permissions";
import api from "../common/api";

export default {
    components: {},
    props: ['value', 'title'],
    data() {
        return {
            currAttachments: [],
            currAttachmentsEpd: [],
            removeIds: [],
            menu1: false,
            menu2: false,
            formValid: true,
            banner_image: '',
            banner_image_left: '',
            roles: [],
            selectedRoles: [],
            errors: {
                banner_image: false,
                banner_image_left: false,
                company_name: false,
                description: false,
                date_start: false,
                date_end: false,
                roles: false,
                videos: false,
            },
        };
    },

    mounted() {
        const self = this;

        self.loadRoles();
    },

    methods: {
        inputChanged() {
            this.currAttachments.splice(10)
        },

        inputChangedEpd() {
            this.currAttachmentsEpd.splice(10)
        },

        removeAttachment(index) {
            this.currAttachments.splice(index, 1)
        },

        removeAttachmentEpd(index) {
            this.currAttachmentsEpd.splice(index, 1)
        },

        removeExistingAttachment(index, attachmentId, epd = false) {
            let attachments = epd ? this.value.attachments_epd : this.value.attachments;

            this.removeIds.push(attachmentId);
            attachments.splice(index, 1);
        },

        async loadRoles() {
            const self = this;
            const response = await api.loadRoles({paginate: 0})
            self.roles = response.data.data;
        },

        isAdmin() {
            return permissions.hasRole('admin');
        },

        handleFileUpload(file) {
            this.banner_image = file;
        },

        handleFileUploadLeft(file) {
            this.banner_image_left = file;
        },

        cancel() {
            this.$emit('cancel', this.value);
        },

        save() {
            this.value.banner_image = this.banner_image;
            this.value.banner_image_left = this.banner_image_left;

            this.formValid = true;
            for (let i in this.errors) {
                this.errors[i] = false;

                if (this.value.id && ['banner_image', 'banner_image_left'].indexOf(i) > -1) {
                    continue;
                }

                if (typeof this.value[i] === 'undefined' || this.value[i] === '') {
                    this.formValid = false;
                    this.errors[i] = true;
                }
            }

            if (this.value['videos'].length === 0) {
                this.formValid = false;
                this.errors['videos'] = true;
            }

            if (this.formValid) {
                this.$emit('save', {
                    ...this.value,
                    attachments: this.currAttachments,
                    attachments_epd: this.currAttachmentsEpd,
                    remove_attachments: this.removeIds
                });
            }
        },
    },
};
</script>

<style>
.redBorder {
    border: solid 1px red;
}

.expo-input-group{
    display: block;
    width: 100%;
    padding-top: 20px;
}

.expo-input-group > .v-input {
    padding: 0;
}

.attachment-group {
    margin-bottom: 5px;
    display: inline-block;
}
</style>
