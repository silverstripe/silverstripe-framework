import React, { StatelessComponent, ReactElement } from 'react';

import { NavigationItem, NavigationNode } from '../types';
import { Menu, MenuLabel, MenuList } from 'bloomer';
import { Link } from 'gatsby';
import ToggleableMenuItem from './ToggleableMenuItem';
import styled from 'styled-components';
import useCurrentNode from '../hooks/useCurrentNode';
import useNodeHierarchy from '../hooks/useNodeHierarchy';
import sortFiles from '../utils/sortFiles';

const StickyNav = styled(Menu)`
  position: sticky;
  top: 8rem;
  height: calc(100vh - 14rem);
  overflow: auto;
`;



const Nav: StatelessComponent<{}> = () => {
    const nav = useNodeHierarchy();
    const currentNode = useCurrentNode();
    const innerMapFn = (item: NavigationNode): ReactElement => {
        const { slug, children } = item.fields;
        const isInHierarchy = currentNode && currentNode.fields.breadcrumbs.includes(slug.slice(0, -1));
        return (
            <MenuList key={slug}>
                <ToggleableMenuItem
                    item={item}
                    active={!!isInHierarchy}
                    mapFn={innerMapFn}
                >
                    {children}
                </ToggleableMenuItem>
            </MenuList>
        );
    };
    
    const outerMapFn = (item: NavigationItem): ReactElement[] => {
        const { slug, title } = item.fields;
        const childItems = item.children.filter(n => n.fields.title !== 'index').sort(sortFiles);
        const items = [];
        
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
        
        return items;
    };
    
    return (
        <StickyNav>
            {nav.filter(n => n.fields.title !== 'index').sort(sortFiles).map(outerMapFn)}
        </StickyNav>

    )
}

export default Nav;