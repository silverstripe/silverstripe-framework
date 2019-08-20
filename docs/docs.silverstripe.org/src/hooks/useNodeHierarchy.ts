import { useStaticQuery } from 'gatsby';
import { graphql } from 'gatsby';
import { HierarchyQuery, GenericHierarchyNode } from '../types';
import { node } from 'prop-types';

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
    }
    parent {
      id
    }
  }
  fragment FileFields on MarkdownRemark {
    id
    fields {
      slug
      title
      fileTitle
      breadcrumbs
    }
    frontmatter {
      summary
    }
    parent {
      id
    }
  }  
  {
      allDirectory(filter: {
        relativeDirectory: {eq: ".."},
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
            .map(mapFn)
        : [],
      siblings: node.parent
        ? nodes.filter(n => n.parent && n.parent.id === node.parent.id)
        : [],
    };

    return newNode;
  };
  nodes = nodes.map(mapFn);

  return nodes;
};

export default useNodeHierarchy;