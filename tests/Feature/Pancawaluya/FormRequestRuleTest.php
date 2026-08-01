<?php

namespace Tests\Feature\Pancawaluya;

use App\Http\Requests\Pancawaluya\StoreRewardCategoryRequest;
use App\Http\Requests\Pancawaluya\StoreRewardItemRequest;
use App\Http\Requests\Pancawaluya\StoreRewardTransactionRequest;
use App\Http\Requests\Pancawaluya\StoreViolationTransactionRequest;
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

    #[Test]
    public function reward_transaction_request_contains_operational_fields(): void
    {
        $rules = (new StoreRewardTransactionRequest())->rules();

        $this->assertArrayHasKey('academic_year_id', $rules);
        $this->assertArrayHasKey('student_id', $rules);
        $this->assertArrayHasKey('reward_item_id', $rules);
        $this->assertArrayHasKey('classroom_id', $rules);
        $this->assertArrayHasKey('attachment', $rules);
    }

    #[Test]
    public function violation_transaction_request_contains_operational_fields(): void
    {
        $rules = (new StoreViolationTransactionRequest())->rules();

        $this->assertArrayHasKey('academic_year_id', $rules);
        $this->assertArrayHasKey('student_id', $rules);
        $this->assertArrayHasKey('violation_item_id', $rules);
        $this->assertArrayHasKey('classroom_id', $rules);
        $this->assertArrayHasKey('evidence_photo', $rules);
    }
}
