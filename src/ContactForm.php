<?php
// This file is part of contact-form <https://github.com/skruppy/contact-form>
// Copyright (c) Skruppy <skruppy@onmars.eu>
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace ContactForm;

use Latte\Engine;
use Nette\Forms\Form;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception; // Needs to be loaded, eeven if not used explicitly

use ContactForm\NetteCaptcha\ApcuCache;
use ContactForm\NetteCaptcha\FriendlyCaptchaControl;
use ContactForm\NetteCaptcha\FriendlyCaptchaConfig;

use Nette\Bridges\FormsLatte\FormsExtension;


class ContactForm {
	private $flashMessages = [];
	private ?Form $form = null;
	private $config = null;
	private ?FriendlyCaptchaConfig $captchaConfig = null;

	public function __construct($config) {
		$this->config = $config;
		$this->captchaConfig = $this->buildCaptchaConfig();
		$this->form = $this->buildForm();
		$this->form->fireEvents();
	}

	public function sendMail(Form $form, $data): void {
		$mail = new PHPMailer();
		$mail->isSMTP();
		$mail->setSMTPInstance(new CompliantSMTP());

		// Adresses
		$mail->setFrom(
			$data['email'] ?? $this->config['default_sender_email'],
			$data['name']  ?? $this->config['default_sender_name'],
			false // Don't set envelope from
		);
		$mail->addAddress(
			$this->config['recipient_email'],
			$this->config['recipient_name']
		);
		$mail->addCustomHeader('List-Id', $this->config['list_id']);

		// Meta data
		$mail->addCustomHeader('X-Browser-IP', $_SERVER['REMOTE_ADDR']);
		if ($_SERVER['HTTP_USER_AGENT']) {
			$mail->addCustomHeader('X-Browser-Agent', $_SERVER['HTTP_USER_AGENT']);
		}

		// Text
		$mail->Subject = $this->config['subject'];
		$mail->Body = $data['message'];

		if ($mail->send()) {
			$this->flashSuccess('Message has been sent successfully.');
			$this->form->reset();
		}
		else {
			$this->flashFail('Message could not be send.');
		}
	}

	function flashSuccess(string $message): void {
		$this->flashMessages[] = [
			'type' => 'success',
			'message' => $message,
		];
	}

	function flashFail(string $message): void {
		$this->flashMessages[] = [
			'type' => 'fail',
			'message' => $message,
		];
	}


	function buildCaptchaConfig(): FriendlyCaptchaConfig {
		$fcc = $this->config['friendly_captcha'];
		$config = new FriendlyCaptchaConfig();
		$config->setStrict(true);
		$config->setAPIKey($fcc['api_key']);
		$config->setSitekey($fcc['site_key']);

		$config->setSiteverifyEndpoint($fcc['verify_endpoint'] ?? $config::DEFAULT_SITEVERIFY_ENDPOINT);
		$config->setPuzzleEndpoint($fcc['puzzle_endpoint'] ?? null);
		$config->setStartEvent($fcc['start_event'] ?? $config::DEFAULT_START_EVENT);
		$config->setLanguage($fcc['language'] ?? null);

		return $config;
	}


	function buildForm() {
		$form = new Form();

		$form->allowCrossOrigin(); // Ignore unset "_nss" cookie (used for CORF protection)

		$form->addText('name', 'Your name')
		     ->setHtmlAttribute('placeholder', '')
		     ->setNullable()
		     ->addRule($form::MaxLength, 'Your name has to be shorter than %d characters.', 40);

		$form->addEmail('email', 'Your email address')
		     ->setHtmlAttribute('placeholder', '')
		     ->setNullable()
		     ->addRule($form::MaxLength, 'Your email address has to be shorter than %d characters.', 320);

		$form->addTextArea('message', 'Your message')
		     ->setHtmlAttribute('placeholder', '')
		     ->setHtmlAttribute('rows', '7')
		     ->setRequired('Please provide a meaningful message')
		     ->addRule($form::MinLength, 'Please provide a meaningful message.', 10)
		     ->addRule($form::MaxLength, 'Your message has to be shorter than %d characters.', 5000);

		$cache = new ApcuCache();
		if (isset($this->config['friendly_captcha']['captcha_ttl'])) {
			$cache->ttl = $this->config['friendly_captcha']['captcha_ttl'];
		}
		$form['captcha'] = new FriendlyCaptchaControl(
			$this->captchaConfig,
			$cache,
		);
		$form['captcha']->setRequired('Please complete anti-robot verification');

		$form->addSubmit('send', 'Send');

		$form->onSuccess[] = [$this, 'sendMail'];

		return $form;
	}


	function render(): void {
		$latte = new Engine();
		// $latte->setTempDirectory('/path/to/tempdir');
		$latte->addExtension(new FormsExtension);

		$params = [
			'form' => $this->form,
			'flashMessages' => $this->flashMessages,
		];

		$puzzle_url = $this->captchaConfig->getPuzzleEndpoint();
		$puzzle_host = substr($puzzle_url, 0, strpos($puzzle_url, '/', 8));
		header('X-Content-Type-Options: nosniff');
		header("Content-Security-Policy: default-src 'self'; object-src 'none'; style-src 'self' 'unsafe-inline'; script-src  'self' 'unsafe-eval' 'unsafe-inline'; base-uri 'self'; connect-src 'self' $puzzle_host; font-src 'self'; frame-src 'none'; worker-src blob:;");
		header('Referrer-Policy: no-referrer');
		header('X-Frame-Options: DENY');

		header_remove('Set-Cookie'); // Remove "_nss" cookie (used for CORF protection by Nette)

		$latte->render(dirname(__DIR__) .'/tpl/index.latte', $params);
	}
}
