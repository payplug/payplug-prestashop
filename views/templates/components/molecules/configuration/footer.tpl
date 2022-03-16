{*
* 2022 PayPlug
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0).
* It is available through the world-wide-web at this URL:
* https://opensource.org/licenses/osl-3.0.php
* If you are unable to obtain it through the world-wide-web, please send an email
* to contact@payplug.com so we can send you a copy immediately.
*
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
*
*  @author PayPlug SAS
*  @copyright 2022 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}
<div class="_footer">
    {capture assign="footerConfiguration_save"}{l s='configuration.footer.save' mod={$module_name}}{/capture}
    {include file='./../../atoms/button/button.tpl'
        buttonClassName='_save'
        buttonData='saveConfiguration'
        buttonName='saveConfiguration'
        buttonStyle='default'
        buttonDisabled=!$connected
        buttonText=$footerConfiguration_save}
</div>