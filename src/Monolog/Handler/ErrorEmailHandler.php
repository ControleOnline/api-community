<?php

namespace App\Monolog\Handler;

use App\Service\EmailService;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ErrorEmailHandler extends AbstractProcessingHandler
{
    private EmailService $emailService;
    private RequestStack $requestStack;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        EmailService $emailService,
        RequestStack $requestStack,
        TokenStorageInterface $tokenStorage,
        $level = Logger::CRITICAL,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);
        $this->emailService = $emailService;
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
    }

    protected function write(LogRecord $record): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $token = $this->tokenStorage->getToken();

        $userEmail = 'N/A';
        if ($token && $token->getUser() && method_exists($token->getUser(), 'getUserIdentifier')) {
            $userEmail = $token->getUser()->getUserIdentifier();
        }

        $requestUri = $request ? $request->getUri() : 'N/A';
        $requestMethod = $request ? $request->getMethod() : 'N/A';
        $clientIp = $request ? $request->getClientIp() : 'N/A';

        $message = $record->message ?? '(sem mensagem)';
        $context = $record->context;
        $exception = $context['exception'] ?? null;

        $body = <<<TXT
🚨 Erro 500 detectado no ambiente de produção

🧑 Utilizador: {$userEmail}
🌐 URI: {$requestUri}
🔁 Método: {$requestMethod}
📍 IP: {$clientIp}

📝 Mensagem:
{$message}

TXT;
        if ($exception instanceof \Throwable) {
            $body .= "\n🔧 Exceção: " . $exception::class . "\n";
            $body .= "📄 Arquivo: " . $exception->getFile() . ':' . $exception->getLine() . "\n";
            $body .= "\n📜 Stack Trace:\n" . $exception->getTraceAsString();
        }

        $this->emailService->sendErrorNotification('Erro 500 Detetado', $body);
    }
}
