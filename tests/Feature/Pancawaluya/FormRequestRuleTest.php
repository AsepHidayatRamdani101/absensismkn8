<?php

namespace Tests\Feature\Pancawaluya;

use App\Http\Requests\Pancawaluya\StoreRewardCategoryRequest;
use App\Http\Requests\Pancawaluya\StoreRewardItemRequest;
use App\Http\Requests\Pancawaluya\StoreViolationCategoryRequest;
use App\Http\Requests\Pancawaluya\StoreViolationItemRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FormRequestRuleTest extends TestCase
{
    #[Test]
    public function reward_category_request_contains_required_rules(): void
    {
        $rules = (new StoreRewardCategoryRequest())->rules();

        $this->assertArrayHasKey('code', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('is_active', $rules);
    }

    #[Test]
    public function violation_category_request_contains_required_rules(): void
    {
        $rules = (new StoreViolationCategoryRequest())->rules();

        $this->assertArrayHasKey('code', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('is_active', $rules);
    }

    #[Test]
    public function reward_request_contains_mapping_fields(): void
    {
        $rules = (new StoreRewardItemRequest())->rules();

        $this->assertArrayHasKey('character_dimension_id', $rules);
        $this->assertArrayHasKey('weight', $rules);
    }

    #[Test]
    public function violation_request_contains_mapping_fields(): void
    {
        $rules = (new StoreViolationItemRequest())->rules();

        $this->assertArrayHasKey('character_dimension_id', $rules);
        $this->assertArrayHasKey('weight', $rules);
    }
}
