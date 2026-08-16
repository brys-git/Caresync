<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class ClientRegistrationControllerTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    public function testPlanRegistrationRouteRequiresAuthentication(): void
    {
        $result = $this->get('/plan-registration');

        $result->assertStatus(302);
        $result->assertRedirect();
    }
}
