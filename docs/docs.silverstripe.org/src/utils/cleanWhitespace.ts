/**
 * The react parser doesn't like whitespace nodes in tags that require
 * specific DOM node children.
 * 
 * @param html
 * @return string
 */
const cleanWhitespace = (html: string): string => {    
    let cleanHTML = html;
    const rxp = /(\<\/?(?:table|tbody|thead|tfoot|tr|th|td)\>)\s+(\<\/?(?:table|tbody|thead|tfoot|tr|th|td)\>)/;
    while (rxp.test(cleanHTML)) {
        cleanHTML = cleanHTML.replace(rxp, (_, tag1, tag2) => `${tag1}${tag2}`)
    }

    return cleanHTML;
};

export default cleanWhitespace;