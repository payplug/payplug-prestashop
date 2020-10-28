<?php


namespace PayPlug\src\repositories;


class HookRepository extends \Payplug
{
    /**
     * Install Oney Hooks
     * @return bool
     */
    public function installOneyHook()
    {
        $hooks = [
            'actionObjectCarrierAddAfter',
            'actionCarrierUpdate',
            'displayProductPriceBlock',
            'displayExpressCheckout',
            'actionClearCompileCache',
        ];

        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $hooks[] = 'displayBeforeShoppingCartBlock';
        }

        $flag = true;
        foreach ($hooks as $hook) {
            $flag = $this->registerHook($hook) && $flag;
        }

        return $flag;
    }
}