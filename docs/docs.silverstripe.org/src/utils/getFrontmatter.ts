import { GenericHierarchyNode, HierarchyFrontmatter } from "../types"

const getFrontmatter = (node: GenericHierarchyNode): HierarchyFrontmatter|null => {
    if (node.__typename === 'Directory') {
        return node.indexFile ? node.indexFile.frontmatter : null;
    }

    return node.frontmatter;
};

export default getFrontmatter;