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

	public function sendErrorNotification(string $subject, string $bodyHtml): void
	{
		$email = (new Email())
			->from($_ENV['REPORT_MAIL'])
			->to($_ENV['REPORT_MAIL'])
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
