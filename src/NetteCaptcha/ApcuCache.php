<?php
// This file is part of contact-form <https://github.com/skruppy/contact-form>
// Copyright (c) Skruppy <skruppy@onmars.eu>
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace ContactForm\NetteCaptcha;


class ApcuCache extends CacheBase {
	public function store(string $key, mixed $value): void {
		if ($value !== null) {
			apcu_store($this->prefix . $key, $value, $this->ttl);
		}
		else {
			apcu_delete($this->prefix . $key);
		}
	}

	public function fetch(string $key): mixed {
		$ret = apcu_fetch($this->prefix . $key, $success);
		return $success ? $ret : null;
	}
}
