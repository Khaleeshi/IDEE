<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * SMS de connexion payé d'avance (CP-07) : le membre paie, puis reçoit son
 * code par SMS. Un achat donne droit à un envoi et à un renvoi.
 */
class SmsPurchase extends Model
{
    use HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Payee;
    }

    /**
     * Payé, pas encore épuisé, et dans le délai d'utilisation.
     */
    public function isUsable(): bool
    {
        return $this->isPaid()
            && $this->sends_remaining > 0
            && $this->paid_at?->gt(now()->subHours(config('idee.otp.sms_purchase_valid_hours')));
    }
}
