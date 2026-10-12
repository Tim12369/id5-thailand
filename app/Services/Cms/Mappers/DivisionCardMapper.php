<?php

namespace App\Services\Cms\Mappers;

use App\Models\HomeDivisionCard;
use Illuminate\Database\Eloquent\Model;

/**
 * The six cards in the homepage "Explore ID5 Services" grid.
 *
 * `key` decides which public route a card links to, so it is chosen from a
 * fixed list in WordPress rather than typed, and the matching row is found by
 * that key. An editor can freely change a card's wording and photo; they cannot
 * accidentally point one at a route that does not exist.
 */
class DivisionCardMapper extends Mapper
{
    public function upsert(array $payload): ?Model
    {
        $key = $this->field($payload, 'card_key');

        if (! $key) {
            return null;
        }

        $card = HomeDivisionCard::firstOrNew(['key' => $key]);

        if (! $this->mayWrite($card->wp_post_id, $payload)) {
            return null;
        }

        $card->fill([
            'key'        => $key,
            'title_th'   => $payload['title'] ?: $card->title_th ?: '',
            'title_en'   => $this->field($payload, 'title_en'),
            'desc_th'    => $this->field($payload, 'desc_th'),
            'desc_en'    => $this->field($payload, 'desc_en'),
            'sort_order' => $payload['menu_order'],
        ]);

        $card->photo_path = $this->media->mirror($payload['image'] ?? null, $card->photo_path);

        $card->wp_post_id    = $payload['wp_post_id'];
        $card->cms_synced_at = now();
        $card->save();

        return $card;
    }

    /**
     * Never removed. The homepage grid renders all six positions, so deleting a
     * row would leave a visible hole; WordPress also blocks deletion of this
     * post type for everyone but an administrator.
     */
    public function delete(int $wpPostId): void
    {
        //
    }
}
