import { DomElement, HTMLReactParserOptions, domToReact } from "html-react-parser";
import { ReactElement, createElement } from "react";

const rewriteTable = (domChildren: DomElement[], parseOptions: HTMLReactParserOptions): ReactElement => {
    return createElement(
        'div',
        { style: { overflowX: 'auto', width: '100%' }},
        createElement(
            'table',
            {},
            domToReact(domChildren, parseOptions)
        )
    );
};

export default rewriteTable;