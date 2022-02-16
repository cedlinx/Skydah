<?php

namespace Tests\Feature;

<<<<<<< HEAD
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
=======
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
>>>>>>> 47aa9433f9855cae6ba9aa220237884821178799

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
<<<<<<< HEAD
    public function test_example()
=======
    public function testBasicTest()
>>>>>>> 47aa9433f9855cae6ba9aa220237884821178799
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
