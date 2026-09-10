<template>
  <v-flex md6 p-2>
    <v-card class="partner-block">
      <div class="text-center m-2 mx-md-10 my-md-2 text-md-left">
        <img :src="partner.logo" />

        <ul class="contacts " :class="{'contacts-right': (!$vuetify.breakpoint.xs && !$vuetify.breakpoint.sm)}">
          <li>Website: {{ partner.website }}</li>
          <li>Email: {{ partner.email }}</li>
          <li>Phone: {{ partner.phone }}</li>
        </ul>

        <h3>Company Description</h3>

        <v-card-text class="px-0">{{ partner.description }}</v-card-text>
      </div>
    </v-card>
  </v-flex>
</template>

<script>
export default {
  props: ["partner"],
  data() {
    return {};
  },
};
</script>

<style scoped>
.partner-block {
  border: solid 3px yellow;
}

.partner-block img {
  max-width: 100%;
  max-height: 200px;
}

.partner-block .contacts {
  list-style-type: none;
  text-align: right;
  padding: 0px;
  font-weight: 200;
}

.partner-block .contacts-right {
    position: absolute;
    right: 30px;
    top: 5px;
    font-size: 18px;
}

.partner-block h3 {
  font-size: 16px;
  font-weight: bold;
  margin: 1em 0em;
}

.partner-block .v-card__text {
  margin: 0px;
  padding: 0px;
}
</style>