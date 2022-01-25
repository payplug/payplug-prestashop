import Vue from "vue";
import VueI18n from "vue-i18n";

Vue.use(VueI18n);

const locale = 'fr';
const messages = {};
const numberFormats = {};
// create standard isoCode with prestashop language locale (1.6.1)

numberFormats[locale] = {
  currency: {
    style: "currency",
    currency: '$'
  }
};

export default new VueI18n({
  locale,
  messages,
  numberFormats
});
