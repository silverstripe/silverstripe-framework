import { ReactElement } from 'react';


export interface SingleFileQuery {
    data: SingleFileResult
};

export interface SingleFileResult {
    markdownRemark: SinglePage
};

export interface SinglePage {
    html: string,
    fields: SinglePageFields
};

export interface SinglePageFields {
    title: string;
};

export interface HierarchyFields {
    slug: string;
    title: string;
    fileTitle: string;
    breadcrumbs: string[],
};

export interface HierarchyParentNode {
    id: string;
};

export interface HierarchyFrontmatter {
    summary?: string,
};

export interface HierarchyFileNode {
    relativeDirectory: string;
    id: string;
    fields: HierarchyFields;
    parent: HierarchyParentNode;
    frontmatter: HierarchyFrontmatter;
};

export interface HierarchyDirectoryFields {
    slug: string;
    title: string;
    fileTitle: string;
};

export interface HierarchyDirectoryNode {
    relativeDirectory: string;
    indexFile: HierarchyFileNode;
    parent: HierarchyParentNode;
    fields: HierarchyFields;
};

export interface GenericHierarchyNode extends HierarchyDirectoryNode, HierarchyFileNode {
    __typename: string;
    children: GenericHierarchyNode[];
    siblings: GenericHierarchyNode[];
};

export interface HierarchyResult {
    nodes: GenericHierarchyNode[]
};

export interface HierarchyQuery {
    allDirectory: HierarchyResult;
};

export interface ChildrenOfProps {
    folderName?: string;
    exclude?: string;
    currentNode: GenericHierarchyNode | null;
};

export interface MenuItemProps {
    active: boolean,
    item: GenericHierarchyNode,
    mapFn(item: GenericHierarchyNode): ReactElement,
}
