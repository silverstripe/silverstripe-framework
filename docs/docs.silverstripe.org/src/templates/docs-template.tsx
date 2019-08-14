import React, { StatelessComponent } from 'react';
import { graphql } from 'gatsby';
import Layout from '../components/layout';
import SEO from '../components/seo';
import { NavigationFields } from '../types';
import { Content } from 'bloomer/lib/elements/Content';

interface FrontMatter {
    path: string,
    title: string,
    baseline: string
}
interface MarkdownRemark {
    markdownRemark: Page
}
interface PageType {
    html: string,
    frontmatter: FrontMatter
}
interface Page {
    page: PageType,
    html: string,
    fields: NavigationFields,
}
interface Props {
    data: MarkdownRemark
}

const Template: StatelessComponent<Props> = ({ data: { markdownRemark: { html, fields} }}) => (    
    <Layout>
      <SEO title={fields.title} />
        <Content>
            <div dangerouslySetInnerHTML={{ __html: html }} />
        </Content>
    </Layout>
);

export default Template;

export const pageQuery = graphql`
  query DocsBySlug($slug: String!) {
    markdownRemark(fields: { slug: { eq: $slug }}) {
      html
      fields {
          title
          slug
      }
    }
  }
`
;