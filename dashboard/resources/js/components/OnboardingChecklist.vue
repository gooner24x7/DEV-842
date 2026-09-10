<template>
    <div>
        <h4>Onboarding Checklist</h4>
        <div>Target Date: {{ onboarding_end_date }}</div>
        <ul class="oc-list">
            <template v-for="(item, key) in onboarding_checklist">
                <li class="oc-item" v-if="item.visible ?? false">
                    <label class="customcheck">
                        <input type="checkbox"
                               v-model="item.value"
                               v-on:change="update(userData, key, item.value)"
                               :disabled="isDisabled(key)"
                        >
                        <span class="checkmark"></span> {{item.label}}
                    </label>
                </li>
                <div class="oc-message" v-if="item.message !== null">{{item.message}}</div>
<!--                <div class="oc-actions" v-if="key === 'form_complete'">-->
<!--                    <v-btn color="primary" small @click="goToOnboardingForm()">Onboarding Form</v-btn>-->
<!--                </div>-->
            </template>
        </ul>
        <template v-if="disableInputs">
            <br>
            <div>Contact support@thebuildchain.co.uk for help</div>
        </template>
    </div>
</template>

<script>
import api from "../common/api";
import permissions from "../common/permissions";

export default {
    name: "OnboardingChecklist",
    props: ["userData", "disableInputs"],

    data() {
        return {
            props: {
                disableInputs: false,
            },
            onboarding_end_date: "",
            onboarding_checklist: {
                account_setup: {
                    label: 'Account set-up & invoice paid',
                    message: 'Pay your first invoice to access your account',
                    value: false,
                    visible: true,
                },
                form_complete: {
                    label: 'Fill out your onboarding form',
                    value: false,
                    visible: true,
                },
                first_enquiry:{
                    label: 'Send your first enquiry',
                    value: false,
                    visible: this.isSubcontractor()
                },
                line_of_credit: {
                    label: 'Line of Credit',
                    value: false,
                    visible: this.isSubcontractor()
                },
                first_project:{
                    label: 'Create your first project',
                    value: false,
                    visible: this.isContractor()
                },
                first_works_package:{
                    label: 'Create your first works package',
                    value: false,
                    visible: this.isContractor()
                },
                first_tender:{
                    label: 'Send your first tender',
                    value: false,
                    visible: this.isContractor()
                },
            },
            showUploadIcon: false,
        }
    },

    watch: {
        userData: function(data) {
            this.load(data);
        }
    },

    methods: {
        async update(item, key, value) {
            let response = await api.updateOnboardingChecklist(item.id, key, value);

            if (response.data) {
                item[key] = value;
            }
        },
        load(data) {
            const self = this;

            self.onboarding_end_date = data.onboarding_end_date ?? "";

            Object.keys(self.onboarding_checklist).forEach((key) => {
                if (typeof data[key] !== 'undefined') {
                    self.onboarding_checklist[key].value = !!data[key];
                }
            });
        },
        goToOnboardingForm() {
            this.$router.push({name: 'onboarding-form'});
        },
        isDisabled(key) {
            let disabledKey = this.onboarding_checklist[key].disabled ?? false;

            return disabledKey || this.disableInputs;
        },
        isContractor() {
            return permissions.hasRole(permissions.role_contractor);
        },
        isSubcontractor() {
            return permissions.hasRole(permissions.role_user) && !permissions.hasRole(permissions.role_contractor);
        }
    },
    mounted() {
        this.load(this.userData);
    },
}
</script>

<style scoped>
.oc-item {
    padding: 0;
}

.oc-message {
    padding-left: 45px;
    font-size: 11px;
}

.oc-actions {
    display: inline-flex;
    padding: 10px 0 0 30px;
}

.oc-upload-icon {
    font-size: 18px;
    margin-left: 10px;
}

.oc-actions > a:hover{
    text-decoration: none;
}

/* checkbox styling */
.oc-list {
    list-style: none;
    padding: 0;
}

.oc-list li {
    border-top: 1px solid #E5E5E5;
    padding: 1em 0 0 0 ;
    margin-top: 1em;
}

.customcheck {
    margin: 0;
    position: relative;
}

.customcheck input {
    display: none;
}

.customcheck input ~ .checkmark {
    background: #d00d0d;
    width: 30px;
    display: inline-block;
    position: relative;
    height: 30px;
    border-radius: 15px;
    vertical-align: middle;
    margin-right: 10px;
}

.customcheck input ~ .checkmark:after,
.customcheck input ~ .checkmark:before {
    content: "";
    position: absolute;
    width: 2px;
    height: 16px;
    background: #fff;
    left: 14px;
    top: 7px;
}

.customcheck input ~ .checkmark:after {
    transform: rotate(-45deg);
    z-index: 1;
}

.customcheck input ~ .checkmark:before {
    transform: rotate(45deg);
    z-index: 1;
}

.customcheck input:checked ~ .checkmark {
    background: #57b60b;
    width: 30px;
    display: inline-block;
    position: relative;
    height: 30px;
    border-radius: 15px;
}

.customcheck input:checked ~ .checkmark:after {
    display: none;
}

.customcheck input:checked ~ .checkmark:before {
    background: none;
    border: 2px solid #fff;
    width: 7px;
    top: 7px;
    left: 12px;
    border-top: 0;
    border-left: 0;
    height: 14px;
}
</style>
