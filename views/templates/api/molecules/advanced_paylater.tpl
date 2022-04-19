{capture assign="accordionTitle"}{l s='paylater.block.accordionTitle' mod='payplug'}{/capture}
{capture assign="accordionThresholdsContent"}
    <div class="paylaterThresholds">{include file='./paylater_thresholds.tpl'}</div>
{/capture}
{capture assign="accordionOptimisedContent"}
    <div class="paylaterOptimised">{include file='./paylater_optimised.tpl'}</div>
{/capture}
{capture assign="accordionContent"}{$accordionThresholdsContent}{$accordionOptimisedContent}{/capture}
{include file='./../atoms/accordion/accordion.tpl'
accordionIdentifier='payplugUIAccordion.identifier'
accordionClassName='-advansedPayLater'
accordionData='oneyAdvancedSettings'
accordionLabel=$accordionTitle
accordionContent=$accordionContent}
