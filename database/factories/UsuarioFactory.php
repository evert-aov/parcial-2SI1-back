<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rol_id' => Role::inRandomOrder()->first()->id ?? 1,
            'contrasena' => 'password123', // Will be hashed automatically
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'telefono' => fake()->phoneNumber(),
            'sexo' => fake()->randomElement(['M', 'F']),
            'correo' => fake()->unique()->safeEmail(),
            'ci' => fake()->unique()->numerify('########'),
            'direccion' => fake()->address(),
            'activo' => true,
        ];
    }
}
