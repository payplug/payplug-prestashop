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

{capture assign="descriptionBlock_content"}
    <div class="_header">
        <div class="_logo">
            {include file="./../../../img/svg/logo-{$module_name|escape:'htmlall':'UTF-8'}.svg"}
        </div>
        <div class="_version">
            {assign var='description_descriptionVersionClassName' value='_descriptionVersion'}
            {capture assign="description_descriptionVersion"}
                V {$pp_version|escape:'htmlall':'UTF-8'}
            {/capture}
            {include file='./../atoms/paragraph/paragraph.tpl'
                paragraphText=$description_descriptionVersion
                paragraphClassName=$description_descriptionVersionClassName}

            {capture assign="description_show_hidden"}{l s='description.show.hidden' mod='payplug'}{/capture}
            {capture assign="description_show_visible"}{l s='description.show.visible' mod='payplug'}{/capture}
            {assign var='descriptionShow' value=[
                ['key' => '0', 'value' => $description_show_hidden, 'selected' => !$payplug_switch.show.checked],
                ['key' => '1', 'value' => $description_show_visible, 'selected' => $payplug_switch.show.checked]
            ]}
            {include file='./../atoms/select/select.tpl'
                selectClassName='_show'
                selectName=$payplug_switch.show.name|escape:'htmlall':'UTF-8'
                selectOptions=$descriptionShow
                selectDisabled=!$ps_account || !$connected}
        </div>
    </div>
    {assign var='description_descriptionTitleClassName' value='_descriptionTitle'}
    {capture assign="description_descriptionTitle"}
        {l s='description.block.title' mod='payplug'}
    {/capture}
    {include file='./../atoms/paragraph/paragraph.tpl'
        paragraphText=$description_descriptionTitle
        paragraphClassName=$description_descriptionTitleClassName}

    {assign var='description_descriptionTextClassName' value='_descriptionText'}
    {capture assign="description_descriptionText"}
        {l s='description.block.text' mod='payplug'}
    {/capture}
    {include file='./../atoms/paragraph/paragraph.tpl'
        paragraphText=$description_descriptionText
        paragraphClassName=$description_descriptionTextClassName}
{/capture}

{assign var='descriptionBlock_className' value='descriptionBlock'}

{include file='./../atoms/block/block.tpl'
    blockContent=$descriptionBlock_content
    blockData='blockDescription'
    blockClassName=$descriptionBlock_className}