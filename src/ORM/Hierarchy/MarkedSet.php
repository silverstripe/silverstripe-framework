<?php

namespace SilverStripe\ORM\Hierarchy;

use InvalidArgumentException;
use LogicException;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ArrayData;

/**
 * Contains a set of hierarchical objects generated from a marking compilation run.
 *
 * A set of nodes can be "marked" for later export, in order to prevent having to
 * export the entire contents of a potentially huge tree.
 */
class MarkedSet
{
    use Injectable;

    /**
     * Marked nodes for a given subtree. The first item in this list
     * is the root object of the subtree.
     *
     * A marked item is an item in a tree which will be included in
     * a resulting tree.
     *
     * @var array Map of [itemID => itemInstance]
     */
    protected $markedNodes;

    /**
     * Optional filter callback for filtering nodes to mark
     *
     * Array with keys:
     *  - parameter
     *  - value
     *  - func
     *
     * @var array
     * @temp made public
     */
    public $markingFilter;

    /**
     * @var DataObject
     */
    protected $rootNode = null;

    /**
     * Method to use for getting children. Defaults to 'AllChildrenIncludingDeleted'
     *
     * @var string
     */
    protected $childrenMethod = null;

    /**
     * Method to use for counting children. Defaults to `numChildren`
     *
     * @var string
     */
    protected $numChildrenMethod = null;

    /**
     * Minimum number of nodes to iterate over before stopping recursion
     *
     * @var int
     */
    protected $nodeCountThreshold = null;

    /**
     * Max number of nodes to return from a single children collection
     *
     * @var int
     */
    protected $maxChildNodes;

    /**
     * Enable limiting
     *
     * @var bool
     */
    protected $enableLimiting = true;

    /**
     * Create an empty set with the given class
     *
     * @param DataObject $rootNode Root node for this set. To collect the entire tree,
     * pass in a singleton object.
     * @param string $childrenMethod Override children method
     * @param string $numChildrenMethod Override children counting method
     * @param int $nodeCountThreshold Minimum threshold for number nodes to mark
     * @param int $maxChildNodes Maximum threshold for number of child nodes to include
     */
    public function __construct(
        DataObject $rootNode,
        $childrenMethod = null,
        $numChildrenMethod = null,
        $nodeCountThreshold = null,
        $maxChildNodes = null
    ) {
        if (! $rootNode::has_extension(Hierarchy::class)) {
            throw new InvalidArgumentException(
                get_class($rootNode) . " does not have the Hierarchy extension"
            );
        }
        $this->rootNode = $rootNode;
        if ($childrenMethod) {
            $this->setChildrenMethod($childrenMethod);
        }
        if ($numChildrenMethod) {
            $this->setNumChildrenMethod($numChildrenMethod);
        }
        if ($nodeCountThreshold) {
            $this->setNodeCountThreshold($nodeCountThreshold);
        }
        if ($maxChildNodes) {
            $this->setMaxChildNodes($maxChildNodes);
        }
    }

    /**
     * Get total number of nodes to get. This acts as a soft lower-bounds for
     * number of nodes to search until found.
     * Defaults to value of node_threshold_total of hierarchy class.
     *
     * @return int
     */
    public function getNodeCountThreshold()
    {
        return $this->nodeCountThreshold
            ?: $this->rootNode->config()->get('node_threshold_total');
    }

    /**
     * Max number of nodes that can be physically rendered at any level.
     * Acts as a hard upper bound, after which nodes will be trimmed for
     * performance reasons.
     *
     * @return int
     */
    public function getMaxChildNodes()
    {
        return $this->maxChildNodes
            ?: $this->rootNode->config()->get('node_threshold_leaf');
    }

    /**
     * Set hard limit of number of nodes to get for this level
     *
     * @param int $count
     * @return $this
     */
    public function setMaxChildNodes($count)
    {
        $this->maxChildNodes = $count;
        return $this;
    }

    /**
     * Set max node count
     *
     * @param int $total
     * @return $this
     */
    public function setNodeCountThreshold($total)
    {
        $this->nodeCountThreshold = $total;
        return $this;
    }

