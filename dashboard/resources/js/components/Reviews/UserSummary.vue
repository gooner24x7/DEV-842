<template>
    <div>
        <v-card class="user-summary">
            <v-card-title class="user-summary__title">
                <span>{{ summary.user?.first_name ?? '' }}</span>
                <v-spacer></v-spacer>
                <v-btn icon @click="$emit('close')">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-card-title>

            <v-card-text class="user-summary__body">
                <div v-if="!summary.user" class="text-center pa-8">
                    <v-progress-circular indeterminate color="primary"></v-progress-circular>
                </div>

                <template v-else>
                    <!-- Overview: contact details + key stats -->
                    <div class="user-summary__overview">
                        <div class="user-summary__contact">
                            <div class="user-summary__avatar">{{ initials }}</div>
                            <div class="user-summary__contact-list">
                                <div class="user-summary__contact-item">
                                    <v-icon small color="primary">mdi-map-marker-outline</v-icon>
                                    <span>{{ summary.user?.postcode ?? '' }}</span>
                                </div>
                                <div class="user-summary__contact-item">
                                    <v-icon small color="primary">mdi-phone-outline</v-icon>
                                    <span>{{ summary.user?.phone ?? '' }}</span>
                                </div>
                                <div class="user-summary__contact-item">
                                    <v-icon small color="primary">mdi-email-outline</v-icon>
                                    <span>{{ summary.user?.email ?? '' }}</span>
                                </div>
                                <div class="user-summary__contact-item" v-if="summary.user?.company_name">
                                    <v-icon small color="primary">mdi-office-building-outline</v-icon>
                                    <span>{{ summary.user?.company_name }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="user-summary__stats">
                            <div class="user-summary__stat">
                                <div class="user-summary__stat-icon">
                                    <v-icon color="primary">mdi-briefcase-outline</v-icon>
                                </div>
                                <div>
                                    <div class="user-summary__stat-label">Total Projects Worked On</div>
                                    <div class="user-summary__stat-value">{{ summary.total_projects ?? 0 }}</div>
                                </div>
                            </div>

                            <div class="user-summary__stat">
                                <div class="user-summary__stat-icon">
                                    <v-icon color="primary">mdi-clipboard-text-outline</v-icon>
                                </div>
                                <div>
                                    <div class="user-summary__stat-label">Total Reviews (Projects)</div>
                                    <div class="user-summary__stat-value">{{ summary.total_reviews ?? 0 }}</div>
                                </div>
                            </div>

                            <div class="user-summary__stat">
                                <div class="user-summary__stat-icon">
                                    <v-icon color="primary">mdi-shield-alert-outline</v-icon>
                                </div>
                                <div>
                                    <div class="user-summary__stat-label">Risk Score</div>
                                    <div class="user-summary__stat-value">{{ summary.user?.risk_score ?? '' }}</div>
                                </div>
                            </div>

                            <div class="user-summary__stat">
                                <div class="user-summary__stat-icon">
                                    <v-icon color="primary">mdi-account-group-outline</v-icon>
                                </div>
                                <div>
                                    <div class="user-summary__stat-label">Main Contractor Score</div>
                                    <div class="user-summary__stat-score">
                                        <span class="user-summary__stat-value">{{ summary.user?.contractor_score }}</span>
<!--                                        <v-rating-->
<!--                                            :value="Number(summary.user?.contractor_score) || 0"-->
<!--                                            color="amber"-->
<!--                                            background-color="grey lighten-1"-->
<!--                                            half-increments-->
<!--                                            readonly-->
<!--                                            dense-->
<!--                                            size="16"-->
<!--                                        ></v-rating>-->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trades -->
                    <div class="user-summary__section" v-if="summary.user.products">
                        <h3 class="user-summary__heading">Trades</h3>
                        <div class="user-summary__trades">
                            <template v-if="summary.user.products">
                                <v-chip
                                    v-for="(product, index) in (expandedProducts ? getProductList() : getProductList().slice(0, 5))"
                                    :key="index"
                                    class="mr-1 mb-1"
                                    small
                                >
                                    {{ product }}
                                </v-chip>
                                <v-btn
                                    v-if="getProductList().length > 5"
                                    @click="toggleProducts()"
                                    icon
                                    x-small
                                >
                                    <v-icon small>{{ expandedProducts ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
                                </v-btn>
                            </template>
                        </div>
                    </div>

                    <!-- Compliance breakdown -->
                    <div class="user-summary__section">
                        <h3 class="user-summary__heading">
                            Compliance Breakdown
                        </h3>
                        <v-row no-gutters>
                            <v-col cols="6">
                                <ul class="cert-list">
                                    <li><span v-html="getCertIcon(summary.user?.cscs)"></span> CSCS</li>
                                    <li><span v-html="getCertIcon(summary.user?.ssip)"></span> SSIP</li>
                                    <li><span v-html="getCertIcon(summary.user?.cyber)"></span> Cyber Essentials</li>
                                    <li><span v-html="getCertIcon(summary.user?.fire_products)"></span> Fire Products</li>
                                    <li><span v-html="getCertIcon(summary.user?.fire_competencies)"></span> Fire Competencies</li>
                                </ul>
                            </v-col>
                            <v-col cols="6">
                                <ul class="cert-list">
                                    <li><span v-html="getCertIcon(summary.user?.pref_suppliers)"></span> Sub-letting</li>
                                    <li><span v-html="getCertIcon(summary.user?.modern_slavery)"></span> Modern Slavery</li>
                                    <li><span v-html="getCertIcon(summary.user?.induction)"></span> Induction</li>
                                    <li><span v-html="getCertIcon(summary.user?.goods_supply)"></span> Goods Supply</li>
                                    <li><span v-html="getCertIcon(summary.user?.cas)"></span> CAS</li>
                                </ul>
                            </v-col>
                        </v-row>
                    </div>

                    <!-- Reviews List -->
                    <div class="user-summary__section">
                        <h3 class="user-summary__heading">Reviews</h3>
                        <v-data-table
                            :headers="reviewHeaders"
                            :items="summary.reviews"
                            class="data-table-mini"
                            hide-default-footer
                            disable-pagination
                        >
                            <template v-slot:item.score="{ item }">
                                <div class="user-summary__review-score">
<!--                                    <v-rating-->
<!--                                        :value="Number(item.score) || 0"-->
<!--                                        color="amber"-->
<!--                                        background-color="grey lighten-1"-->
<!--                                        half-increments-->
<!--                                        readonly-->
<!--                                        dense-->
<!--                                        size="16"-->
<!--                                    ></v-rating>-->
                                    <div>{{ `${item.score}/${item.total}` }}</div>
                                    <div
                                        :class="getOverallPercentClass(item)"
                                        class="type-chip"
                                        style="padding: 0 8px"
                                    >
                                        {{ getOverallPercent(item) }}
                                    </div>
                                </div>
                            </template>

                            <template v-slot:item.actions="{ item }">
                                <v-btn
                                    small
                                    outlined
                                    color="primary"
                                    @click="viewReview(item)"
                                >View Summary</v-btn>
                            </template>
                        </v-data-table>
                    </div>
                </template>
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn @click="$emit('close')">Close</v-btn>
            </v-card-actions>
        </v-card>
    </div>
</template>

<script>
import api from '../../common/api';

export default {
    name: 'UserSummary',

    props: {
        userId: {
            type: Number,
            required: true,
        }
    },

    data() {
        return {
            summary: {
                user: null,
                reviews: [],
                total_projects: 0,
                total_reviews: 0,
            },
            expandedProducts: false,
            reviewHeaders: [
                { text: 'Project', value: 'project_name', sortable: false },
                { text: 'Works Package', value: 'works_package_name', sortable: false },
                { text: 'Reviewed By', value: 'reviewer_name', sortable: false },
                { text: 'Created At', value: 'created_at', sortable: false },
                { text: 'Score', value: 'score', sortable: false },
                { text: '', value: 'actions', sortable: false, align: 'end' },
            ],
        };
    },

    computed: {
        initials() {
            const name = this.summary.user?.first_name ?? '';

            return name
                .split(' ')
                .filter(Boolean)
                .slice(0, 2)
                .map(part => part.charAt(0).toUpperCase())
                .join('');
        }
    },

    watch: {
        userId: {
            immediate: true,
            handler(userId) {
                if (userId) {
                    this.load(userId);
                }
            },
        },
    },

    methods: {
        async load(userId) {
            api.getUserSummary({id: userId})
                .then((response) => {
                    this.summary = response.data;
                })
                .catch((error) => {
                    console.log(error);
                });
        },

        getProductList() {
            if (!this.summary.user.products) {
                return [];
            }

            return this.summary.user.products.split(',');
        },

        toggleProducts() {
            this.expandedProducts = !this.expandedProducts;
        },

        getCertIcon(val) {
            if (val) {
                return `<i class="v-icon mdi mdi-check-circle-outline" style="color: green"></i>`;
            }

            return `<i class="v-icon mdi mdi-close-circle-outline" style="color: red"></i>`;
        },

        getOverallPercent(item) {
            if (item.total === 0) return '0%';
            return `${Math.round((item.score / item.total) * 100)}%`;
        },

        getOverallPercentClass(item) {
            let color = 'chip-orange';
            let percent = Math.round((item.score / item.total) * 100);

            if (percent > 60) {
                color = 'chip-green';
            } else if (percent > 30) {
                color = 'chip-yellow';
            }

            return color;
        },

        viewReview(item) {
            this.$emit('viewReview', item.id)
        }
    },
};
</script>

<style>
.user-summary__title {
    font-size: 18px !important;
    font-weight: 600;
    color: #3f51b5;
    padding: 16px 20px;
}

.user-summary__body {
    padding: 0 20px 20px;
}

/* Overview */
.user-summary__overview {
    display: flex;
    flex-wrap: wrap;
    gap: 24px;
    padding: 16px 0;
    border-bottom: 1px solid #e0e0e0;
}

.user-summary__contact {
    display: flex;
    gap: 16px;
    min-width: 260px;
}

.user-summary__avatar {
    flex: 0 0 auto;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #e8eaf6;
    color: #3f51b5;
    font-size: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
}

.user-summary__contact-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.user-summary__contact-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #333;
}

/* Stats */
.user-summary__stats {
    flex: 1 1 320px;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-content: flex-start;
}

.user-summary__stat {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1 1 180px;
    min-width: 180px;
    padding: 12px 16px;
    background: #fafbff;
    border: 1px solid #eceef5;
    border-radius: 8px;
}

.user-summary__stat-icon {
    flex: 0 0 auto;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: #e8eaf6;
    display: flex;
    align-items: center;
    justify-content: center;
}

.user-summary__stat-label {
    font-size: 11px;
    color: #888;
    line-height: 1.3;
}

.user-summary__stat-value {
    font-size: 20px;
    font-weight: 700;
    color: #222;
}

.user-summary__stat-score {
    display: flex;
    align-items: center;
    gap: 8px;
}

.user-summary__trades {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

/* Sections */
.user-summary__section {
    padding: 16px 0;
    border-bottom: 1px solid #e0e0e0;
}

.user-summary__section:last-child {
    border-bottom: none;
}

.user-summary__heading {
    font-size: 16px;
    font-weight: 600;
    color: #222;
    margin-bottom: 12px;
}

/* Reviews */
.user-summary__review-score {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
}
</style>
