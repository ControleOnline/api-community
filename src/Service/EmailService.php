<?php

namespace App\Service;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Email;

class EmailService
{

	private $mailer;

	public function __construct()
	{
		$this->mailer = Transport::fromDsn($_ENV['MAILER_URL']);
	}

	public function sendErrorNotification(string $subject, string $bodyHtml, array $recipients = []): void
	{
		$normalizedRecipients = array_values(array_unique(array_filter(array_map(
			static function (mixed $recipient): ?string {
				$normalizedRecipient = strtolower(trim((string) $recipient));
				if ($normalizedRecipient === '') {
					return null;
				}

				return filter_var($normalizedRecipient, FILTER_VALIDATE_EMAIL)
					? $normalizedRecipient
					: null;
			},
			$recipients
		))));

		if ($normalizedRecipients === []) {
			$normalizedRecipients = [$_ENV['REPORT_MAIL']];
		}

		$email = (new Email())
			->from($_ENV['REPORT_MAIL'])
			->to(...$normalizedRecipients)
			->subject('[PROD][ERRO] ' . $subject)
			->html($bodyHtml);

		try {
			$this->mailer->send($email);
		} catch (TransportExceptionInterface $e) {
			try {
				error_log('[ERRO AO ENVIAR EMAIL DE ERRO] ' . $e->getMessage());
			} catch (\Throwable $inner) {
				// Fallback absoluto: não fazer nada
			}
		}
	}
}
