import React, { StatelessComponent, ReactElement } from 'react';
import { StaticQuery, graphql } from 'gatsby';
import buildNavigationNode from '../utils/buildNavigationNode';
import { NavigationItem, AllFilesQuery, AllFilesData } from '../types';
import { Menu, MenuLabel, MenuList } from 'bloomer';
import { Link } from 'gatsby';

const query = graphql`
query Navigation {
  allMarkdownRemark(limit: 1000, sort: {fields: fields___title, order: ASC}) {
    edges {
      node {
        fields {
          slug
          title
          fileTitle
          filePath
          dir
          path
        }
      }
    }
  }
}
`


const innerMapFn = (item: NavigationItem): ReactElement => {
  const { slug, title } = item.node.fields;
  return (
    <MenuList key={slug}>
      <li>
        <Link activeClassName={`is-active`} to={slug}>{title}</Link>
        {!!item.children.length &&
          <MenuList>
            {item.children.map(innerMapFn)}
          </MenuList>
        }
      </li>
    </MenuList>
  )
};

const outerMapFn = (item: NavigationItem): ReactElement[] => {
  const { slug, title, dir } = item.node.fields;
  const childItems = item.children;
  const items = [];
  if (dir === '') {
      if (childItems.length) {
        items.push(
            <MenuLabel key={slug}>{title}</MenuLabel>
        );
        return items.concat(
            childItems.map(innerMapFn)
        );
      }
      items.push(
        <MenuList key={slug}>
          <li><Link activeClassName={`is-active`} to={slug}>{title}</Link></li>
        </MenuList>
      )
  }
  return items;
};

const render = (data: AllFilesData) => {
    const nodes = data.allMarkdownRemark.edges.map(edge => edge.node);
    const nav = buildNavigationNode('', nodes);

    return (
        <Menu>
            <>
                {nav.map(outerMapFn)}
            </>
        </Menu>

    )
}
const Nav: StatelessComponent<{}> = () =>(
    <StaticQuery query={query} render={render} />
);

export default Nav;