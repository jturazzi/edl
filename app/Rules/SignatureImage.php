<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Signature envoyée par le canevas : image PNG (data URL) contenant réellement un tracé.
 * Un canevas vierge (fond blanc ou transparent) est refusé.
 */
class SignatureImage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $value, $m)) {
            $fail('La signature est invalide.');

            return;
        }

        $binary = base64_decode($m[1], true);
        $image = $binary === false || ! function_exists('imagecreatefromstring') ? null : @imagecreatefromstring($binary);

        // Sans GD, on ne peut pas analyser l'image : on se contente du format
        if ($image === null && ! function_exists('imagecreatefromstring')) {
            return;
        }

        if ($image === null || $image === false) {
            $fail('La signature est invalide.');

            return;
        }

        if (! $this->hasStroke($image)) {
            $fail('La signature est vide.');
        }
    }

    /** Cherche (échantillonnage) un pixel ni transparent ni blanc. */
    private function hasStroke(\GdImage $image): bool
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $stepX = max(1, intdiv($w, 600));
        $stepY = max(1, intdiv($h, 300));

        for ($y = 0; $y < $h; $y += $stepY) {
            for ($x = 0; $x < $w; $x += $stepX) {
                $c = imagecolorat($image, $x, $y);
                $alpha = ($c >> 24) & 0x7F; // 127 = transparent
                $r = ($c >> 16) & 0xFF;
                $g = ($c >> 8) & 0xFF;
                $b = $c & 0xFF;

                if ($alpha < 100 && ($r < 235 || $g < 235 || $b < 235)) {
                    return true;
                }
            }
        }

        return false;
    }
}
