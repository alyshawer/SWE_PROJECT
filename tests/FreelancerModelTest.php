<?php
// TEST SUMMARY:
// This file tests FreelancerModel:
// - Creating freelancer profile and verifying getters/fields
use PHPUnit\Framework\TestCase;

class FreelancerModelTest extends TestCase
{
    public function testCreateAndGetters()
    {
        $model = new FreelancerModel(1, 'user1', 'user1@example.com', 'secret', 'freelancer', 1, 'php,js', 1000, 5, 20, 'http://example.com', 'bio', 'available');
        $this->assertEquals('php,js', $model->getSkills());
        $this->assertEquals(1000, $model->getTotalEarned());
        $this->assertEquals('available', $model->getAvailability());
    }
}
