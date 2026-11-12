<?php

/**
 * PrestashopAdapter17::displayPaymentOption() builds a
 * \PrestaShop\PrestaShop\Core\Payment\PaymentOption for every entry it is
 * given, regardless of which guard branch fired. That class ships with
 * PrestaShop core and is not available in this repo's unit test runtime
 * (tests/bootstrap.php only defines _PS_VERSION_; the only other stub for
 * this class name lives in tests/stubs/phpstan-bootstrap.php and is loaded
 * solely for static analysis, never for phpunit). Without a stand-in,
 * `new PaymentOption()` fatals as soon as displayPaymentOption() is
 * exercised, so we provide a minimal fluent stand-in here, scoped to this
 * test directory only.
 */

namespace PrestaShop\PrestaShop\Core\Payment;

if (!class_exists(PaymentOption::class)) {
    class PaymentOption
    {
        private $logo;
        private $callToActionText;
        private $moduleName;
        private $inputs;
        private $action;
        private $additionalInformation;

        public function setLogo($logo)
        {
            $this->logo = $logo;

            return $this;
        }

        public function setCallToActionText($call_to_action_text)
        {
            $this->callToActionText = $call_to_action_text;

            return $this;
        }

        public function setModuleName($module_name)
        {
            $this->moduleName = $module_name;

            return $this;
        }

        public function setInputs($inputs)
        {
            $this->inputs = $inputs;

            return $this;
        }

        public function setAction($action)
        {
            $this->action = $action;

            return $this;
        }

        public function setAdditionalInformation($additional_information)
        {
            $this->additionalInformation = $additional_information;

            return $this;
        }

        public function getLogo()
        {
            return $this->logo;
        }

        public function getCallToActionText()
        {
            return $this->callToActionText;
        }

        public function getModuleName()
        {
            return $this->moduleName;
        }

        public function getInputs()
        {
            return $this->inputs;
        }

        public function getAction()
        {
            return $this->action;
        }

        public function getAdditionalInformation()
        {
            return $this->additionalInformation;
        }
    }
}
