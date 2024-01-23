<?php
// This file is part of contact-form <https://github.com/skruppy/contact-form>
// Copyright (c) Skruppy <skruppy@onmars.eu>
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace ContactForm;

use PHPMailer\PHPMailer\SMTP;


class CompliantSMTP extends SMTP {
	public function mail($from) {
		$useVerp = ($this->do_verp ? ' XVERP' : '');

		return $this->sendCommand(
			'MAIL FROM',
			'MAIL FROM:<>' . $useVerp,
			250
		);
	}
}
