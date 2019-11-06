import { DOMElement, createElement, ReactElement } from "react";

const rewriteHeaders = (domNode: DOMElement): ReactElement | false => {
    const firstChild = domNode.children[0];
    if (firstChild && firstChild.type === 'text') {
        const { data } = firstChild;
        const matches = data.match(/^(.*?){#([A-Za-z0-9_-]+)\}$/);
        if (matches) {
            const header = matches[1]
            const id = matches[2];
            return createElement(
                domNode.name,
                { id },
                header
            );
        }
    }

    return false;
}

export default rewriteHeaders;