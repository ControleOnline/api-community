<?php

namespace App\Tests\Service;

use App\Service\EmailService;
use PHPUnit\Framework\TestCase;

class EmailServiceTest extends TestCase
{
    public function testSendMessageCapturesSenderFromMailerConfiguration(): void
    {
        $captureDir = sys_get_temp_dir() . '/email-service-test-' . bin2hex(random_bytes(4));

        $_ENV['MAILER_URL'] = 'null://localhost';
        $_ENV['MAILER_DSN'] = 'smtp://no-reply%40lave-go.com:secret@mail.lave-go.com:465?encryption=ssl&auth_mode=login';
        $_ENV['MAILER_FROM'] = '';
        $_ENV['REPORT_MAIL'] = 'legacy@example.com';
        $_ENV['EMAIL_CAPTURE_DIR'] = $captureDir;

        $service = new EmailService();
        $service->sendMessage(
            'destinatario@example.com',
            'Teste',
            '<p>Corpo</p>'
        );

        $files = glob($captureDir . '/email-*.json') ?: [];
        self::assertCount(1, $files);

        $payload = json_decode((string) file_get_contents($files[0]), true);

        self::assertSame(['no-reply@lave-go.com'], $payload['from'] ?? []);
        self::assertSame(['destinatario@example.com'], $payload['to'] ?? []);

        @unlink($files[0]);
        @rmdir($captureDir);
    }
}
