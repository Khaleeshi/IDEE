<?php

namespace App\Services\Auth;

use App\Contracts\CodeSender;
use App\Enums\VerificationLevel;
use App\Exceptions\CodeDeliveryFailed;
use App\Models\OneTimeCode;
use App\Models\User;
use App\Notifications\LoginCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Connexion par code à usage unique (CP-01, section 10), envoyé au numéro de
 * téléphone ou à l'adresse e-mail : le code est valable 10 minutes et
 * accepte 5 essais au plus.
 */
class OneTimeCodeService
{
    public function __construct(private readonly CodeSender $sender) {}

    /**
     * @throws ValidationException si aucun canal n'a pu remettre le code
     */
    public function issue(string $identifier): void
    {
        OneTimeCode::query()
            ->where('identifier', $identifier)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $length = config('idee.otp.length');
        $code = str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);

        OneTimeCode::create([
            'identifier' => $identifier,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(config('idee.otp.ttl_minutes')),
        ]);

        if (self::isEmail($identifier)) {
            Notification::route('mail', $identifier)->notify(new LoginCode($code));

            return;
        }

        try {
            $this->sender->send($identifier, $code);
        } catch (CodeDeliveryFailed $exception) {
            Log::error('Code de connexion non envoyé', ['raison' => $exception->getMessage()]);

            throw ValidationException::withMessages([
                'phone' => 'Impossible d\'envoyer le code pour le moment. Réessayez dans quelques minutes.',
            ]);
        }
    }

    /**
     * Vérifie le code et renvoie le membre, créé à sa première connexion.
     *
     * @throws ValidationException
     */
    public function verify(string $identifier, string $code, ?string $name = null): User
    {
        $oneTimeCode = OneTimeCode::query()
            ->where('identifier', $identifier)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if ($oneTimeCode === null || ! $oneTimeCode->isUsable()) {
            throw ValidationException::withMessages([
                'code' => 'Ce code a expiré ou n\'est plus valable. Demandez un nouveau code.',
            ]);
        }

        $oneTimeCode->increment('attempts');

        if (! Hash::check($code, $oneTimeCode->code_hash)) {
            $remaining = max(0, config('idee.otp.max_attempts') - $oneTimeCode->attempts);

            throw ValidationException::withMessages([
                'code' => $remaining > 0
                    ? "Code incorrect. Il vous reste {$remaining} essai(s)."
                    : 'Code incorrect. Demandez un nouveau code.',
            ]);
        }

        return DB::transaction(function () use ($oneTimeCode, $identifier, $name) {
            $oneTimeCode->update(['consumed_at' => now()]);

            $byEmail = self::isEmail($identifier);
            $user = User::withTrashed()->firstOrNew([$byEmail ? 'email' : 'phone' => $identifier]);

            // Une adresse ajoutée au profil mais jamais confirmée ne permet pas d'entrer dans le compte.
            if ($byEmail && $user->exists && $user->email_verified_at === null) {
                throw ValidationException::withMessages([
                    'code' => 'Cette adresse n\'est pas encore confirmée. Connectez-vous avec votre téléphone, puis confirmez-la depuis votre profil.',
                ]);
            }

            if (! $user->exists) {
                $user->name = $name ?: 'Membre IDEE';
            }

            if ($user->exists && $user->isRestricted()) {
                throw ValidationException::withMessages(['code' => $user->restrictionMessage()]);
            }

            if ($user->trashed()) {
                $user->restore();
            }

            if ($byEmail) {
                $user->email_verified_at ??= now();
                $user->verification_level ??= VerificationLevel::None;
            } else {
                $user->phone_verified_at ??= now();

                if (! $user->verification_level?->isAtLeast(VerificationLevel::Phone)) {
                    $user->verification_level = VerificationLevel::Phone;
                }
            }

            $user->save();

            return $user;
        });
    }

    public static function isEmail(string $identifier): bool
    {
        return str_contains($identifier, '@');
    }
}
