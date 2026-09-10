<template>
  <v-app :dark="false" id="inspire" baseline>
    <print v-if="printable"></print>
    <normal v-else></normal>
  </v-app>
</template>

<script>
import Normal from "./Normal.vue";
import Print from "./Print.vue";

export default {
  computed: {
    printable: function() {
      return this.$route.query.printable;
    }
  },
  components: { Normal, Print },
  data() {
    return { showLeftMenuToolTip: true };
  }
};
</script>