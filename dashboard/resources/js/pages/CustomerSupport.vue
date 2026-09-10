<template>
    <div style="margin: 1em">
        <v-tabs
            v-model="tab"
            :hide-slider="true"
            :show-arrows="true"
            background-color="transparent"
            grow
        >
            <v-tab
                v-for="item in sections"
                :key="item"
            >
                <v-btn
                    elevation="2"
                    class="primary"
                    large
                    width="100%"
                >{{ item }}
                </v-btn>
            </v-tab>
        </v-tabs>

        <v-tabs-items v-model="tab" style="margin-bottom: 5em;">
            <v-tab-item key="Support Tickets">
                <div style="margin: 1em">
                    <h2>Tickets</h2>

                    <div style="max-width: 600px;"><create-ticket></create-ticket></div>
                </div>
            </v-tab-item>

            <v-tab-item key="Video Tutorials">
                <div style="margin: 1em">
                    <h2>Video Tutorials</h2>
                    <v-row class="tutorials-container">
                        <v-col cols="12" lg="3" v-for="item in tutorials" :key="item.id">
                            <div class="video-tutorial" :title="item.title" v-html="item.embedCode"></div>
                            <div>{{ item.title }}</div>
                        </v-col>
                    </v-row>
                </div>
            </v-tab-item>

            <v-tab-item key="Contact Us">
                <div class="contacts" style="margin: 1em">
                    <div><i class="fal fa-phone"></i> 01302 554 990</div>

                    <div><i class="fal fa-envelope"></i> contact@ntuk.co.uk</div>

                    <div><i class="fal fa-building"></i> Doncaster Business Innovation Centre, Ten Pound Walk,
                        Doncaster, DN4 5HX
                    </div>
                </div>
            </v-tab-item>
        </v-tabs-items>

        <script type="application/javascript" defer id="zsiqchat">
            var $zoho = $zoho || {};
            $zoho.salesiq = $zoho.salesiq || {
                widgetcode: "264cad8431433b609382a0cbad951390f47fa9105c8f74864ea1fd23a6c96d7a19e2cfe8ecb9347474abf11ef214119d",
                values: {},
                ready: function () {
                }
            };
            var d = document;
            s = d.createElement("script");
            s.type = "text/javascript";
            s.id = "zsiqscript";
            s.defer = true;
            s.src = "https://salesiq.zoho.eu/widget";
            t = d.getElementsByTagName("script")[0];
            t.parentNode.insertBefore(s, t);
        </script>

    </div>
</template>

<script>
import CreateTicket from "../components/CreateTicketForm";
import api from "../common/api";

export default {
    name: "CustomerSupport",
    components: {CreateTicket},
    data() {
        return {
            tab: null,
            tutorials: [],
            sections: [
                'Support Tickets', 'Video Tutorials', 'Contact Us',
            ],
        }
    },
    mounted() {
        const self = this;
        let params = {
            page: 1,
            itemsPerPage: 20,
            sortBy: 'id',
            sortDesc: 1,
        };

        api
            .loadTutorials(params)
            .then((response) => {
                self.tutorials = response.data.data;
            });
    }
}
</script>

<style>
.theme--light.v-tabs-items {
    background-color: transparent;
}

.contacts {
    font-size: 18pt;
}

.contacts div {
    margin-bottom: 1em;
}

.tutorials-container .video-tutorial > iframe {
    width: 100%;
}
</style>
