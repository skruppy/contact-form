<?php
// This file is part of contact-form <https://github.com/skruppy/contact-form>
// Copyright (c) Skruppy <skruppy@onmars.eu>
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace ContactForm\NetteCaptcha;


abstract class CacheBase {
    	public const int DEFAULT_TTL = 3 * 24 * 60 * 60;

	public int $ttl = self::DEFAULT_TTL;
	public string $prefix = 'captcha_result_';

	public abstract function store(string $key, mixed $value): void;
	public abstract function fetch(string $key): mixed;
}
