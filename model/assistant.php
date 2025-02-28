<?php
class Assistant
{
    public string $id;
    public int $user_id;

    public function __construct()
    {
        $this->user_id = get_current_user_id();
    }
}