const path = require('path');
const { createFilePath } = require(`gatsby-source-filesystem`);
const fileToTitle = require('./src/utils/fileToTitle');
const buildBreadcrumbs = require('./src/utils/buildBreadcrumbs');
const fs = require('fs');

const createSlug = (path) => (
  path
  .split('/')
  .map(part => part.replace(/^\d+_/, ''))
  .join('/')
  .toLowerCase()
);
exports.onCreateNode = ({ node, getNode, getNodesByType, actions }) => {
    const { createNodeField, createParentChildLink } = actions;
    if (node.internal.type === 'Directory') {
        const filePath = createFilePath({
          node,
          getNode,
          basePath: ``
        });

      const fileTitle = path.basename(node.absolutePath);

      createNodeField({
        node,
        name: `fileTitle`,
        value: fileTitle,
      });

      createNodeField({
        node,
        name: `title`,
        value: fileToTitle(fileTitle)
      });
      const slug = createSlug(filePath);
      createNodeField({
        node,
        name: `slug`,
        value: slug,
      });

      createNodeField({
        node,
        name: `breadcrumbs`,
        value: buildBreadcrumbs(slug),
      });

      const parentDirectory = path.normalize(node.dir + '/');
      const parent = getNodesByType('Directory').find(
        n => path.normalize(n.absolutePath + '/') === parentDirectory
      );
      if (parent) {
        node.parent = parent.id
        createParentChildLink({
            child: node,
            parent: parent
        })
      }            
    }
    if (node.internal.type === 'MarkdownRemark') {
        const filePath = createFilePath({
            node,
            getNode,
            basePath: ``
        });
        
        const slug = createSlug(filePath);

        createNodeField({
            node,
            name: `slug`,
            value: slug
        });
        let parentDirectory = path.dirname(node.fileAbsolutePath);
        const parent = getNodesByType('Directory').find(
          n => path.normalize(n.absolutePath + '/') === `${parentDirectory}/`
        );

        const fileTitle = path.basename(node.fileAbsolutePath, '.md');
        const isIndex = fileTitle === 'index';
        let { title } = node.frontmatter;
        if (!title) {
          if (isIndex && parent) {
            title = parent.fields.title
          } else {
            title = fileToTitle(fileTitle);
          }
        }

        createNodeField({
            node,
            name: `fileTitle`,
            value: fileTitle
        });
        createNodeField({
          node,
          name: `title`,
          value: title,
        });
        
        createNodeField({
          node,
          name: `breadcrumbs`,
          value: buildBreadcrumbs(slug),
        });
        
        if (parent) {
          node.parent = parent.id
          createParentChildLink({
              child: node,
              parent: parent
          });
          if (isIndex) {
            parent.indexFile___NODE = node.id;
            parent.fields.title = title;
          }
        }            
    }
}
exports.createPages = async ({ actions, graphql }) => {
  const { createPage, createNode } = actions;
  const docTemplate = path.resolve(`src/templates/docs-template.tsx`);
  const result = await graphql(`
  {
    allDirectory(filter: { base: { ne: "_images" }}) {
      nodes {
        dir
        absolutePath
      }
    }
    allMarkdownRemark(filter: {fields: { fileTitle: { ne: "" } }}) {
      nodes {
        fileAbsolutePath
        fields {
          slug
        }
        frontmatter {
          title
        }
      }
    }
  }`); 


    if (result.errors) {
        console.log(result.errors);
        throw new Error(result.errors);
    }
    result.data.allMarkdownRemark.nodes
        .forEach(node => {
            createPage({
                path: node.fields.slug,
                component: docTemplate,
                context: {
                    slug: node.fields.slug,
                }
            });
        })  

};  
