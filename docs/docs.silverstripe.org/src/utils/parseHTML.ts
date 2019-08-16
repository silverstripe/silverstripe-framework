import React from 'react';
import ReactDOMServer from 'react-dom/server';
import ChildrenOf from '../components/ChildrenOf';
import useCurrentNode from '../hooks/useCurrentNode';

const parseHTML = (html: string): string => {
    const currentNode = useCurrentNode();
    let parsed = html;
    parsed = parsed.replace(
        /\[CHILDREN\]/g,
        ReactDOMServer.renderToStaticMarkup(
            React.createElement(ChildrenOf, { currentNode })
        )
    );
    
    parsed = parsed.replace(
        /\[CHILDREN Folder="?([A-Za-z0-9_]+)"?\]/g,
        function (match: string, folderName: string) {
            console.log(match, folderName);
            return ReactDOMServer.renderToStaticMarkup(
                React.createElement(ChildrenOf, { folderName, currentNode })
            );     
        }
    );
    parsed = parsed.replace(
        /\[CHILDREN Exclude="?([A-Za-z0-9_]+)"?\]/g,
        function (match: string, exclude: string) {
            return ReactDOMServer.renderToStaticMarkup(
                React.createElement(ChildrenOf, { exclude, currentNode })
            );    
        }
    );

    return parsed;
};

export default parseHTML;