<template>
    <div style="margin: 1em">
<!--        <h2>Settings</h2>-->

        <v-row>
            <v-col cols="12">
                <v-card class="elevation-1">
                    <v-form @submit="saveSettings" ref="profile_form">
                        <v-card-text>
                            <v-text-field v-model="logo_image" placeholder="Logo Image"/>
                            <v-color-picker
                                dot-size="25"
                                swatches-max-height="200"
                                v-model="color"
                            ></v-color-picker>
                        </v-card-text>

                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="primary" type="submit">Save</v-btn>
                        </v-card-actions>
                    </v-form>
                </v-card>
            </v-col>
        </v-row>

    </div>
</template>

<script>
import api from '../common/api.js';

export default {
    data() {
        return {
            logo_image: '//ntuk.co.uk/hire/dashboard/img/logo_ntuk.png',
            color: '#FFD20A',
        };
    },
    methods: {
        saveSettings(event) {
            const self = this;
            event.preventDefault();

            api.storeSettings({
                'color': self.color,
                'logo_image': self.logo_image,
            }).then((data) => {
                self.$store.commit("showSnackbar", {
                    message: "Success!",
                    color: "success",
                });
            });
        },
    }
};
</script>

<style>
</style>
