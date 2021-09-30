function getHtmlTags (html) {
    console.log("getHtmlTags");
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
    // if (str.match(/ on\w+="[^"]*"/g)) {
    //     return;
    // }
    var tagsWhitelist = ['DIV','P', 'INPUT','BUTTON','A','UL','LI','STRONG','SPAN','FORM','SMALL','B','BR'];
    tags = this.getHtmlTags(str);
    return tagsWhitelist.filter(e => tags.indexOf(e) !== -1).length === tags.length;
}