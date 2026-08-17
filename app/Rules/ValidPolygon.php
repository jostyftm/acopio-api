<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPolygon implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): mixed  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('El polígono debe ser un array de coordenadas [longitud, latitud].');

            return;
        }

        $points = $this->uniquePoints($value);

        if (count($points) < 3) {
            $fail('El polígono debe tener al menos 3 puntos distintos.');

            return;
        }

        if ($this->samePoint($points[0], $points[count($points) - 1])) {
            $fail('El primer y el último punto del polígono no deben repetirse.');

            return;
        }

        if ($this->areCollinear($points)) {
            $fail('Los puntos del polígono deben formar un área, no pueden ser colineales.');
        }
    }

    /**
     * Elimina puntos consecutivos duplicados.
     *
     * @param  array<int, array<int, float>>  $points
     * @return array<int, array<int, float>>
     */
    private function uniquePoints(array $points): array
    {
        $unique = [];

        foreach ($points as $point) {
            if (! is_array($point) || count($point) !== 2 || ! is_numeric($point[0]) || ! is_numeric($point[1])) {
                $unique[] = $point;

                continue;
            }

            $last = end($unique);
            if ($last === false || ! $this->samePoint($last, $point)) {
                $unique[] = $point;
            }
        }

        return $unique;
    }

    /**
     * Determina si dos puntos son equivalentes.
     *
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    private function samePoint(array $a, array $b): bool
    {
        return (float) $a[0] === (float) $b[0] && (float) $a[1] === (float) $b[1];
    }

    /**
     * Determina si todos los puntos son colineales (el área del polígono es cero).
     *
     * @param  array<int, array<int, float>>  $points
     */
    private function areCollinear(array $points): bool
    {
        $n = count($points);

        for ($i = 0; $i < $n; $i++) {
            $p1 = $points[$i];
            $p2 = $points[($i + 1) % $n];
            $p3 = $points[($i + 2) % $n];

            $cross = ((float) $p2[0] - (float) $p1[0]) * ((float) $p3[1] - (float) $p1[1])
                - ((float) $p2[1] - (float) $p1[1]) * ((float) $p3[0] - (float) $p1[0]);

            if (abs($cross) > 1e-10) {
                return false;
            }
        }

        return true;
    }
}
