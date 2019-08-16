import { ReactElement } from 'react';

export interface NavigationFields {
    slug: string;
    parentSlug: string;
    fileTitle: string;
    filePath: string;
    relativeDirectory: string;    
    title: string;
    dir: string;
    breadcrumbs: string[];
    isFolder: boolean;
}

export interface NavigationNode {
    html: string;
    fileAbsoultePath: string;
    fields: NavigationFields;
    frontmatter: FrontMatter;
}

export interface AllFilesEdge {
    node: NavigationNode;
}

export interface AllFilesResult {
    edges: AllFilesEdge[];
}

export interface AllFilesData {
    allMarkdownRemark: AllFilesResult;
}

export interface AllFilesQuery {
    data: AllFilesData
}

export interface NavigationItem {
    children: NavigationItem[];
    node: NavigationNode;
}

export interface FrontMatter {
    title: string,
    summary: string,
    introduction: string,
}

export interface MarkdownRemark {
    markdownRemark: Page
}

export interface PageType {
    html: string,
    frontmatter: FrontMatter
}

export interface Page {
    page: PageType,
    html: string,
    fields: NavigationFields,
}

export interface SingleFileQuery {
    data: MarkdownRemark
}

export interface MenuItemProps {
    active: boolean,
    item: NavigationItem,
    mapFn(item: NavigationItem): ReactElement,
}
