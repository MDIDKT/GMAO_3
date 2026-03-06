<?php

declare(strict_types=1);

namespace App\Doctrine\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

use function in_array;
use function sprintf;
use function strtoupper;

final class TimestampDiffFunction extends FunctionNode
{
    private const SUPPORTED_UNITS = [
        'MICROSECOND',
        'SECOND',
        'MINUTE',
        'HOUR',
        'DAY',
        'WEEK',
        'MONTH',
        'QUARTER',
        'YEAR',
    ];

    private string $unit;

    /** @var Node|string */
    private Node|string $startDateExpression;

    /** @var Node|string */
    private Node|string $endDateExpression;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $parser->match(TokenType::T_IDENTIFIER);
        $lexer = $parser->getLexer();
        assert($lexer->token !== null);
        $this->unit = strtoupper($lexer->token->value);

        $parser->match(TokenType::T_COMMA);
        $this->startDateExpression = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_COMMA);
        $this->endDateExpression = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        if (! in_array($this->unit, self::SUPPORTED_UNITS, true)) {
            throw QueryException::semanticalError(sprintf('Unsupported TIMESTAMPDIFF unit "%s".', $this->unit));
        }

        return sprintf(
            'TIMESTAMPDIFF(%s, %s, %s)',
            $this->unit,
            $this->dispatchExpression($this->startDateExpression, $sqlWalker),
            $this->dispatchExpression($this->endDateExpression, $sqlWalker),
        );
    }

    private function dispatchExpression(Node|string $expression, SqlWalker $sqlWalker): string
    {
        return is_string($expression) ? $expression : $expression->dispatch($sqlWalker);
    }
}
