<?php

/**
 * Guided Assessment — status definitions & scoring signals (LOGIC, in code).
 *
 * The ~18 candidate immigration "audiences"/statuses the engine can infer.
 * Adding a future status = add one entry here (that's the whole extensibility
 * story). NONE of this is legal advice — it only ranks likely categories so we
 * can show relevant guidance; the result always carries the mandatory disclaimer.
 *
 * Each status:
 *   label     localized display name
 *   icon      Tabler icon (icon font — safe from Tailwind purge)
 *   color     accent token (from a fixed safelist, see AssessmentResult)
 *   persona   maps to an existing SmartGuide persona for the deep-dive links
 *   topics    existing GuideTopic slugs surfaced under "Next steps"
 *   signals   map of answerKey => [answerValue => weight]. StatusInferenceEngine
 *             sums the weight whenever the user's answer for answerKey matches
 *             answerValue (for multi-select answers, when the value is present).
 *             Highest total wins; ties / low totals fall back to "not sure".
 *
 * The human-readable RESULT CONTENT (legal basis text, risk level, allowed /
 * restricted activities, notices) is NOT here — it lives in the DB
 * (assessment_recommendations), admin-editable, per the Hybrid design.
 */

return [

    'thai_citizen' => [
        'label' => ['th' => 'คนไทย', 'en' => 'Thai Citizen'],
        'icon' => 'ti-flag',
        'color' => 'red',
        'persona' => 'thai_citizen',
        'topics' => ['tm30', '90day'],
        'signals' => [
            'who_are_you' => ['thai_citizen' => 10],
            'unsure_nationality' => ['yes' => 6],
        ],
    ],

    'tourist' => [
        'label' => ['th' => 'นักท่องเที่ยว', 'en' => 'Tourist'],
        'icon' => 'ti-beach',
        'color' => 'blue',
        'persona' => 'tourist',
        'topics' => ['visa', 'tm30', 'extension'],
        'signals' => [
            'purpose' => ['tourism' => 6],
            'legal_basis' => ['has_visa' => 2],
            'who_are_you' => ['foreign_national' => 1],
            // "Not sure" screener contributions
            'unsure_activity' => ['visit' => 5],
            'unsure_duration' => ['short' => 3],
            'unsure_has_passport' => ['yes' => 1],
        ],
    ],

    'visa_exemption' => [
        'label' => ['th' => 'ผู้ได้รับยกเว้นวีซ่า', 'en' => 'Visa Exemption'],
        'icon' => 'ti-world-check',
        'color' => 'sky',
        'persona' => 'tourist',
        'topics' => ['entry', 'extension', 'tm30'],
        'signals' => [
            'legal_basis' => ['visa_exemption' => 7],
            'purpose' => ['tourism' => 3, 'transit' => 2],
        ],
    ],

    'visa_on_arrival' => [
        'label' => ['th' => 'ผู้ใช้ Visa on Arrival', 'en' => 'Visa on Arrival'],
        'icon' => 'ti-plane-arrival',
        'color' => 'cyan',
        'persona' => 'tourist',
        'topics' => ['entry', 'extension'],
        'signals' => [
            'legal_basis' => ['visa_on_arrival' => 8],
            'purpose' => ['tourism' => 2],
        ],
    ],

    'student' => [
        'label' => ['th' => 'นักเรียน / นักศึกษา', 'en' => 'Student'],
        'icon' => 'ti-school',
        'color' => 'purple',
        'persona' => 'student',
        'topics' => ['extension', '90day', 'tm30'],
        'signals' => [
            'purpose' => ['study' => 8, 'research' => 3],
            'unsure_activity' => ['study' => 6],
        ],
    ],

    'worker' => [
        'label' => ['th' => 'ผู้ทำงาน', 'en' => 'Worker'],
        'icon' => 'ti-briefcase',
        'color' => 'teal',
        'persona' => 'worker',
        'topics' => ['work_permit', 'extension', '90day'],
        'signals' => [
            'purpose' => ['work' => 7, 'professional' => 5, 'migrant_worker' => 4],
            'actions' => ['work_permit' => 4, 'change_employer' => 2],
            'unsure_activity' => ['work' => 6],
        ],
    ],

    'business' => [
        'label' => ['th' => 'ผู้ประกอบธุรกิจ', 'en' => 'Business'],
        'icon' => 'ti-building-store',
        'color' => 'indigo',
        'persona' => 'employer',
        'topics' => ['work_permit', 'extension'],
        'signals' => [
            'purpose' => ['business' => 7],
        ],
    ],

    'investor' => [
        'label' => ['th' => 'นักลงทุน', 'en' => 'Investor'],
        'icon' => 'ti-chart-line',
        'color' => 'amber',
        'persona' => 'investor',
        'topics' => ['investor_visa', 'extension'],
        'signals' => [
            'purpose' => ['investment' => 8],
        ],
    ],

    'family' => [
        'label' => ['th' => 'ครอบครัว / ผู้ติดตาม', 'en' => 'Family / Dependent'],
        'icon' => 'ti-users-group',
        'color' => 'rose',
        'persona' => 'family',
        'topics' => ['extension', 'marriage_visa', '90day'],
        'signals' => [
            'purpose' => ['family' => 7, 'marriage' => 6],
            'actions' => ['register_marriage' => 3, 'family_birth' => 2],
            'unsure_activity' => ['live' => 2],
        ],
    ],

    'retirement' => [
        'label' => ['th' => 'ผู้เกษียณอายุ', 'en' => 'Retirement'],
        'icon' => 'ti-armchair',
        'color' => 'orange',
        'persona' => 'retiree',
        'topics' => ['extension', '90day', 'tm30'],
        'signals' => [
            'purpose' => ['retirement' => 8],
            'unsure_activity' => ['live' => 3],
            'unsure_duration' => ['long' => 2],
        ],
    ],

    'permanent_resident' => [
        'label' => ['th' => 'ผู้มีถิ่นที่อยู่ถาวร', 'en' => 'Permanent Resident'],
        'icon' => 'ti-home-check',
        'color' => 'emerald',
        'persona' => 'general',
        'topics' => ['extension'],
        'signals' => [
            'legal_basis' => ['permanent_residence' => 9, 'applying_pr' => 4],
            'purpose' => ['applying_pr' => 4],
            'actions' => ['permanent_residence' => 3],
        ],
    ],

    'stateless' => [
        'label' => ['th' => 'บุคคลไร้สัญชาติ', 'en' => 'Stateless'],
        'icon' => 'ti-id-off',
        'color' => 'slate',
        'persona' => 'general',
        'topics' => [],
        'signals' => [
            'who_are_you' => ['stateless' => 10],
            'unsure_nationality' => ['unsure' => 3],
        ],
    ],

    'refugee_protection' => [
        'label' => ['th' => 'ผู้ขอรับความคุ้มครอง', 'en' => 'Refugee / Protection'],
        'icon' => 'ti-shield-heart',
        'color' => 'violet',
        'persona' => 'general',
        'topics' => [],
        'signals' => [
            'purpose' => ['protection' => 8],
            'legal_basis' => ['special_permission' => 2],
        ],
    ],

    'representative' => [
        'label' => ['th' => 'ผู้ดำเนินการแทน', 'en' => 'Representative'],
        'icon' => 'ti-users',
        'color' => 'blue',
        'persona' => 'general',
        'topics' => [],
        'signals' => [
            'who_are_you' => ['representative' => 10],
        ],
    ],

    'employer' => [
        'label' => ['th' => 'นายจ้าง', 'en' => 'Employer'],
        'icon' => 'ti-briefcase-2',
        'color' => 'indigo',
        'persona' => 'employer',
        'topics' => ['work_permit', 'tm30'],
        'signals' => [
            'thai_role' => ['employer' => 9],
            'represent_relationship' => ['employer_of' => 6],
        ],
    ],

    'landlord' => [
        'label' => ['th' => 'เจ้าของที่พัก', 'en' => 'Landlord'],
        'icon' => 'ti-home',
        'color' => 'amber',
        'persona' => 'hotel',
        'topics' => ['tm30'],
        'signals' => [
            'thai_role' => ['landlord' => 9],
            'actions' => ['report_tm30' => 4],
        ],
    ],

    'embassy_consular' => [
        'label' => ['th' => 'สถานทูต / กงสุล', 'en' => 'Embassy / Consular'],
        'icon' => 'ti-building-bank',
        'color' => 'slate',
        'persona' => 'officer',
        'topics' => ['entry'],
        'signals' => [
            'purpose' => ['diplomatic' => 8, 'official' => 4],
            'actions' => ['contact_embassy' => 3],
        ],
    ],

    'emergency' => [
        'label' => ['th' => 'ต้องการความช่วยเหลือฉุกเฉิน', 'en' => 'Emergency Assistance'],
        'icon' => 'ti-urgent',
        'color' => 'red',
        'persona' => 'general',
        'topics' => ['overstay'],
        'signals' => [
            'foreign_location' => ['in_detention' => 6],
            'legal_basis' => ['awaiting_removal' => 6, 'revoked' => 4, 'overstay' => 4, 'entered_without_records' => 3],
            'actions' => ['emergency' => 8, 'overstay_help' => 4, 'lost_passport' => 2, 'legal_assistance' => 2],
        ],
    ],

];
