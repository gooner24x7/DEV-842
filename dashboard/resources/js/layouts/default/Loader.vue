<template>
    <div class="loader-progress-wrapper" v-if="animate">
        <div class="loader-progress">
            <v-progress-circular
                :rotate="360"
                :size="80"
                :width="10"
                :value="value"
                color="blue"
            >
                <v-icon x-large :color="current.color">{{ current.icon }}</v-icon>
            </v-progress-circular>
            <div class="message">{{ current.message }}</div>
        </div>
    </div>
</template>

<script>
export default {
    props: ["animate"],
    data() {
        return {
            value: 0,
            interval: {},
            currentFrame: 0,
            current: {},
            progressSteps: [
                {
                    message: "Please wait",
                    icon: "mdi-timer-sand",
                    color: "black",
                },
            ],
        };
    },
    beforeDestroy() {
        this.stopAnimation();
    },
    methods: {
        startAnimation() {
            const self = this;

            self.currentFrame = 0;

            self.current = self.progressSteps[self.currentFrame];
            self.value = 0;

            self.interval = setInterval(() => {
                if (self.value === 100) {
                    self.stopAnimation();
                }
                self.value += 10;

                self.currentFrame = Math.floor(self.value / 25);

                if (self.currentFrame < self.progressSteps.length) {
                    self.current = self.progressSteps[self.currentFrame];
                }
            }, 600);
        },

        stopAnimation() {
            const self = this;

            clearInterval(self.interval);

            this.$emit('loader:stop', true)
        },
    },

    watch: {
        animate: {
            handler: function (n, o) {
                const self = this;

                if (n) {
                    self.startAnimation();
                } else {
                    self.stopAnimation();
                }
            },
        },
    },
};
</script>

<style scoped>
.loader-progress {
    position: relative;
    top: 50%;
    margin-top: -45px;
    padding: 1em;
}

.loader-progress-wrapper {
    position: fixed;
    z-index: 10000;
    top: 0px;
    right: 0px;
    bottom: 0px;
    left: 0px;
    background-color: #fffd;
    text-align: center;
}
.loader-progress .message {
    margin-top: 10px;
    font-weight: 200;
    font-size: 1.5em;
}
</style>
