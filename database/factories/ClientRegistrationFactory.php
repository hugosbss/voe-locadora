<?php

namespace Database\Factories;

use App\Enums\FacialStatus;
use App\Enums\RegistrationStatus;
use App\Models\ClientRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientRegistration>
 */
class ClientRegistrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'cpf' => fake()->numerify('###########'),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'phone' => '(11) 98765-4321',
            'whatsapp' => '(11) 98765-4321',
            'email' => fake()->safeEmail(),
            'cep' => '01310-100',
            'address' => fake()->streetName(),
            'address_number' => (string) fake()->numberBetween(1, 9999),
            'neighborhood' => fake()->citySuffix(),
            'city' => 'São Paulo',
            'state' => 'SP',
            'cnh_number' => fake()->numerify('############'),
            'cnh_category' => fake()->randomElement(['A', 'B', 'AB', 'D']),
            'cnh_expiry_date' => fake()->dateTimeBetween('+1 year', '+5 years')->format('Y-m-d'),
            'cnh_front_path' => null,
            'cnh_back_path' => null,
            'proof_of_residence_path' => null,
            'selfie_path' => null,
            'facial_status' => FacialStatus::Pending,
            'status' => RegistrationStatus::Novo,
            'veracity_declaration_accepted' => true,
            'privacy_policy_accepted' => true,
        ];
    }
}
