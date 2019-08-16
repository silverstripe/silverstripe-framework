import { useStaticQuery, graphql } from 'gatsby';
import { NavigationItem } from '../types';

let path: string | undefined;
let node: NavigationItem | undefined;

const useCurrentNode = (): NavigationItem | null => {
    if (!path || path !== window.location.pathname) {
        path = window.location.pathname;
        const result = useStaticQuery(graphql`
            {
                allMarkdownRemark {
                    edges {
                        node {
                            html
                            fields {
                                title
                                slug
                                breadcrumbs
                                fileTitle
                            }
                            frontmatter {
                                summary
                            }
                            parent {
                                ... on Directory {
                                    fields {
                                        title
                                        slug
                                        fileTitle
                                    }
                                    children {
                                        ... on MarkdownRemark {
                                            fields {
                                                title
                                                fileTitle
                                                slug
                                            }
                                            frontmatter {
                                                summary
                                            }
                                        }
                                        ... on Directory {
                                            fields {
                                                title
                                                slug
                                                fileTitle
                                            }
                                            children {
                                                ... on MarkdownRemark {
                                                    fields {
                                                        title
                                                        fileTitle
                                                        slug
                                                    }
                                                    frontmatter {
                                                        summary
                                                    }
                                                }
                                            }
        
                                        }
                                    }
                                }
                            }
                        }            
                    }
                }
            }
        `);
        node = result.allMarkdownRemark.edges.find(e => e.node.fields.slug === path);
    }

    return node ? node.node : null;
};

export default useCurrentNode;