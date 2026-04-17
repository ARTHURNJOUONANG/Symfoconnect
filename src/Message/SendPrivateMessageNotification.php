<?php

namespace App\Message;

final readonly class SendPrivateMessageNotification
{
    public function __construct(
        private int $messageId
    ) {
    }

    public function getMessageId(): int
    {
        return $this->messageId;
    }
}
