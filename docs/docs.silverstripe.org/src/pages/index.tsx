import React, { StatelessComponent } from "react";
import { Link, graphql } from "gatsby";
import Layout from "../components/layout";
import SEO from "../components/seo";
import { AllFilesQuery } from '../types';

const IndexPage: StatelessComponent<AllFilesQuery> = ({ data: {allMarkdownRemark: { edges }}}) => {
  const nodes = edges.map(e => e.node);
  return (
    <Layout>
      <SEO title="Home" />
      <h1>SilverStripe Documentation</h1>
    </Layout>
  );
};
export default IndexPage;

export const pageQuery = graphql`
query AllFiles {
  allMarkdownRemark(limit: 1000, sort: {fields: fields___title, order: ASC}) {
    edges {
      node {
        fields {
          slug
          title
          fileTitle
          dir
          path
        }
      }
    }
  }
}
`