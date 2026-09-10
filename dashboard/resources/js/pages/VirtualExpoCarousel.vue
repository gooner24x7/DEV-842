<template>
    <div style="margin: 1em">
        <div class="now_live"><h2>Virtual Expo <span>NOW LIVE!</span></h2>
            <div v-if="currentVirtualExpo"><img :src="currentVirtualExpo.banner_url"></div>
        </div>

        <v-alert v-if="items.length === 0">Empty list</v-alert>

        <div v-if="items.length>0">
            <v-row>
                <v-col cols="12" md="6">
                    <v-carousel v-model="currentIndex">
                        <v-carousel-item
                            eager
                            v-for="(item,i) in items"
                            :key="i"
                            reverse-transition="fade-transition"
                            transition="fade-transition"
                        >
                            <div class="carousel-item-video" :id="'video-container-' + i" :data-video-id="getVideoId(item.video)"></div>
                        </v-carousel-item>
                    </v-carousel>
                </v-col>
                <v-col cols="12" md="6">
                    <v-container fluid grid-list-sm>
                        <v-layout row wrap>
                            <v-flex v-for="(item,i) in items" :key="i" xs4 style="position: relative">
                                <div class="carousel-item-video" v-html="item.video"></div>
                                <a href="#" v-on:click.prevent="currentIndex=i;"
                                   style="position:absolute; top: 0; left: 0; width: 100%; height: 100%;"></a>
                            </v-flex>
                        </v-layout>
                    </v-container>
                </v-col>
            </v-row>
        </div>

        <div>
            <div class="exhibition_attachments">
                <h3>Attached files</h3>

                <div class="exhibition_files">
                    <div :class="'file_' + key" v-for="(file, key) in (currentVirtualExpo?.attachments ?? [])">
                        <v-btn v-on:click="getAttachment(currentVirtualExpo.id, file)">{{ file.filename }}</v-btn>
                    </div>
                </div>
            </div>

            <div class="exhibition_attachments_epd">
                <h3>EPD Information</h3>

                <div class="exhibition_files">
                    <div :class="'file_' + key" v-for="(file, key) in (currentVirtualExpo?.attachments_epd ?? [])">
                        <v-btn v-on:click="getAttachment(currentVirtualExpo.id, file)">{{ file.filename }}</v-btn>
                    </div>
                </div>
            </div>

            <p class="text-center mt-2 font-weight-bold">
                Want to talk about the products you have seen today?
                <v-btn class="primary ml-1" v-on:click="shareContactInfo">Share contact information</v-btn>
            </p>

            <div class="shared_contacts">
                <h3>Shared Contacts</h3>
                <v-data-table
                    item-key="id"
                    :headers="headers"
                    :items="contacts"
                    :server-items-length="serverItemsLength"
                    :loading="loader"
                    :options.sync="options"
                    loading-text="Loading... Please wait"
                >
                    <template v-slot:item.banner_image="{item}">
                        <a :href="item.banner_image">
                            <img :src="item.banner_image" height="50px" alt="Banner Image"/>
                        </a>
                    </template>

                    <template v-slot:item.actions="{ item }">
                        <div class="text-center">
                            <v-btn color="primary" small @click="deleteItem(item)"
                            >Delete
                            </v-btn
                            >
                            <v-tooltip top>
                                <template v-slot:activator="{ on, attrs }">
                                    <v-btn
                                        small
                                        class="mr-2"
                                        @click="viewItem(item)"
                                        icon
                                        color="black"
                                        v-bind="attrs"
                                        v-on="on"
                                    >
                                        <v-badge
                                            :value="item.newMsgQty > 0"
                                            color="red"
                                            :content="item.newMsgQty"
                                            :overlap="true"
                                        >
                                            <v-icon>
                                                mdi-forum
                                            </v-icon>
                                        </v-badge>
                                    </v-btn
                                    >
                                </template>
                                <span>Live Chat</span>
                            </v-tooltip>
                        </div>
                    </template>
                </v-data-table>
            </div>

            <v-dialog v-model="chatDialog" max-width="500px">
                <chat
                    :visibility="chatDialog"
                    :sharedContact="sharedContact"
                    v-on:close="chatDialog = false"
                />
            </v-dialog>
        </div>
    </div>
</template>

