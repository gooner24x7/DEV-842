<template>
    <div>
        <left-side-bar :showToolTips="showLeftMenuToolTip"></left-side-bar>

        <top-tool-bar v-if="$store.getters.showTopToolBar" :loginAs="$store.getters.getLoginAs"></top-tool-bar>

        <v-main :id="getPageId()">
            <div class="p-2">
                <div style="text-align: center" v-if="virtualExpos.length > 0 && $route.name !== 'virtual-expo-item' && isAuthorized()">
                    <v-carousel
                        hide-delimiters
                        height="64px"
                        :continuous="true"
                        :show-arrows="false"
                        cycle
                        style="width: 640px; margin: 5px auto;"
                    >
                        <v-carousel-item
                            v-for="(item,i) in virtualExpos"
                            :key="i"
                            reverse-transition="fade-transition"
                            transition="fade-transition"
                            :src="item.banner_url"
                            v-on:click.stop="viewItem(item)"
                            :alt="item.company_name"
                            class="virtual-expo-carousel-item"
                        >
                        </v-carousel-item>
                    </v-carousel>
                </div>

                <template>
                    <div>
                        <v-breadcrumbs :items="crumbs">
                            <template v-slot:divider>
                                <v-icon>mdi-chevron-right</v-icon>
                            </template>
                        </v-breadcrumbs>
                    </div>
                </template>

                <router-view :key="$route.fullPath"/>
            </div>
        </v-main>

        <loader
            :animate="$store.getters.showLoader"
            v-on:loader:stop="loaderClose"
        />

        <register-form-loader
            :animate="$store.getters.showRegFormLoader"
            v-on:loader:stop="loaderClose"
        />

        <v-snackbar
            :timeout="snackbarSettings.duration"
            :color="snackbarSettings.color"
            top
            v-model="showSnackbar"
        >
            <div v-html="snackbarSettings.message"></div>
        </v-snackbar>
    </div>
</template>

<script>
import LeftSideBar from "./LeftSideBar";
import TopToolBar from "./TopToolBar";
import Loader from "./Loader";
import RegisterFormLoader from "../../components/RegisterFormLoader";
import api from "../../common/api.js";
import permissions from "../../common/permissions";

export default {
    components: {LeftSideBar, TopToolBar, Loader, RegisterFormLoader},
    data() {
        return {
            crumbs: [],
            virtualExpos: [],
            showLeftMenuToolTip: false,
        };
    },
    async mounted() {
        const self = this;

        self.getBreadcrumbs();

        self.virtualExpos = [];

        self.$eventBus.$on('authorized', async () => {
            await self.loadVirtualExpoAdd();
        })
    },
    computed: {
        snackbarSettings() {
            return {
                'message': this.$store.getters.snackbarMessage,
                'duration': this.$store.getters.snackbarDuration,
                'color': this.$store.getters.snackbarColor,
            };
        },
        showSnackbar: {
            get() {
                return this.$store.getters.showSnackbar;
            },
            set(val) {
                if (!val) this.$store.commit("hideSnackbar");
            },
        },
    },

    watch: {
        $route (to, from){
            //console.log(to);
            this.getBreadcrumbs();
        }
    },

    methods: {
        getPageId() {
            if (this.$route.name === 'virtual-expo-item') {
                return 'virtual-expo-item-' + this.$route.params['virtual-expo-id'];
            }

            return undefined
        },
        async viewItem(item) {

            await api.newAnalyticsEvent({
                'object_id': item.id,
                'object_type': 'virtual-expo',
                'action': 'click',
                'location': 'top-banner',
                'url': window.location.pathname,
            });

            await this.$router.push({
                name: 'virtualExpoItem',
                query: {
                    t: new Date().getTime()
                },
                params: {
                    'virtual_expo_id': item.id
                }
            });
        },

        isAuthorized() {
            return permissions.isAuthorized()
        },

        isCompany() {
            return permissions.hasRole(permissions.role_company);
        },

        isUser() {
            return permissions.hasRole(permissions.role_user);
        },

        isContractor() {
            return permissions.hasRole(permissions.role_contractor)
        },

        async loadVirtualExpoAdd() {
            const self = this;

            const response = await api.getVirtualExpos()
            self.virtualExpos = response.data.data

            self.virtualExpos.map((item) => {
                api.newAnalyticsEvent({
                    'object_id': item.id,
                    'object_type': 'virtual-expo',
                    'action': 'view',
                    'location': 'top-banner',
                    'url': window.location.pathname,
                });
            })
        },
        loaderClose() {
            const self = this;
            setTimeout(() => {
                self.$store.commit("hideRegFormLoader");
            }, 2000);
        },
        getBreadcrumbs() {
            const self = this;

            //console.log(self.$route);

            if (self.$route.meta?.crumbs) {
                let crumbs = self.$route.meta.crumbs;
                let params = self.$route.params;

                crumbs.forEach((item, index) => {
                    if (item.to) {
                        let regex = /:[A-Za-z]+/i;
                        let match = item.to.match(regex);

                        if (match) {
                            let paramKey = match[0].replace(':', '');

                            if (params[paramKey]) {
                                item.to = item.to.replace(match[0], params[paramKey]);
                            }
                        }
                    }

                    crumbs[index] = item;
                });

                self.crumbs = crumbs;
            }
        }
    },
};
</script>
