import axios from 'axios'
import store from '../store/index'

const api = {
    baseUrl: '/api',
    client: (headers) => {
        let props = {
            baseURL: api.baseUrl,

            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "Access-Control-Allow-Origin": "*",
                "Access-Control-Allow-Credentials": true,
                "Authorization": 'Bearer ' + api.getToken()
            }
        }

        if (typeof headers != 'undefined') {
            for (let i in headers) {
                props.headers[i] = headers[i]
            }
        }

        return axios.create(props)
    },
    'getToken': () => {
        return window.token || store.state.session.access_token;
    },
    'login': (username, password, rememberMe) => {
        return api.client().post('/auth/login', {
            'username': username,
            'password': password,
            'rememberMe': rememberMe,
        });
    },
    'passwordReset': (username) => {
        return api.client().post('/auth/password-reset', {
            'username': username,
        });
    },
    'logout': () => {
        return api.client().post('/auth/logout', {});
    },
    'refresh': () => {
        return api.client({'Authorization': ''}).post('/auth/refresh', {});
    },
    'me': () => {
        return api.client().post('/auth/me', {});
    },
    'register': (data) => {
        return api.client().post('/auth/register', data);
    },
    'saveProfile': (formData) => {
        return api.client().post('/profile', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    'getQuestion': (id) => {
        return api.client().get('/questions/' + encodeURIComponent(id), {});
    },

    'loadQuestions': (data) => {
        return api.client().get('/questions', {params: data});
    },

    loadTemplates: (data) => {
        return api.client().get('/templates', {params: data});
    },

    deleteTemplate: (id) => {
        return api.client().delete('/templates/' + encodeURIComponent(id), {});
    },

    updateTemplate: (id, formData) => {
        return api.client().post('/templates/' + encodeURIComponent(id), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    createTemplate: (formData) => {
        return api.client().post('/templates', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    loadFavInquiries: (data) => {
        return api.client().get('/inquiries/fav', {params: data});
    },

    'getSupplyFitEnquiry': (id) => {
        return api.client().get('/supply-fit-enquiries/' + encodeURIComponent(id), {});
    },

    'loadSupplyFitEnquiries': (data) => {
        return api.client().get('/supply-fit-enquiries', {params: data});
    },

    storeSupplyFitEnquiry: (data) => {
        return api.client().post('/supply-fit-enquiries', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    updateSupplyFitEnquiry: (id, data) => {
        return api.client().post('/supply-fit-enquiries/' + encodeURIComponent(id), data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    getHash(item) {
        return api.client().get('/questionnaire/hash', {
            params: {inquiry_id: item.id}
        });
    },

    'deleteSupplyFitEnquiry': (id) => {
        return api.client().delete('/supply-fit-enquiries/' + encodeURIComponent(id), {});
    },

    'archiveSupplyFitEnquiry': (id) => {
        return api.client().post('/supply-fit-enquiries/' + encodeURIComponent(id) + '/archive', {});
    },

    archiveProject: (id) => {
        return api.client().post('/projects/' + encodeURIComponent(id) + '/archive', {});
    },

    restoreProject: (id) => {
        return api.client().post('/projects/' + encodeURIComponent(id) + '/restore', {});
    },

    'restoreSupplyFitEnquiry': (id) => {
        return api.client().post('/supply-fit-enquiries/' + encodeURIComponent(id) + '/restore', {});
    },

    duplicateSupplyFitEnquiry: (item) => {
        return api.client().post('/supply-fit-enquiries/' + encodeURIComponent(item.id) + '/duplicate', item);
    },

    'loadSupplyFitEnquiryQuotes': (id, data) => {
        return api.client().get('/supply-fit-enquiries/' + encodeURIComponent(id) + '/quotes', {params: data});
    },

    'storeSupplyFitEnquiryQuote': (data, id, enquiryId) => {
        if (typeof id != 'undefined' && id > 0) {
            data.append('_method', 'PUT');

            return api.client().post('/supply-fit-enquiry-quotes/' + encodeURIComponent(id), data, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
        }

        return api.client().post('/supply-fit-enquiries/' + encodeURIComponent(enquiryId) + '/quotes', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    'deleteSupplyFitEnquiryQuote': (id) => {
        return api.client().delete('/supply-fit-enquiry-quotes/' + encodeURIComponent(id), {});
    },

    acceptSupplyFitEnquiryQuote: (id) => {
        return api.client().post('/supply-fit-enquiry-quotes/' + encodeURIComponent(id) + '/accept', {});
    },

    unacceptSupplyFitEnquiryQuote: (id) => {
        return api.client().post('/supply-fit-enquiry-quotes/' + encodeURIComponent(id) + '/unaccept', {});
    },

    'loadSupplyFitQuoteMessages': (quoteId, data) => {
        return api.client().get('/supply-fit-enquiry-quotes/' + encodeURIComponent(quoteId) + '/messages', {params: data});
    },

    'storeSupplyFitQuoteMessage': (data, quoteId) => {
        return api.client().post('/supply-fit-enquiry-quotes/' + encodeURIComponent(quoteId) + '/messages', data);
    },

    'loadEnquiryChats': (questionId, data) => {
        return api.client().get('/questions/' + encodeURIComponent(questionId) + '/chats', {params: data});
    },

    'loadEnquiryMessages': (questionId, data) => {
        return api.client().get('/questions/' + encodeURIComponent(questionId) + '/messages', {params: data});
    },

    'storeEnquiryMessage': (data, questionId) => {
        return api.client().post('/questions/' + encodeURIComponent(questionId) + '/messages', data);
    },

    'loadSupplyFitEnquiryChats': (questionId, data) => {
        return api.client().get('/supply-fit-enquiries/' + encodeURIComponent(questionId) + '/chats', {params: data});
    },

    'loadSupplyFitEnquiryMessages': (questionId, data) => {
        return api.client().get('/supply-fit-enquiries/' + encodeURIComponent(questionId) + '/messages', {params: data});
    },

    'storeSupplyFitEnquiryMessage': (data, questionId) => {
        return api.client().post('/supply-fit-enquiries/' + encodeURIComponent(questionId) + '/messages', data);
    },

    'storeQuestions': (formData) => {
        return api.client().post('/questions/batch', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    'storeQuestion': (data, id) => {
        if (typeof id != 'undefined' && id > 0) {
            return api.client().put('/questions/' + encodeURIComponent(id), data)
        }

        return api.client().post('/questions', data);
    },

    'storeQuestionsByHouseName': (formData) => {
        return api.client().post('/questions/storeByHouseName', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    'importUsersFromCsv': (billingUserId, data) => {
        return api.client().post('/users/' + encodeURIComponent(billingUserId) + '/import', data);
    },

    addToFav: (id) => {
        return api.client().post('/questions/' + encodeURIComponent(id) + '/fav', {});
    },

    removeFav: (id) => {
        return api.client().delete('/questions/' + encodeURIComponent(id) + '/fav', {});
    },

    'deleteQuestion': (id) => {
        return api.client().delete('/questions/' + encodeURIComponent(id), {});
    },

    'archiveQuestion': (id) => {
        return api.client().post('/questions/' + encodeURIComponent(id) + '/archive', {});
    },

    'restoreQuestion': (id) => {
        return api.client().post('/questions/' + encodeURIComponent(id) + '/restore', {});
    },

    duplicateEnquiry: (item) => {
        return api.client().post('/questions/' + encodeURIComponent(item.id) + '/duplicate', item);
    },

    exportQuotesComparison: (questionId, quoteIds) => {
        return api.client().get('/questions/' + encodeURIComponent(questionId) + '/answers/export-comparison', {
            params: {
                quoteIds,
            },
            responseType: 'arraybuffer'
        });
    },

    'loadAnswers': (questionId, data) => {
        return api.client().get('/questions/' + encodeURIComponent(questionId) + '/answers', {params: data});
    },

    'exportAnswers': (questionId, data) => {
        return api.client().get('/questions/' + encodeURIComponent(questionId) + '/answers/export', {
            params: data,
            responseType: 'arraybuffer'
        });
    },

    'exportQuoteComparison': (data) => {
        return api.client().get('/answers/export-comparison', {params: data, responseType: 'arraybuffer'});
    },

    'storeAnswer': (data, id, questionId) => {
        if (typeof id != 'undefined' && id > 0) {
            data.append('_method', 'PUT');

            return api.client().post('/answers/' + encodeURIComponent(id), data, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
        }

        return api.client().post('/questions/' + encodeURIComponent(questionId) + '/answers', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    'deleteAnswer': (id) => {
        return api.client().delete('/answers/' + encodeURIComponent(id), {});
    },

    acceptQuote: (id) => {
        return api.client().post('/answers/' + encodeURIComponent(id) + '/accept', {});
    },

    unacceptQuote: (id) => {
        return api.client().post('/answers/' + encodeURIComponent(id) + '/unaccept', {});
    },

    setQuoteCheckedAt: (id, value) => {
        return api.client().post('/answers/' + encodeURIComponent(id) + '/checked', {checked: value});
    },

    'loadMessages': (answerId, data) => {
        return api.client().get('/answers/' + encodeURIComponent(answerId) + '/messages', {params: data});
    },

    'storeMessage': (data, answerId) => {
        return api.client().post('/answers/' + encodeURIComponent(answerId) + '/messages', data);
    },

    'loadProducts': (data) => {
        return api.client().get('/products', {params: data});
    },

    'storeProduct': (data, id) => {
        if (typeof id != 'undefined' && id > 0) {
            return api.client().put('/products/' + encodeURIComponent(id), data)
        }
        return api.client().post('/products', data);
    },

    'deleteProduct': (id) => {
        return api.client().delete('/products/' + encodeURIComponent(id), {});
    },

    'loadRoles': (data) => {
        return api.client().get('/roles', {params: data});
    },

    'loadPermissions': (data) => {
        return api.client().get('/permissions', {params: data});
    },

    'updateRolePermissions': (roleId, data) => {
        return api.client().post('/permissions/' + encodeURIComponent(roleId), data);
    },

    getContractorStats: (data) => {
        return api.client().get('/stats/contractor', {params: data})
    },

    getMerchantStats: (data) => {
        return api.client().get('/stats/merchant', {params: data})
    },

    getManufacturerStats: (data) => {
        return api.client().get('/stats/manufacturer', {params: data})
    },

    'loadUsers': (data) => {
        return api.client().get('/users', {params: data});
    },

    loadBranches: (billingUserId, data) => {
        return api.client().get('/billing_users/' + encodeURIComponent(billingUserId) + '/branches', {params: data});
    },

    'storeUser': (data, id) => {
        if (typeof id != 'undefined' && id > 0) {
            data.append('_method', 'PUT');

            return api.client().post('/users/' + encodeURIComponent(id), data, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
        }

        return api.client().post('/users', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    'storeUserCsp': (data, id) => {
        data.append('_method', 'PUT');

        return api.client().post('/users/' + encodeURIComponent(id) + '/updateCsp', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    'deleteUser': (id) => {
        return api.client().delete('/users/' + encodeURIComponent(id), {});
    },

    'getUser': (id) => {
        return api.client().get('/users/' + encodeURIComponent(id), {});
    },

    'getCreditsafeInfo': (params) => {
        return api.client().get('/creditsafe', {params: params});
    },

    'updateQuestionnaireSessions': (userId) => {
        return api.client().get('/updateQuestionnaireSessions', {params: {id: userId}});
    },

    'productOptions': (typeIds) => {
        return api.client().get('/product-options', {
            params: {
                'type_ids': typeIds,
            }
        });
    },

    'nationalsOptions': () => {
        return api.client().get('/nationals-options');
    },

    'billingUserOptions': (search) => {
        return api.client().get('/users/role/billing_user?search=' + (search ? encodeURIComponent(search) : ''));
    },

    'companyUsersOptions': (search) => {
        return api.client().get('/users/role/company?search=' + (search ? encodeURIComponent(search) : ''));
    },

    'manufacturerUsersOptions': (search, productIds) => {
        return api.client().get('/users/role/manufacturer', {
            params: {
                productIds,
                search,
            }
        });
    },

    getManufacturerProductsOptions: (merchantId) => {
        return api.client().get('/manufacturers/' + encodeURIComponent(merchantId) + '/products');
    },

    getManufacturerByManufacturerProductId: (productId) => {
        return api.client().get('/manufacturers/byProductId/' + encodeURIComponent(productId));
    },

    getProjectOptions: (data) => {
        return api.client().get('/projects/options', {params: data});
    },

    getWorksPackageOptions: (data) => {
        return api.client().get('/works-packages/options', {params: data});
    },

    projectOptions: (search) => {
        return api.client().get('/questions/project-options?search=' + (search ? encodeURIComponent(search) : ''));
    },

    worksPackageOptions: (search, projectIds) => {
        return api.client().get('/questions/works-package-options', {params: {search: search, projectIds: projectIds}});
    },

    'projectOptionsSupplyFit': (search) => {
        return api.client().get('/supply-fit-enquiries/project-options?search=' + (search ? encodeURIComponent(search) : ''));
    },

    'projectOptionsSupplyFitArchived': (search) => {
        return api.client().get('/supply-fit-enquiries/project-options-archived?search=' + (search ? encodeURIComponent(search) : ''));
    },

    'worksPackageOptionsSupplyFit': (search, projectIds) => {
        return api.client().get('/supply-fit-enquiries/works-package-options', {params: {search: search, projectIds: projectIds}});
    },

    'suppliersOptions': (search) => {
        return api.client().get('/users/preferred-suppliers/options?search=' + (search ? encodeURIComponent(search) : ''));
    },

    'storePreferredSupplier': (userId, supplierId) => {
        return api.client().post('/users/preferred-suppliers/' + encodeURIComponent(userId), {
            'supplier_id': supplierId,
        });
    },

    'deletePreferredSupplier': (userId, supplierId) => {
        return api.client().delete('/users/preferred-suppliers/' + encodeURIComponent(userId) + "/" + encodeURIComponent(supplierId));
    },

    'getPreferredSuppliers': (userId, data) => {
        return api.client().get('/users/preferred-suppliers/' + encodeURIComponent(userId), {
            params: data
        });
    },

    'subcontractorsOptions': (search) => {
        return api.client().get('/users/preferred-subcontractors/options?search=' + (search ? encodeURIComponent(search) : ''));
    },

    'storePreferredSubcontractor': (userId, subcontractorId) => {
        return api.client().post('/users/preferred-subcontractors/' + encodeURIComponent(userId), {
            'subcontractorId': subcontractorId,
        });
    },

    'deletePreferredSubcontractor': (userId, subcontractorId) => {
        return api.client().delete('/users/preferred-subcontractors/' + encodeURIComponent(userId) + "/" + encodeURIComponent(subcontractorId));
    },

    'getPreferredSubcontractors': (userId, data) => {
        return api.client().get('/users/preferred-subcontractors/' + encodeURIComponent(userId), {
            params: data
        });
    },

    'loadPartners': (params) => {
        return api.client().get('/partners', params);
    },

    'getStripePlans': (params) => {
        return api.client().get('/stripe/plans', params)
    },

    'getUserStripeSettings': (userId) => {
        return api.client().get('/stripe/user/' + encodeURIComponent(userId), {})
    },

    'loadUserNotes': (userId, params) => {
        return api.client().get('/user/' + encodeURIComponent(userId) + '/notes', {
            params: params
        })
    },

    'storeUserNote': (userId, data) => {
        return api.client().post('/user/' + encodeURIComponent(userId) + '/notes', data);
    },

    'updateAnswerPartial': (id, data) => {
        return api.client().patch('/answers/' + encodeURIComponent(id), data);
    },

    'updateSupplyFitQuotePartial': (id, data) => {
        return api.client().patch('/supply-fit-enquiry-quotes/' + encodeURIComponent(id), data);
    },

    loginAs: (id) => {
        return api.client().post('/loginas?id=' + encodeURIComponent(id), {});
    },

    getEnquiriesToTimeContractor: () => {
        return api.client().get('/reports/enquiries_to_time_contractor', {});
    },

    getEnquiriesToTimeMerchant: () => {
        return api.client().get('/reports/enquiries_to_time_merchant', {});
    },

    getTotalsContractorOnEnquiries: () => {
        return api.client().get('/reports/totals_contractor_on_enquiries', {});
    },

    getTotalsSupplierOnEnquiries: () => {
        return api.client().get('/reports/totals_supplier_on_enquiries', {});
    },

    getCategoriesPercentageSelectedForContractor: () => {
        return api.client().get('/reports/categories_percentage_selected_for_contractor', {});
    },

    getCategoriesPercentageSelectedForMerchant: () => {
        return api.client().get('/reports/categories_percentage_selected_for_merchant', {});
    },

    'getTotalEnquiries': () => {
        return api.client().get('/reports/total_enquiries', {});
    },

    'getTotalQuotes': () => {
        return api.client().get('/reports/total_quotes', {});
    },

    'getBranchPerformance': () => {
        return api.client().get('/reports/branch_performance', {});
    },

    'getBuyerReportPdf': (params) => {
        return api.client().get('/reports/buyer_report_pdf', {params: params});
    },

    'getContractorReport': (params) => {
        return api.client().get('/reports/contractor_report', {params: params});
    },

    'getContractorReportPdf': (params) => {
        return api.client().get('/reports/contractor_report_pdf', {params: params});
    },

    'getContractorReportWdPdf': (data) => {
        return api.client().post('/reports/contractor_report_wd_pdf', data);
    },

    'getContractorReportCsv': (params) => {
        return api.client().get('/reports/contractor_report_csv', {params: params});
    },

    'getContractorMapData': (params) => {
        return api.client().get('/reports/contractor_map_data', {params: params});
    },

    'getBuyerReportOptions': (params) => {
        return api.client().get('/reports/buyer_report/options', {params: params});
    },

    'getSubcontractorReport': (params) => {
        return api.client().get('/reports/subcontractor_report', {params: params});
    },

    'categoryOptions': (params) => {
        return api.client().get('/categories/options', {
            params: params
        });
    },

    'loadCategories': (data) => {
        return api.client().get('/categories', {params: data});
    },

    'storeCategory': (data, id) => {
        if (typeof id != 'undefined' && id > 0) {
            return api.client().put('/categories/' + encodeURIComponent(id), data)
        }
        return api.client().post('/categories', data);
    },

    'deleteCategory': (id) => {
        return api.client().delete('/categories/' + encodeURIComponent(id), {});
    },

    'loadTree': () => {
        return api.client().get('/categories/tree', {});
    },

    'saveTicket': (data) => {
        return api.client().post('/tickets', data);
    },

    'loadTutorials': (data) => {
        return api.client().get('/tutorials', {params: data});
    },

    'storeTutorial': (data, id) => {
        if (typeof id != 'undefined' && id > 0) {
            return api.client().put('/tutorials/' + encodeURIComponent(id), data)
        }
        return api.client().post('/tutorials', data);
    },

    'deleteTutorial': (id) => {
        return api.client().delete('/tutorials/' + encodeURIComponent(id), {});
    },

    'loadVirtualExpo': (data) => {
        return api.client().get('/virtual-expo', {params: data});
    },

    'storeVirtualExpo': (data, id) => {
        if (typeof id != 'undefined' && id > 0) {
            return api.client().post('/virtual-expo/' + encodeURIComponent(id), data, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
        }
        return api.client().post('/virtual-expo', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    'updateExpoCheckbox': (expoId, key, value) => {
        return api.client().post('/virtual-expo/' + encodeURIComponent(expoId) + '/updateCheckbox', {
            key: key,
            value: value
        });
    },

    'getRandomVirtualExpo': (productIds) => {
        return api.client().get('/virtual-expo-random?productIds=' + JSON.stringify(productIds), {});
    },

    'getVirtualExpos': (params) => {
        return api.client().get('/virtual-expo-list?top-banner', {
            params: params
        });
    },

    'deleteVirtualExpo': (id) => {
        return api.client().delete('/virtual-expo/' + encodeURIComponent(id), {});
    },

    'loadVirtualExpoSharedContacts': (data) => {
        return api.client().get('/virtual-expo-contacts', {params: data});
    },

    storeVirtualExpoSharedContact: (data) => {
        return api.client().post('/virtual-expo-contacts', data);
    },

    storeVirtualExpoSharedContactToLastExpo: (manufacturerId) => {
        return api.client().post('/virtual-expo-contacts', {
            manufacturerId
        });
    },

    loadVirtualExpoVideos: (data, id) => {
        return api.client().get('/virtual-expo/' + encodeURIComponent(id) + '/videos', data);
    },

    loadVirtualExpoById: (id) => {
        return api.client().get('/virtual-expo/' + encodeURIComponent(id), {});
    },

    'deleteVirtualExpoSharedContact': (id) => {
        return api.client().delete('/virtual-expo-contacts/' + encodeURIComponent(id), {});
    },

    'loadMessagesVirtualExpo': (sharedContactId, data) => {
        return api.client().get('/virtual-expo-contacts/' + encodeURIComponent(sharedContactId) + '/messages', {params: data});
    },

    'storeMessageVirtualExpo': (data, sharedContactId) => {
        return api.client().post('/virtual-expo-contacts/' + encodeURIComponent(sharedContactId) + '/messages', data);
    },

    'storeSettings': (data) => {
        return api.client().post('/settings', data);
    },

    ticketCategoriesOptions: () => {
        return api.client().get('/tickets/categories', {});
    },

    toggleAssign: (id) => {
        return api.client().post('/questions/' + encodeURIComponent(id) + '/toggle-assign');
    },

    togglePurchaseHireIgnore: (id) => {
        return api.client().post('/questions/' + encodeURIComponent(id) + '/toggle-ignore');
    },

    getMerchantsInRange: (inquiryId, data) => {
        return api.client().get('/questions/' + encodeURIComponent(inquiryId) + '/seen-by-merchants', {params: data});
    },

    getMerchantsPreferred: (inquiryId, data) => {
        return api.client().get('/questions/' + encodeURIComponent(inquiryId) + '/merchants-preferred', {params: data});
    },

    sendNudgeSalesEmail: (inquiryId) => {
        return api.client().post('/questions/' + encodeURIComponent(inquiryId) + '/nudgeSales', {});
    },

    setCalledFlag: (inquiry_id, user_id, comment) => {
        if (inquiry_id === '') {
            return api.client().post('/users/' + encodeURIComponent(user_id) + '/called', {
                comment
            });
        }

        return api.client().post('/questions/' + encodeURIComponent(inquiry_id) + '/called', {
            user_id, comment
        });
    },

    getOnboardingChecklist: (userId) => {
        return api.client().get('/users/' + encodeURIComponent(userId) + '/getOnboardingChecklist');
    },

    updateOnboardingChecklist: (userId, key, value) => {
        return api.client().post('/users/' + encodeURIComponent(userId) + '/updateOnboardingChecklist', {
            key: key,
            value: value
        });
    },

    saveContractorOnboardingForm: (data) => {
        return api.client().post('/onboarding', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    loadContractorOnboarding: (data) => {
        return api.client().get('/onboarding', {params: data});
    },

    getContractorOnboarding: () => {
        return api.client().get('/onboarding/get');
    },

    zohoReportSettings: () => {
        return api.client().get('/zoho-report-settings');
    },

    getZohoDeals: (data) => {
        return api.client().get('/zoho-deals', {params: data});
    },

    updateZohoDeals: (data) => {
        return api.client().post('/zoho-deals', data);
    },

    newAnalyticsEvent: (data) => {
        return api.client().post('/analytics/events', data);
    },

    supplierReport: (userId) => {
        return api.client().get('/user/' + encodeURIComponent(userId) + '/supplierReport', {});
    },

    logisticsReport: () => {
        return api.client().get('/user/logisticsReport', {});
    },

    getSupplyFitQuotesOverview: (enquiryId) => {
        return api.client().get('/supply-fit-enquiries/' + encodeURIComponent(enquiryId) + '/overview', {});
    },

    'getQuestionnaireItem': (id) => {
        return api.client().get('/questionnaire-items/' + encodeURIComponent(id), {});
    },

    'loadQuestionnaireItems': (data) => {
        return api.client().get('/questionnaire-items', {params: data});
    },

    'storeQuestionnaireItem': (data, id) => {
        if (typeof id != 'undefined' && id > 0) {
            return api.client().put('/questionnaire-items/' + encodeURIComponent(id), data)
        }

        return api.client().post('/questionnaire-items', data);
    },

    'deleteQuestionnaireItem': (id) => {
        return api.client().delete('/questionnaire-items/' + encodeURIComponent(id), {});
    },

    'sendQuestionnaire': (projectTag, worksPackageId) => {
        return api.client().post('/projects/' + encodeURIComponent(projectTag) + '/works-packages/' + encodeURIComponent(worksPackageId) + '/sendToMerchants', {})
    },


    saveTemplate: (templateName, projectTag, worksPackageId) => {
        return api.client().post('/projects/' + encodeURIComponent(projectTag) + '/works-packages/' + encodeURIComponent(worksPackageId) + '/questionnaire-templates', {
            name: templateName
        })
    },

    applyTemplate: (templateId, projectTag, worksPackageId) => {
        return api.client().post('/projects/' + encodeURIComponent(projectTag) + '/works-packages/' + encodeURIComponent(worksPackageId) + '/applyTemplate', {
            id: templateId
        })
    },

    'questionnaireTemplateOptions': (search) => {
        return api.client().get('/questionnaire-templates?search=' + (search ? encodeURIComponent(search) : ''));
    },


    'storeCompanyQuestionnaire': (item, id) => {
        if (typeof id != 'undefined' && id > 0) {
            return api.client().put('/company_questionnaires/' + encodeURIComponent(id), item)
        }

        return api.client().post('/company_questionnaires', item);
    },
    'deleteCompanyQuestionnaire': (id) => {
        return api.client().delete('/company_questionnaires/' + encodeURIComponent(id), {});
    },
    'loadCompanyQuestionnaires': (data) => {
        return api.client().get('/company_questionnaires', {params: data});
    },
    'getCompanyQuestionnaire': (id) => {
        return api.client().get('/company_questionnaires/' + encodeURIComponent(id), {});
    },

    'checkHash': (hash) => {
        return api.client().get('/companyByHash/' + hash, {
            params: {
                'q': (new Date()).toUTCString()
            }
        });
    },

    'submitAnswers': (hash, replies) => {
        return api.client().post('/companyByHash/' + hash + '/answers', {
            replies
        });
    },

    loadScores: (ids) => {
        return api.client().post('/company_questionnaires/scores', {
            'ids': ids
        });
    },

    loadAnswersWithScores: (ids) => {
        return api.client().post('/company_questionnaires/answersWithScores', {
            'ids': ids
        });
    },

    loadCalledMerchants: (params) => {
        return api.client().get('/reports/called_users', {
            params
        });
    },

    acceptCompanyQuestionnaire: (id) => {
        return api.client().post('/company_questionnaires/' + encodeURIComponent(id) + '/accept', {});
    },

    declineCompanyQuestionnaire: (id) => {
        return api.client().post('/company_questionnaires/' + encodeURIComponent(id) + '/decline', {});
    },

    updateScore: (score, answerId) => {
        return api.client().post('/answers/' + encodeURIComponent(answerId) + '/score', {
            'score': score,
        });
    },

    loadProjects: (params) => {
        return api.client().get('/projects', {params: params});
    },

    loadProjectRegions: () => {
        return api.client().get('/projects/regions');
    },

    loadProjectSupplyFitQuotes: (id) => {
        return api.client().get('/projects/' + encodeURIComponent(id) + '/supply-fit-quotes', {});
    },

    getProjectReport: (id) => {
        return api.client().get('/projects/' + encodeURIComponent(id) + '/report', {});
    },

    loadWorksPackages: (projectId, data) => {
        return api.client().get('/projects/' + encodeURIComponent(projectId) + '/works-packages', {params: data});
    },

    storeProject: (props) => {
        return api.client().post('/projects', props);
    },

    updateProjectStage: (id, data) => {
        return api.client().post('/projects/' + encodeURIComponent(id) + '/updateStage', data);
    },

    updateProjectType: (id, data) => {
        return api.client().post('/projects/' + encodeURIComponent(id) + '/updateType', data);
    },

    storeWorksPackage: (projectId, props) => {
        return api.client().post('/projects/' + encodeURIComponent(projectId) + '/works-packages', props);
    },

    updateWorksPackage: (id, props) => {
        return api.client().post('/works-packages/' + encodeURIComponent(id), props);
    },

    deleteWorksPackage: (id) => {
        return api.client().delete('/works-packages/' + encodeURIComponent(id));
    },

    getWorksPackageAssignedUsers: (worksPackageId) => {
        return api.client().get('/works-packages/' + encodeURIComponent(worksPackageId) + '/assigned-users');
    },

    storeWorksPackageAssignedUsers: (worksPackageId, data) => {
        return api.client().post('/works-packages/' + encodeURIComponent(worksPackageId) + '/assigned-users', data);
    },

    getProjectDocuments: (projectId, data) => {
        return api.client().get('/projects/' + encodeURIComponent(projectId) + '/documents', {params: data});
    },

    createProjectDocument: (projectId, data) => {
        return api.client().post('/projects/' + encodeURIComponent(projectId) + '/documents', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    deleteProjectDocument: (projectId, data) => {
        return api.client().delete('/projects/' + encodeURIComponent(projectId) + '/documents/', {params: data});
    },

    getProjectTimeTracking: (projectId, data) => {
        return api.client().get('/projects/' + encodeURIComponent(projectId) + '/time-tracking', {params: data});
    },

    storeProjectTimeTracking: (projectId, data) => {
        return api.client().post('/projects/' + encodeURIComponent(projectId) + '/time-tracking', data);
    },

    getUsers: (params) => {
        return api.client().get('/projects/getUsers', {params: params});
    },

    getProjectUsers: (projectId) => {
        return api.client().get('/projects/' + projectId + '/getProjectUsers');
    },

    createProjectUserAccess: (projectId, data) => {
        return api.client().post('/projects/' + encodeURIComponent(projectId) + '/userAccess', data);
    },

    deleteProjectUserAccess: (projectId, userId) => {
        return api.client().delete('/projects/' + encodeURIComponent(projectId) + '/userAccess/' + encodeURIComponent(userId));
    },

    getVirtualExpoForAll: (dateFrom, dateTo, selectedManufacturers) => {
        return api.client().get('/virtual_expos/report', {
            params: {
                dateFrom, dateTo,
                selectedManufacturers
            }
        });
    },

    getVirtualExpoReport: (expoId, dateFrom, dateTo) => {
        return api.client().get('/virtual_expos/' + encodeURIComponent(expoId) + '/report', {
            params: {
                dateFrom, dateTo
            }
        });
    },

    findPlace: (searchText) => {
        return api.client().get('/other-merchants/findPlace', {params: {searchText}});
    },

    updateOtherMerchants: (placeId, data) => {
        return api.client().post('/other-merchants/update', {
            'place_id': placeId,
            'data': data
        });
    },

    resendQuestionnaireForQuote: (id) => {
        return api.client().post('/supply-fit-enquiry-quotes/' + encodeURIComponent(id) + '/resend-questionnaire')
    },

    externalActionGetEnquiryData: (params) => {
        return api.client().get('/externalAction/getEnquiryData', {params: params});
    },

    externalActionCustomerResponse: (params) => {
        return api.client().get('/externalAction/customerResponse', {params: params});
    },

    externalActionAcceptQuote: (data) => {
        return api.client().post('/externalAction/acceptQuote', data);
    },

    externalActionUpdateEnquiry: (data) => {
        return api.client().post('/externalAction/updateEnquiry', data);
    },

    getLogisticsEnquiry: (id) => {
        return api.client().get('/logistics-enquiries/' + encodeURIComponent(id), {});
    },

    loadLogisticsEnquiries: (data) => {
        return api.client().get('/logistics-enquiries', {params: data});
    },

    storeLogisticsEnquiry: (data, id) => {
        if (typeof id != 'undefined' && id > 0) {
            return api.client().post('/logistics-enquiries/' + encodeURIComponent(id), data, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
        }

        return api.client().post('/logistics-enquiries', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    deleteLogisticsEnquiry: (id) => {
        return api.client().delete('/logistics-enquiries/' + encodeURIComponent(id), {});
    },

    archiveLogisticsEnquiry: (id) => {
        return api.client().post('/logistics-enquiries/' + encodeURIComponent(id) + '/archive', {});
    },

    restoreLogisticsEnquiry: (id) => {
        return api.client().post('/logistics-enquiries/' + encodeURIComponent(id) + '/restore', {});
    },

    loadLogisticsQuotes: (enquiryId, data) => {
        return api.client().get('/logistics-enquiries/' + encodeURIComponent(enquiryId) + '/quotes', {params: data});
    },

    storeLogisticsQuote: (data, id, enquiryId) => {
        if (typeof id != 'undefined' && id > 0) {
            return api.client().post('/logistics-quotes/' + encodeURIComponent(id), data, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
        }

        return api.client().post('/logistics-enquiries/' + encodeURIComponent(enquiryId) + '/quotes', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    deleteLogisticsQuote: (id) => {
        return api.client().delete('/logistics-quotes/' + encodeURIComponent(id), {});
    },

    acceptLogisticsQuote: (id) => {
        return api.client().post('/logistics-quotes/' + encodeURIComponent(id) + '/accept', {});
    },

    unacceptLogisticsQuote: (id) => {
        return api.client().post('/logistics-quotes/' + encodeURIComponent(id) + '/unaccept', {});
    },

    toggleLogisticsEnquiryIgnore: (id) => {
        return api.client().post('/logistics-enquiries/' + encodeURIComponent(id) + '/toggle-ignore');
    },

    getLogisticsProjectOptions: (search) => {
        return api.client().get('/logistics-enquiries/project-options?search=' + (search ? encodeURIComponent(search) : ''));
    },

    getLogisticsWorksPackageOptions: (search, projectIds) => {
        return api.client().get('/logistics-enquiries/works-package-options', {params: {search: search, projectIds: projectIds}});
    },

    loadLogisticsEnquiryChats: (enquiryId, data) => {
        return api.client().get('/logistics-enquiries/' + encodeURIComponent(enquiryId) + '/chats', {params: data});
    },

    loadLogisticsEnquiryMessages: (enquiryId, data) => {
        return api.client().get('/logistics-enquiries/' + encodeURIComponent(enquiryId) + '/messages', {params: data});
    },

    storeLogisticsEnquiryMessage: (data, enquiryId) => {
        return api.client().post('/logistics-enquiries/' + encodeURIComponent(enquiryId) + '/messages', data);
    },

    loadLogisticsQuoteMessages: (quoteId, data) => {
        return api.client().get('/logistics-quotes/' + encodeURIComponent(quoteId) + '/messages', {params: data});
    },

    storeLogisticsQuoteMessage: (data, quoteId) => {
        return api.client().post('/logistics-quotes/' + encodeURIComponent(quoteId) + '/messages', data);
    },

    loadQuoteProgress: (quoteId, type, data) => {
        return api.client().get('/quote-progress/' + encodeURIComponent(quoteId) + '/' + encodeURIComponent(type), {params: data});
    },

    storeQuoteProgress: (data) => {
        return api.client().post('/quote-progress', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    completeQuoteProgress: (id) => {
        return api.client().post('/quote-progress/' + encodeURIComponent(id) + '/complete', {});
    },

    uncompleteQuoteProgress: (id) => {
        return api.client().post('/quote-progress/' + encodeURIComponent(id) + '/uncomplete', {});
    },

    loadNotes: (type, parentId, params) => {
        return api.client().get('/notes/' + encodeURIComponent(type) + '/' + encodeURIComponent(parentId), {
            params: params
        });
    },

    storeNote: (data) => {
        return api.client().post('/notes', data);
    },

    updateNote: (data) => {
        return api.client().patch('/notes', data);
    },

    loadHousebuildingProducts: (data) => {
        return api.client().get('/housebuilding', {params: data});
    },

    getHouseNames: (search) => {
        return api.client().get('/housebuilding/houses?search=' + (search ? encodeURIComponent(search) : ''));
    },

    getHousebuildingCategories: (search) => {
        return api.client().get('/housebuilding/categories?search=' + (search ? encodeURIComponent(search) : ''));
    },

    getHousebuildingTotals: () => {
        return api.client().get('/housebuilding/totals');
    },

    updateHousebuildingBudget: (data) => {
        return api.client().post('/housebuilding/budgets', data);
    },

    loadSupplyChainUsers: (params) => {
        return api.client().get('/supply-chain-users', {params: params});
    },

    loadSupplyChainCompanyNames: () => {
        return api.client().get('/supply-chain-users/companies');
    },

    storeSupplyChainUser: (data, id) => {
        if (id) {
            return api.client().post('/supply-chain-users/' + encodeURIComponent(id) + '/update', data);
        }

        return api.client().post('/supply-chain-users', data);
    },

    convertSupplyChainUser: (data) => {
        return api.client().post('/supply-chain-users/convert', data);
    },

    importSupplyChainUsers: (data) => {
        return api.client().post('/supply-chain-users/importCsv', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    deleteSupplyChainUser: (id) => {
        return api.client().delete('/supply-chain-users/' + encodeURIComponent(id));
    },

    loadTenderNotices: (data) => {
        return api.client().get('/tender-notices', {params: data});
    },

    getTenderNotice: (id) => {
        return api.client().get('/tender-notices/' + encodeURIComponent(id));
    },

    convertTenderNotice: (data) => {
        return api.client().post('/tender-notices/convert', data);
    },

    loadAttachments: (params) => {
        return api.client().get('/attachments', {params: params});
    },

    createAttachment: (data) => {
        return api.client().post('/attachments', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    deleteAttachment: (id) => {
        return api.client().delete('/attachments/' + encodeURIComponent(id));
    },

    loadWorksPackageQuestions: (params) => {
        return api.client().get('/works-packages/questions', {params: params});
    },

    storeWorksPackageQuestion: (wpid, data) => {
        return api.client().post('/works-packages/' + encodeURIComponent(wpid) + '/questions', data);
    },

    loadSupplyChainSubcontractors:(params) => {
        return api.client().get('/supply-chain-users/subcontractors', {params: params});
    },

    getCertificates: (params) => {
        return api.client().get('/certificates', {params: params});
    },

    getCertificateTypes: () => {
        return api.client().get('/certificates/types');
    },

    getCertificateStats:() => {
        return api.client().get('/certificates/stats');
    },

    getDashboardStats:() => {
        return api.client().get('/certificates/stats/dashboard');
    },

    getOnboardingUsers:() => {
        return api.client().get('/onboarding/users');
    },

    createOnboardingUser: (data) => {
        return api.client().post('/onboarding/users', data);
    },

    updateOnboardingUser: (id, data) => {
        return api.client().put('/onboarding/users/' + encodeURIComponent(id), data);
    },

    deleteOnboardingUser: (id) => {
        return api.client().delete('/onboarding/users/' + encodeURIComponent(id));
    },

    uploadBoqFile: (data) => {
        return api.client().post('/projects/upload-boq-file', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    createWorksPackagesFromBoq: (data) => {
        return api.client().post('/projects/create-works-packages', data);
    },

    loadReviewTemplates: (params) => {
        return api.client().get('/reviews/templates', {params: params});
    },

    getReviewTemplateOptions: () => {
        return api.client().get('/reviews/templates/options');
    },

    storeReviewTemplate: (data) => {
        return api.client().post('/reviews/templates', data);
    },

    deleteReviewTemplate: (id) => {
        return api.client().delete('/reviews/templates/' + encodeURIComponent(id));
    },

    loadReviews: (params) => {
        return api.client().get('/reviews', {params: params});
    },

    getReview: (id) => {
        return api.client().get('/reviews/' + encodeURIComponent(id));
    },

    storeReview: (data) => {
        return api.client().post('/reviews', data);
    },

    deleteReview: (id) => {
        return api.client().delete('/reviews/' + encodeURIComponent(id));
    },

    getUserSummary: (params) => {
        return api.client().get('/reports/user_summary', {params: params});
    },

    getProjectDocumentCategories: (params) => {
        return api.client().get('/projects/documents/categories', {params: params});
    },
}

export default api