    /**
     * Get method to use for getting children
     *
     * @return string
     */
    public function getChildrenMethod()
    {
        return $this->childrenMethod ?: 'AllChildrenIncludingDeleted';
    }

    /**
     * Get children from this node
     *
     * @param DataObject $node
     * @return SS_List<DataObject>
     */
    protected function getChildren(DataObject $node)
    {
        $method = $this->getChildrenMethod();
        return $node->$method() ?: ArrayList::create();
    }

    /**
     * Set method to use for getting children
     *
     * @param string $method
     * @throws InvalidArgumentException
     * @return $this
     */
    public function setChildrenMethod($method)
    {
        // Check method is valid
        if (!$this->rootNode->hasMethod($method)) {
            throw new InvalidArgumentException(sprintf(
                "Can't find the method '%s' on class '%s' for getting tree children",
                $method,
                get_class($this->rootNode)
            ));
        }
        $this->childrenMethod = $method;
        return $this;
    }

    /**
     * Get method name for num children
     *
     * @return string
     */
    public function getNumChildrenMethod()
    {
        return $this->numChildrenMethod ?: 'numChildren';
    }

    /**
     * Count children
     *
     * @param DataObject $node
     * @return int
     */
    protected function getNumChildren(DataObject $node)
    {
        $method = $this->getNumChildrenMethod();
        return (int)$node->$method();
    }

    /**
     * Set method name to get num children
     *
     * @param string $method
     * @return $this
     */
    public function setNumChildrenMethod($method)
    {
        // Check method is valid
        if (!$this->rootNode->hasMethod($method)) {
            throw new InvalidArgumentException(sprintf(
                "Can't find the method '%s' on class '%s' for counting tree children",
                $method,
                get_class($this->rootNode)
            ));
        }
        $this->numChildrenMethod = $method;
        return $this;
    }

    /**
     * Returns the children of this DataObject as an XHTML UL. This will be called recursively on each child, so if they
     * have children they will be displayed as a UL inside a LI.
     *
     * @param string $template Template for items in the list
     * @param array|callable $context Additional arguments to add to template when rendering
     * due to excessive line length. If callable, this will be executed with the current node dataobject
     * @return string
     */
    public function renderChildren(
        $template = null,
        $context = []
    ) {
        // Default to HTML template
        if (!$template) {
            $template = [
                'type' => 'Includes',
                MarkedSet::class . '_HTML'
            ];
        }
        $tree = $this->getSubtree($this->rootNode, 0);
        $node = $this->renderSubtree($tree, $template, $context);
        return (string)$node->getField('SubTree');
    }

    /**
     * Get child data formatted as JSON
     *
     * @param callable $serialiseEval A callback that takes a DataObject as a single parameter,
     * and should return an array containing a simple array representation. This result will
     * replace the 'node' property at each point in the tree.
     * @return array
     */
    public function getChildrenAsArray($serialiseEval = null)
    {
        if (!$serialiseEval) {
            $serialiseEval = function ($data) {
                /** @var DataObject $node */
                $node = $data['node'];
                return [
                    'id' => $node->ID,
                    'title' => $node->getTitle()
                ];
            };
        }

        $tree = $this->getSubtree($this->rootNode, 0);

        return $this->getSubtreeAsArray($tree, $serialiseEval);
    }

    /**
     * Render a node in the tree with the given template
     *
     * @param array $data array data for current node
     * @param string|array $template Template to use
     * @param array|callable $context Additional arguments to add to template when rendering
     * due to excessive line length. If callable, this will be executed with the current node dataobject
     * @return ArrayData Viewable object representing the root node. use getField('SubTree') to get HTML
     */
    protected function renderSubtree($data, $template, $context = [])
    {
        // Render children
        $childNodes = new ArrayList();
        foreach ($data['children'] as $child) {
            $childData = $this->renderSubtree($child, $template, $context);
            $childNodes->push($childData);
        }

        // Build parent node
        $parentNode = new ArrayData($data);
        $parentNode->setField('children', $childNodes); // Replace raw array with template-friendly list
        $parentNode->setField('markingClasses', $this->markingClasses($data['node']));

        // Evaluate custom context
        if (!is_string($context) && is_callable($context)) {
            $context = call_user_func($context, $data['node']);
        }
        if ($context) {
            foreach ($context as $key => $value) {
                $parentNode->setField($key, $value);
            }
        }

        // Render
        $subtree = $parentNode->renderWith($template);
        $parentNode->setField('SubTree', $subtree);
        return $parentNode;
    }

