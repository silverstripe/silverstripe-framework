import React, { StatelessComponent } from 'react';
import { graphql } from 'gatsby';
import Layout from '../components/layout';
import SEO from '../components/seo';
import { SingleFileQuery } from '../types';
import { Content } from 'bloomer/lib/elements/Content';
import parseHTML from '../utils/parseHTML';

const Template: StatelessComponent<SingleFileQuery> = (result) => {
    const currentNode = result.data.markdownRemark;
    const { html, fields } = currentNode;
    return (
    <Layout currentNode={result.data.markdownRemark}>
      <SEO title={fields.title} />
        <Content>
            <div dangerouslySetInnerHTML={{ __html: parseHTML(html) }} />
        </Content>
    </Layout>
    );
};

export default Template;

export const pageQuery = graphql`
  query DocsBySlug($slug: String!) {
    markdownRemark(fields: { slug: { eq: $slug }}) {
      html
      fields {
          title
      }
    }
  }
`
;