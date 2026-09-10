<template>
    <v-navigation-drawer
        v-model="showleftMenu"
        :fixed="mini"
        :temporary="mini"
        mobile-breakpoint="md"
        width="320px"
        app
    >
        <v-row class="fill-height" no-gutters>
            <v-navigation-drawer v-model="showleftMenu" width="84">
                <v-toolbar>
                    <a class="logo-link" @click.prevent="routeCall('dashboard')">
                        <img :src="getLogo" class="logo"/>
                    </a>
                </v-toolbar>

                <div class="nav-left">
                    <div>
                        <v-list dense style="width: 100%">
                            <div v-for="(item, i) in menuItems" :key="i">
                                <div v-if="item.display">
                                    <v-list-item
                                        v-if="item.route_type === 'submenu'"
                                        slot="activator"
                                        style="text-decoration: none; padding: 0;"
                                        two-line
                                        @click="subMenuItemsNum=i"
                                    >
                                        <v-list-item-content>
                                            <v-list-item-icon style="display: block;">
                                                <v-icon small>{{ item.icon }}</v-icon>
                                            </v-list-item-icon>
                                            <v-list-item-title>{{ item.label }}</v-list-item-title>
                                        </v-list-item-content>
                                    </v-list-item>

                                    <v-list-item
                                        v-if="item.route_type === 'link'"
                                        slot="activator"
                                        :href="item.href"
                                        style="text-decoration: none; padding: 0;"
                                        two-line
                                    >
                                        <v-list-item-content>
                                            <v-list-item-icon style="display: block;">
                                                <v-icon small>{{ item.icon }}</v-icon>
                                            </v-list-item-icon>
                                            <v-list-item-title>{{ item.label }}</v-list-item-title>
                                        </v-list-item-content>
                                    </v-list-item>

                                    <v-list-item
                                        v-if="item.route_type === 'vue'"
                                        slot="activator"
                                        :class="(item.route_name === $route.name) ? 'active' : '' "
                                        style="padding: 0;"
                                        two-line
                                        @click="subMenuItemsNum=null; routeCall(item.route_name, item?.params, item?.query);"
                                    >
                                        <v-list-item-content>
                                            <v-list-item-icon style="display: block;">
                                                <v-icon small>{{ item.icon }}</v-icon>
                                            </v-list-item-icon>
                                            <v-list-item-title>
                                                {{ item.label }}
                                            </v-list-item-title>
                                        </v-list-item-content>
                                    </v-list-item>
                                </div>
                            </div>
                        </v-list>
                    </div>
                    <div v-if="isAuthorized">
                        <v-list dense style="width: 100%">
                            <v-list-item
                                style="text-decoration: none; padding: 0;"
                                two-line
                                @click="routeCall('virtual-expo')"
                            >
                                <v-list-item-content>
                                    <v-list-item-icon style="display: block;">
                                        <v-icon small>fa-video</v-icon>
                                    </v-list-item-icon>
                                    <v-list-item-title>Exhibitions</v-list-item-title>
                                </v-list-item-content>
                            </v-list-item>
                        </v-list>
                    </div>
                </div>
            </v-navigation-drawer>

            <div class="grow" style="max-width: 236px;">
                <v-toolbar></v-toolbar>
                <div>
                    <v-list dense>
                        <div class="middle-menu">
                            <div v-for="(item, i) in (subMenuItemsNum ? menuItems[subMenuItemsNum].sub_menu : [])" :key="i">
                                <div v-if="item.label==='Marketplace' && onlyDemoOrLocalEnv()" style="margin-top: 20px; text-align: center;">
                                    <h4 style="font-weight: bold;">Coming Soon!</h4>
                                </div>

                                <div v-if="item?.spacer">
                                    <v-divider />
                                </div>

                                <div v-else-if="item.display">
                                    <v-list-item
                                        v-if="item.route_type === 'link'"
                                        slot="activator"
                                        :href="item.href"
                                        style="text-decoration: none"
                                    >
                                        <v-list-item-action v-if="item.icon">
                                            <v-icon small>{{ item.icon }}</v-icon>
                                        </v-list-item-action>

                                        <v-list-item-content>
                                            <v-list-item-title>
                                                <v-badge
                                                    :content="item.numberOfUpdates"
                                                    :value="item.numberOfUpdates > 0"
                                                    color="red"
                                                    inline
                                                >
                                                    <v-list-item-title>{{ item.label }}</v-list-item-title>
                                                </v-badge>
                                            </v-list-item-title>
                                        </v-list-item-content>
                                    </v-list-item>

                                    <v-list-item
                                        v-if="item.route_type === 'vue'"
                                        slot="activator"
                                        v-ripple="false"
                                        :class="(item.route_name === $route.name) ? 'active sub-menu-item' : 'sub-menu-item' "
                                        @click="routeCall(item.route_name, item?.params, item?.query)"
                                    >
                                        <v-list-item-action v-if="item.icon">
                                            <v-icon small>{{ item.icon }}</v-icon>
                                        </v-list-item-action>

                                        <v-list-item-content>
                                            <v-badge
                                                :content="item.numberOfUpdates"
                                                :value="item.numberOfUpdates > 0"
                                                color="red"
                                                inline
                                            >
                                                <v-list-item-title style="text-wrap-mode: wrap;">
                                                    {{ item.label }}
                                                </v-list-item-title>
                                            </v-badge>
                                        </v-list-item-content>
                                    </v-list-item>
                                </div>
                            </div>

                            <v-list-item v-if="!isAuthorized" to="login">
                                <v-list-item-action>
                                    <v-icon small>fa-sign-in</v-icon>
                                </v-list-item-action>

                                <v-list-item-content>
                                    <v-list-item-title class="text-xs-left">
                                        Login
                                    </v-list-item-title>
                                </v-list-item-content>
                            </v-list-item>
                        </div>
                    </v-list>
                </div>

                <!-- <v-divider></v-divider> -->

                <div class="left-sidebar">
                    <div>
                        <div v-html="banners">
                        </div>
                        <virtual-expo-single-carousel v-if="isCompanyOrContractor()"></virtual-expo-single-carousel>
                    </div>
                </div>

                <div v-show="showPartnerLinks" class="partner-links">
                    <a href="https://lenkie.com/partners/buildchain">
                        ▶ Click here to apply now for your line of credit...
                    </a>
                </div>

            </div>
        </v-row>
    </v-navigation-drawer>
