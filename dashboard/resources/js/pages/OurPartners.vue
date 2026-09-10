<template>
  <div style="margin: 1em">
    <h2>Our Partners</h2>

    <v-layout row wrap>
      <partner v-for="item in items" :key="item.id" :partner="item" />
    </v-layout>
  </div>
</template>

<script>
import Partner from "../components/Partner";
import api from "../common/api.js";

export default {
  components: {
    Partner,
  },
  data() {
    return {
      loader: false,
      items: [],
    };
  },
  mounted() {
    this.load();
  },
  methods: {
    load() {
      const self = this;
      self.loader = true;

      let params = {};

      api
        .loadPartners(params)
        .then((response) => {
          self.items = response.data.data;
          self.loader = false;
        })
        .catch((err) => {
          self.loader = false;
        });
    },
  },
};
</script>

<style>
</style>
