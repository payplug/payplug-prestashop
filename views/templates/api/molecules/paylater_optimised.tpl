{capture assign="optimisedTitle"}{l s='paylater.block.optimisedTitle' mod='payplug'}{/capture}
{capture assign='faq_oneyBlock'}
    {include file='./../atoms/link/link.tpl'
    linkText=''
    linkHref=$faq_links.oney
    linkData='faqOney'
    linkNoTag=true}
{/capture}
{capture assign="optimisedDescription"}{l s='paylater.block.optimisedDescription' tags=[$faq_oneyBlock] mod='payplug'}{/capture}
<div class="optimisedImg" >
    {include file="./../_svg/optimised.tpl"}
</div>

<div class="optimisedDescription" >
    <div class="activateOptimisationText">
        {include file='./../atoms/title/title.tpl' titleClassName='_optimisedTitle' titleText=$optimisedTitle}
        {include file='./../atoms/paragraph/paragraph.tpl'
        paragraphClassName='optimisedParagraph'
        paragraphText=$optimisedDescription }

    </div>
    <div class="activeOptimisationButton">
        {include file='./../atoms/switch/switch.tpl' switchEnabledLabel='On'
        switchDisabledLabel='Off'
        switchDataName='optimisedSwitch'
        checked=false
        switchClassName="optimisedSwitch"
        switchName=$payplug_switch.oney_optimized.name}
    </div>
</div>