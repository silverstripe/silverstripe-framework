import React, { StatelessComponent, ReactElement } from 'react';
import { GenericHierarchyNode, ChildrenOfProps } from '../types';
import { Link } from 'gatsby';

const createCards = (children: GenericHierarchyNode[]): ReactElement[] => {
    return children.map(child => {
        const frontmatter = child.indexFile ? child.indexFile.frontmatter : child.frontmatter;
        const content = frontmatter && frontmatter.summary;
        const icon = frontmatter && frontmatter.icon;
        return (
            <div className="col-12 col-lg-6 py-3" key={child.fields.slug}>
                <div className="card shadow-sm">
                    <div className="card-body">
                        <h5 className="card-title mb-3">
                            <span className="theme-icon-holder card-icon-holder mr-2">                                
                                <i className={`fas fa-${icon || 'file-alt'}`}></i>                                
                            </span>
                            <span className="card-title-text">{child.fields.title}</span>
                        </h5>
                        <div className="card-text">
                            {content || ''}
                        </div>
                        <Link className="card-link-mask" to={child.fields.slug}></Link>
                    </div>
                </div>
            </div>
        );
    })
};

const createList= (children: GenericHierarchyNode[]): ReactElement[] => {
    return children.map(child => {
        const frontmatter = child.indexFile ? child.indexFile.frontmatter : child.frontmatter;
        const content = frontmatter && frontmatter.summary;
        return (
            <React.Fragment key={child.fields.slug}>
                <dt><Link to={child.fields.slug}>{child.fields.title}</Link></dt>
                <dd>{content || ''}</dd>
            </React.Fragment>
        );
    });
};

const ChildrenOf: StatelessComponent<ChildrenOfProps> = ({ folderName, exclude, currentNode, asList }) => {
    if (!currentNode) {
        return null;
    }
    let children: ReactElement[] = [];
    if (!folderName && !exclude) {
        const sourceNodes = currentNode.indexFile ? currentNode.children : currentNode.siblings;
        children = asList ? createList(sourceNodes) : createCards(sourceNodes);
    } else if (folderName) {
        const targetFolder = currentNode.children.find(
            child => child.fields.fileTitle.toLowerCase() === folderName.toLowerCase() && child.__typename === 'Directory'
        );
        if (targetFolder) {
            children = asList ? createList(targetFolder.children) : createCards(targetFolder.children);
        } else {
            children = [];
        }
    } else if (exclude) {
        const exclusions = exclude.split(',').map(e => e.toLowerCase());
        const nodes = currentNode.children.filter(child => !exclusions.includes(child.fields.fileTitle.toLowerCase()));
        children = asList ? createList(nodes) : createCards(nodes);
    }

    return (
        <div className="docs-overview py-5">
            {asList &&
                <dl>{children}</dl>
            }
            {!asList &&
            <div className="row">
                {children}
            </div>
            }
        </div>
    )

};

export default ChildrenOf;