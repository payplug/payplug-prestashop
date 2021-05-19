{*
* 2021 PayPlug
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
*  @copyright 2021 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}
{if isset($js_def) && is_array($js_def) && $js_def|@count}
    <script type="text/javascript">
        {foreach from=$js_def key=k item=def}
            {if !empty($k) && is_string($k)}
                {if is_bool($def)}
                    var {$k} = {$def|var_export:true};
                {elseif is_int($def)}
                    var {$k} = {$def|intval};
                {elseif is_float($def)}
                    var {$k} = {$def|floatval|replace:',':'.'};
                {elseif is_string($def)}
                    var {$k} = '{$def|strval}';
                {elseif is_array($def) || is_object($def)}
                    var {$k} = {$def|json_encode};
                {elseif is_null($def)}
                    var {$k} = null;
                {else}
                    var {$k} = '{$def|@addcslashes:'\''}';
                {/if}
            {/if}
        {/foreach}
    </script>
{/if}
