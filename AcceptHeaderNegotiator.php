<?php

/*
 * This file is part of the Koded package.
 *
 * (c) Mihail Binev <mihail@kodeart.com>
 *
 * Please view the LICENSE distributed with this source code
 * for the full copyright and license information.
 *
 */

namespace Koded\Http;

/**
 * Content negotiation module.
 *
 * Supported HTTP/1.1 Accept headers:
 *
 *  Accept
 *  Accept-Language
 *  Accept-Charset
 *  Accept-Encoding
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Content_negotiation
 */

use Generator;
use InvalidArgumentException;
use Koded\Http\Interfaces\HttpStatus;
use function array_shift;
use function explode;
use function join;
use function parse_str;
use function preg_match;
use function preg_replace;
use function sprintf;
use function strtolower;
use function trim;
use function usort;

class AcceptHeaderNegotiator
{
    private string $supports = '';

    public function __construct(string $supportHeader)
    {
        $this->supports = $supportHeader;
    }

    public function match(string $accepts): AcceptHeader
    {
        return $this->matches($accepts)[0];
    }

    public function matches(string $accepts): array
    {
        /** @var AcceptHeader $support */
        foreach ($this->parse($accepts) as $accept) {
            foreach ($this->parse($this->supports) as $support) {
                $support->matches($accept, $matches);
            }
        }
        usort($matches, fn(AcceptHeader $a, AcceptHeader $b) => $b->weight() <=> $a->weight());
        if (empty($matches)) {
            /* Set "q=0", meaning the header is explicitly rejected.
             * The consuming clients should handle this according to
             * their internal logic. This is much better then throwing
             * exceptions which must be handled in every place where
             * match() is called. For example, the client may issue a
             * 406 status code and be done with it.
             */
            $matches[] = new class('*;q=0') extends AcceptHeader {};
        }
        return $matches;
    }

    /**
     * @param string $header
     *
     * @return Generator
     */
    private function parse(string $header): Generator
    {
        foreach (explode(',', $header) as $header) {
            yield new class($header) extends AcceptHeader {};
        }
    }
}


abstract class AcceptHeader
{
    private string $header    = '';
    private string $separator = '/';
    private string $type      = '';
    private string $subtype   = '*';
    private float  $quality   = 1.0;
    private float  $weight    = 0.0;
    private bool   $catchAll  = false;
    private array  $params    = [];

    public function __construct(string $header)
    {
        $this->header = $header;

        $header = preg_replace('/[[:space:]]/', '', $header);
        $bits   = explode(';', $header);
        $type   = array_shift($bits);
        if (!empty($type) && !preg_match('~^(\*|[a-z0-9._]+)([/|_\-])?(\*|[a-z0-9.\-_+]+)?$~i', $type, $matches)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid Access header', $header),
                HttpStatus::NOT_ACCEPTABLE);
        }
        $this->separator = $matches[2] ?? '/';
        [$type, $subtype] = explode($this->separator, $type, 2) + [1 => '*'];
        if ('*' === $type && '*' !== $subtype) {
            // @see https://tools.ietf.org/html/rfc7231#section-5.3.2
            throw new InvalidArgumentException(sprintf('"%s" is not a valid Access header', $header),
                HttpStatus::NOT_ACCEPTABLE);
        }
        // @see https://tools.ietf.org/html/rfc7540#section-8.1.2
        $this->type = trim(strtolower($type));
        /*
         * Uses a simple heuristic to check if subtype is part of
         * some convoluted media type like "vnd.api-v1+json".
         *
         * NOTE: It is a waste of time to negotiate on the basis
         * of obscure parameters while using a meaningless media
         * type like "vnd.whatever". The web world is a big mess
         * but this module can handle the Dunning-Kruger effect.
         */
        $this->subtype  = trim(explode('+', $subtype)[1] ?? $subtype);
        $this->catchAll = ('*' === $this->type) && ('*' === $this->subtype);
        parse_str(join('&', $bits), $this->params);
        $this->quality = (float)($this->params['q'] ?? 1);
        unset($this->params['q']);
    }

    public function __toString(): string
    {
        return $this->value();
    }

    public function value(): string
    {
        // The header is explicitly rejected
        if (0.0 === $this->quality) {
            $this->type = $this->subtype = '';
            return '';
        }
        // If language, encoding or charset
        if ('*' === $this->subtype) {
            return $this->type;
        }
        return $this->type . $this->separator . $this->subtype;
    }

    public function quality(): float
    {
        return $this->quality;
    }

    public function weight(): float
    {
        return $this->weight;
    }

    public function is(string $type): bool
    {
        return ($type === $this->subtype) && ($this->subtype !== '*');
    }

    /**
     * @param AcceptHeader   $accept  The accept header part
     * @param AcceptHeader[] $matches Matched types
     *
     * This method finds the best match for the Accept header,
     * including lots of nonsense that may be passed by the
     * developers who do not follow RFC standards.
     *
     * @internal
     */
    public function matches(AcceptHeader $accept, array &$matches = null): void
    {
        $matches   = (array)$matches;
        $accept    = clone $accept;
        $typeMatch = ($this->type === $accept->type);
        if (1.0 === $accept->quality) {
            $accept->quality = (float)$this->quality;
        }
        if ($accept->catchAll) {
            $accept->type    = $this->type;
            $accept->subtype = $this->subtype;
            $matches[]       = $accept;
            return;
        }
        // Explicitly denied
        if (0.0 === $this->quality) {
            $matches[] = clone $this;
            return;
        }
        // Explicitly denied
        if (0.0 === $accept->quality) {
            $matches[] = $accept;
            return;
        }
        // Explicit type mismatch (w/o asterisk); bail out
        if ((false === $typeMatch) && ('*' !== $this->type)) {
            return;
        }
        if ('*' === $accept->subtype) {
            $accept->subtype = $this->subtype;
        }
        if (($accept->subtype !== $this->subtype) && ('*' !== $this->subtype)) {
            return;
        }
        $matches[] = $this->rank($accept);
    }


    private function rank(AcceptHeader $accept): AcceptHeader
    {
        // +100 if types are exact match w/o asterisk
        if (($this->type === $accept->type) &&
            ($this->subtype === $accept->subtype)) {
            $accept->weight += 100;
        }
        $accept->weight += ($this->catchAll ? 0.0 : $accept->quality);
        // +1 for each parameter that matches, except "q"
        foreach ($this->params as $k => $v) {
            if (isset($accept->params[$k]) && ($accept->params[$k] === $v)) {
                $accept->weight += 1;
            } else {
                $accept->weight -= 1;
            }
        }
        // Add "q"
        $accept->weight += $accept->quality;
        return $accept;
    }
}
