import { NavigationNode, NavigationItem } from '../types';

const buildNavigationNode = (parentCategory: string, allNodes: NavigationNode[] = []): NavigationItem[] => {
    const filteredNodes = allNodes.filter(({ fields: { slug, dir } }) => {
        if (slug === '/') {
            return false;
        }
        return dir === parentCategory;
    });
    return filteredNodes.map(node => {
        return {
            children: buildNavigationNode(node.fields.filePath.slice(0, -1), allNodes),
            node,
        }
    }).sort((a, b) => a.node.fields.fileTitle < b.node.fields.fileTitle ? -1 : 1);    
};


export default buildNavigationNode;