import { GenericHierarchyNode } from '../types';
import useNodeHierarchy from './useNodeHierarchy';

let path: string | null = null;
let currentNode: GenericHierarchyNode | null = null;

const useCurrentNode = (): GenericHierarchyNode | null => {
    const browserPath = typeof window !== 'undefined' ? window.location.pathname : '/';
    if (!path || path !== browserPath) {
        path = browserPath;
        const nodes = useNodeHierarchy();
        const finder = (node: GenericHierarchyNode): boolean => {
            const { slug } = node.fields;
            const { children } = node;
            if (slug === path) {
                currentNode = { ...node };
                return true;
            }
            return children ? children.some(finder) : false;
        }
        nodes.some(finder);
    }
    return currentNode || null;
};

export default useCurrentNode;