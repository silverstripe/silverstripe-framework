import { NavigationNode, NavigationItem } from '../types';
import { relative } from 'path';
import nodePath from 'path';
import fileToTitle from '../utils/fileToTitle';
import buildBreadcrumbs from './buildBreadcrumbs';

const createDirectoryNode = (path: string, allNodes: NavigationNode[]): NavigationItem | null => {
    const indexDocument = allNodes.find(({ fields: { parentSlug, slug}}) => {
        return slug === path && slug === parentSlug
    });

    if (indexDocument) {
        return {
            node: indexDocument,
            children: [],
        };
    }
    const fileTitle = nodePath.basename(path);
    const { breadcrumbs } = children[0].fields;
    const slug = breadcrumbs[breadcrumbs.length - 2];
    const parentSlug = breadcrumbs[breadcrumbs.length - 3];
    const title = fileToTitle(fileTitle);
    return {
        node: {
            html: '',
            fields: {
                title,
                slug,
                parentSlug,
                fileTitle,
                filePath: path,
                relativeDirectory: nodePath.dirname(path),
                dir: 'iam a dir',
                breadcrumbs: buildBreadcrumbs(slug)
            },
            frontmatter: {
                title,
                summary: '',
                introduction: '',            
            }
        },
        children: [],
    }
};

const buildNavigation = (allNodes: NavigationNode[]): NavigationItem[] => {
    const map = {};
    allNodes.forEach(({ fields: { parentSlug }}) => {
        map[parentSlug] = true;
    });

    const allDirs = Object.keys(map);
    let nodes: NavigationItem[] = [];
    allDirs.forEach(path => {    
        const node = createDirectoryNode(path, allNodes);
        if (node) {
            nodes.push(node);
        }
    });
    nodes.forEach(node => {
        const children = nodes.filter(n => n.node.fields.parentSlug === node.node.fields.slug);
        console.log('found', children.length, 'children for ', node.node.fields.slug);
        node.children = children;
    })
    nodes = nodes.sort((a, b) => a.node.fields.fileTitle < b.node.fields.fileTitle ? -1 : 1);

    return nodes;
};

export default buildNavigation;