    /**
     * Return sub-tree as json array
     *
     * @param array $data
     * @param callable $serialiseEval A callback that takes a DataObject as a single parameter,
     * and should return an array containing a simple array representation. This result will
     * replace the 'node' property at each point in the tree.
     * @return mixed|string
     */
    protected function getSubtreeAsArray($data, $serialiseEval)
    {
        $output = $data;

        // Serialise node
        $serialised = $serialiseEval($data['node']);

        // Force serialisation of DBField instances
        if (is_array($serialised)) {
            foreach ($serialised as $key => $value) {
                if ($value instanceof DBField) {
                    $serialised[$key] = $value->getSchemaValue();
                }
            }

            // Merge with top level array
            unset($output['node']);
            $output = array_merge($output, $serialised);
        } else {
            if ($serialised instanceof DBField) {
                $serialised = $serialised->getSchemaValue();
            }

            // Replace node with serialised value
            $output['node'] = $serialised;
        }

        // Replace children with serialised elements
        $output['children'] = [];
        foreach ($data['children'] as $child) {
            $output['children'][] = $this->getSubtreeAsArray($child, $serialiseEval);
        }
        return $output;
    }

    /**
     * Get tree data for node
     *
     * @param DataObject $node
     * @param int $depth
     * @return array|string
     */
    protected function getSubtree($node, $depth = 0)
    {
        // Check if this node is limited due to child count
        $numChildren = $this->getNumChildren($node);
        $limited = $this->isNodeLimited($node, $numChildren);

        // Build root rode
        $expanded = $this->isExpanded($node);
        $opened = $this->isTreeOpened($node);
        $count = ($limited && $numChildren > $this->getMaxChildNodes()) ? 0 : $numChildren;
        $output = [
            'node' => $node,
            'marked' => $this->isMarked($node),
            'expanded' => $expanded,
            'opened' => $opened,
            'depth' => $depth,
            'count' => $count, // Count of DB children
            'limited' => $limited, // Flag whether 'items' has been limited
            'children' => [], // Children to return in this request
        ];

        // Don't iterate children if past limit
        // or not expanded (requires subsequent request to get)
        if ($limited || !$expanded) {
            return $output;
        }

        // Get children
        $children = $this->getChildren($node);
        foreach ($children as $child) {
            // Recurse
            if ($this->isMarked($child)) {
                $output['children'][] = $this->getSubtree($child, $depth + 1);
            }
        }
        return $output;
    }

    /**
     * Mark a segment of the tree, by calling mark().
     *
     * The method performs a breadth-first traversal until the number of nodes is more than minCount. This is used to
     * get a limited number of tree nodes to show in the CMS initially.
     *
     * This method returns the number of nodes marked.  After this method is called other methods can check
     * {@link isExpanded()} and {@link isMarked()} on individual nodes.
     *
     * @return $this
     */
    public function markPartialTree()
    {
        $nodeCountThreshold = $this->getNodeCountThreshold();

        // Add root node, not-expanded by default
        $rootNode = $this->rootNode;
        $this->clearMarks();
        $this->markUnexpanded($rootNode);

        // Build markedNodes for this subtree until we reach the threshold
        // foreach can't handle an ever-growing $nodes list
        foreach (ArrayLib::iterateVolatile($this->markedNodes) as $node) {
            $children = $this->markChildren($node);
            if ($nodeCountThreshold && sizeof($this->markedNodes ?? []) > $nodeCountThreshold) {
                // Undo marking children as opened since they're lazy loaded
                foreach ($children as $child) {
                    $this->markClosed($child);
                }
                break;
            }
        }
        return $this;
    }

