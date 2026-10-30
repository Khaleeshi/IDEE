<?php

namespace App\Services\Members;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileService
{
    /**
     * Côté de la photo de profil, en pixels : assez pour un écran de téléphone,
     * assez léger pour la 3G.
     */
    private const PHOTO_SIZE = 256;

    /**
     * @param  array<string, mixed>  $attributes  données validées, rôles et photo compris
     */
    public function update(User $user, array $attributes): User
    {
        $user->fill(Arr::except($attributes, ['roles', 'photo']));

        if (($attributes['photo'] ?? null) instanceof UploadedFile) {
            $this->replacePhoto($user, $attributes['photo']);
        }

        $user->save();

        if (array_key_exists('roles', $attributes)) {
            $user->syncRoles(array_map(UserRole::from(...), $attributes['roles']));
        }

        return $user;
    }

    /**
     * Recadre la photo en carré et la réduit avant de l'enregistrer (CP-02).
     */
    private function replacePhoto(User $user, UploadedFile $file): void
    {
        $source = imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            return;
        }

        $side = min(imagesx($source), imagesy($source));
        $square = imagecreatetruecolor(self::PHOTO_SIZE, self::PHOTO_SIZE);
        imagecopyresampled(
            $square, $source, 0, 0,
            intdiv(imagesx($source) - $side, 2), intdiv(imagesy($source) - $side, 2),
            self::PHOTO_SIZE, self::PHOTO_SIZE, $side, $side,
        );

        ob_start();
        imagejpeg($square, null, 80);
        $jpeg = (string) ob_get_clean();

        $path = "photos/{$user->id}-".Str::lower(Str::random(8)).'.jpg';
        Storage::disk('public')->put($path, $jpeg);

        if ($user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
        }

        $user->photo_path = $path;
    }
}
