<?php

namespace App\Service;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as MimeEmail;

class EmailService
{

	private $mailer;

	public function __construct()
	{
		$this->mailer = Transport::fromDsn($this->resolveMailerDsn());
	}

	public function sendMessage(string $recipient, string $subject, string $bodyHtml): void
	{
		$normalizedRecipients = $this->normalizeRecipients([$recipient]);
		if ($normalizedRecipients === []) {
			throw new \InvalidArgumentException('A valid recipient email is required.');
		}

		$email = $this->createBaseEmail($normalizedRecipients, $subject, $bodyHtml);
		$this->captureMessage($email, $bodyHtml);
		$this->mailer->send($email);
	}

	public function sendErrorNotification(string $subject, string $bodyHtml, array $recipients = []): void
	{
		$normalizedRecipients = $this->normalizeRecipients($recipients);

		if ($normalizedRecipients === []) {
			$normalizedRecipients = $this->normalizeRecipients([
				$_ENV['REPORT_MAIL'] ?? $_SERVER['REPORT_MAIL'] ?? getenv('REPORT_MAIL') ?? '',
				$this->resolveSenderAddress(),
			]);
		}

		$email = $this->createBaseEmail(
			$normalizedRecipients,
			'[PROD][ERRO] ' . $subject,
			$bodyHtml
		);

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

	private function createBaseEmail(array $recipients, string $subject, string $bodyHtml): MimeEmail
	{
		$email = (new MimeEmail())
			->to(...$recipients)
			->subject($subject)
			->html($bodyHtml);

		$senderAddress = $this->resolveSenderAddress();
		$senderName = trim((string) (
			$_ENV['MAILER_FROM_NAME']
			?? $_SERVER['MAILER_FROM_NAME']
			?? getenv('MAILER_FROM_NAME')
			?? ''
		));

		if ($senderName !== '') {
			$email->from(new Address($senderAddress, $senderName));
		} else {
			$email->from($senderAddress);
		}

		return $email;
	}

	private function normalizeEmail(string $value): string
	{
		$normalized = strtolower(trim($value));

		return filter_var($normalized, FILTER_VALIDATE_EMAIL)
			? $normalized
			: '';
	}

	private function normalizeRecipients(array $recipients): array
	{
		return array_values(array_unique(array_filter(array_map(
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
	}

	private function captureMessage(MimeEmail $email, string $bodyHtml): void
	{
		$captureDir = trim((string) ($_ENV['EMAIL_CAPTURE_DIR'] ?? $_SERVER['EMAIL_CAPTURE_DIR'] ?? getenv('EMAIL_CAPTURE_DIR') ?? ''));
		if ($captureDir === '') {
			return;
		}

		if (!is_dir($captureDir) && !@mkdir($captureDir, 0777, true) && !is_dir($captureDir)) {
			return;
		}

		$payload = [
			'from' => array_map(
				static fn ($address) => $address->getAddress(),
				$email->getFrom()
			),
			'to' => array_map(
				static fn ($address) => $address->getAddress(),
				$email->getTo()
			),
			'subject' => $email->getSubject(),
			'html' => $bodyHtml,
			'captured_at' => gmdate(DATE_ATOM),
		];

		@file_put_contents(
			rtrim($captureDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . sprintf(
				'email-%s-%s.json',
				date('YmdHis'),
				bin2hex(random_bytes(4))
			),
			json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
		);
	}

	private function resolveMailerDsn(): string
	{
		$dsn = trim((string) (
			$_ENV['MAILER_URL']
			?? $_ENV['MAILER_DSN']
			?? $_SERVER['MAILER_URL']
			?? $_SERVER['MAILER_DSN']
			?? getenv('MAILER_URL')
			?? getenv('MAILER_DSN')
			?? ''
		));

		if ($dsn === '') {
			throw new \RuntimeException('MAILER_URL or MAILER_DSN must be configured.');
		}

		return $dsn;
	}

	private function resolveSenderAddress(): string
	{
		$candidates = [
			$_ENV['MAILER_FROM'] ?? null,
			$_SERVER['MAILER_FROM'] ?? null,
			getenv('MAILER_FROM') ?: null,
			$this->extractEmailFromDsn($this->resolveMailerDsn()),
			$_ENV['REPORT_MAIL'] ?? null,
			$_SERVER['REPORT_MAIL'] ?? null,
			getenv('REPORT_MAIL') ?: null,
			'no-reply@localhost',
		];

		foreach ($candidates as $candidate) {
			$normalized = $this->normalizeEmail((string) $candidate);
			if ($normalized !== '') {
				return $normalized;
			}
		}

		return 'no-reply@localhost';
	}

	private function extractEmailFromDsn(string $dsn): ?string
	{
		if (!preg_match('#^[a-z0-9+.-]+://([^:/?]+)#i', $dsn, $matches)) {
			return null;
		}

		$email = rawurldecode((string) ($matches[1] ?? ''));
		$normalized = $this->normalizeEmail($email);

		return $normalized !== '' ? $normalized : null;
	}
}