    /**
     * Filter the marking to only those object with $node->$parameterName == $parameterValue
     *
     * @param string $parameterName  The parameter on each node to check when marking.
     * @param mixed  $parameterValue The value the parameter must be to be marked.
     * @return $this
     */
    public function setMarkingFilter($parameterName, $parameterValue)
    {
        $this->markingFilter = [
            "parameter" => $parameterName,
            "value" => $parameterValue
        ];
        return $this;
    }

    /**
     * Filter the marking to only those where the function returns true. The node in question will be passed to the
     * function.
     *
     * @param callable $callback Callback to filter
     * @return $this
     */
    public function setMarkingFilterFunction($callback)
    {
        $this->markingFilter = [
            "func" => $callback,
        ];
        return $this;
    }

    /**
     * Returns true if the marking filter matches on the given node.
     *
     * @param DataObject $node Node to check
     * @return bool
     */
    protected function markingFilterMatches(DataObject $node)
    {
        if (!$this->markingFilter) {
            return true;
        }

        // Func callback filter
        if (isset($this->markingFilter['func'])) {
            $func = $this->markingFilter['func'];
            return call_user_func($func, $node);
        }

        // Check object property filter
        if (isset($this->markingFilter['parameter'])) {
            $parameterName = $this->markingFilter['parameter'];
            $value = $this->markingFilter['value'];

            if (is_array($value)) {
                return in_array($node->$parameterName, $value ?? []);
            } else {
                return $node->$parameterName == $value;
            }
        }

        throw new LogicException("Invalid marking filter");
    }

    /**
     * Mark all children of the given node that match the marking filter.
     *
     * @param DataObject $node Parent node
     * @return array<DataObject> List of children marked by this operation
     */
    protected function markChildren(DataObject $node)
    {
        $this->markExpanded($node);

        // If too many children leave closed
        if ($this->isNodeLimited($node)) {
            // Limited nodes are always expanded
            $this->markClosed($node);
            return [];
        }

        // Iterate children if not limited
        $children = $this->getChildren($node);
        if (!$children) {
            return [];
        }

        // Mark all children
        $markedChildren = [];
        foreach ($children as $child) {
            $markingMatches = $this->markingFilterMatches($child);
            if (!$markingMatches) {
                continue;
            }
            // Mark a child node as unexpanded if it has children and has not already been expanded
            if ($this->getNumChildren($child) > 0 && !$this->isExpanded($child)) {
                $this->markUnexpanded($child);
            } else {
                $this->markExpanded($child);
            }

            $markedChildren[] = $child;
        }
        return $markedChildren;
    }

    /**
     * Return CSS classes of 'unexpanded', 'closed', both, or neither, as well as a 'jstree-*' state depending on the
     * marking of this DataObject.
     *
     * @param DataObject $node
     * @return string
     */
    protected function markingClasses($node)
    {
        $classes = [];
        if (!$this->isExpanded($node)) {
            $classes[] = 'unexpanded';
        }

        // Set jstree open state, or mark it as a leaf (closed) if there are no children
        if (!$this->getNumChildren($node)) {
            // No children
            $classes[] = "jstree-leaf closed";
        } elseif ($this->isTreeOpened($node)) {
            // Open with children
            $classes[] = "jstree-open";
        } else {
            // Closed with children
            $classes[] = "jstree-closed closed";
        }
        return implode(' ', $classes);
    }

