import React, { StatelessComponent, ReactElement } from 'react';
import { Card, CardHeaderTitle, CardHeader, CardFooter, CardFooterItem } from 'bloomer';
import { CardContent } from 'bloomer/lib/components/Card/CardContent';
import { NavigationItem, NavigationNode } from '../types';
import { Link } from 'gatsby';

const createChildren = (children: NavigationNode[]): ReactElement[] => {
    return children.map(child => {
        console.log(child);
        return (
            <Card>
                <CardHeader>
                    <CardHeaderTitle>
                        {child.fields.title}
                    </CardHeaderTitle>
                </CardHeader>
                <CardContent>
                    {child.frontmatter.summary}
                </CardContent>
                <CardFooter>
                    <CardFooterItem>
                        <Link to={child.fields.slug}>View</Link>
                    </CardFooterItem>
                </CardFooter>
            </Card>
        );
    })

}
const ChildrenOf: StatelessComponent<any> = ({ folderName, exclude, currentNode }) => {
    if (!currentNode) {
        return null;
    }
    let children: ReactElement[] = [];
    if (!folderName && !exclude) {
        children = createChildren((currentNode.parent.children || []).filter(c => c.__typename === 'MarkdownRemark'));
    } else if (folderName) {
        console.log(currentNode);
        const targetFolder = currentNode.parent.children.find(
            child => child.fields.fileTitle === folderName
        );
        children = targetFolder ? createChildren(targetFolder.children) : [];
    } else if (exclude) {
        children = createChildren(
            currentNode.parent.children.filter(child => child.fields.fileTitle === exclude)
        );
    }

    return <div>{children}</div>

};

export default ChildrenOf;