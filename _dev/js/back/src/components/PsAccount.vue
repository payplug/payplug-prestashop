<template>
  <div>
    <section key="display-module-plan">
      <div class="p-0 m-auto tw-container">
        <PsAccounts :force-show-plans="true"></PsAccounts>
      </div>
      <ps-billing-customer
          v-if="billingContext.user.email"
          ref="psBillingCustomerRef"
          :context="billingContext"
          :onOpenModal="openBillingModal"
      />
      <ps-billing-modal
          v-if="modalType !== ''"
          :context="billingContext"
          :type="modalType"
          :onCloseModal="closeBillingModal"
      />
    </section>
  </div>
</template>

<script>
import Vue from 'vue';
import {PsAccounts} from "prestashop_accounts_vue_components";
import moduleLogo from "@/assets/logo.png";
import {CustomerComponent, ModalContainerComponent, EVENT_HOOK_TYPE} from "@prestashopcorp/billing-cdc/dist/bundle.umd";

export default {
  components: {
    PsAccounts,
    PsBillingCustomer: CustomerComponent.driver('vue', Vue),
    PsBillingModal: ModalContainerComponent.driver('vue', Vue),
  },
  data() {
    return {
      billingContext: {...window.psBillingContext.context, moduleLogo},
      modalType: '',
      sub: null,
    }
  },
  provide() {
    return {
      emailSupport: window.psBillingContext.context.user.emailSupport
    }
  },
  methods: {
    openBillingModal(type, data) {
      this.modalType = type;
      this.billingContext = {...this.billingContext, ...data};
    },
    closeBillingModal(data) {
      this.modalType = '';
      this.$refs.psBillingCustomerRef.parent.updateProps({
        context: {
          ...this.billingContext,
          ...data
        }
      });
    },
    eventHookHandler(type, data) {
      switch (type) {
        case EVENT_HOOK_TYPE.BILLING_INITIALIZED:
          // data structure is: { customer, subscription }
          console.log('Billing initialized', data);
          this.sub = data.subscription;
          break;
        case EVENT_HOOK_TYPE.SUBSCRIPTION_UPDATED:
          // data structure is: { customer, subscription, card }
          console.log('Sub updated', data);
          this.sub = data.subscription;
          break;
        case EVENT_HOOK_TYPE.SUBSCRIPTION_CANCELLED:
          // data structure is: { customer, subscription }
          console.log('Sub cancelled', data);
          this.sub = data.subscription;
          break;
      }
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped></style>
