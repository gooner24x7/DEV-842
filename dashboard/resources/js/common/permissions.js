import store from '../store/'

const permissions = {
    is_authorized: '@',

    view_enquiries_ph: 'view_enquiries_ph',
    view_enquiries_sf: 'view_enquiries_sf',
    view_enquiries_logistics: 'view_enquiries_logistics',
    view_enquiries_ap: 'view_enquiries_ap',
    view_merchant_report: 'view_merchant_report',
    view_contractor_report: 'view_contractor_report',
    view_logistics_report: 'view_logistics_report',
    view_framework_comparison: 'view_framework_comparison',
    view_contractor_benchmarking: 'view_contractor_benchmarking',

    create_enquiry_ph: 'create_enquiry_ph',
    create_quote_ph: 'create_quote_ph',
    create_enquiry_sf: 'create_enquiry_sf',
    create_quote_sf: 'create_quote_sf',
    create_enquiry_logistics: 'create_enquiry_logistics',
    create_quote_logistics: 'create_quote_logistics',

    manage_branches: 'manage_branches',
    manage_users: 'manage_users',
    manage_subscriptions: 'manage_subscriptions',
    manage_supply_chain: 'manage_supply_chain',
    manage_supply_chain_users: 'manage_supply_chain_users',
    manage_house_list: 'manage_house_list',

    view_projects: 'view_projects',
    edit_projects: 'edit_projects',
    project_assign_users: 'project_assign_users',
    view_project_pipeline: 'view_project_pipeline',
    view_tender_notices: 'view_tender_notices',

    role_user: 'user', // subcontractor
    role_contractor: 'contractor', // main contractor
    role_company: 'company', // supplier/merchant
    role_admin: 'admin',
    role_manufacturer: 'manufacturer',
    role_partner: 'partner',
    role_billing_user: 'billing_user',
    role_branch_manager: 'branch_manager',
    role_customer_success_admin: 'customer_success_admin',
    role_framework: 'framework',
    role_client: 'client',
    role_logistics: 'logistics',
    role_consultant: 'consultant',
    role_keepmoat: 'keepmoat',

    isAuthorized: () => {
        return typeof store.getters.getSession.user != 'undefined';
    },

    can(perm) {
        return permissions.isAllowed(store.getters.getSession.user, [perm]);
    },

    hasRole(roleName) {
        if (typeof store.getters.getSession.user == 'undefined') {
            return false;
        }

        let roles = store?.getters?.getSession?.user?.roles || [];

        for (let i in roles) {
            if (roles[i].slug === roleName) {
                return true;
            }
        }

        return false;
    },

    isAllowed: (user, perms) => {
        let userPermissions = [];

        if (!perms) {
            return true;
        }

        if (perms.length === 1 && perms[0] === permissions.is_authorized) {
            return true;
        }

        if (typeof user != 'undefined') {
            for (let perm of (user ? user.permissions : [])) {
                userPermissions.push(perm.slug);
            }

            for (let role of (user ? user.roles : [])) {
                userPermissions.push(role.slug);
            }
        }

        for (let perm of perms) {
            if (perm === true) {
                return true;
            }

            if (perm === permissions.is_authorized) {
                continue;
            }

            if (userPermissions.indexOf(perm) >= 0) {
                return true;
            }
        }

        return false;
    },

    getQuestionsLabel() {
        if (permissions.hasRole(permissions.role_contractor) && permissions.hasRole(permissions.role_user)) {
            return 'Materials & Hire';
        }

        return 'Purchase & Hire';
    },

    getSupplyFitLabel() {
        if (permissions.hasRole(permissions.role_contractor)) {
            return 'Trade Package Tenders';
        }

        return 'Marketplace';
    }
}

export default permissions
