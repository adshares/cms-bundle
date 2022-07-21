<?php

namespace Adshares\CmsBundle\Twig;

use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;
use Twig\Node\TextNode;

class ContentTagNode extends Node
{
    public function __construct(
        string $name,
        Node $body,
        AbstractExpression $vars = null,
        int $lineno = 0,
        string $tag = null
    ) {
        $nodes = ['body' => $body];
        if (null !== $vars) {
            $nodes['vars'] = $vars;
        }
        parent::__construct($nodes, ['name' => $name], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        $defaults = new ArrayExpression([], -1);
        if ($this->hasNode('vars') && ($vars = $this->getNode('vars')) instanceof ArrayExpression) {
            $defaults = $this->getNode('vars');
            $vars = null;
        }
        $msg = $this->getNode('body');

        $compiler
            ->write('ob_start();')
            ->subcompile($msg)
            ->write("\$tmp = ob_get_clean();\n")
            ->write('echo $this->env->getExtension(\'Adshares\CmsBundle\Twig\CmsExtension\')->getContent(')
            ->repr($this->getAttribute('name'))
            ->raw(', $tmp, ');

        if (null !== $vars) {
            $compiler
                ->raw('array_merge(')
                ->subcompile($defaults)
                ->raw(', ')
                ->subcompile($this->getNode('vars'))
                ->raw(')');
        } else {
            $compiler->subcompile($defaults);
        }

        $compiler->raw(");\n");
    }
}
