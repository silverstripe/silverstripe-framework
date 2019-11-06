import { ReactElement, createElement } from "react";
import Callout, { CalloutType } from '../components/Callout';
import { domToReact, DomElement, HTMLReactParserOptions } from 'html-react-parser';
const rewriteCallout = (
    domClass: string,
    children: DomElement[],
    parseOptions: HTMLReactParserOptions
): ReactElement|false => {
    const typeKey = domClass as keyof typeof CalloutType;
    const type = CalloutType[typeKey];
    if (type) {
        return createElement(
            Callout,
            { type },
            domToReact(children || [], parseOptions)
        );
    }

    return false;
};

export default rewriteCallout;
