<?php

namespace SilverStripe\Core\Manifest;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class ClassManifestVisitor extends NodeVisitorAbstract
{

    private $classes = [];

    private $traits = [];

    private $interfaces = [];

    public function resetState()
    {
        $this->classes = [];
        $this->traits = [];
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
                $extends = array((string)$node->extends);
            }

            if ($node->implements) {
                foreach ($node->implements as $interface) {
                    $interfaces[] = (string)$interface;
                }
            }

            $this->classes[(string)$node->namespacedName] = [
                'extends' => $extends,
                'interfaces' => $interfaces,
            ];
        } elseif ($node instanceof Node\Stmt\Trait_) {
            $this->traits[(string)$node->namespacedName] = array();
        } elseif ($node instanceof Node\Stmt\Interface_) {
            $extends = array();
            foreach ($node->extends as $ancestor) {
                $extends[] = (string)$ancestor;
            }
            $this->interfaces[(string)$node->namespacedName] = [
                'extends' => $extends,
            ];
        }
        if (!$node instanceof Node\Stmt\Namespace_) {
            //break out of traversal as we only need highlevel information here!
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }
    }

    public function getClasses()
    {
        return $this->classes;
    }

    public function getTraits()
    {
        return $this->traits;
    }

    public function getInterfaces()
    {
        return $this->interfaces;
    }
}
