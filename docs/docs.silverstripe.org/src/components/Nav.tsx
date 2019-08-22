import React, { StatelessComponent, ReactElement, useState } from 'react';

import { GenericHierarchyNode } from '../types';
import { Menu, MenuLabel, MenuList } from 'bloomer';
import { Link } from 'gatsby';
import ToggleableMenuItem from './ToggleableMenuItem';
import styled from 'styled-components';
import useCurrentNode from '../hooks/useCurrentNode';
import useNodeHierarchy from '../hooks/useNodeHierarchy';
import sortFiles from '../utils/sortFiles';
import { animated, useSpring } from 'react-spring';
import Chevron from './Chevron';

const StickyNav = styled(Menu)`
  position: sticky;
  top: 8rem;
  height: calc(100vh - 14rem);
  overflow: auto;
`;

const MenuButton = styled.div`
    position:fixed;
    bottom: 44px;
    right: 20px;
    height: 4rem;
    width: 4rem;
    border-radius: 100px;
    background: blue;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
    z-index:2000;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
`;

const MobileNav = styled(animated.aside)`
    width: 100vw;
    height: 100vh;
    position: fixed;
    z-index: 50;
    background: #fff;
    right: 0;
    left: 0;
    top: 0;
    z-index: 1000;
    padding: 4rem;
    overflow: auto;
`


const Nav: StatelessComponent<{}> = () => {
    const nav = useNodeHierarchy();
    const currentNode = useCurrentNode();
    const [showNav, setShowNav] = useState(false);
    const mobileMenuStyles = useSpring({
        from: { opacity: 0, transform: `translate3d(0, -100%, 0)`},
        to: { opacity: showNav ? 1 : 0, transform: `translate3d(0, ${showNav ? 0 : '-100%'}, 0)`}
    });
    const innerMapFn = (item: GenericHierarchyNode): ReactElement => {
        const { slug } = item.fields;
        const { children } = item;
        const isInHierarchy = currentNode ? currentNode.fields.breadcrumbs.includes(slug.slice(0, -1)) : false;
        isInHierarchy && console.log(`${item.fields.title} is in the hierarchy`)
        return (
            <ToggleableMenuItem
                key={slug}
                item={item}
                active={!!isInHierarchy}
                mapFn={innerMapFn}
            >
                {children}
            </ToggleableMenuItem>
        );
    };
    
    const outerMapFn = (item: GenericHierarchyNode): ReactElement[] => {
        const { slug, title } = item.fields;
        const childItems = item.children.sort(sortFiles);
        const items = [];
        
        if (childItems.length) {
            items.push(
                <MenuLabel key={`${slug}-label`}>{title}</MenuLabel>
            );
            return items.concat(
                <MenuList key={`${slug}-list`}>
                    {childItems.map(innerMapFn)}
                </MenuList>
            );
        }
        items.push(
            <MenuList className="outer-mapfn" key={slug}>
            <li><Link activeClassName={`is-active`} to={slug}>{title}</Link></li>
            </MenuList>
        )
        
        return items;
    };
    
    let navChildren: ReactElement[] = [];
    const top = nav.find(n => n.fields.slug === '/');
    if (top) {
        navChildren = top.children.sort(sortFiles).map(outerMapFn);
    }

    return (
        <>
            <div className="is-hidden-tablet">
                <MenuButton onClick={() => setShowNav(!showNav)}>
                    <Chevron dir='up' />
                    <Chevron dir='down' />
                </MenuButton>
                <MobileNav style={mobileMenuStyles} className="menu">
                    {navChildren}                    
                </MobileNav>
            </div>
            <StickyNav className="is-hidden-mobile">
                {navChildren}
            </StickyNav>
        </>
    )
}

export default Nav;