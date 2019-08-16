import { useStaticQuery } from 'gatsby';
import { graphql } from 'gatsby';
import { NavigationNode, AllFilesData } from '../types';

let nodes: NavigationNode[] | undefined;
const useNodeHierarchy = (): NavigationNode[] => {
    if (!nodes) {
        const result:AllFilesData = useStaticQuery(graphql`
        {
            allDirectory(filter: {
              relativeDirectory: {eq:""}
            }) {
              edges {
                node {
                  relativePath
                  fields {
                    slug
                    title
                    fileTitle
                  }
                  children {
                    internal {
                      type
                    }
                    ... on MarkdownRemark {
                      fields {
                        slug
                        title
                        fileTitle
                        breadcrumbs
                      }
                      frontmatter {
                        summary
                      }
                    }
                    ... on Directory {
                      relativePath
                      fields {
                        slug
                        title
                        fileTitle
                      }
                      children {
                        internal {
                          type
                        }
                        ... on MarkdownRemark {
                          fields {
                            slug
                            title
                            fileTitle
                            breadcrumbs
                          }
                          frontmatter {
                            summary
                          }
                        }
                        ... on Directory {
                          relativePath
                          fields {
                            slug
                            title
                            fileTitle
                          }
                          children {
                            internal {
                              type
                            }
                            ... on MarkdownRemark {
                              fields {
                                slug
                                title
                                fileTitle
                                breadcrumbs
                              }
                              frontmatter {
                                summary
                              }
                            }
                            ... on Directory {
                              relativePath
                              fields {
                                slug
                                title
                                fileTitle
                              }
                              children {
                                internal {
                                  type
                                }
                                ... on MarkdownRemark {
                                  fields {
                                    slug
                                    title
                                    fileTitle
                                    breadcrumbs
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
            }
          }
          
        `
        );
        nodes = result.allDirectory.edges.map(e => e.node);
    }
    return nodes;
};

export default useNodeHierarchy;