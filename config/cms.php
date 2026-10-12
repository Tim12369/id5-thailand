<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WordPress connection
    |--------------------------------------------------------------------------
    |
    | The public site never calls WordPress while rendering a page — content is
    | synced into this application's own tables. These credentials are used only
    | by the sync (webhook handler, `cms:pull`, `cms:push`) and must therefore
    | never be exposed to the browser or compiled into frontend assets.
    |
    | The password is a WordPress *Application Password*, not an account
    | password: it can be revoked on its own and carries only that user's
    | capabilities.
    |
    */

    'enabled' => env('CMS_ENABLED', false),

    'base_url' => rtrim((string) env('CMS_WORDPRESS_URL', ''), '/'),

    'auth' => [
        'username' => env('CMS_WORDPRESS_USER'),
        'password' => env('CMS_WORDPRESS_APP_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP behaviour
    |--------------------------------------------------------------------------
    |
    | Deliberately short. A sync that cannot finish quickly is better retried by
    | the scheduler than left holding a request open.
    |
    */

    'timeout'       => (int) env('CMS_TIMEOUT', 10),
    'connect_timeout' => (int) env('CMS_CONNECT_TIMEOUT', 5),
    'retries'       => (int) env('CMS_RETRIES', 2),
    'retry_delay_ms' => (int) env('CMS_RETRY_DELAY_MS', 500),
    'verify_tls'    => (bool) env('CMS_VERIFY_TLS', true),

    /*
    |--------------------------------------------------------------------------
    | Inbound webhook
    |--------------------------------------------------------------------------
    |
    | WordPress signs each notification with HMAC-SHA256 over
    | "<timestamp>\n<body>". The shared secret must match the one stored in
    | WordPress under Settings > การเชื่อมต่อเว็บไซต์ ID5.
    |
    | `tolerance` is the replay window in seconds.
    |
    */

    'webhook' => [
        'secret'    => env('CMS_WEBHOOK_SECRET'),
        'tolerance' => (int) env('CMS_WEBHOOK_TOLERANCE', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | How an incoming change is applied
    |--------------------------------------------------------------------------
    |
    | "after_response" (default): the web process that received the webhook
    | applies the change itself, straight after replying to WordPress. No queue
    | worker is needed for edits to appear.
    |
    | "queue": the change is handed to the queue and a worker applies it. Use
    | this only where a supervised `queue:work` process is guaranteed to be
    | running; if it stops, edits stop appearing until the hourly reconcile.
    |
    */

    'sync' => [
        'mode' => env('CMS_SYNC_MODE', 'after_response'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Media mirroring
    |--------------------------------------------------------------------------
    |
    | Images referenced by WordPress content are copied into this application's
    | own public disk, so the public site keeps serving its own assets and does
    | not hotlink the editing install. Files are addressed by a hash of their
    | source URL, which makes re-syncing idempotent.
    |
    */

    'media' => [
        'disk'            => env('CMS_MEDIA_DISK', 'public'),
        'directory'       => 'cms',
        'private_disk'    => env('CMS_MEDIA_PRIVATE_DISK', 'local'),
        'private_directory' => 'cms-private',
        'max_bytes'       => (int) env('CMS_MEDIA_MAX_BYTES', 26214400), // 25 MB
        'allowed_mime'    => [
            'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml',
            'application/pdf',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WordPress post type -> sync mapper
    |--------------------------------------------------------------------------
    |
    | The one place that says which class owns which content type. Mirrors
    | wordpress/plugins/id5-cms/includes/class-id5-schema.php.
    |
    */

    'mappers' => [
        'post'              => \App\Services\Cms\Mappers\ArticleMapper::class,
        'id5_announcement'  => \App\Services\Cms\Mappers\ArticleMapper::class,
        'id5_alert'         => \App\Services\Cms\Mappers\ArticleMapper::class,
        'id5_knowledge'     => \App\Services\Cms\Mappers\ArticleMapper::class,
        'id5_faq'           => \App\Services\Cms\Mappers\FaqMapper::class,
        'id5_form'          => \App\Services\Cms\Mappers\FormMapper::class,
        'id5_office'        => \App\Services\Cms\Mappers\OfficeMapper::class,
        'id5_person'        => \App\Services\Cms\Mappers\PersonMapper::class,
        'id5_ita_card'      => \App\Services\Cms\Mappers\ItaCardMapper::class,
        'id5_division_card' => \App\Services\Cms\Mappers\DivisionCardMapper::class,
        'id5_guide_topic'   => \App\Services\Cms\Mappers\GuideTopicMapper::class,
        'id5_guide_content' => \App\Services\Cms\Mappers\GuideContentMapper::class,
        'id5_assessment'    => \App\Services\Cms\Mappers\AssessmentMapper::class,
    ],

];
