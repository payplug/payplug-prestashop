<template>
  <div class="panel">
    <div class="panel-heading">
      Connexion
    </div>
    <div class="panel-row">
      <section id="loginForm">
        <div class="loginForm_row">
          <label>Email</label>
          <input type="text" v-model="email">
        </div>
        <button v-on:click="submit">Submit</button>
      </section>
    </div>
    <div class="panel-row">
      <p>Votre login : {{ email }}</p>
    </div>
  </div>
</template>

<script>
const axios = require('axios');

export default {
  name: 'Login',
  data: function(){
    return {
      email: null
    }
  },
  methods:{
    submit: function (e) {
      e.preventDefault();
      this.testRequest(this.email)
    },
    testRequest (email) {
      const ajaxUrl = 'https://d811-34-255-210-67.ngrok.io/admin-dev/index.php?controller=AdminModules&token=ffb8c68dec88d22405c121a4a6002923&configure=payplug&tab_module=payments_gateways&module_name=payplug';
      axios.post(ajaxUrl, {
          _ajax: 1,
          email: email
        })
        .then(function (response) {
          console.log(response);
        })
        .catch(function (error) {
          console.log(error);
        });
    }
  }
}
</script>