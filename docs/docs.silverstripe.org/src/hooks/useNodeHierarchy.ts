import { useStaticQuery } from 'gatsby';
import { graphql } from 'gatsby';
import { HierarchyQuery, GenericHierarchyNode } from '../types';
import getFrontmatter from '../utils/getFrontmatter';

let nodes: GenericHierarchyNode[] | undefined;

const useNodeHierarchy = (): GenericHierarchyNode[] => {
  if (nodes) {
    return nodes;
  }
  const result:HierarchyQuery = useStaticQuery(graphql`
  fragment DirectoryFields on Directory {
    relativeDirectory
    fields {
      slug
      title
      fileTitle
      breadcrumbs
    }
    indexFile {
      ...FileFields
      frontmatter {
        hideFromMenus
        icon
        summary
      }
    }
    parent {
      id
    }
  }
  fragment FileFields on MarkdownRemark {
    id
    parentDirectory {
      fields {
        slug
      }
    }

    fields {
      slug
      title
      fileTitle
      breadcrumbs
    }
    frontmatter {
      summary
      icon
    }
    parent {
      id
    }
  }  
  {
      allDirectory(filter: {
        relativeDirectory: {eq: ".."}
      }) {
        
        nodes {
          ...DirectoryFields
          children {
            ... on MarkdownRemark {
              ...FileFields
            }
            ... on Directory {
              ...DirectoryFields
              children {
                ... on MarkdownRemark {
                  ...FileFields
                }
                ... on Directory {
                  ...DirectoryFields
                  children {
                    ... on MarkdownRemark {
                      ...FileFields
                    }
                    ... on Directory {
                      ...DirectoryFields
                      children {
                        ... on MarkdownRemark {
                          ...FileFields
                        }
                        ... on Directory {
                          ...DirectoryFields
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
  nodes = result.allDirectory.nodes.map(node => ({
    ...node,
  }));

  const mapFn = (node:GenericHierarchyNode): GenericHierarchyNode => {
    const newNode = {
      ...node,
      children: node.children
        ? node.children
            .filter(c => !['_images', 'treeicons', 'index'].includes(c.fields.fileTitle))            
            .filter(node => {
              let shouldInclude = true;
              const frontmatter = getFrontmatter(node);
              if (frontmatter) {
                shouldInclude = frontmatter.hideFromMenus !== true;
              }
              return shouldInclude;
            })        
            .map(mapFn)
        : [],
    };
    return newNode;
  };
  nodes = nodes.map(mapFn);

  return nodes;
};

export default useNodeHierarchy;