</template>

<script>
import api from "../../common/api.js";
import permissions from "../../common/permissions.js";
import VirtualExpoSingleCarousel from "../../components/VirtualExpoSingleCarousel";

export default {
    components: {VirtualExpoSingleCarousel},
    props: ["showToolTips"],
    data() {
        return {
            subMenuItemsNum: null,
            refreshHandler2: null,
            newEnquiries: 0,
            newEnquiriesChatMessages: 0,
            banners: "",
            settings: {},
            countries: [],
            country: null,
            options: {
                height: "100%",
            },
        };
    },
    filters: {
        joinNames: (value) => {
            let names = [];
            for (let i in value) {
                names.push(value[i].name);
            }
            return names.join(", ");
        },
    },
    async created() {
        const self = this;

        self.banners = window.banners;

        if (self.isAuthorized) {
            try {
                const resp = await api.me()
                self.$store.commit('setSession', {
                    ...self.$store.getters.getSession,
                    user: resp.data.user
                });

                self.newEnquiries = resp?.data?.updates?.newEnquiries ?? 0;
                self.newEnquiriesChatMessages = resp?.data?.updates.newEnquiriesChatMessages ?? 0;
            } catch (err) {
                self.$store.commit('setSession', []);
            }
        }

        self.refreshHandler2 = setInterval(async () => {
            try {
                const resp = await api.me()
                self.$store.commit('setSession', {
                    ...self.$store.getters.getSession,
                    user: resp.data.user
                });
                self.newEnquiries = resp?.data?.updates?.newEnquiries ?? 0;
                self.newEnquiriesChatMessages = resp?.data?.updates?.newEnquiriesChatMessages ?? 0;
            } catch (e) {
                console.log(e);
            }
        }, 30000);

        self.$eventBus.$on("authorized", (data) => {});
    },
    watch: {
        '$vuetify.breakpoint.mdAndDown': function (o, n) {
            this.showleftMenu = n
        },
        $route (to, from) {
            let itemNum = null;

            this.menuItems.forEach(function(item, index) {
                if (item.route_name === to.name) {
                    itemNum = index;
                }
            });

            if (to.name === 'supply-fit-enquiries') {
                itemNum = 1;
            }

            if (itemNum !== null) {
                this.subMenuItemsNum = itemNum;
            }
        }
    },
    mounted() {
        this.showleftMenu = this.mini
    },
    computed: {
        enquiriesNumberOfUpdates() {
            const self = this;
            return self.newEnquiries + self.newEnquiriesChatMessages;
        },

        menuItems() {
            const self = this;

            return [
                {
                    route_name: "dashboard",
                    route_type: "vue",
                    label: "Home",
                    display: false,
                    icon: "fa-home",
                    params: {},
                },
                {
                    route_name: "questions",
                    route_type: "submenu",
                    label: "Enquiries",
                    display: permissions.can(permissions.view_enquiries_ph) || permissions.can(permissions.view_enquiries_sf) || permissions.can(permissions.view_enquiries_logistics),
                    icon: "fa-question-circle",
                    params: {},
                    sub_menu: [
                        {
                            route_name: "questions",
                            route_type: "vue",
                            label: permissions.getQuestionsLabel(),
                            display: permissions.can(permissions.view_enquiries_ph),
                            icon: "fa-question-circle",
                            params: {},
                            numberOfUpdates: self.enquiriesNumberOfUpdates,
                        },
                        {
                            route_name: "archived-enquiries",
                            route_type: "vue",
                            label: permissions.getQuestionsLabel() + " Archive",
                            display: permissions.can(permissions.view_enquiries_ph),
                            icon: "fa-folder-open",
                            params: {},
                        },
                        {
                            route_name: "supply-fit-enquiries",
                            route_type: "vue",
                            label: permissions.getSupplyFitLabel(),
                            display: permissions.can(permissions.view_enquiries_sf),
                            icon: "fa-question-circle",
                            params: {},
                            numberOfUpdates: 0,
                        },
                        {
                            route_name: "archived-supply-fit-enquiries",
                            route_type: "vue",
                            label: permissions.getSupplyFitLabel() + " Archive",
                            display: permissions.can(permissions.view_enquiries_sf),
                            icon: "fa-folder-open",
                            params: {},
                        },
                        {
                            route_name: "called_merchants",
                            route_type: "vue",
                            label: "Called Merchants",
                            display: permissions.hasRole(permissions.role_admin),
                            icon: "fa-user-plus",
                            params: {},
                        },
                        {
                            route_name: "logistics-enquiries",
                            route_type: "vue",
                            label: "Logistics",
                            display: permissions.can(permissions.view_enquiries_logistics),
                            icon: "fa-question-circle",
                            params: {},
                        },
                        {
                            route_name: "archived-logistics-enquiries",
                            route_type: "vue",
                            label: "Logistics Archive",
                            display: permissions.can(permissions.view_enquiries_logistics),
                            icon: "fa-folder-open",
                            params: {},
                        },
                    ]
                },
                {
                    route_name: "projects",
                    route_type: "submenu",
                    label: "Projects",
                    display: permissions.can(permissions.view_projects),
                    icon: "fa-folder-open",
                    params: {},
                    sub_menu: [
                        {
                            route_name: "projects",
                            route_type: "vue",
                            label: "TBC Projects",
                            display: permissions.can(permissions.view_projects),
                            icon: "fa-folder-open",
                            query: {type: 2}
                        },
                        {
                            route_name: "archived-projects",
                            route_type: "vue",
                            label: "Archived Projects",
                            display: permissions.can(permissions.view_projects),
                            icon: "fa-folder-open",
                            query: {type: 2}
                        },
                        { spacer: true },
                        {
                            route_name: "workspace-projects",
                            route_type: "vue",
                            label: "Workspace Projects",
                            display: permissions.can(permissions.view_projects),
                            icon: "fa-folder-open",
                            query: {type: 1}
                        },
                        {
                            route_name: "archived-workspace-projects",
                            route_type: "vue",
                            label: "Archived Workspace Projects",
                            display: permissions.can(permissions.view_projects),
                            icon: "fa-folder-open",
                            query: {type: 1}
                        },
                        {
                            route_name: "contractor-report",
                            route_type: "vue",
                            label: "Reports",
                            display: permissions.hasRole(permissions.view_contractor_report),
                            icon: "fa-chart-bar",
                            params: {},
                        },
                    ]
                },
                {
                    route_name: "project-pipeline",
                    route_type: "vue",
                    label: "Project Pipeline",
                    display: permissions.can(permissions.view_project_pipeline),
                    icon: "fa-folder-open",
                    params: {},
                },
                {
                    route_name: "reports",
                    route_type: "vue",
                    label: "Reports",
                    display: permissions.can(permissions.view_merchant_report),
                    icon: "fa-chart-bar",
                    params: {},
                },
                {
                    route_name: "contractor-report",
                    route_type: "vue",
                    label: "Reports",
                    display: permissions.can(permissions.view_contractor_report),
                    icon: "fa-chart-bar",
                    params: {},
                },
                {
                    route_name: "logistics-report",
                    route_type: "vue",
                    label: "Reports",
                    display: permissions.can(permissions.view_logistics_report),
                    icon: "fa-chart-bar",
                    params: {},
                },
                // {
                //     route_name: "our-partners",
                //     route_type: "vue",
                //     label: "Our Partners",
                //     display: permissions.can(permissions.read_question),
                //     icon: "fa-users",
                //     params: {},
                // },
                {
                    route_name: 'customer-success-platform',
                    route_type: "submenu",
                    label: "Customer Success Platform",
                    icon: "fa-user-plus",
                    display: permissions.hasRole(permissions.role_customer_success_admin),
                    sub_menu: [
                        {
                            route_name: "user-stats",
                            route_type: "vue",
                            label: "User Stats",
                            display: permissions.hasRole(permissions.role_customer_success_admin),
                            icon: "fa-list",
                            params: {},
                            numberOfUpdates: self.enquiriesNumberOfUpdates,
                        },
                        {
                            route_name: "zoho-deals",
                            route_type: "vue",
                            label: "Contract Status",
                            display: permissions.hasRole(permissions.role_customer_success_admin),
                            icon: "fa-list",
                            params: {},
                        },
                    ]
                },
                // {
                //     route_type: "link",
                //     href: "//www.ntuk.co.uk/hire/support",
                //     label: "Customer Support",
                //     display: true,
                //     sub_menu: false,
                //     icon: "fa-phone",
                //     params: {},
                // },
                // {
                //     route_name: "customer-support",
                //     route_type: "vue",
                //     label: "Support",
                //     display: true,
                //     sub_menu: false,
                //     icon: "fa-phone",
                //     params: {},
                // },
                {
                    route_name: "categories",
                    route_type: "vue",
                    label: "Categories",
                    display: permissions.hasRole(permissions.role_admin),
                    icon: "fa-sitemap",
                    params: {},
                },
                {
                    route_name: "products",
                    route_type: "vue",
                    label: "Products",
                    display: permissions.hasRole(permissions.role_admin),
                    icon: "fa-wrench",
                    params: {},
                },
                {
                    route_name: "users-for-billing-user",
                    route_type: "vue",
                    label: "Users",
                    display: permissions.can(permissions.manage_users),
                    icon: "fa-user-plus",
                    params: {
                        billing_user_id: this.user ? this.user.id : null
                    },
                },
                {
                    route_name: "stripe-plans",
                    route_type: "vue",
                    label: "Stripe Plans",
                    display: permissions.hasRole(permissions.role_admin),
                    icon: "fa-stripe",
                    params: {},
                },
                {
                    route_name: "users",
                    route_type: "vue",
                    label: "Users",
                    display: permissions.hasRole(permissions.role_admin),
                    icon: "fa-user-plus",
                    params: {},
                },
                {
                    route_name: "manage_branches",
                    route_type: "vue",
                    label: "Branches",
                    display: permissions.can(permissions.manage_branches),
                    icon: "fa-user-plus",
                    params: {},
                },
                {
                    route_name: "zoho-report",
                    route_type: "vue",
                    label: "Zoho Crm Report",
                    display: permissions.hasRole(permissions.role_admin),
                    icon: "fa-user-plus",
                    params: {},
                },
                {
                    route_name: "zoho-map",
                    route_type: "vue",
                    label: "Map Zoho Users",
                    display: permissions.hasRole(permissions.role_admin),
                    icon: "fa-user-plus",
                    params: {},
                },
                {
                    route_name: "tutorials",
                    route_type: "vue",
                    label: "Tutorials",
                    display: permissions.hasRole(permissions.role_admin),
                    icon: "fa-play",
                    params: {},
                },
                {
                    route_name: "roles",
                    route_type: "vue",
                    label: "User Roles",
                    display: permissions.hasRole(permissions.role_admin),
                    icon: "fa-check-square",
                    params: {},
                },
                {
                    route_name: "preferred-suppliers",
                    route_type: "vue",
                    label: "Preferred Suppliers",
                    display: permissions.hasRole(permissions.role_admin),
                    icon: "fa-user",
                    params: {},
                },
                {
                    route_name: "housebuilding-products",
                    route_type: "vue",
                    label: "Manage House List",
                    display: permissions.can(permissions.manage_house_list),
                    icon: "fa-home",
                    params: {},
                },
                {
                    route_name: "supply-chain-users",
                    route_type: "vue",
                    label: "Local SME users",
                    display: permissions.can(permissions.manage_supply_chain_users),
                    icon: "fa-users",
                    params: {},
                },
                {
                    route_name: "supply-chain-management",
                    route_type: "vue",
                    label: "Supply Chain Management",
                    display: permissions.can(permissions.manage_supply_chain),
                    icon: "fa-users",
                    params: {},
                },
                {
                    route_name: "tender-notices",
                    route_type: "vue",
                    label: "Tender Notices",
                    display: permissions.can(permissions.view_tender_notices),
                    icon: "fa-calendar",
                    params: {},
                },
                {
                    route_name: "onboarding-manager",
                    route_type: "vue",
                    label: "Onboarding Manager",
                    display: permissions.hasRole(permissions.role_admin),
                    icon: "fa-users",
                    params: {},
                },
                {
                    route_name: "permissions-manager",
                    route_type: "vue",
                    label: "Permissions Manager",
                    display: permissions.hasRole(permissions.role_admin),
                    icon: "fa-list",
                    params: {},
                },
                {
                    route_name: "framework-comparison",
                    route_type: "vue",
                    label: "Framework Comparison",
                    display: permissions.can(permissions.view_framework_comparison),
                    icon: "fa-users",
                    params: {},
                },
                {
                    route_name: "contractor-benchmarking",
                    route_type: "vue",
                    label: "Contractor Benchmarking",
                    display: permissions.can(permissions.view_contractor_benchmarking),
                    icon: "fa-users",
                    params: {},
                },
                {
                    route_name: "apprenticeships",
                    route_type: "submenu",
                    label: "Apprenticeships",
                    display: permissions.can(permissions.view_enquiries_ap),
                    icon: "fa-question-circle",
                    params: {},
                    sub_menu: [
                        {
                            route_name: "apprenticeship-enquiries",
                            route_type: "vue",
                            label: "Apprenticeship Enquiries",
                            display: permissions.can(permissions.view_enquiries_ap),
                            icon: "fa-question-circle",
                            params: {},
                        },
                        {
                            route_name: "apprenticeship-enquiries-archive",
                            route_type: "vue",
                            label: "Apprenticeship Enquiries Archive",
                            display: permissions.can(permissions.view_enquiries_ap),
                            icon: "fa-folder-open",
                            params: {},
                        },
                    ]
                },
            ];
        },

        getLogo() {
            return '/img/logo_ntuk.png';
        },

        mini() {
            return this.$vuetify.breakpoint.mdAndDown;
        },

        session() {
            return this.$store.getters.getSession;
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        isAuthorized() {
            return permissions.isAuthorized();
        },

        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },

        showleftMenu: {
            get() {
                return this.$store.getters.leftMenu;
            },
            set(val) {
                this.$store.commit("toggleleftMenu", val);
            },
        },

        showTopToolbar: {
            get() {
                return this.$store.getters.showTopToolBar;
            },
            set(val) {
                this.$store.commit("toggleTopToolBar");
            },
        },

        showPartnerLinks() {
            return this.$store.getters.getSession.user && this.$store.getters.getSession.user.role_ids.includes(2);
        },
    },
    methods: {
        onlyDevOrLocalEnv() {
            return ['dev.thebuildchain.co.uk', 'demo.thebuildchain.co.uk', 'localhost'].indexOf(window.location.hostname) > -1;
        },

        onlyDemoOrLocalEnv() {
            return ['demo.thebuildchain.co.uk', 'localhost'].indexOf(window.location.hostname) > -1;
        },

        isManufacturer() {
            return permissions.hasRole(permissions.manufacturer);
        },

        isCompany() {
            return permissions.hasRole(permissions.role_company);
        },

        isCompanyOrContractor() {
            return permissions.hasRole(permissions.role_company) || permissions.hasRole(permissions.role_contractor) ||
                permissions.hasRole(permissions.role_user);
        },

        async clickLogout() {
            const self = this;

            await api.logout()

            self.$store.commit("setSession", []);
            self.$store.commit("setLoginAs", false);

            await self.$router.push("login");
        },

        routeCall(name, params, query) {
            this.$router.push({
                name: name,
                params: params,
                query: query
            });
        },

        getQuestionsLabel() {
            if (permissions.hasRole(permissions.role_user)) {
                return "Materials & Hire";
            }

            return "Purchase & Hire";
        },

        showContractorReport() {
            return (permissions.hasRole(permissions.role_user) && !permissions.hasRole(permissions.role_contractor))
            || permissions.hasRole(permissions.role_framework) || permissions.hasRole(permissions.role_client) || permissions.hasRole(permissions.role_admin);
        }
    },
};
</script>

<style scoped>
.user-icon {
    font-size: 1.5em;
    margin-right: 5px;
    vertical-align: bottom;
    margin-left: -20px;
}

.scroll-area {
    position: relative;
    margin: auto;
    height: auto;
}

.logo {
    max-width: 100%;
}

.logo-link {
    display: block;
    margin: 0 auto;
}

.middle-menu .v-list-item__title {
    font-weight: 550 !important;
}

.v-application--is-ltr .v-list-item__action:first-child {
    margin-right: 0;
}

.v-list-item--link:before {
    background-color: transparent;
}

.partner-links {
    text-align: center;
    padding-top: 10px;
}

.partner-links > a {
    width: 180px;
    display: block;
    height: 140px;
    background-color: #274eec;
    border-radius: 10px;
    margin: 0 auto;
    font-size: large;
    font-weight: bold;
    color: white;
    text-align: center;
    padding: 16px 8px;
}

</style>
