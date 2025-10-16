<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Penjual>
 */
class PenjualFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nama' => fake()->company(),
            'deskripsi' => fake()->paragraph(),
            'produk' => fake()->words(3, true),
            'lokasi' => 'L' . str_pad(fake()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'patokan' => fake()->sentence(3),
            'kontak' => fake()->phoneNumber(),
            'foto_profil' => null,
            'foto_kios' => null,
        ];
    }
}
