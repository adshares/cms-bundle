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
        [$msg, $defaults] = $this->compileString($this->getNode('body'), $defaults, (bool)$vars);

        $compiler
            ->write('echo $this->env->getExtension(\'Adshares\CmsBundle\Twig\CmsExtension\')->getContent(')
            ->repr($this->getAttribute('name'))
            ->raw(', ')
            ->subcompile($msg);

        $compiler->raw(', ');
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

        $compiler
            ->raw(");\n");
    }

    private function compileString(Node $body, ArrayExpression $vars, bool $ignoreStrictCheck = false): array
    {
        if ($body instanceof ConstantExpression) {
            $msg = $body->getAttribute('value');
        } elseif ($body instanceof TextNode) {
            $msg = $body->getAttribute('data');
        } else {
            return [$body, $vars];
        }

        preg_match_all('/(?<!%)%([^%]+)%/', $msg, $matches);

        foreach ($matches[1] as $var) {
            $key = new ConstantExpression('%' . $var . '%', $body->getTemplateLine());
            if (!$vars->hasElement($key)) {
                $varExpr = new NameExpression($var, $body->getTemplateLine());
                $varExpr->setAttribute('ignore_strict_check', $ignoreStrictCheck);
                $vars->addElement($varExpr, $key);
            }
        }

        return [new ConstantExpression(str_replace('%%', '%', trim($msg)), $body->getTemplateLine()), $vars];
    }
}
