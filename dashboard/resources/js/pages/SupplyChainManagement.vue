<template>
    <div class="scm-page">
        <div class="scm-topbar">
            <div>
                <h1 class="scm-heading">Supply Chain Management</h1>
                <p class="scm-subtitle">
                    Overview of compliance coverage across all categories for your preferred sub-contractors.
                </p>
            </div>
            <input
                v-model="search"
                class="scm-search"
                type="text"
                placeholder="Search compliance categories..."
            />
        </div>

        <div class="scm-summary-grid">
            <div class="scm-summary-card scm-card-link" @click="goToSubcontractors()">
                <h3>Total Sub-contractors</h3>
                <strong>{{ getTotalSubcontractors() }}</strong>
            </div>
            <div class="scm-summary-card">
                <h3>Average Compliance</h3>
                <strong class="color-green">{{ averageCompliance }}%</strong>
            </div>
            <div class="scm-summary-card">
                <h3>Require Attention</h3>
                <strong class="color-amber">{{ requireAttention }}</strong>
            </div>
            <div class="scm-summary-card">
                <h3>Fully Compliant</h3>
                <strong class="color-purple">{{ fullyCompliant }}</strong>
            </div>
        </div>

        <div class="scm-category-grid">
            <div
                v-for="(item, index) in filteredStats"
                :key="index"
                class="scm-category-card"
            >
                <div class="scm-category-header">
                    <div class="scm-icon" :class="getStatusLevel(item.stats[0]).iconClass">
                        {{ getStatusLevel(item.stats[0]).icon }}
                    </div>
                    <div class="scm-category-title">
                        <h3>{{ item.label }}</h3>
                        <p>{{ item.slug }}</p>
                    </div>
                    <div class="scm-arrow" @click="goToSubcontractors(item.slug)">›</div>
                </div>

                <div class="scm-card-body">
                    <div class="scm-circle" :class="{ 'warning': item.stats[0] < 60 }">
                        {{ item.stats[0] }}%
                    </div>
                    <div class="scm-click-boxes">
                        <div
                            class="scm-click-box compliant"
                            @click="goToSubcontractors(item.slug, 1)"
                        >
                            <strong>{{ item.stats[0] }}%</strong>
                            Compliant
                            <span>Click to view ›</span>
                        </div>
                        <div
                            class="scm-click-box non-compliant"
                            @click="goToSubcontractors(item.slug, 0)"
                        >
                            <strong>{{ item.stats[1] }}%</strong>
                            Not Compliant
                            <span>Click to view ›</span>
                        </div>
                    </div>
                </div>

                <div class="scm-status" :class="getStatusLevel(item.stats[0]).statusClass">
                    {{ getStatusLevel(item.stats[0]).statusLabel }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import api from "../common/api.js";

export default {
    name: 'SupplyChainManagement',
    components: {},
    data() {
        return {
            loader: false,
            total: 0,
            stats: [],
            search: '',
        };
    },

    async mounted() {
        await this.load();
    },

    computed: {
        filteredStats() {
            if (!this.search) {
                return this.stats;
            }
            const term = this.search.toLowerCase();
            return this.stats.filter(item =>
                item.label.toLowerCase().includes(term) ||
                item.slug.toLowerCase().includes(term)
            );
        },
        averageCompliance() {
            if (this.stats.length === 0) return 0;
            const sum = this.stats.reduce((acc, item) => acc + Number(item.stats[0]), 0);
            return Math.round(sum / this.stats.length);
        },
        requireAttention() {
            return this.stats.filter(item => Number(item.stats[0]) < 60).length;
        },
        fullyCompliant() {
            return this.stats.filter(item => Number(item.stats[0]) === 100).length;
        },
    },

    methods: {
        async load() {
            const self = this;
            self.loader = true;

            const response = await api.getCertificateStats();

            self.stats = response.data.data;
            self.total = response.data.total;

            self.loader = false;
        },

        getStatusLevel(compliantPerc) {
            const perc = Number(compliantPerc);
            if (perc >= 80) {
                return {
                    statusLabel: 'Excellent',
                    statusClass: 'excellent',
                    icon: '✓',
                    iconClass: 'icon-green',
                };
            } else if (perc >= 60) {
                return {
                    statusLabel: 'Good',
                    statusClass: 'good',
                    icon: '✓',
                    iconClass: 'icon-green',
                };
            } else {
                return {
                    statusLabel: 'Fair',
                    statusClass: 'fair',
                    icon: '!',
                    iconClass: 'icon-amber',
                };
            }
        },

        goToSubcontractors(type = null, status = null) {
            this.$router.push({
                name: "supply-chain-subcontractors",
                query: {
                    type: type,
                    status: status,
                },
            });
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        getTotalSubcontractors() {
            let demoUrl = "demo.thebuildchain.co.uk";

            if (window.location.hostname === demoUrl && this.user().id === 15609) {
                return 1258;
            }

            return this.total;
        }
    },
};
</script>

<style scoped>
.scm-page {
    padding: 32px;
    /* background: #f7f9fc; */
    min-height: 100vh;
    color: #0f1b3d;
    font-family: Arial, sans-serif;
}

.scm-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
    flex-wrap: wrap;
    gap: 16px;
}

.scm-heading {
    margin: 0 0 8px;
    font-size: 30px;
}

.scm-subtitle {
    color: #64748b;
    margin: 0;
}

.scm-search {
    width: 320px;
    padding: 14px 18px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
}

.scm-search:focus {
    border-color: #1749f5;
}

/* Summary cards row */
.scm-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 28px;
}

