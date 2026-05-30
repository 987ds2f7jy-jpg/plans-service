<?php

namespace Tests\Feature;

use App\Models\SubscriptionScore;
use App\Services\Payment\ExternalPaymentApiServiceInterface;
use App\Services\Payment\PaymentServiceInterface;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class ExternalPlanActivationTest extends TestCase
{
    use RefreshDatabase;

    private string $apiKey = 'test-internal-key';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.internal_api.key' => $this->apiKey]);

        $this->seed(DatabaseSeeder::class);
    }

    public function test_it_requires_internal_api_key(): void
    {
        $this->postJson('/api/plans/external/activate', $this->payload())
            ->assertUnauthorized()
            ->assertJson(['error' => 'unauthorized']);

        $this->withHeader('X-Internal-Api-Key', 'invalid-key')
            ->postJson('/api/plans/external/activate', $this->payload())
            ->assertUnauthorized()
            ->assertJson(['error' => 'unauthorized']);

        $this->assertDatabaseCount('external_plan_activations', 0);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_it_activates_all_supported_external_plan_codes(): void
    {
        foreach ([
            'psychology' => ['plan_id' => 1, 'scores' => 4],
            'weight_loss' => ['plan_id' => 2, 'scores' => 3],
            'family' => ['plan_id' => 3, 'scores' => 1],
        ] as $planCode => $expectations) {
            $response = $this->withHeader('X-Internal-Api-Key', $this->apiKey)
                ->postJson('/api/plans/external/activate', $this->payload(
                    planCode: $planCode,
                    reference: 'pay-' . $planCode,
                ));

            $response
                ->assertCreated()
                ->assertJsonPath('data.plan_code', $planCode)
                ->assertJsonPath('data.external_payment_reference', 'pay-' . $planCode)
                ->assertJsonPath('data.subscription.plan_id', $expectations['plan_id'])
                ->assertJsonPath('data.subscription.status', 1);

            $subscriptionId = $response->json('data.subscription.id');

            $this->assertDatabaseHas('subscriptions', [
                'id' => $subscriptionId,
                'plan_id' => $expectations['plan_id'],
                'external_key' => 'external-user@example.com',
                'status' => 1,
            ]);

            $this->assertSame(
                $expectations['scores'],
                SubscriptionScore::query()->where('subscription_id', $subscriptionId)->count(),
            );
        }

        $this->assertDatabaseCount('external_plan_activations', 3);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_it_is_idempotent_by_external_payment_reference_and_does_not_duplicate_scores(): void
    {
        $this->mock(PaymentServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('initializeSubscriptionPayment');
            $mock->shouldNotReceive('syncPaymentStatus');
        });

        $this->mock(ExternalPaymentApiServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('createPayment');
            $mock->shouldNotReceive('getPayment');
        });

        $payload = $this->payload(planCode: 'psychology', reference: 'pay-idempotent');

        $firstResponse = $this->withHeader('X-Internal-Api-Key', $this->apiKey)
            ->postJson('/api/plans/external/activate', $payload)
            ->assertCreated();

        $secondResponse = $this->withHeader('X-Internal-Api-Key', $this->apiKey)
            ->postJson('/api/plans/external/activate', $payload)
            ->assertOk();

        $subscriptionId = $firstResponse->json('data.subscription.id');

        $this->assertSame($subscriptionId, $secondResponse->json('data.subscription.id'));
        $this->assertDatabaseCount('external_plan_activations', 1);
        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame(4, SubscriptionScore::query()->where('subscription_id', $subscriptionId)->count());
    }

    private function payload(string $planCode = 'psychology', string $reference = 'pay-reference'): array
    {
        return [
            'external_key' => 'external-user@example.com',
            'plan_code' => $planCode,
            'external_payment_reference' => $reference,
            'paid_at' => '2026-05-30T10:00:00-03:00',
            'amount' => 100,
            'currency' => 'BRL',
            'customer' => [
                'email' => 'external-user@example.com',
                'first_name' => 'External',
                'last_name' => 'User',
                'document' => '12345678900',
            ],
            'metadata' => [
                'source' => 'feature-test',
            ],
        ];
    }
}
