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

    private function getSuggestions(string $persona, string $locale): array
    {
        $questions = [
            'tourist' => [
                'th' => ['ฉันต้องขอวีซ่าก่อนเดินทางมาไหม?', 'ขอต่ออายุวีซ่าทำอย่างไร?', 'ถ้าอยู่เกินกำหนดจะเกิดอะไรขึ้น?'],
                'en' => ['Do I need a visa to enter Thailand?', 'How do I extend my tourist visa?', 'What happens if I overstay my visa?'],
                'zh' => ['我需要签证才能入境泰国吗？', '如何延长旅游签证？', '逾期留泰会怎样？'],
            ],
            'student' => [
                'th' => ['วีซ่านักเรียนขอที่ไหน?', 'ต้องใช้เอกสารอะไรบ้าง?', 'วีซ่านักเรียนอยู่ได้นานแค่ไหน?'],
                'en' => ['How do I apply for a student visa?', 'What documents do I need?', 'How long can I stay on a student visa?'],
                'zh' => ['如何申请学生签证？', '需要哪些文件？', '学生签证可以待多久？'],
            ],
            'worker' => [
                'th' => ['ขอใบอนุญาตทำงานต้องทำอย่างไร?', 'นายจ้างมีหน้าที่อะไรบ้าง?', 'วีซ่าทำงานแตกต่างกันอย่างไร?'],
                'en' => ['How do I get a work permit?', 'What are my employer\'s obligations?', 'What work visa types are available?'],
                'zh' => ['如何申请工作许可证？', '雇主有什么义务？', '有哪些工作签证类型？'],
            ],
            'investor' => [
                'th' => ['วีซ่านักลงทุน (Non-B) คืออะไร?', 'BOI ช่วยอะไรได้บ้าง?', 'ต้องลงทุนขั้นต่ำเท่าไหร่?'],
                'en' => ['What is a Non-B investor visa?', 'How can BOI help me?', 'What is the minimum investment required?'],
                'zh' => ['什么是非移民B类投资签证？', 'BOI能为我做什么？', '最低投资额是多少？'],
            ],
            'hotel' => [
                'th' => ['ยื่น TM30 ทำอย่างไร?', 'ต้องแจ้งภายในกี่วัน?', 'บทลงโทษถ้าไม่แจ้งคืออะไร?'],
                'en' => ['How do I submit TM30?', 'Within how many days must I report?', 'What are the penalties for not filing TM30?'],
                'zh' => ['如何提交TM30？', '必须在几天内报告？', '不提交TM30的处罚是什么？'],
            ],
            'employer' => [
                'th' => ['จ้างแรงงานต่างด้าวต้องทำอะไรบ้าง?', 'ต้องใช้เอกสารอะไร?', 'โควต้าการจ้างงานคืออะไร?'],
                'en' => ['What do I need to hire a foreign worker?', 'What documents are required?', 'What is the employment quota?'],
                'zh' => ['聘用外国劳工需要做什么？', '需要哪些文件？', '雇用配额是什么？'],
            ],
            'thai_citizen' => [
                'th' => ['ช่วยให้คู่สมรสชาวต่างชาติอยู่ในไทยได้อย่างไร?', 'วีซ่าแต่งงานขอยังไง?', 'บุตรต่างชาติต้องขอวีซ่าไหม?'],
                'en' => ['How can I sponsor my foreign spouse?', 'How do I apply for a marriage visa?', 'Does my foreign child need a visa?'],
                'zh' => ['如何为外国配偶申请签证？', '如何申请结婚签证？', '我的外国子女需要签证吗？'],
            ],
            'officer' => [
                'th' => ['ขั้นตอน SOP การตรวจสอบผู้เดินทางคืออะไร?', 'บทลงโทษการอยู่เกินกำหนดมีอะไรบ้าง?', 'แบบฟอร์มไหนใช้กับกรณีนี้?'],
                'en' => ['What is the SOP for traveler screening?', 'What are overstay penalties?', 'Which form applies to this case?'],
                'zh' => ['旅客审查标准操作程序是什么？', '逾期留泰的处罚是什么？', '此案例适用哪种表格？'],
            ],
            'retiree' => [
                'th' => ['วีซ่าผู้เกษียณ (Non-OA) คืออะไร?', 'ต้องมีเงินในบัญชีเท่าไหร่?', 'ต่ออายุวีซ่าผู้เกษียณทำอย่างไร?'],
                'en' => ['What is a retirement visa (Non-OA)?', 'How much money must I have in my account?', 'How do I renew my retirement visa?'],
                'zh' => ['什么是退休签证（非OA类）？', '账户里需要多少钱？', '如何续签退休签证？'],
            ],
            'family' => [
                'th' => ['วีซ่าครอบครัว (Non-O) ขอยังไง?', 'ต้องใช้เอกสารอะไรบ้าง?', 'เด็กต้องทำวีซ่าไหม?'],
                'en' => ['How do I apply for a family visa (Non-O)?', 'What documents do I need?', 'Does my child need a visa?'],
                'zh' => ['如何申请家庭签证（非O类）？', '需要哪些文件？', '我的孩子需要签证吗？'],
            ],
        ];

        $localeKey = in_array($locale, ['th', 'en', 'zh']) ? $locale : 'th';
        return $questions[$persona][$localeKey]
            ?? $questions['tourist'][$localeKey]
            ?? ['ฉันต้องการความช่วยเหลือเรื่องวีซ่า', 'ขอต่ออายุวีซ่าทำอย่างไร?', 'ต้องใช้เอกสารอะไรบ้าง?'];
    }
}
