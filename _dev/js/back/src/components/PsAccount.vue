<template>
  <div>
    <section key="display-module-plan">
      <ps-accounts
          class="ps_gs-ps-account-card"
          :context="psAccountsContext"
      />
    </section>
  </div>
</template>

<script>
import {PsAccounts} from "prestashop_accounts_vue_components";

export default {
  components: {
    PsAccounts,
  },
  computed: {
    psAccountsContext() {
      return window.contextPsAccounts.context;
    }
  }
}
</script>
