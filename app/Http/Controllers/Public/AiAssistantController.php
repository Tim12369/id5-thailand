<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Services\Ai\AiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiAssistantController extends Controller
{
    /** Personas that have their own starter questions. */
    private const SUGGESTION_PERSONAS = ['tourist', 'student', 'worker', 'investor', 'hotel', 'employer', 'thai_citizen', 'officer', 'retiree', 'family'];

    public function __construct(
        private readonly AiAssistantService $assistant
    ) {}

    /**
     * Start or resume a conversation session.
     */
    public function startConversation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'persona' => 'nullable|string|max:50',
            'locale'  => 'nullable|string|in:th,en,zh',
        ]);

        $persona = $validated['persona'] ?? 'general';
        $locale  = $validated['locale'] ?? app()->getLocale();

        $conversation = AiConversation::create([
            'session_id'  => Str::random(40),
            'persona'     => $persona,
            'locale'      => $locale,
            'ip_address'  => $request->ip(),
            'user_agent'  => substr($request->userAgent() ?? '', 0, 500),
        ]);

        return response()->json([
            'conversation_id'    => $conversation->id,
            'suggested_questions' => $this->getSuggestions($persona, $locale),
        ]);
    }

    /**
     * Process a chat message.
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => 'required|integer|exists:ai_conversations,id',
            'message'         => 'required|string|max:500',
        ]);

        $conversation = AiConversation::findOrFail($validated['conversation_id']);

        // Guard: max 50 messages per conversation to prevent abuse
        if ($conversation->message_count >= 50) {
            return response()->json([
                'error' => __('ai.max_messages_reached'),
            ], 429);
        }

        // Update persona/locale if changed (user may have switched)
        $newPersona = $request->input('persona');
        $newLocale  = $request->input('locale');
        if ($newPersona && $newPersona !== $conversation->persona) {
            $conversation->update(['persona' => $newPersona]);
        }
        if ($newLocale && $newLocale !== $conversation->locale) {
            $conversation->update(['locale' => $newLocale]);
        }

        $response = $this->assistant->chat($conversation, $validated['message']);

        return response()->json($response);
    }

    /**
     * Return suggested questions for a given persona in the current locale.
     */
    public function suggestedQuestions(string $persona): JsonResponse
    {
        $locale = app()->getLocale();
        return response()->json([
            'questions' => $this->getSuggestions($persona, $locale),
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    /**
     * The three starter questions for a persona. The wording lives in the
     * language files (`ai.suggestions_<persona>`), where editors can change
     * it from WordPress; an unknown persona gets the tourist's questions.
     */
    private function getSuggestions(string $persona, string $locale): array
    {
        $locale  = in_array($locale, ['th', 'en', 'zh'], true) ? $locale : 'th';
        $persona = in_array($persona, self::SUGGESTION_PERSONAS, true) ? $persona : 'tourist';

        return array_values((array) __('ai.suggestions_'.$persona, [], $locale));
    }
}
