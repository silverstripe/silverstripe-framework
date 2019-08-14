const path = require('path');
const { createFilePath } = require(`gatsby-source-filesystem`);

exports.onCreateNode = ({ node, getNode, actions }) => {
    const { createNodeField } = actions;
    if (node.internal.type === 'MarkdownRemark') {
        const filePath = createFilePath({
            node,
            getNode,
            basePath: ``
        });
        
        const slug = filePath.split('/').map(part => part.replace(/^\d+_/, '')).join('/').toLowerCase();
        createNodeField({
            node,
            name: `slug`,
            value: slug
        });
        createNodeField({
          node,
          name: `filePath`,
          value: filePath,
        });
        let parts = filePath.split('/');
        parts.pop();
        const title = parts.pop();
        parts = filePath.slice(0, -1).split('/');
        parts.pop();
        const dir = parts.join('/');
        createNodeField({
          node,
          name: `dir`,
          value: dir,
        });
        createNodeField({
            node,
            name: `fileTitle`,
            value: title
        });
        createNodeField({
          node,
          name: `title`,
          value: title.replace(/^\d+_/, '').replace(/_/g, ' '),
        });
        createNodeField({
          node,
          name: `path`,
          value: dir.split('/'),
        });
    }

}
exports.createPages = async ({ actions, graphql }) => {
  const { createPage, createNode } = actions;
  const docTemplate = path.resolve(`src/templates/docs-template.tsx`);
  const result = await graphql(`
  {
    allMarkdownRemark {
      edges {
        node {
          fields {
            slug
          }
        }
      }
    }
  }`); 


    if (result.errors) {
        console.log(result.errors);
        throw new Error(result.errors);
    }
    result.data.allMarkdownRemark.edges
        .forEach(({ node }) => {
            createPage({
                path: node.fields.slug,
                component: docTemplate,
                context: {
                    slug: node.fields.slug,
                }
            });
        })  

};  
