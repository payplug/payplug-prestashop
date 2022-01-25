<template>
  <div id="app">
    <PsAccount />
  </div>
</template>

<script>
import PsAccount from "@/components/PsAccount.vue";

export default {
  name: 'App',
  components: {
    PsAccount
  },
  setup() {
    console.log('setup Payplug Vue.js App');
  }
}
</script>

<style>
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
  margin-top: 60px;
}
</style>