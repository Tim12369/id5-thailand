<?php

namespace Tests\Feature\Cms;

use App\Jobs\Cms\SyncCmsChange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The webhook is the only way into this application from WordPress, so its
 * authentication is the boundary that matters most.
 */
class WebhookSignatureTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-secret-value-not-used-anywhere-real';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cms.enabled'             => true,
            'cms.webhook.secret'      => self::SECRET,
            'cms.webhook.tolerance'   => 300,
            'cms.sync.mode'           => 'queue',
            'cms.mappers.id5_faq'     => \App\Services\Cms\Mappers\FaqMapper::class,
        ]);

        Queue::fake();
    }

    /**
     * Send a request, signing it the same way the WordPress plugin does.
     */
    private function send(array $payload, ?string $signature = null, ?int $timestamp = null)
    {
        $body      = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $timestamp ??= time();
        $signature ??= hash_hmac('sha256', $timestamp."\n".$body, self::SECRET);

        return $this->call(
            'POST',
            '/cms/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE'         => 'application/json',
                'HTTP_X_ID5_TIMESTAMP' => (string) $timestamp,
                'HTTP_X_ID5_SIGNATURE' => $signature,
            ],
            $body
        );
    }

    #[Test]
    public function it_accepts_a_correctly_signed_notification(): void
    {
        $this->send(['event' => 'post.updated', 'post_type' => 'id5_faq', 'post_id' => 42])
            ->assertOk()
            ->assertJson(['status' => 'queued']);

        Queue::assertPushed(
            SyncCmsChange::class,
            fn (SyncCmsChange $job) => $job->event === 'post.updated'
                && $job->postType === 'id5_faq'
                && $job->postId === 42
        );
    }

    #[Test]
    public function by_default_the_change_is_applied_right_after_replying_with_no_queue_worker(): void
    {
        config([
            'cms.sync.mode'     => 'after_response',
            'cms.base_url'      => 'https://cms.test',
            'cms.auth.username' => 'sync',
            'cms.auth.password' => 'app-password',
            'cms.retries'       => 0,
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'cms.test/wp-json/id5/v1/post/42' => \Illuminate\Support\Facades\Http::response([
                'wp_post_id' => 42,
                'post_type'  => 'id5_faq',
                'title'      => 'ต้องแจ้ง ตม.30 ภายในกี่วัน?',
                'content'    => '<p>ภายใน 24 ชั่วโมง</p>',
                'status'     => 'published',
                'menu_order' => 1,
                'image'      => null,
                'fields'     => [],
                'terms'      => [],
            ]),
        ]);

        $response = $this->send(['event' => 'post.updated', 'post_type' => 'id5_faq', 'post_id' => 42]);

        $response->assertOk()->assertJson(['status' => 'accepted']);
        $this->assertSame((string) strlen($response->getContent()), $response->headers->get('Content-Length'));

        // Nothing went to the queue, and the content is already in place.
        Queue::assertNothingPushed();
        $this->assertSame('ต้องแจ้ง ตม.30 ภายในกี่วัน?', \App\Models\Faq::firstWhere('wp_post_id', 42)?->question_th);
    }

    #[Test]
    public function it_rejects_a_request_with_no_signature(): void
    {
        $this->postJson('/cms/webhook', ['event' => 'settings.updated'])
            ->assertUnauthorized();

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_rejects_a_signature_made_with_the_wrong_secret(): void
    {
        $timestamp = time();
        $body      = json_encode(['event' => 'settings.updated']);

        $this->send(
            ['event' => 'settings.updated'],
            hash_hmac('sha256', $timestamp."\n".$body, 'the-wrong-secret'),
            $timestamp
        )->assertUnauthorized();

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_rejects_a_body_that_was_changed_after_signing(): void
    {
        $timestamp = time();
        $signed    = json_encode(['event' => 'settings.updated']);
        $signature = hash_hmac('sha256', $timestamp."\n".$signed, self::SECRET);

        // Same signature, different body.
        $this->send(['event' => 'post.deleted', 'post_type' => 'id5_faq', 'post_id' => 1], $signature, $timestamp)
            ->assertUnauthorized();

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_rejects_a_replayed_request_from_outside_the_time_window(): void
    {
        $this->send(['event' => 'settings.updated'], null, time() - 3600)
            ->assertUnauthorized();

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_refuses_to_run_at_all_when_no_secret_is_configured(): void
    {
        config(['cms.webhook.secret' => null]);

        $this->postJson('/cms/webhook', ['event' => 'settings.updated'])
            ->assertStatus(503);
    }

    #[Test]
    public function it_ignores_an_event_it_does_not_understand(): void
    {
        $this->send(['event' => 'something.invented'])
            ->assertOk()
            ->assertJson(['status' => 'ignored']);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_ignores_a_post_type_this_application_does_not_map(): void
    {
        $this->send(['event' => 'post.updated', 'post_type' => 'wp_block', 'post_id' => 9])
            ->assertOk()
            ->assertJson(['status' => 'ignored']);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_rejects_a_post_event_with_no_post_id(): void
    {
        $this->send(['event' => 'post.updated', 'post_type' => 'id5_faq'])
            ->assertStatus(422);

        Queue::assertNothingPushed();
    }
}
