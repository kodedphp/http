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

trait CookieTrait
{
    protected array $cookieParams = [];

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies): static
    {
        $instance               = clone $this;
        $instance->cookieParams = $cookies;
        return $instance;
    }
}
