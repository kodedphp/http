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
 *
 *
 * Content negotiation module.
 *
 * Supported access headers:
 *
 *  Access
 *  Access-Language
 *  Access-Charset
 *  Access-Encoding
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Content_negotiation
 */

use InvalidArgumentException;


abstract class AcceptHeader
{
//    protected const VALID_MIME_TYPE_REGEX = '~^(\*|[a-zA-Z0-9._\-]+)(/|-|_)?(\*|[a-zA-Z0-9._\-+]+)?$~';
//    protected const VALID_MIME_TYPE_REGEX = '~^(\*|[a-z0-9\-._]+)([/_-](\*|[a-z0-9.\-_+]+))?$~i';
    protected const VALID_MIME_TYPE_REGEX = '~^(\*|[a-z0-9._]+)([/|_|-])?(\*|[a-z0-9.\-_+]+)?$~i';

    private $header;
    private $separator;
    private $type;
    private $subtype;
    private $quality = 1.0;
    private $weight = 0.0;
    private $obscure = '';
    private $catchAll = false;
    private $params = [];

    public function __construct(string $header)
    {
        $this->header = $header;

        $header = preg_replace('/[[:space:]]/', '', $header);
        $bits   = explode(';', $header);
        $type   = array_shift($bits);

        if (!preg_match(self::VALID_MIME_TYPE_REGEX, $type, $matches)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid Access header', $header));
        }

        $this->separator = $matches[2] ?? '/';
        [$type, $this->subtype] = explode($this->separator, $type, 2) + [1 => '*'];

        if ($type === '*' && $this->subtype !== '*') {
            // @see https://tools.ietf.org/html/rfc7231#section-5.3.2
            throw new InvalidArgumentException(sprintf('"%s" is not a valid Access header', $header));
        }

        // @see https://tools.ietf.org/html/rfc7540#section-8.1.2
        $this->type = strtolower($type);

        /* Uses a simple heuristic to check if subtype is part of a
         * custom media type like "vnd.api-v1+json".
         * */
        $this->obscure = explode('+', $this->subtype)[1] ?? '';
        $this->catchAll = $this->type === '*' && $this->subtype === '*';

        parse_str(join('&', $bits), $this->params);
        /* NOTE: It is a waste of time to negotiate on the basis
         * of obscure parameters while using a meaningless media
         * type like "vnd.whatever". The IT world is a big mess.
         */
        $this->quality = (float)($this->params['q'] ?? 1.0);
        unset($this->params['q']);
    }

    public function __toString(): string
    {
        return $this->value();
    }

    public function quality(): float
    {
        return $this->quality;
    }

    public function value(): string
    {
        // The header is explicitly rejected
        if (0.0 === $this->quality()) {
            return '';
        }

        // If language, encoding or charset
        if ('*' === $this->subtype) {
            return $this->type;
        }

        return $this->type . $this->separator . $this->subtype;
    }

    public function weight(): float
    {
        return $this->weight;
    }

    /**
     * @internal
     *
     * This method finds the best match from the accept header,
     * including all the stupidity that may be passed by the
     * ignorant developers who do not follow standards.
     *
     * @param AcceptHeader $accept The accept header part
     * @param array        $matches Matched types
     *
     * @return bool TRUE if the accept header part match
     * against the supported (this) header part
     */
    public function matches(AcceptHeader $accept, array &$matches = null): bool
    {
        $matches = (array)$matches;
        $accept = clone $accept;

        $typeMatch = $this->type === $accept->type;

        if (1.0 === $accept->quality) {
            $accept->quality = (float)$this->quality;
        }

        if ($accept->catchAll || ($typeMatch && $this->obscure) || $accept->obscure) {
            $accept->type = $this->type;
            $accept->subtype = $this->subtype;
            $matches[] = $accept;
            return true;
        }

        if (0.0 === $this->quality) {
            // Explicitly denied
            $matches[] = clone $this;
            return true;
        }

        if (0.0 === $accept->quality) {
            // Explicitly denied
            $matches[] = $accept;
            return true;
        }

        // Type do not match; bail out
        if (!$typeMatch && $this->type !== '*') {
            return false;
        }

        if ($accept->subtype !== $this->subtype && $this->subtype !== '*') {
            return false;
        }

        $matches[] = $this->rank(clone $accept);

        return true;
    }

    private function rank(AcceptHeader $accept): AcceptHeader
    {
        // +100 if types are exact match
        if ($this->type === $accept->type && $accept->type !== '*') {
            $accept->weight += 100;
        }

        $accept->weight += $this->catchAll ? 0.0 : $accept->quality;

        // +1 for each parameter that matches, except "q"
        foreach ($this->params as $k => $v) {
            if (isset($accept->params[$k]) && $accept->params[$k] === $v) {
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


class AcceptHeaderNegotiate
{
    /** @var AcceptHeader[] */
    private $supports;

    public function __construct(string $supportHeader)
    {
        $this->supports = $supportHeader;
    }

    public function match(string $accepts): AcceptHeader
    {
        /** @var AcceptHeader $support */
        foreach ($this->parse($accepts) as $accept) {
            foreach ($this->parse($this->supports) as $support) {
                $support->matches($accept, $types);
            }
        }

        usort($types, function(AcceptHeader $a, AcceptHeader $b) {
            return $b->weight() <=> $a->weight();
        });

        if (empty($types)) {
            /* Set "q=0", meaning the header is explicitly rejected.
             * The consuming clients should handle this according to
             * their internal logic. This is much better then throwing
             * exceptions which must be handled in every place where
             * match() is called. The client may issue a 406 status code.
             */
            $types[] = new class('*;q=0') extends AcceptHeader {};
        }

        return $types[0];
    }

    /**
     * @param string $header
     *
     * @return \Generator
     */
    private function parse(string $header): \Generator
    {
        foreach (explode(',', $header) as $header) {
            yield new class($header) extends AcceptHeader {};
        }
    }
}
