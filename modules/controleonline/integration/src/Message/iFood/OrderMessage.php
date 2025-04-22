<?php

namespace ControleOnline\Message\iFood;

class OrderMessage
{
    private array $event;
    private string $rawInput;

    public function __construct(array $event, string $rawInput)
    {
        $this->event = $event;
        $this->rawInput = $rawInput;
    }

    public function getEvent(): array
    {
        return $this->event;
    }

    public function getRawInput(): string
    {
        return $this->rawInput;
    }
}