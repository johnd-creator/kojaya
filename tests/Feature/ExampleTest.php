<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use DatabaseMigrations;

    public function test_returns_a_successful_response()
    {
        $response = $this->get(route('home'));

        $response->assertRedirect('/login');
    }
}
