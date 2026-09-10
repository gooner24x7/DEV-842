import Vue from 'vue'
import Router from 'vue-router'
import permissions from '../common/permissions.js'
import store from "../store";

Vue.use(Router);

const router = new Router({
    mode: 'history',
    base: '/',
    routes: [
        {
            name: 'dashboard',
            path: '/',
            component: require('../pages/Home').default,
            props: (route) => ({forbidden: route.query.forbidden}),
            meta: {
                allow: [
                    permissions.is_authorized
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: true,
                    }
                ]
            },
        },
        {
            name: 'login',
            path: '/login',
            component: require('../pages/Login').default,
            meta: {
                crumbs: []
            }
        },
        {
            name: 'password-reset',
            path: '/password-reset',
            component: require('../pages/PasswordReset').default,
            meta: {
                crumbs: []
            }
        },
        {
            name: 'register',
            path: '/register',
            component: require('../pages/Register').default,
            meta: {
                crumbs: []
            }
        },
        {
            name: 'profile',
            path: '/profile',
            component: require('../pages/Profile').default,
            meta: {
                allow: [
                    permissions.is_authorized
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Profile',
                        to: '/profile',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'reports',
            path: '/reports',
            component: require('../pages/Reports').default,
            meta: {
                allow: [
                    permissions.view_merchant_report
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Reports',
                        to: '/reports',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'logistics-report',
            path: '/logistics-report',
            component: require('../pages/LogisticsReport.vue').default,
            meta: {
                allow: [
                    permissions.view_logistics_report
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Logistics Report',
                        to: '/logistics-report',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'contractor-report',
            path: '/contractor-report',
            component: require('../pages/ContractorReport.vue').default,
            meta: {
                allow: [
                    permissions.view_contractor_report
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Contractor Report',
                        to: '/contractor-report',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'review-templates',
            path: '/review-templates',
            component: require('../pages/ReviewTemplates.vue').default,
            meta: {
                allow: [
                    permissions.role_admin,
                    permissions.role_contractor
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Review Templates',
                        to: '/review-templates',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'questions',
            path: '/questions',
            component: require('../pages/Questions').default,
            meta: {
                allow: [
                    permissions.view_enquiries_ph
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: permissions.getQuestionsLabel(),
                        to: '/questions',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'answers',
            path: '/answers/:question_id',
            component: require('../pages/Answers').default,
            meta: {
                allow: [
                    permissions.view_enquiries_ph
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: permissions.getQuestionsLabel(),
                        to: '/questions',
                        disabled: false,
                    },
                    {
                        text: "Quotes",
                        to: '/answers',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'archived-enquiries',
            path: '/archived-enquiries',
            component: require('../pages/ArchivedEnquiries').default,
            meta: {
                allow: [
                    permissions.view_enquiries_ph
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: permissions.getQuestionsLabel() + " Archive",
                        to: '/archived-enquiries',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'supply-fit-enquiries',
            path: '/supply-fit-enquiries',
            component: require('../pages/SupplyFitEnquiries.vue').default,
            meta: {
                allow: [
                    permissions.view_enquiries_sf
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: permissions.getSupplyFitLabel(),
                        to: '/supply-fit-enquiries',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'archived-supply-fit-enquiries',
            path: '/archived-supply-fit-enquiries',
            component: require('../pages/SupplyFitEnquiriesArchive.vue').default,
            meta: {
                allow: [
                    permissions.view_enquiries_sf
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: permissions.getSupplyFitLabel() + ' Archive',
                        to: '/archived-supply-fit-enquiries',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'supply-fit-quotes',
            path: '/supply-fit-enquiries/:enquiry_id/quotes',
            component: require('../pages/SupplyFitEnquiryQuotes.vue').default,
            meta: {
                allow: [
                    permissions.view_enquiries_sf
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: permissions.getSupplyFitLabel(),
                        to: '/supply-fit-enquiries',
                        exact: true,
                        disabled: false,
                    },
                    {
                        text: 'Quotes',
                        to: '/supply-fit-enquiries/:enquiry_id/quotes',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'supply-fit-quote-progress',
            path: '/supply-fit-quote-progress',
            component: require('../pages/SupplyFitQuoteProgress.vue').default,
            meta: {
                allow: [
                    permissions.view_enquiries_sf,
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: permissions.getSupplyFitLabel(),
                        to: '/supply-fit-enquiries',
                        disabled: false,
                    },
                    {
                        text: 'Quote Progress',
                        to: '/supply-fit-quote-progress',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'logistics-enquiries',
            path: '/logistics-enquiries',
            component: require('../pages/LogisticsEnquiries.vue').default,
            meta: {
                allow: [
                    permissions.view_enquiries_logistics
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Logistics Enquiries',
                        to: '/logistics-enquiries',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'archived-logistics-enquiries',
            path: '/archived-logistics-enquiries',
            component: require('../pages/LogisticsEnquiriesArchive.vue').default,
            meta: {
                allow: [
                    permissions.view_enquiries_logistics
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Logistics Archive',
                        to: '/archived-logistics-enquiries',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'logistics-quotes',
            path: '/logistics-quotes/:enquiry_id',
            component: require('../pages/LogisticsQuotes.vue').default,
            meta: {
                allow: [
                    permissions.view_enquiries_logistics
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Logistics Enquiries',
                        to: '/logistics-enquiries',
                        disabled: false,
                    },
                    {
                        text: 'Logistics Quotes',
                        to: '/logistics-quotes',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'customer-support',
            path: '/customer-support',
            component: require('../pages/CustomerSupport').default,
            meta: {
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Support',
                        to: '/customer-support',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'tickets',
            path: '/tickets',
            component: require('../pages/Tickets').default,
            meta: {
                allow: [
                    permissions.is_authorized
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Tickets',
                        to: '/tickets',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'tutorials',
            path: '/tutorials',
            component: require('../pages/Tutorials').default,
            meta: {
                allow: [
                    permissions.role_admin
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Tutorials',
                        to: '/tutorials',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'virtual-expo',
            path: '/virtual-expo',
            component: require('../pages/VirtualExpo').default,
            meta: {
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Exhibitions',
                        to: '/virtual-expo',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'virtualExpoItem',
            path: '/virtual-expo/:virtual_expo_id',
            props: true,
            component: require('../pages/VirtualExpoCarousel').default,
            meta: {
                allow: [
                    permissions.is_authorized
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Exhibitions',
                        to: '/virtual-expo',
                        exact: true,
                        disabled: false,
                    },
                    {
                        text: 'Exhibition Item',
                        to: '/virtual-expo/:virtual_expo_id',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'seen-by-merchants',
            path: '/questions/:enquiry_id/seen-by-merchants',
            props: true,
            component: require('../pages/SeenByMerchants').default,
            meta: {
                allow: [
                    permissions.role_admin,
                    permissions.role_customer_success_admin
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Seen By Merchants',
                        to: '/seen-by-merchants',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'merchants-preferred',
            path: '/questions/:enquiry_id/merchants-preferred',
            props: true,
            component: require('../pages/MerchantsPreferred').default,
            meta: {
                allow: [
                    permissions.role_admin,
                    permissions.role_customer_success_admin
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Preferred Merchants',
                        to: '/merchants-preferred',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'categories',
            path: '/categories',
            component: require('../pages/Categories').default,
            meta: {
                allow: [
                    permissions.role_admin
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Categories',
                        to: '/categories',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'products',
            path: '/products',
            component: require('../pages/Products').default,
            meta: {
                allow: [
                    permissions.role_admin
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Products',
                        to: '/products',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'users',
            path: '/users',
            component: require('../pages/Users').default,
            meta: {
                allow: [
                    permissions.role_admin
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Users',
                        to: '/users',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'permissions-manager',
            path: '/permissions-manager',
            component: require('../pages/PermissionsManager').default,
            meta: {
                allow: [
                    permissions.role_admin
                ]
            }
        },
        {
            name: 'zoho-report',
            path: '/zoho-report',
            component: require('../pages/ZohoReport').default,
            meta: {
                allow: [
                    permissions.role_admin
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Zoho Report',
                        to: '/zoho-report',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'zoho-map',
            path: '/zoho-map',
            component: require('../pages/CustomersMap').default,
            meta: {
                allow: [
                    permissions.role_admin
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Zoho Map',
                        to: '/zoho-map',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'stripe-plans',
            path: '/stripe-plans',
            component: require('../pages/StripePlans').default,
            meta: {
                allow: [
                    permissions.role_admin
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Stripe Plans',
                        to: '/stripe-plans',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'users-for-billing-user',
            path: '/users/billing_user/:billing_user_id',
            component: require('../pages/Users').default,
            props: true,
            meta: {
                allow: [
                    permissions.manage_users
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Users',
                        to: '/users',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'roles',
            path: '/roles',
            component: require('../pages/Roles').default,
            meta: {
                allow: [
                    permissions.role_admin
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Roles',
                        to: '/roles',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'branch-enquiries-quotes',
            path: '/branch-enquiries/:branchId/quotes/:question_id',
            props: true,
            component: require('../pages/BranchEnquiriesQuotes').default,
            meta: {
                allow: [
                    permissions.view_enquiries_ph
                ]
            }
        },
        {
            name: 'branch-enquiries',
            path: '/branch-enquiries/:branchId/:isQuoted/:onlyUnexpiredDefault',
            props: true,
            component: require('../pages/BranchEnquiries').default,
            meta: {
                allow: [
                    permissions.view_enquiries_ph
                ]
            }
        },
        {
            name: 'manage_branches',
            path: '/manage_branches',
            component: require('../pages/ManageBranches').default,
            meta: {
                allow: [
                    permissions.manage_branches
                ]
            }
        },
        {
            name: 'settings',
            path: '/settings',
            component: require('../pages/Settings').default,
            meta: {
                allow: [
                    permissions.is_authorized
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Settings',
                        to: '/settings',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'our-partners',
            path: '/our-partners',
            component: require('../pages/OurPartners').default,
            meta: {
                allow: [
                    permissions.is_authorized
                ]
            }
        },
        {
            name: 'questionnaire-replies',
            path: '/projects/:projectId/works-packages/:worksPackageId/questionnaire-replies',
            component: require('../pages/QuestionnaireReplies').default,
            meta: {
                allow: [
                    permissions.view_projects
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Projects',
                        to: '/projects',
                        exact: true,
                        disabled: false,
                    },
                    {
                        text: 'Works Packages',
                        to: '/projects/:projectId/works-packages',
                        exact: true,
                        disabled: false,
                    },
                    {
                        text: 'Questionnaire Replies',
                        to: '/projects/:projectId/works-packages/:worksPackageId/questionnaire-replies',
                        disabled: true,
                    },
                ]
            }
        },
        {
            name: 'projects',
            path: '/projects',
            component: require('../pages/Projects').default,
            meta: {
                allow: [
                    permissions.view_projects
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Projects',
                        to: '/projects',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'workspace-projects',
            path: '/workspace-projects',
            component: require('../pages/Projects').default,
            meta: {
                allow: [
                    permissions.view_projects
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Projects',
                        to: '/projects',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'archived-projects',
            path: '/archived-projects',
            component: require('../pages/ArchivedProjects').default,
            meta: {
                allow: [
                    permissions.view_projects
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Archived Projects',
                        to: '/archived-projects',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'archived-workspace-projects',
            path: '/archived-workspace-projects',
            component: require('../pages/ArchivedProjects').default,
            meta: {
                allow: [
                    permissions.view_projects
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Archived Projects',
                        to: '/archived-projects',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'project-documents',
            path: '/project-documents',
            component: require('../pages/ProjectDocuments').default,
            meta: {
                allow: [
                    permissions.view_projects,
                    permissions.view_project_pipeline
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Projects',
                        to: '/projects',
                        disabled: false,
                    },
                    {
                        text: 'Documents',
                        to: '/project-documents',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'project-document-revisions',
            path: '/project-document-revisions',
            component: require('../pages/ProjectDocumentRevisions').default,
            meta: {
                allow: [
                    permissions.view_projects,
                    permissions.view_project_pipeline
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Projects',
                        to: '/projects',
                        disabled: false,
                    },
                    {
                        text: 'Document Revisions',
                        to: '/project-document-revisions',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'project-document-categories',
            path: '/project-document-categories',
            component: require('../pages/ProjectDocumentCategories.vue').default,
            meta: {
                allow: [
                    permissions.view_projects,
                    permissions.view_project_pipeline
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Projects',
                        to: '/projects',
                        disabled: false,
                    },
                    {
                        text: 'Document Categories',
                        to: '/project-document-categories',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'project-pipeline',
            path: '/project-pipeline',
            component: require('../pages/ProjectPipeline').default,
            meta: {
                allow: [
                    permissions.view_project_pipeline
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Project Pipeline',
                        to: '/project-pipeline',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'works-packages-pipeline',
            path: '/projects/:projectId/works-packages-pipeline',
            component: require('../pages/WorksPackagesPipeline').default,
            meta: {
                allow: [
                    permissions.view_project_pipeline
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Project Pipeline',
                        to: '/project-pipeline',
                        disabled: false,
                        exact: true,
                    },
                    {
                        text: 'Works Packages',
                        to: '/works-packages-pipeline',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'works-packages',
            path: '/projects/:projectId/works-packages',
            component: require('../pages/WorksPackages').default,
            meta: {
                allow: [
                    permissions.view_projects
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Projects',
                        to: '/projects',
                        disabled: false,
                        exact: true,
                    },
                    {
                        text: 'Works Packages',
                        to: '/works-packages',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'works-package-questions',
            path: '/works-package-questions',
            component: require('../pages/WorksPackageQuestions').default,
            meta: {
                allow: [
                    permissions.view_projects
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Projects',
                        to: '/projects',
                        disabled: false,
                    },
                    {
                        text: 'Works Packages',
                        to: '/works-packages',
                        disabled: true,
                    },
                    {
                        text: 'Clarifications',
                        to: '/works-package-questions',
                        disabled: true,
                    },
                ]
            }
        },
        {
            name: 'project-quote-progress',
            path: '/project-quote-progress',
            component: require('../pages/ProjectQuoteProgress.vue').default,
            meta: {
                allow: [
                    permissions.view_projects
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Projects',
                        to: '/projects',
                        disabled: false,
                    },
                    {
                        text: 'Quote Progress',
                        to: '/project-quote-progress',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'templates',
            path: '/templates',
            component: require('../pages/Templates').default,
            meta: {
                allow: [
                    permissions.view_projects
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Projects',
                        to: '/projects',
                        disabled: false,
                    },
                    {
                        text: 'Works Packages',
                        to: '/works-packages',
                        disabled: false,
                    },
                    {
                        text: 'Questionnaires',
                        to: '/questionnaires',
                        disabled: false,
                    },
                    {
                        text: 'Templates',
                        to: '/templates',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'questionnaires',
            path: '/projects/:projectId/works-packages/:worksPackageId/questionnaire-items',
            props: true,
            component: require('../pages/QuestionnaireItems').default,
            meta: {
                allow: [
                    permissions.view_projects
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Projects',
                        to: '/projects',
                        exact: true,
                        disabled: false,
                    },
                    {
                        text: 'Works Packages',
                        to: '/projects/:projectId/works-packages',
                        exact: true,
                        disabled: false,
                    },
                    {
                        text: 'Questionnaires',
                        to: '/projects/:projectId/works-packages/:worksPackageId/questionnaire-items',
                        disabled: true,
                    },
                ]
            }
        },
        {
            name: 'user-stats',
            path: '/customer-success-platform/user-stats',
            props: true,
            component: require('../pages/csp/UserStats').default,
            meta: {
                allow: [
                    permissions.role_admin,
                    permissions.role_customer_success_admin
                ]
            }
        },
        {
            name: 'zoho-deals',
            path: '/customer-success-platform/zoho-deals',
            props: true,
            component: require('../pages/csp/ZohoDeals').default,
            meta: {
                allow: [
                    permissions.role_admin,
                    permissions.role_customer_success_admin
                ]
            }
        },
        {
            name: 'called_merchants',
            path: '/called-merchants',
            props: true,
            component: require('../pages/CalledMerchants').default,
            meta: {
                allow: [
                    permissions.role_admin
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Called Merchants',
                        to: '/called-merchants',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'preferred-suppliers',
            path: '/preferred-suppliers',
            props: true,
            component: require('../pages/PreferredSuppliers').default,
            meta: {
                allow: [
                    permissions.role_admin,
                    permissions.role_customer_success_admin
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Preferred Suppliers',
                        to: '/preferred-suppliers',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'housebuilding-products',
            path: '/housebuilding-products',
            props: true,
            component: require('../pages/HousebuildingProducts').default,
            meta: {
                allow: [
                    permissions.manage_house_list
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Housebuilding Products',
                        to: '/housebuilding-products',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'supply-chain-users',
            path: '/supply-chain-users',
            props: true,
            component: require('../pages/SupplyChainUsers').default,
            meta: {
                allow: [
                    permissions.manage_supply_chain_users
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Local SME Users',
                        to: '/supply-chain-users',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'supply-chain-management',
            path: '/supply-chain-management',
            props: true,
            component: require('../pages/SupplyChainManagement').default,
            meta: {
                allow: [
                    permissions.manage_supply_chain
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Supply Chain Management',
                        to: '/supply-chain-management',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'supply-chain-subcontractors',
            path: '/supply-chain-subcontractors',
            props: true,
            component: require('../pages/SupplyChainSubcontractors').default,
            meta: {
                allow: [
                    permissions.manage_supply_chain
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Supply Chain Management',
                        to: '/supply-chain-management',
                        disabled: false,
                    },
                    {
                        text: 'Preferred Subcontractors',
                        to: '/supply-chain-subcontractors',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'tender-notices',
            path: '/tender-notices',
            props: true,
            component: require('../pages/TenderNotices').default,
            meta: {
                allow: [
                    permissions.view_tender_notices
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Tender Notices',
                        to: '/tender-notices',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'tender-notice-details',
            path: '/tender-notice-details',
            props: true,
            component: require('../pages/TenderNoticeDetails').default,
            meta: {
                allow: [
                    permissions.view_tender_notices
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Tender Notices',
                        to: '/tender-notices',
                        disabled: false,
                    },
                    {
                        text: 'Details',
                        to: '/tender-notice-details',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'onboarding-form',
            path: '/onboarding-form',
            props: true,
            component: require('../pages/OnboardingForm').default,
            meta: {
                allow: [
                    permissions.is_authorized
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Onboarding Form',
                        to: '/onboarding-form',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'onboarding-manager',
            path: '/onboarding-manager',
            props: true,
            component: require('../pages/OnboardingManager').default,
            meta: {
                allow: [
                    permissions.role_admin
                ]
            }
        },
        {
            name: 'framework-comparison',
            path: '/framework-comparison',
            props: true,
            component: require('../pages/FrameworkComparison').default,
            meta: {
                allow: [
                    permissions.view_framework_comparison
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Framework Comparison',
                        to: '/framework-comparison',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'contractor-benchmarking',
            path: '/contractor-benchmarking',
            props: true,
            component: require('../pages/ContractorBenchmarking').default,
            meta: {
                allow: [
                    permissions.view_contractor_benchmarking
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Contractor Benchmarking',
                        to: '/contractor-benchmarking',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'contractor-profile',
            path: '/contractor-profile',
            props: true,
            component: require('../pages/ContractorProfile').default,
            meta: {
                allow: [
                    permissions.view_contractor_benchmarking
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Contractor Benchmarking',
                        to: '/contractor-benchmarking',
                        disabled: false,
                    },
                    {
                        text: 'Contractor Profile',
                        to: '/contractor-profile',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'apprenticeship-enquiries',
            path: '/apprenticeship-enquiries',
            props: true,
            component: require('../pages/ApprenticeshipEnquiries').default,
            meta: {
                allow: [
                    permissions.view_enquiries_ap
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Apprenticeship Enquiries',
                        to: '/apprenticeship-enquiries',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'apprenticeship-responses',
            path: '/apprenticeship-responses',
            props: true,
            component: require('../pages/ApprenticeshipResponses').default,
            meta: {
                allow: [
                    permissions.view_enquiries_ap
                ],
                crumbs: [
                    {
                        text: 'Home',
                        to: '/dashboard',
                        disabled: false,
                    },
                    {
                        text: 'Apprenticeship Enquiries',
                        to: '/apprenticeship-enquiries',
                        disabled: false,
                    },
                    {
                        text: 'Apprenticeship Responses',
                        to: '/apprenticeship-responses',
                        disabled: true,
                    }
                ]
            }
        },
        {
            name: 'enquiry_action',
            path: '/enquiry-action',
            component: require('../pages/EnquiryAction').default,
        },
        { path: '*', redirect: '/' },
    ],
})

router.beforeEach((to, from, next) => {
    let {allow} = to.meta

    //console.log(allow);

    if (typeof allow != 'undefined') {
        if (!permissions.isAuthorized()) {
            return next({path: '/login', query: {returnUrl: to.path}});
        }

        if (!permissions.isAllowed(store.getters.getSession.user, allow)) {
            return next({path: '/', query: {forbidden: true}});
        }
    }

    next();
})

export default router
