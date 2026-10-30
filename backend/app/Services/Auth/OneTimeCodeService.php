<?php

namespace App\Services\Auth;

use App\Contracts\CodeSender;
use App\Enums\VerificationLevel;
use App\Models\OneTimeCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Connexion par code à usage unique (CP-01, section 10) : le code est
 * valable 10 minutes et accepte 5 essais au plus.
 */
class OneTimeCodeService
{
    public function __construct(private readonly CodeSender $sender) {}

    public function issue(string $phone): void
    {
        OneTimeCode::query()
            ->where('phone', $phone)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $length = config('idee.otp.length');
        $code = str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);

        OneTimeCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(config('idee.otp.ttl_minutes')),
        ]);

        $this->sender->send($phone, $code);
    }

    /**
     * Vérifie le code et renvoie le membre, créé à sa première connexion.
     *
     * @throws ValidationException
     */
    public function verify(string $phone, string $code, ?string $name = null): User
    {
        $oneTimeCode = OneTimeCode::query()
            ->where('phone', $phone)
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

        return DB::transaction(function () use ($oneTimeCode, $phone, $name) {
            $oneTimeCode->update(['consumed_at' => now()]);

            $user = User::withTrashed()->firstOrNew(['phone' => $phone]);

            if (! $user->exists) {
                $user->name = $name ?: 'Membre IDEE';
            }

            if ($user->trashed()) {
                $user->restore();
            }

            $user->phone_verified_at ??= now();

            if (! $user->verification_level?->isAtLeast(VerificationLevel::Phone)) {
                $user->verification_level = VerificationLevel::Phone;
            }

            $user->save();

            return $user;
        });
    }
}
