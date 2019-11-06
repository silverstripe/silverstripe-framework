import parse, { DomElement } from 'html-react-parser';
import cleanChildrenTags from './cleanChildrenTags';
import cleanWhitespace from './cleanWhitespace';
import rewriteLink from './rewriteLink';
import parseChildrenOf from './parseChildrenOf';
import rewriteCallout from './rewriteCallout';
import { ReactElement } from 'react';
import rewriteTable from './rewriteTable';
import remark from 'remark';
/**
 * Replace all the [CHILDREN] with proper React components.
 * @param html 
 * @return ReactElement | ReactElement[] | string
 */
const parseHTML = (html: string): ReactElement | ReactElement[] | string => {
    let cleanHTML = cleanChildrenTags(html);
    cleanHTML = cleanWhitespace(cleanHTML);
    const parseOptions = {
        replace(domNode: DomElement): ReactElement | object | undefined | false {
            const { name, attribs, children } = domNode;
            const domChildren = children || [];
            if (attribs) {
                if (name === 'a') {
                    return rewriteLink(attribs, domChildren, parseOptions);
                }
                if (name === 'div') {
                    if (attribs && attribs.markdown) {
                        return rewriteCallout(attribs.class, domChildren, parseOptions);                    
                    }
                }
                if (name === 'table') {
                    return rewriteTable(domChildren, parseOptions);
                }
            }
            if (domNode.data) {
                const { data } = domNode;
                return parseChildrenOf(data);
            }

            return false;
        }
    };
    const component = parse(cleanHTML, parseOptions);

    return component;
};

export default parseHTML;