    /**
     * Mark the children of the DataObject with the given ID.
     *
     * @param int  $id   ID of parent node
     * @param bool $open If this is true, mark the parent node as opened
     * @return bool
     */
    public function markById($id, $open = false)
    {
        if (isset($this->markedNodes[$id])) {
            $this->markChildren($this->markedNodes[$id]);
            if ($open) {
                $this->markOpened($this->markedNodes[$id]);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Expose the given object in the tree, by marking this page and all it ancestors.
     *
     * @param DataObject|Hierarchy $childObj
     * @return $this
     */
    public function markToExpose(DataObject $childObj)
    {
        if (!$childObj) {
            return $this;
        }
        $stack = $childObj->getAncestors(true)->reverse();
        foreach ($stack as $stackItem) {
            $this->markById($stackItem->ID, true);
        }
        return $this;
    }

    /**
     * Return the IDs of all the marked nodes.
     *
     * @refactor called from CMSMain
     * @return array
     */
    public function markedNodeIDs()
    {
        return array_keys($this->markedNodes ?? []);
    }

    /**
     * Cache of DataObjects' expanded statuses: [ClassName][ID] = bool
     * @var array
     */
    protected $expanded = [];

    /**
     * Cache of DataObjects' opened statuses: [ID] = bool
     * @var array
     */
    protected $treeOpened = [];

    /**
     * Reset marked nodes
     */
    public function clearMarks()
    {
        $this->markedNodes = [];
        $this->expanded = [];
        $this->treeOpened = [];
    }

    /**
     * Mark this DataObject as expanded.
     *
     * @param DataObject $node
     * @return $this
     */
    public function markExpanded(DataObject $node)
    {
        $id = $node->ID ?: 0;
        $this->markedNodes[$id] = $node;
        $this->expanded[$id] = true;
        return $this;
    }

    /**
     * Mark this DataObject as unexpanded.
     *
     * @param DataObject $node
     * @return $this
     */
    public function markUnexpanded(DataObject $node)
    {
        $id = $node->ID ?: 0;
        $this->markedNodes[$id] = $node;
        unset($this->expanded[$id]);
        return $this;
    }

    /**
     * Mark this DataObject's tree as opened.
     *
     * @param DataObject $node
     * @return $this
     */
    public function markOpened(DataObject $node)
    {
        $id = $node->ID ?: 0;
        $this->markedNodes[$id] = $node;
        $this->treeOpened[$id] = true;
        return $this;
    }

    /**
     * Mark this DataObject's tree as closed.
     *
     * @param DataObject $node
     * @return $this
     */
    public function markClosed(DataObject $node)
    {
        $id = $node->ID ?: 0;
        $this->markedNodes[$id] = $node;
        unset($this->treeOpened[$id]);
        return $this;
    }

    /**
     * Check if this DataObject is marked.
     *
     * @param DataObject $node
     * @return bool
     */
    public function isMarked(DataObject $node)
    {
        $id = $node->ID ?: 0;
        return !empty($this->markedNodes[$id]);
    }

    /**
     * Check if this DataObject is expanded.
     * An expanded object has had it's children iterated through.
     *
     * @param DataObject $node
     * @return bool
     */
    public function isExpanded(DataObject $node)
    {
        $id = $node->ID ?: 0;
        return !empty($this->expanded[$id]);
    }

    /**
     * Check if this DataObject's tree is opened.
     * This is an expanded node which also should have children visually shown.
     *
     * @param DataObject $node
     * @return bool
     */
    public function isTreeOpened(DataObject $node)
    {
        $id = $node->ID ?: 0;
        return !empty($this->treeOpened[$id]);
    }

    /**
     * Check if this node has too many children
     *
     * @param DataObject|Hierarchy $node
     * @param int $count Children count (if already calculated)
     * @return bool
     */
    protected function isNodeLimited(DataObject $node, $count = null)
    {
        // Singleton root node isn't limited
        if (!$node->ID) {
            return false;
        }

        // Check if limiting is enabled first
        if (!$this->getLimitingEnabled()) {
            return false;
        }

        // Count children for this node and compare to max
        if (!isset($count)) {
            $count = $this->getNumChildren($node);
        }
        return $count > $this->getMaxChildNodes();
    }

    /**
     * Toggle limiting on or off
     *
     * @param bool $enabled
     * @return $this
     */
    public function setLimitingEnabled($enabled)
    {
        $this->enableLimiting = $enabled;
        return $this;
    }

    /**
     * Check if limiting is enabled
     *
     * @return bool
     */
    public function getLimitingEnabled()
    {
        return $this->enableLimiting;
    }
}
