<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetUserMfa extends Command
{
    protected $signature = 'xdr:mfa-reset {email : E-mail du compte}';

    protected $description = 'Désactive Google Authenticator pour un utilisateur (récupération accès)';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Utilisateur introuvable : {$email}");

            return self::FAILURE;
        }

        if (! $user->hasMfaEnabled()) {
            $this->info('MFA déjà désactivé.');

            return self::SUCCESS;
        }

        $user->forceFill([
            'mfa_secret' => null,
            'mfa_recovery_codes' => null,
            'mfa_confirmed_at' => null,
            'mfa_last_used_ts' => null,
        ])->save();

        $this->info("MFA désactivé pour {$email}. Reconnectez-vous, puis réactivez via Sécurité.");

        return self::SUCCESS;
    }
}
