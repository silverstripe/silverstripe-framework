import React from 'react';
import useNodeHierarchy from '../hooks/useNodeHierarchy';
import sortFiles from '../utils/sortFiles';
import { GenericHierarchyNode } from '../types';
import { Link } from 'gatsby';
import useCurrentNode from '../hooks/useCurrentNode';

const Nav = () => {
    const hierarchy = useNodeHierarchy();
    const currentNode = useCurrentNode();
    const top = hierarchy.find(n => n.fields.slug === '/');
    return (
        <nav id="docs-nav" className="docs-nav navbar">
        <ul className="section-items list-unstyled nav flex-column pb-3">
            {top.children.sort(sortFiles).map((node: GenericHierarchyNode) => {
                const { slug, title } = node.fields;
                const childItems = node.children.sort(sortFiles);
                return (
                    <React.Fragment key={slug}>
                    <li className="nav-item section-title">
                        <Link activeClassName='active' className="nav-link" to={slug}>{title}</Link>
                    </li>
                    {childItems.map((node: GenericHierarchyNode) => {
                        const { slug, title } = node.fields;
                        const shouldShowChildren = currentNode.fields.slug.startsWith(slug);
                        return (
                            <>
                            <li key={slug} className="nav-item">
                                <Link activeClassName='active' className="nav-link" to={slug}>{title}</Link>
                            </li>
                            {shouldShowChildren && node.children.map((child: GenericHierarchyNode) => {
                                const { title, slug } = child.fields;
                                return (
                                    <li key={slug} className="nav-item third-level">
                                        <Link activeClassName='active' className="nav-link" to={slug}>{title}</Link>
                                    </li>
                                );
                            })}
                            </>
                        );
                    })}
                    </React.Fragment>
                );
                
            })}
        </ul>
      </nav>
    );
};

export default Nav;