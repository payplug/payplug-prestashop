/**
 * 2013 - 2021 PayPlug SAS
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
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2021 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  @version   3.4.0
 *  International Registered Trademark & Property of PayPlug SAS
 */
function getHtmlTags (html) {
    var htmlContent =  document.createElement('div');
    htmlContent.innerHTML = html;
    allTags = htmlContent.getElementsByTagName("*");
    var tags = [];
    for (var i = 0, max = allTags.length; i < max; i++) {
        var tagname = allTags[i].tagName;
        if (tags.indexOf(tagname) === -1) {
            tags.push(tagname);
        }
    }
    return tags;
}

function sanitizePopupHtml (str) {
    if (str.match(/ on\w+="[^"]*"/g)) {
        return;
    }
    var tagsWhitelist = ['DIV','P', 'INPUT','BUTTON','A','UL','U','LI','STRONG','SPAN','FORM','SMALL','B','BR'];
    tags = this.getHtmlTags(str);
    return tagsWhitelist.filter(e => tags.indexOf(e) !== -1).length === tags.length;
}