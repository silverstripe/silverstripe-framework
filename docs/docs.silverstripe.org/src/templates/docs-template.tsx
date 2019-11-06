import React, { StatelessComponent, ReactElement } from 'react';
import { graphql } from 'gatsby';
import Layout from '../components/Layout';
import SEO from '../components/SEO';
import { SingleFileQuery } from '../types';
import parseHTML from '../utils/parseHTML';

const Template: StatelessComponent<SingleFileQuery> = (result): ReactElement => {
    const currentNode = result.data.markdownRemark;
    const { html, fields } = currentNode;
    return (
    <Layout currentNode={result.data.markdownRemark}>
      <SEO title={fields.title} />
      {parseHTML(html)}
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