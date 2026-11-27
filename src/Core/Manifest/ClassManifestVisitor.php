<?php

namespace SilverStripe\Core\Manifest;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class ClassManifestVisitor extends NodeVisitorAbstract
{

    private $classes = [];

    private $traits = [];

    private $interfaces = [];

    private bool $includeEnums;

    private $enums = [];

    public function __construct()
    {
        $this->includeEnums = version_compare(phpversion(), '8.1.0', '>');
    }

    public function resetState()
    {
        $this->classes = [];
        $this->traits = [];
        $this->enums = [];
        $this->interfaces = [];
    }

    public function beforeTraverse(array $nodes)
    {
        $this->resetState();
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            $extends = [];
            $interfaces = [];

            if ($node->extends) {
                $extends = [(string)$node->extends];
            }

            $this->classes[(string)$node->namespacedName] = [
                'extends' => $extends,
                'interfaces' => $this->getClassNames($node->implements),
                'attributes' => $this->getAttributes($node),
            ];
        } elseif ($node instanceof Node\Stmt\Trait_) {
            $this->traits[(string)$node->namespacedName] = [
                'attributes' => $this->getAttributes($node),
            ];
        } elseif ($this->includeEnums && $node instanceof Node\Stmt\Enum_) {
            $this->enums[(string)$node->namespacedName] = [
                'attributes' => $this->getAttributes($node),
            ];
        } elseif ($node instanceof Node\Stmt\Interface_) {
            $this->interfaces[(string)$node->namespacedName] = [
                'extends' => $this->getClassNames($node->extends),
                'attributes' => $this->getAttributes($node),
            ];
        }
        if (!$node instanceof Node\Stmt\Namespace_) {
            //break out of traversal as we only need highlevel information here!
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }
    }

    private function getClassNames(array $list): array
    {
        return array_map(
            static fn ($classLike) => (string)$classLike,
            $list
        );
    }

    private function getAttributes(ClassLike $classLike): array
    {
        $attributes = [];

        if ($classLike->attrGroups) {
            foreach ($classLike->attrGroups as $group) {
                foreach ($group->attrs as $attr) {
                    $attributes[] = (string)$attr->name;
                }
            }
        }

        return $attributes;
    }

    public function getClasses()
    {
        return $this->classes;
    }

    public function getTraits()
    {
        return $this->traits;
    }

    public function getEnums()
    {
        return $this->enums;
    }

    public function getInterfaces()
    {
        return $this->interfaces;
    }
}
