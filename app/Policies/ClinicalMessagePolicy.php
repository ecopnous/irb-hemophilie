<?php

namespace App\Policies;

use App\Models\ClinicalMessage;
use App\Models\User;

class ClinicalMessagePolicy
{
    public function view(User $user, ClinicalMessage $message): bool
    {
        if ((int) $message->hopital_id !== (int) (current_hopital_id() ?? $user->hopital_id)) {
            return false;
        }

        if ($message->message_type !== 'internal') {
            return false;
        }

        if ((int) $message->sender_id === (int) $user->id) {
            return true;
        }

        return $message->recipients()
            ->where('recipient_type', 'user')
            ->where('recipient_id', $user->id)
            ->exists();
    }

    public function send(User $user): bool
    {
        return filled($user->hopital_id);
    }

    public function manage(User $user, ClinicalMessage $message): bool
    {
        return $this->view($user, $message);
    }
}
