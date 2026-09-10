<template>
  <div class="reg-form-loader-progress-wrapper" v-if="animate">
    <div class="reg-form-loader-progress">
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
          message: "Pinpointing Location",
          icon: "mdi-map-marker-radius",
          color: "black",
        },
        {
          message: "Filtering Suppliers",
          icon: "mdi-filter-menu-outline",
          color: "black",
        },
        {
          message: "Success your enquiry has been received by regional merchants",
          icon: "mdi-check-circle",
          color: "green",
        },
        {
          message: "Please check your emails for notifications",
          icon: "mdi-email-outline",
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
      }, 800);
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
.reg-form-loader-progress {
  position: relative;
  top: 50%;
  margin-top: -45px;
  padding: 1em;
}

.reg-form-loader-progress-wrapper {
  position: fixed;
  z-index: 10000;
  top: 0px;
  right: 0px;
  bottom: 0px;
  left: 0px;
  background-color: #fffd;
  text-align: center;
}
.reg-form-loader-progress .message {
  margin-top: 10px;
  font-weight: 200;
  font-size: 1.5em;
}
</style>
