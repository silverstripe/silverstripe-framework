import React, { StatelessComponent, ReactElement } from 'react';
import { Card, CardHeaderTitle, CardHeader, CardFooter, CardFooterItem } from 'bloomer';
import { CardContent } from 'bloomer/lib/components/Card/CardContent';
import { GenericHierarchyNode, ChildrenOfProps } from '../types';
import { Link } from 'gatsby';
import styled from 'styled-components';

const ChildrenBlock = styled.div`
    display: grid;
    grid-gap: 4rem;
    align-items: stretch;
    grid-template-columns: repeat( auto-fit, minmax(250px, 1fr) );
`;
const StretchContent = styled(CardContent)`
    flex-grow: 1;
`;
const createChildren = (children: GenericHierarchyNode[]): ReactElement[] => {
    return children.map(child => {
        const content = child.indexFile && child.indexFile.frontmatter.summary;
        return (
            <Card key={child.fields.slug}>
                <CardHeader>
                    <CardHeaderTitle>
                        {child.indexFile ? child.indexFile.fields.title : child.fields.slug}
                    </CardHeaderTitle>
                </CardHeader>
                {content && 
                <StretchContent>
                    {content}
                </StretchContent>
                }
                <CardFooter>
                    <CardFooterItem>   
                        <Link to={child.fields.slug}>View</Link>
                    </CardFooterItem>
                </CardFooter>
            </Card>
        );
    })

}
const ChildrenOf: StatelessComponent<ChildrenOfProps> = ({ folderName, exclude, currentNode }) => {
    if (!currentNode) {
        return null;
    }
    let children: ReactElement[] = [];
    if (!folderName && !exclude) {
        children = createChildren((currentNode.siblings || []).filter(c => c.__typename === 'MarkdownRemark'));
    } else if (folderName) {
        console.log('current node', currentNode);
        const targetFolder = currentNode.children.find(
            child => child.fields.fileTitle === folderName && child.__typename === 'Directory'
        );
        console.log('the target folder is', targetFolder);
        children = targetFolder ? createChildren(targetFolder.children) : [];
    } else if (exclude) {
        children = createChildren(
            currentNode.children.filter(child => child.fields.fileTitle === exclude)
        );
    }

    return <ChildrenBlock>{children}</ChildrenBlock>

};

export default ChildrenOf;