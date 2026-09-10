import Vue from 'vue'
import Vuex from 'vuex'
import createPersistedState from "vuex-persistedstate"
import api from "../common/api"

Vue.use(Vuex)

export default new Vuex.Store({
    plugins: [
        createPersistedState()
    ],
    state: {
        showLoader: false,
        showRegFormLoader: false,
        showSnackbar: false,
        leftMenu: false,
        topToolBar: true,
        loginAs: false,
        loginAsReturnId: 0,
        loginAsReturnUrl: '',
        session: [],
        tenderNoticesFilters: {}
    },
    mutations: {
        showLoader(state) {
            state.showLoader = true;
        },
        hideLoader(state) {
            state.showLoader = false;
        },
        showRegFormLoader(state) {
            state.showRegFormLoader = true;
        },
        hideRegFormLoader(state) {
            state.showRegFormLoader = false;
        },
        toggleleftMenu(state, val) {
            state.leftMenu = val;
        },
        toggleTopToolBar(state) {
            state.topToolBar = !state.topToolBar;
        },
        showSnackbar(state, data) {
            state.snackbarDuration = data.duration || 8000;
            state.snackbarMessage = data.message || 'No message.';
            state.snackbarColor = data.color || 'info';
            state.showSnackbar = true;
        },
        hideSnackbar(state) {
            state.showSnackbar = false;
        },
        setSession(state, session) {
            state.session = session;
        },
        setLoginAs(state, val) {
            state.loginAs = val;
        },
        setLoginAsReturnId(state, val) {
            state.loginAsReturnId = val;
        },
        setLoginAsReturnUrl(state, val) {
            state.loginAsReturnUrl = val;
        },
        setTenderNoticesFilters(state, val) {
            state.tenderNoticesFilters = val;
        }
    },
    getters: {
        showLoader: state => {
            return state.showLoader;
        },
        showRegFormLoader: state => {
            return state.showRegFormLoader;
        },
        leftMenu: state => {
            return state.leftMenu;
        },
        showSnackbar: state => {
            return state.showSnackbar;
        },
        snackbarMessage: state => {
            return state.snackbarMessage;
        },
        snackbarColor: state => {
            return state.snackbarColor;
        },
        snackbarDuration: state => {
            return state.snackbarDuration;
        },
        showTopToolBar: state => {
            return state.topToolBar;
        },
        getSession: state => {
            return state.session;
        },
        getLoginAs: state => {
            return state.loginAs;
        },
        getLoginAsReturnId: state => {
            return state.loginAsReturnId;
        },
        getLoginAsReturnUrl: state => {
            return state.loginAsReturnUrl;
        },
        getTenderNoticesFilters: state => {
            return state.tenderNoticesFilters;
        }
    }
})
