<?php

class HumanSessionService {
    public static function isActive(string $threadId, string $instanceName): bool
    {
        $session = HumanSession::isActive($threadId, $instanceName);
        return !empty($session);
    }

    public static function startSession(string $threadId, string $instanceName): bool
    {
        return HumanSession::start($threadId, $instanceName);
    }

    public static function endSession(string $threadId, string $instanceName): bool
    {
        return HumanSession::end($threadId, $instanceName);
    }
}