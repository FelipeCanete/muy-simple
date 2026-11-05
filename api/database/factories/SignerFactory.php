<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Signer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Por defecto el token en claro es aleatorio y no se conoce; usa
 * withToken('...') en los tests que necesitan el enlace.
 *
 * @extends Factory<Signer>
 */
class SignerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'email' => fake()->unique()->safeEmail(),
            'rut' => fake()->unique()->randomElement(['11111111-1', '22222222-2', '12345678-5', '7654321-6', '9876543-3', '33333333-3']),
            'token_hash' => Signer::hashToken(bin2hex(random_bytes(32))),
            'token_expires_at' => now()->addDays(7),
        ];
    }

    public function withToken(string $plainToken): static
    {
        return $this->state(fn () => ['token_hash' => Signer::hashToken($plainToken)]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['token_expires_at' => now()->subMinute()]);
    }

    public function signed(): static
    {
        return $this->state(fn () => ['signed_at' => now(), 'signed_via' => 'link']);
    }
}
