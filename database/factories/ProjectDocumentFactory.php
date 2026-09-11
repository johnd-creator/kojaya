<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectDocument>
 */
class ProjectDocumentFactory extends Factory
{
    protected $model = ProjectDocument::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'project_id' => Project::factory(),
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(['SIKA', 'PERMIT', 'DRAWING', 'OTHER']),
            'file_path' => 'project-documents/'.fake()->uuid().'.pdf',
            'expiry_date' => now()->addDays(30)->toDateString(),
            'status' => 'VALID',
        ];
    }
}
