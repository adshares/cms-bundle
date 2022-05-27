<?php

namespace Adshares\CmsBundle\Twig;

use Twig\Error\SyntaxError;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class ContentTagTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $name = $stream->expect(Token::NAME_TYPE)->getValue();

        $vars = new ArrayExpression([], $lineno);
        if (!$stream->test(Token::BLOCK_END_TYPE)) {
            if ($stream->test('with')) {
                // {% content name with vars %}
                $stream->next();
                $vars = $this->parser->getExpressionParser()->parseExpression();
            } elseif (!$stream->test(Token::BLOCK_END_TYPE)) {
                throw new SyntaxError(
                    'Unexpected token. Twig was looking for the "with" keyword.',
                    $stream->getCurrent()->getLine(),
                    $stream->getSourceContext()
                );
            }
        }

        // {% content name %}default content{% endcontent %}
        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideContentEnd'], true);

        if (!$body instanceof TextNode && !$body instanceof AbstractExpression) {
            throw new SyntaxError(
                'A message inside a content tag must be a simple text.',
                $body->getTemplateLine(),
                $stream->getSourceContext()
            );
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new ContentTagNode($name, $body, $vars, $lineno, $this->getTag());
    }

    public function decideContentEnd(Token $token): bool
    {
        return $token->test('endcontent');
    }

    public function getTag(): string
    {
        return 'content';
    }
}
