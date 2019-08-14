export interface NavigationFields {
    slug: string;
    fileTitle: string;
    filePath: string;
    title: string;
    dir: string;
    path: string[];
}

export interface NavigationNode {
    fields: NavigationFields;
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

