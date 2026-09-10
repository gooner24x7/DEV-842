import api from "./common/api";

require('./bootstrap');
import Vue from "vue";

import Vuetify from 'vuetify';
import 'vuetify/dist/vuetify.min.css';

import router from './router/';
import store from './store/';
import eventBus from './common/event/';
import layout from './layouts/default/Layout.vue';
import '@mdi/font/css/materialdesignicons.css';
import VueSlimScroll from 'vue-slimscroll';
import moment from 'moment';
import VueGtm from '@gtm-support/vue2-gtm';

const GTM_ID = 'GTM-PBLVPJF';

Vue.use(VueGtm, {
    id: GTM_ID, // Your GTM single container ID, array of container ids ['GTM-xxxxxx', 'GTM-yyyyyy'] or array of objects [{id: 'GTM-xxxxxx', queryParams: { gtm_auth: 'abc123', gtm_preview: 'env-4', gtm_cookies_win: 'x'}}, {id: 'GTM-yyyyyy', queryParams: {gtm_auth: 'abc234', gtm_preview: 'env-5', gtm_cookies_win: 'x'}}], // Your GTM single container ID or array of container ids ['GTM-xxxxxx', 'GTM-yyyyyy']
    queryParams: {
        // Add URL query string when loading gtm.js with GTM ID (required when using custom environments)
        //gtm_auth: 'AB7cDEf3GHIjkl-MnOP8qr',
        //gtm_preview: 'env-4',
        //gtm_cookies_win: 'x',
    },
    defer: false, // Script can be set to `defer` to speed up page load at the cost of less accurate results (in case visitor leaves before script is loaded, which is unlikely but possible). Defaults to false, so the script is loaded `async` by default
    compatibility: false, // Will add `async` and `defer` to the script tag to not block requests for old browsers that do not support `async`
    //nonce: '2726c7f26c', // Will add `nonce` to the script tag
    enabled: true, // defaults to true. Plugin can be disabled by setting this to false for Ex: enabled: !!GDPR_Cookie (optional)
    debug: true, // Whether or not display console logs debugs (optional)
    loadScript: true, // Whether or not to load the GTM Script (Helpful if you are including GTM manually, but need the dataLayer functionality in your components) (optional)
    vueRouter: router, // Pass the router instance to automatically sync with router (optional)
    ignoredViews: ['homepage'], // Don't trigger events for specified router names (optional)
    trackOnNextTick: false, // Whether or not call trackView in Vue.nextTick
});

Vue.use(VueSlimScroll);
Vue.use(Vuetify);
Vue.use(eventBus);

Vue.filter('limit20Chars', function(value) {
    return (value.length > 20) ? (value.substring(0, 20) + '...') : value;
});

Vue.filter('formatPrice', function (value) {
    if (value) {
        return '£ ' + parseFloat(value).toFixed(2);
    }

    return '';
});

Vue.filter('fromNow', function(value) {
    if (value) {
        return moment(String(value)).fromNow();
    }

    return value;
});

Vue.filter('formatDateTime', function(value) {
    if (value) {
        return moment(String(value)).format("DD-MM-YYYY HH:mm");
    }

    return value;
});

Vue.filter('formatDateTimeAdm', function(value) {
    if (value) {
        return moment(String(value)).format("DD/MM/YYYY HH:mm");
    }

    return value;
});

Vue.filter('formatDate', function(value) {
    if (value) {
        return moment(String(value)).format("DD-MM-YYYY");
    }

    return value;
});

async function init() {
    const response = await function () { return api.refresh() } ();
    store.commit('setSession', response.data);
}

// utility function to filter an object by key or value
// usage: var filtered = Object.filter(scores, ([name, score]) => score > 1)
Object.filter = (obj, predicate) =>
    Object.fromEntries(Object.entries(obj).filter(predicate));

init().then(() => {
    new Vue({
        el: '#app',
        vuetify: new Vuetify({
            theme: {
                themes: {
                    light: {
                        background: '#f0f0f0',
                        primary: '#264DEC',
                    }
                },
            },
            icons: {
                iconfont: 'mdi', // 'mdi' || 'mdiSvg' || 'md' || 'fa' || 'fa4' || 'faSvg'
            },
        }),
        eventBus,
        router,
        store,
        render: h => h(layout),
    });
}).catch((response) => {
    console.log(response);
});
