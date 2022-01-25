<template>
  <div class="panel">
    <div class="panel-heading">
      Connexion
    </div>
    <div class="panel-row">
      <section class="loginForm">
        <div class="loginForm_row">
          <label>Email</label>
          <input type="text" v-model="form.email">
        </div>
        <div class="loginForm_row">
          <label>Password</label>
          <input type="password" v-model="form.password">
      </div>
        <button v-on:click="submit">Submit</button>
      </section>
    </div>
    <div class="panel-row">
      <p>Votre login : {{ login.email }}</p>
      <p>Votre login : {{ login.password }}</p>
    </div>
  </div>
</template>

<script>
export default {
  data: function(){
    return {
      form: {
        email: null,
        password: null
      }
    };
  },
  methods: {
    submit: function (e) {
      console.log(this.form);
      e.preventDefault();
    }
  }
})
</script>