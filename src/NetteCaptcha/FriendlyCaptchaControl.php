<?php
// This file is part of contact-form <https://github.com/skruppy/contact-form>
// Copyright (c) Skruppy <skruppy@onmars.eu>
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace ContactForm\NetteCaptcha;

use Nette\Forms\Form;
use Nette\Forms\Controls\BaseControl;

use Nette\Utils\Html;
use Nette\Utils\Random;

use FriendlyCaptcha\SDK\Client as FriendlyCaptchaClient;


class FriendlyCaptchaControl extends BaseControl {
	public string $tokenPrefix = 'token_';
	public int $tokenLength = 30; // 155 bits of entropy > 128 bits => good
	public ?string $token = null;

	public FriendlyCaptchaConfig $captchaConfig;
	public CacheBase $cache;


	public function __construct(
		FriendlyCaptchaConfig $captchaConfig,
		CacheBase $cache,
		?string $label = null
	) {
		parent::__construct($label);

        $this->captchaConfig = $captchaConfig;
		$this->cache = $cache;

		$this->control->type = 'hidden';
		$this->setOption('type', 'friendly-captcha');
	}

	public function getControl(): Html {
		$this->setOption('rendered', true);
		if ($this->token === null) {
			$el = Html::el('div');
			$el->addClass('frc-captcha');
			$el->addAttributes([
				'data-sitekey' => $this->captchaConfig->sitekey,
				'data-solution-field-name' => $this->getHtmlName(),
				'data-start' => $this->captchaConfig->getStartEvent(true),
				'data-lang' => $this->captchaConfig->getLanguage(),
				'data-puzzle-endpoint' => $this->captchaConfig->getPuzzleEndpoint(true),
			]);
			return $el;
		}
		else {
			$el = clone $this->control;
			return $el->addAttributes([
				'name' => $this->getHtmlName(),
				'id' => $this->getHtmlId(),
				'value' => $this->tokenPrefix . $this->token,
			]);
		}
	}

	public function getLabel($caption = null): Html|string|null {
		return $caption !== null || $this->caption !== null ? parent::getLabel($caption) : null;
	}


	public function setValue($value) {
		if ($this->token !== null || $value !== null) {
			if ($this->token === null) {
				$this->token = Random::generate($this->tokenLength);
			}

			$this->cache->store($this->token, $value);

			if ($value === null) {
				$this->token = null;
			}
		}
		$this->value = $value;
		return $this;
	}


	public function loadHttpData(): void {
		$value = $this->getHttpData(Form::DataText);

		if (
			$value !== null &&
			strlen($value) == strlen($this->tokenPrefix) + $this->tokenLength &&
			str_starts_with($value, $this->tokenPrefix)
		) {
			$token = substr($value, strlen($this->tokenPrefix));
			$this->value = $this->cache->fetch($token);
			if ($this->value !== null) {
				$this->token = $token;
			}
			else {
				$this->addError('Please repeat anti-robot verification.');
			}
		}
		else if($value !== null && $value !== '' && $value !== '.UNSTARTED') {
			$captchaClient = new FriendlyCaptchaClient($this->captchaConfig);
			$result = $captchaClient->verifyCaptchaResponse($value);

			if ($result->shouldAccept()) {
				$this->setValue(true);
			}
			else if (! $result->isClientError()) {
				$this->addError('Anti-robot verification failed.');
			}
		}
	}
}
