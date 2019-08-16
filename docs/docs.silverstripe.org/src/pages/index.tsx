import React, { StatelessComponent } from "react";
import { graphql } from "gatsby";
import Layout from "../components/layout";
import SEO from "../components/seo";
import { Content } from "bloomer";
import { SingleFileQuery } from '../types';
import parseHTML from '../utils/parseHTML';

const IndexPage: StatelessComponent<SingleFileQuery> = ({ data: { markdownRemark: { html } }}) => {
  return (
    <Layout>
      <SEO title="Home" />
      <Content>
        <div dangerouslySetInnerHTML={{__html: parseHTML(html)}} />
      </Content>
    </Layout>
  );
};
export default IndexPage;

export const pageQuery = graphql`
query {
  markdownRemark(fields: {
    slug: {
      eq: "/"
    }
  }) {
    html
  }
}`;