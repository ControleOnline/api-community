<?php

namespace App\Service;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use RuntimeException;

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

	public function sendMessage(
		string $recipient,
		string $subject,
		string $bodyHtml,
		?string $from = null
	): void {
		$normalizedRecipient = strtolower(trim($recipient));

		if (
			$normalizedRecipient === '' ||
			!filter_var($normalizedRecipient, FILTER_VALIDATE_EMAIL)
		) {
			throw new RuntimeException('E-mail de destino invalido para recuperacao de senha.');
		}

		$email = (new Email())
			->from($from ?: $_ENV['REPORT_MAIL'])
			->to($normalizedRecipient)
			->subject($subject)
			->html($bodyHtml);

		try {
			$this->mailer->send($email);
		} catch (TransportExceptionInterface $e) {
			throw new RuntimeException(
				'Nao foi possivel enviar o e-mail de recuperacao.',
				0,
				$e
			);
		}
	}
}
