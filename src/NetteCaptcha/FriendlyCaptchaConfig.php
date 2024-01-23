<?php
// This file is part of contact-form <https://github.com/skruppy/contact-form>
// Copyright (c) Skruppy <skruppy@onmars.eu>
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace ContactForm\NetteCaptcha;

use FriendlyCaptcha\SDK\ClientConfig;


class FriendlyCaptchaConfig extends ClientConfig {
    public const DEFAULT_SITEVERIFY_ENDPOINT = 'global';
    public const DEFAULT_START_EVENT = 'focus';

    public string $startEvent = self::DEFAULT_START_EVENT; // one of 'auto', 'focus', 'none' or null;
    public ?string $language = null;
    private ?string $puzzleEndpoint = null;

    // Setters
    public function setStartEvent(string $startEvent): self
    {
        $this->startEvent = $startEvent;
        return $this;
    }

    public function setLanguage(?string $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function setPuzzleEndpoint(?string $endpoint): self
    {
        $this->puzzleEndpoint = $endpoint;
        return $this;
    }

    // Getters
    public function getStartEvent(bool $null_default = false): ?string
    {
        return $null_default && $this->startEvent == self::DEFAULT_START_EVENT ?
            null :
            $this->startEvent;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getPuzzleEndpoint(bool $null_default = false): ?string
    {
        $endpoint = $this->puzzleEndpoint ?? $this->siteverifyEndpoint;
        if ($endpoint == 'global') {
            return $null_default ? null : 'https://api.friendlycaptcha.com/api/v1/puzzle';
        }
        else if ($endpoint == 'eu') {
            return 'https://eu-api.friendlycaptcha.eu/api/v1/puzzle';
        }
        else if ($this->puzzleEndpoint === null) {
            throw new Exception("siteverify endpoint is set to custom URL without setting puzzle URL.");
        }
        else {
            return $this->puzzleEndpoint;
        }
    }

    // Extra base class getters
    public function getAPIKey(): string
    {
        return $this->apiKey;
    }

    public function getSitekey(): string
    {
        return $this->sitekey;
    }

    public function getSiteverifyEndpoint(): string
    {
        return $this->siteverifyEndpoint;
    }

    public function getStrict(): bool
    {
        return $this->strict;
    }

    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
