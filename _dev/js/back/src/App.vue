<template>
  <div id="app">
    <PsAccount />
    <Login />
  </div>
</template>

<script>
import PsAccount from "@/components/PsAccount.vue";
import Login from "@/components/Login.vue";

export default {
  name: 'App',
  components: {
    Login,
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