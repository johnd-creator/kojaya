<?php

namespace Database\Factories;

use App\Models\DocumentationArticle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DocumentationArticle>
 */
class DocumentationArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->bothify('###'),
            'title' => rtrim($title, '.'),
            'summary' => fake()->paragraph(),
            'body_markdown' => implode("\n\n", [
                '# '.rtrim($title, '.'),
                '',
                fake()->paragraph(),
                '## Prosedur',
                '1. '.fake()->sentence(),
                '2. '.fake()->sentence(),
            ]),
            'target_role' => 'all',
            'required_permissions' => null,
            'category' => 'Umum',
            'sort_order' => 0,
            'published_at' => now(),
        ];
    }

    public function forRole(string $role): static
    {
        return $this->state(fn () => ['target_role' => $role]);
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['published_at' => null]);
    }

    public function withPermission(string ...$permissions): static
    {
        return $this->state(fn () => ['required_permissions' => array_values($permissions)]);
    }
}
