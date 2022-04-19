{capture assign="thresholdsTitle"}{l s='paylater.block.thresholdsTitle' mod='payplug'}{/capture}
{capture assign="thresholdsDescription"}{l s='paylater.block.thresholdsDescription' mod='payplug'}{/capture}
{capture assign="thresholdsBetweenText"}{l s='paylater.block.thresholdsBetweenText' mod='payplug'}{/capture}
<div class="thresholdsImg">
    {include file="./../_svg/thresholds.tpl"}
</div>

<div class="thresholdsDescription">
    {include file='./../atoms/title/title.tpl' titleClassName='_thresholdsTitle' titleText=$thresholdsTitle}
    {include file='./../atoms/paragraph/paragraph.tpl'
    paragraphClassName='_thresholdsParagraph'
    paragraphText=$thresholdsDescription }
    <div class="thresholdsInput">
        <div class="min">
            {include file='./../atoms/input/input.tpl'
            inputType='number'
            inputMin='100'
            inputPlaceholder='100'
            inputValue=$oney_custom_min_amounts
            inputIcon='Euro'
            inputClassName='minThreshold'
            inputName='payplug_oney_custom_min_amounts'
            inputData='oneyThresholdMin'}
        </div>


        {include file='./../atoms/paragraph/paragraph.tpl'
        paragraphClassName='thresholdsAnd'
        paragraphText=$thresholdsBetweenText }
        <div class="max">{include file='./../atoms/input/input.tpl'
            inputType='number'
            inputPlaceholder='3000'
            inputValue=$oney_custom_max_amounts
            inputIcon='Euro'
            inputClassName='maxThreshold'
            inputName='payplug_oney_custom_max_amounts'
            inputData='oneyThresholdMax'}
        </div>
    </div>

    <div class="thresholdStatement">
        {include file='./../atoms/icon/icon.tpl'
        iconName='error'
        iconClassName='thresholdErrorIcon'
        }
        <span class="thresholdError" data-e2e-error="oneyThresholdError"></span>
    </div>
</div>