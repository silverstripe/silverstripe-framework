import React, { StatelessComponent } from "react";
import { graphql } from "gatsby";
import Layout from "../components/Layout";
import SEO from "../components/SEO";
import { SingleFileQuery } from '../types';
import parseHTML from '../utils/parseHTML';

const IndexPage: StatelessComponent<SingleFileQuery> = ({ data: { markdownRemark: { html } }}) => {
  return (
    <Layout>
      <SEO title="Home" />
      <div>
        {parseHTML(html)}
      </div>
    </Layout>
  );
};
export default IndexPage;

export const pageQuery = graphql`
query IndexPage {
  markdownRemark(fields: {
    slug: {
      eq: "/"
    }
  }) {
    html
    fields {
        title
    }
}
}`;