<script>
import api from "../common/api";
import permissions from "../common/permissions";
import Chat from "../components/ChatVirtualExpo";
const YTPlayer = require('yt-player')

export default {
    components: {
        Chat,
    },
    data() {
        return {
            currentIndex: 0,
            loader: false,
            chatDialog: false,
            items: [],
            contacts: [],
            currentVirtualExpo: null,
            sharedContact: undefined,
            headers: [
                {
                    text: 'Name',
                    value: 'company_name',
                    sortable: true,
                },
                {
                    text: 'Banner Image',
                    value: 'banner_image',
                    sortable: true,
                },
                {
                    text: 'Date Start',
                    value: 'date_start',
                    sortable: true,
                },
                {
                    text: 'Date End',
                    value: 'date_end',
                    sortable: true,
                },
                {
                    text: '',
                    value: 'actions',
                    sortable: false,
                },
            ],
            serverItemsLength: 0,
            options: {
                page: 1,
                itemsPerPage: 10,
                sortBy: ['id'],
                sortDesc: [true],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false,
            },
        }
    },
    async mounted() {
        const self = this;

        const response = await api.loadVirtualExpoById(self.$route.params['virtual_expo_id']);
        self.currentVirtualExpo = response.data;

        await self.load();
        await self.loadContacts();

        setTimeout(() => {
            self.items.map((item, index) => {
                let videoId = document.getElementById('video-container-' + index).getAttribute('data-video-id')
                if (videoId) {
                    const player = new YTPlayer('#video-container-' + index)
                    player.load(videoId)
                    player.setSize('100%', 315)
                    player.on('playing', () => {
                        self.playVideo(videoId)
                    })
                    console.log(player)
                }
            })
        }, 1000);
    },
    watch: {
        currentIndex: (o, v) => {
        },
    },
    methods: {
        getVideoId(video) {
            return /([^\/]+)\?.*/gm.exec(video)?.[1] ?? ''
        },

        getAttachment(expoId, item) {
            api.newAnalyticsEvent({
                'object_id': expoId,
                'object_type': 'virtual-expo',
                'action': 'download',
                'target': item.url,
                'location': 'virtual-expo',
                'url': window.location.pathname,
            });

            window.open(item.url, '__blank');
        },

        playVideo(itemId) {
            const self = this;
            api.newAnalyticsEvent({
                'object_id': self.$route.params['virtual_expo_id'],
                'object_type': 'virtual-expo',
                'action': 'play-video',
                'target': itemId,
                'location': 'virtual-expo',
                'url': window.location.pathname,
            });
        },

        viewItem(item) {
            const self = this;
            self.sharedContact = item;
            self.chatDialog = !this.chatDialog;
        },

        isCompany() {
            return permissions.hasRole(permissions.role_company);
        },

        async shareContactInfo() {
            const self = this;

            await api.storeVirtualExpoSharedContact({
                'virtual_expo_id': self.$route.params['virtual_expo_id'],
            })

            await self.loadContacts();

            self.$store.commit("showSnackbar", {
                message: "Success!",
                color: "success",
            });
        },
        async deleteItem(item) {
            const self = this;

            if (confirm('Are you sure you want to delete this record?')) {
                self.loader = true;
                const response = await api.deleteVirtualExpoSharedContact(item.id)

                await self.loadContacts();
                self.loader = false;
            }
        },
        changeItem(e) {
            console.log(e);
        },
        async loadContacts() {
            const self = this;
            self.loader = true;

            let params = {
                ...self.options,
                page: self.options.page,
                itemsPerPage: self.options.itemsPerPage,
                sortBy: self.options.sortBy[0],
                sortDesc: self.options.sortDesc[0] ? 1 : 0,
            };

            const response = await api.loadVirtualExpoSharedContacts(params)

            self.contacts = response?.data?.data;
            self.serverItemsLength = response?.data?.total;
            self.loader = false;
        },
        async load() {
            const self = this;
            self.loader = true;

            let params = {
                paginate: 0
            };

            const response = await api.loadVirtualExpoVideos(params, self.$route.params['virtual_expo_id'])
            self.items = response?.data?.data;
            self.loader = false;
        },
    }
}
</script>

<style>
.carousel-item-video {
    height: 100%;
}

.carousel-item-video iframe {
    width: 100%;
    height: 100%;
}
</style>