.scm-summary-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}

.scm-summary-card h3 {
    margin: 0 0 10px;
    font-size: 15px;
    color: #334155;
}

.scm-summary-card strong {
    font-size: 34px;
    color: #0f1b3d;
}

.scm-card-link {
    cursor: pointer;
}

.scm-card-link:hover {
    background: #f8f8f8;
}

.color-green {
    color: #16a34a !important;
}

.color-amber {
    color: #f59e0b !important;
}

.color-purple {
    color: #6d28d9 !important;
}

/* Category cards grid */
.scm-category-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
}

.scm-category-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}

.scm-category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
}

.scm-icon {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
    font-size: 20px;
}

.icon-green {
    background: #22c55e;
}

.icon-amber {
    background: #f59e0b;
}

.scm-category-title {
    flex: 1;
    margin-left: 12px;
}

.scm-category-title h3 {
    margin: 0;
    font-size: 17px;
}

.scm-category-title p {
    margin: 4px 0 0;
    font-size: 12px;
    color: #64748b;
}

.scm-arrow {
    font-size: 22px;
    color: #1749f5;
    cursor: pointer;
    font-weight: 700;
}

.scm-card-body {
    display: flex;
    gap: 14px;
    align-items: center;
}

.scm-circle {
    width: 86px;
    height: 86px;
    border-radius: 50%;
    border: 8px solid #22c55e;
    display: grid;
    place-items: center;
    font-weight: 700;
    font-size: 20px;
    flex-shrink: 0;
}

.scm-circle.warning {
    border-color: #f59e0b;
    background-color: unset !important;
}

.scm-click-boxes {
    flex: 1;
    display: grid;
    gap: 8px;
}

.scm-click-box {
    border-radius: 8px;
    padding: 10px 12px;
    cursor: pointer;
    border: 1px solid;
    transition: 0.2s;
}

.scm-click-box:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
}

.scm-click-box.compliant {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #15803d;
}

.scm-click-box.non-compliant {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
}

.scm-click-box strong {
    display: block;
    font-size: 18px;
    margin-bottom: 2px;
}

.scm-click-box span {
    display: block;
    font-size: 12px;
    margin-top: 4px;
    color: inherit;
}

.scm-status {
    margin-top: 18px;
    text-align: center;
    border-radius: 8px;
    padding: 10px;
    font-weight: 700;
    font-size: 13px;
}

.scm-status.excellent {
    background: #ecfdf5;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

.scm-status.good {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

.scm-status.fair {
    background: #fff7ed;
    color: #ea580c;
    border: 1px solid #fed7aa;
}

/* Responsive */
@media (max-width: 1200px) {
    .scm-summary-grid,
    .scm-category-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .scm-summary-grid,
    .scm-category-grid {
        grid-template-columns: 1fr;
    }

    .scm-topbar {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
