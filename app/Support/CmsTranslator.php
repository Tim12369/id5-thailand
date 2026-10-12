<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Translation\Translator;

/**
 * Laravel's translator, with the wording editors changed in WordPress layered
 * over the language files.
 *
 * The layering happens once, when a language file is loaded, so everything the
 * translator normally does — placeholders, lists, falling back to another
 * language — keeps working on the changed lines exactly as on the built-in
 * ones. Vendor packages and JSON translations are left alone.
 */
class CmsTranslator extends Translator
{
    public function load($namespace, $group, $locale)
    {
        if ($this->isLoaded($namespace, $group, $locale)) {
            return;
        }

        parent::load($namespace, $group, $locale);

        if ($namespace !== '*' || $group === '*') {
            return;
        }

        foreach (CmsText::forGroup($locale, $group) as $item => $text) {
            Arr::set($this->loaded[$namespace][$group][$locale], $item, $text);
        }
    }

    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        $line = parent::get($key, $replace, $locale, $fallback);

        // Edit mode only: remember which line this is, so a click on it can
        // open the right form.
        return CmsText::tagging() ? CmsText::tagLine((string) $key, $line) : $line;
    }
}
