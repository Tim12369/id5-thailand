<?php

/**
 * Guided Assessment — question tree (LOGIC, versioned in code per the Hybrid design).
 *
 * This is the single source of truth for the wizard's conditional branching.
 * It is serialized to JSON and handed to a GENERIC Alpine interpreter on the
 * client (resources/js/assessment.js) — there is no hardcoded branching logic
 * in Blade or JS; the interpreter just reads these rules.
 *
 * Each node:
 *   id       unique key; also the answer key stored for that question
 *   stage    1-4 (maps to the four wizard stages)
 *   type     'single' (radio) | 'multi' (checkbox)
 *   title    localized ['th' => .., 'en' => .., 'zh' => ..]
 *   help     optional localized hint
 *   show_if  null = always visible in its stage; otherwise a map of
 *            answerKey => [allowedValues]. The node is visible only when,
 *            for EVERY key, the current answer is one of the allowed values.
 *            This drives progressive disclosure (follow-ups appear as the
 *            user answers) and per-branch questions.
 *   options  [ ['value' => .., 'icon' => 'ti-..', 'label' => [localized]], .. ]
 *
 * Display labels live inline here (not in lang files) because the tree is
 * logic/config, kept editable as one unit — adding a branch = editing this file.
 * Wizard chrome (buttons, stage names, disclaimer) lives in lang/assessment.php.
 */

return [

    // ── Stage 1 — Know Your Status ────────────────────────────────────────
    [
        'id' => 'who_are_you',
        'stage' => 1,
        'type' => 'single',
        'title' => ['th' => 'คุณเป็นใคร?', 'en' => 'Who are you?'],
        'help' => ['th' => 'เลือกข้อที่ตรงกับคุณมากที่สุด', 'en' => 'Choose the option that best describes you.'],
        'show_if' => null,
        'options' => [
            ['value' => 'thai_citizen', 'icon' => 'ti-flag', 'label' => ['th' => 'ฉันเป็นคนไทย', 'en' => 'I am a Thai citizen']],
            ['value' => 'foreign_national', 'icon' => 'ti-world', 'label' => ['th' => 'ฉันเป็นคนต่างชาติ', 'en' => 'I am a foreign national']],
            ['value' => 'stateless', 'icon' => 'ti-id-off', 'label' => ['th' => 'ฉันเป็นบุคคลไร้สัญชาติหรือยังไม่มีสถานะชัดเจน', 'en' => 'I am stateless / my status is unclear']],
            ['value' => 'representative', 'icon' => 'ti-users', 'label' => ['th' => 'ฉันดำเนินการแทนบุคคลอื่น', 'en' => 'I am acting on behalf of someone else']],
            ['value' => 'not_sure', 'icon' => 'ti-help-circle', 'label' => ['th' => 'ฉันไม่แน่ใจว่าตนเองอยู่ในกลุ่มใด', 'en' => "I'm not sure which group I belong to"]],
        ],
    ],

    // Thai citizen branch
    [
        'id' => 'thai_role',
        'stage' => 1,
        'type' => 'single',
        'title' => ['th' => 'คุณติดต่อในฐานะใด?', 'en' => 'In what role are you here?'],
        'help' => ['th' => 'บทบาทของคุณกำหนดว่าคุณต้องดำเนินการหรือแจ้งข้อมูลใดบ้างในฐานะคนไทย', 'en' => 'Your role determines what you may need to file or report as a Thai citizen (e.g. a landlord must report a foreign tenant\'s residence).'],
        'show_if' => ['who_are_you' => ['thai_citizen']],
        'options' => [
            ['value' => 'spouse', 'icon' => 'ti-heart', 'label' => ['th' => 'คู่สมรสของชาวต่างชาติ', 'en' => 'Spouse of a foreign national']],
            ['value' => 'employer', 'icon' => 'ti-briefcase', 'label' => ['th' => 'นายจ้าง', 'en' => 'Employer']],
            ['value' => 'landlord', 'icon' => 'ti-home', 'label' => ['th' => 'เจ้าของที่พัก', 'en' => 'Landlord / accommodation provider']],
            ['value' => 'family', 'icon' => 'ti-users-group', 'label' => ['th' => 'สมาชิกในครอบครัว', 'en' => 'Family member']],
            ['value' => 'representative', 'icon' => 'ti-file-description', 'label' => ['th' => 'ผู้ดำเนินการแทน', 'en' => 'Authorized representative']],
            ['value' => 'government', 'icon' => 'ti-building-bank', 'label' => ['th' => 'หน่วยงานราชการ', 'en' => 'Government contact']],
            ['value' => 'other', 'icon' => 'ti-dots', 'label' => ['th' => 'อื่น ๆ', 'en' => 'Other']],
        ],
    ],

    // Foreign national branch — document + current location
    [
        'id' => 'foreign_document',
        'stage' => 1,
        'type' => 'single',
        'title' => ['th' => 'คุณใช้เอกสารอะไร?', 'en' => 'What document do you hold?'],
        'help' => ['th' => 'ประเภทเอกสารที่คุณถือมีผลต่อสิทธิและขั้นตอนการเข้าเมืองที่ใช้กับคุณ', 'en' => 'The type of document you hold affects which entry rules and procedures apply to you.'],
        'show_if' => ['who_are_you' => ['foreign_national']],
        'options' => [
            ['value' => 'passport', 'icon' => 'ti-passport', 'label' => ['th' => 'หนังสือเดินทาง', 'en' => 'Passport']],
            ['value' => 'travel_document', 'icon' => 'ti-file-text', 'label' => ['th' => 'เอกสารเดินทาง', 'en' => 'Travel document']],
            ['value' => 'border_pass', 'icon' => 'ti-id', 'label' => ['th' => 'หนังสือผ่านแดน', 'en' => 'Border pass']],
            ['value' => 'certificate_of_identity', 'icon' => 'ti-certificate', 'label' => ['th' => 'หนังสือรับรองบุคคล', 'en' => 'Certificate of identity']],
            ['value' => 'expired_document', 'icon' => 'ti-clock-x', 'label' => ['th' => 'เอกสารหมดอายุ', 'en' => 'Expired document']],
            ['value' => 'none', 'icon' => 'ti-ban', 'label' => ['th' => 'ไม่มีเอกสาร', 'en' => 'No document']],
            ['value' => 'not_sure', 'icon' => 'ti-help-circle', 'label' => ['th' => 'ไม่แน่ใจ', 'en' => 'Not sure']],
        ],
    ],
    [
        'id' => 'foreign_location',
        'stage' => 1,
        'type' => 'single',
        'title' => ['th' => 'ขณะนี้คุณอยู่ที่ใด?', 'en' => 'Where are you right now?'],
        'help' => ['th' => 'ตำแหน่งปัจจุบันของคุณช่วยให้เราทราบว่าควรแนะนำขั้นตอนใด และคุณอาจต้องการความช่วยเหลือเร่งด่วนหรือไม่', 'en' => 'Your current location helps us know what to recommend, and whether you may need urgent help.'],
        'show_if' => ['who_are_you' => ['foreign_national']],
        'options' => [
            ['value' => 'outside_thailand', 'icon' => 'ti-plane', 'label' => ['th' => 'อยู่นอกประเทศไทย', 'en' => 'Outside Thailand']],
            ['value' => 'at_checkpoint', 'icon' => 'ti-plane-arrival', 'label' => ['th' => 'อยู่ที่สนามบินหรือด่านตรวจคนเข้าเมือง', 'en' => 'At an airport / border checkpoint']],
            ['value' => 'inside_thailand', 'icon' => 'ti-map-pin', 'label' => ['th' => 'อยู่ในประเทศไทย', 'en' => 'Inside Thailand']],
            ['value' => 'at_office', 'icon' => 'ti-building', 'label' => ['th' => 'อยู่ที่สำนักงานตรวจคนเข้าเมือง', 'en' => 'At an immigration office']],
            ['value' => 'in_detention', 'icon' => 'ti-lock', 'label' => ['th' => 'ถูกควบคุมตัวหรืออยู่ระหว่างรอส่งกลับ', 'en' => 'In detention / awaiting removal']],
        ],
    ],

    // Stateless branch
    [
        'id' => 'stateless_documents',
        'stage' => 1,
        'type' => 'single',
        'title' => ['th' => 'คุณมีเอกสารใดบ้าง?', 'en' => 'What documents do you have?'],
        'help' => ['th' => 'เอกสารที่คุณมีอยู่ตอนนี้ช่วยจำกัดสถานะที่เป็นไปได้ของคุณให้แคบลง', 'en' => 'What documents you already have helps narrow down which status may apply to you.'],
        'show_if' => ['who_are_you' => ['stateless']],
        'options' => [
            ['value' => 'civil_registration', 'icon' => 'ti-clipboard-check', 'label' => ['th' => 'มีการจดทะเบียนราษฎร', 'en' => 'I have civil registration']],
            ['value' => 'birth_certificate', 'icon' => 'ti-file-certificate', 'label' => ['th' => 'มีเฉพาะสูติบัตร', 'en' => 'Birth certificate only']],
            ['value' => 'non_thai_id', 'icon' => 'ti-id', 'label' => ['th' => 'มีบัตรประจำตัวคนซึ่งไม่มีสัญชาติไทย', 'en' => 'Non-Thai identity card']],
            ['value' => 'no_documents', 'icon' => 'ti-ban', 'label' => ['th' => 'ไม่มีเอกสาร', 'en' => 'No documents']],
            ['value' => 'not_sure', 'icon' => 'ti-help-circle', 'label' => ['th' => 'ไม่แน่ใจ', 'en' => 'Not sure']],
        ],
    ],
    [
        'id' => 'stateless_birthplace',
        'stage' => 1,
        'type' => 'single',
        'title' => ['th' => 'คุณเกิดในประเทศไทยหรือไม่?', 'en' => 'Were you born in Thailand?'],
        'help' => ['th' => 'สถานที่เกิดเป็นปัจจัยสำคัญในกฎหมายว่าด้วยสัญชาติและการทะเบียนราษฎรของไทย', 'en' => 'Place of birth is a key factor under Thai nationality and civil registration law.'],
        'show_if' => ['who_are_you' => ['stateless']],
        'options' => [
            ['value' => 'in_thailand', 'icon' => 'ti-map-pin', 'label' => ['th' => 'เกิดในประเทศไทย', 'en' => 'Born in Thailand']],
            ['value' => 'outside', 'icon' => 'ti-plane', 'label' => ['th' => 'เกิดนอกประเทศไทย', 'en' => 'Born outside Thailand']],
            ['value' => 'unknown', 'icon' => 'ti-help-circle', 'label' => ['th' => 'ไม่ทราบ', 'en' => 'Unknown']],
        ],
    ],

    // Representative branch (a privacy notice is shown before these in the UI)
    [
        'id' => 'represent_relationship',
        'stage' => 1,
        'type' => 'single',
        'title' => ['th' => 'คุณมีความสัมพันธ์กับบุคคลนั้นอย่างไร?', 'en' => 'What is your relationship to that person?'],
        'help' => ['th' => 'ข้อมูลของบุคคลที่คุณดำเนินการแทนจะถูกเก็บเป็นความลับ', 'en' => 'Information about the person you represent is kept confidential.'],
        'show_if' => ['who_are_you' => ['representative']],
        'options' => [
            ['value' => 'family_member', 'icon' => 'ti-users-group', 'label' => ['th' => 'สมาชิกในครอบครัว', 'en' => 'Family member']],
            ['value' => 'employer_of', 'icon' => 'ti-briefcase', 'label' => ['th' => 'นายจ้างของบุคคลนั้น', 'en' => 'Their employer']],
            ['value' => 'legal_representative', 'icon' => 'ti-scale', 'label' => ['th' => 'ผู้แทนตามกฎหมาย / ทนายความ', 'en' => 'Legal representative / lawyer']],
            ['value' => 'other', 'icon' => 'ti-dots', 'label' => ['th' => 'อื่น ๆ', 'en' => 'Other']],
        ],
    ],
    [
        'id' => 'represent_authority',
        'stage' => 1,
        'type' => 'single',
        'title' => ['th' => 'คุณได้รับมอบอำนาจให้ดำเนินการแทนหรือไม่?', 'en' => 'Are you authorized to act on their behalf?'],
        'help' => ['th' => 'หน่วยงานราชการมักต้องการหลักฐานว่าคุณมีอำนาจดำเนินการแทนก่อนให้ความช่วยเหลือ', 'en' => 'Government offices usually require proof of authorization before they can assist you on someone else\'s behalf.'],
        'show_if' => ['who_are_you' => ['representative']],
        'options' => [
            ['value' => 'yes_written', 'icon' => 'ti-file-check', 'label' => ['th' => 'ได้รับมอบอำนาจเป็นลายลักษณ์อักษร', 'en' => 'Yes, in writing']],
            ['value' => 'yes_verbal', 'icon' => 'ti-message', 'label' => ['th' => 'ได้รับมอบอำนาจด้วยวาจา', 'en' => 'Yes, verbally']],
            ['value' => 'no', 'icon' => 'ti-x', 'label' => ['th' => 'ยังไม่ได้รับมอบอำนาจ', 'en' => 'Not yet']],
            ['value' => 'applying', 'icon' => 'ti-clock', 'label' => ['th' => 'อยู่ระหว่างดำเนินการ', 'en' => 'Arranging it']],
        ],
    ],
    [
        'id' => 'represent_has_poa',
        'stage' => 1,
        'type' => 'single',
        'title' => ['th' => 'มีหนังสือมอบอำนาจ (Power of Attorney) หรือไม่?', 'en' => 'Do you have a power of attorney?'],
        'help' => ['th' => 'หนังสือมอบอำนาจที่เป็นลายลักษณ์อักษรมักจำเป็นสำหรับธุรกรรมทางราชการหลายประเภท', 'en' => 'A written power of attorney is often required for many kinds of official transactions.'],
        'show_if' => ['who_are_you' => ['representative']],
        'options' => [
            ['value' => 'yes', 'icon' => 'ti-file-certificate', 'label' => ['th' => 'มี', 'en' => 'Yes']],
            ['value' => 'no', 'icon' => 'ti-file-off', 'label' => ['th' => 'ไม่มี', 'en' => 'No']],
            ['value' => 'not_sure', 'icon' => 'ti-help-circle', 'label' => ['th' => 'ไม่แน่ใจ', 'en' => 'Not sure']],
        ],
    ],
    [
        'id' => 'represent_person_status',
        'stage' => 1,
        'type' => 'single',
        'title' => ['th' => 'บุคคลที่คุณดำเนินการแทนเป็นใคร?', 'en' => 'The person you represent is…'],
        'help' => ['th' => 'สถานะของบุคคลที่คุณดำเนินการแทนเป็นตัวกำหนดแนวทางที่เกี่ยวข้อง ไม่ใช่สถานะของคุณเอง', 'en' => 'The status of the person you represent (not your own) determines which guidance is relevant.'],
        'show_if' => ['who_are_you' => ['representative']],
        'options' => [
            ['value' => 'thai', 'icon' => 'ti-flag', 'label' => ['th' => 'คนไทย', 'en' => 'A Thai national']],
            ['value' => 'foreign', 'icon' => 'ti-world', 'label' => ['th' => 'คนต่างชาติ', 'en' => 'A foreign national']],
            ['value' => 'stateless', 'icon' => 'ti-id-off', 'label' => ['th' => 'บุคคลไร้สัญชาติ', 'en' => 'Stateless']],
            ['value' => 'unsure', 'icon' => 'ti-help-circle', 'label' => ['th' => 'ไม่แน่ใจ', 'en' => 'Not sure']],
        ],
    ],

    // Not-sure branch — short screener (max 5 questions) that suggests likely groups
    [
        'id' => 'unsure_nationality',
        'stage' => 1,
        'type' => 'single',
        'title' => ['th' => 'คุณถือสัญชาติไทยหรือไม่?', 'en' => 'Do you hold Thai nationality?'],
        'help' => ['th' => 'นี่คือคำถามแรกในชุดคำถามสั้น ๆ เพื่อช่วยจำกัดกลุ่มที่เป็นไปได้ของคุณให้แคบลง', 'en' => 'This is the first of a short screening set to help narrow down which group you likely belong to.'],
        'show_if' => ['who_are_you' => ['not_sure']],
        'options' => [
            ['value' => 'yes', 'icon' => 'ti-check', 'label' => ['th' => 'ใช่', 'en' => 'Yes']],
            ['value' => 'no', 'icon' => 'ti-x', 'label' => ['th' => 'ไม่ใช่', 'en' => 'No']],
            ['value' => 'unsure', 'icon' => 'ti-help-circle', 'label' => ['th' => 'ไม่แน่ใจ', 'en' => 'Not sure']],
        ],
    ],
    [
        'id' => 'unsure_in_thailand',
        'stage' => 1,
        'type' => 'single',
        'title' => ['th' => 'ขณะนี้คุณอยู่ในประเทศไทยหรือไม่?', 'en' => 'Are you currently in Thailand?'],
        'help' => ['th' => 'สถานที่ของคุณตอนนี้มีผลต่อขั้นตอนที่เกี่ยวข้อง เช่น การขอวีซ่าก่อนเดินทางเทียบกับการดำเนินการขณะอยู่ในประเทศ', 'en' => 'Your current location affects which procedures apply, e.g. applying for a visa in advance versus acting while already in the country.'],
        'show_if' => ['who_are_you' => ['not_sure']],
        'options' => [
            ['value' => 'yes', 'icon' => 'ti-map-pin', 'label' => ['th' => 'อยู่ในประเทศไทย', 'en' => 'Yes, in Thailand']],
            ['value' => 'no', 'icon' => 'ti-plane', 'label' => ['th' => 'อยู่นอกประเทศไทย', 'en' => 'No, outside Thailand']],
        ],
    ],
    [
        'id' => 'unsure_has_passport',
        'stage' => 1,
        'type' => 'single',
        'title' => ['th' => 'คุณมีหนังสือเดินทางหรือเอกสารเดินทางหรือไม่?', 'en' => 'Do you have a passport or travel document?'],
        'help' => ['th' => 'การมีหรือไม่มีเอกสารเดินทางเป็นตัวบ่งชี้สำคัญว่าคุณอยู่ในกลุ่มใด', 'en' => 'Whether or not you hold a travel document is a strong signal for which group you likely fall into.'],
        'show_if' => ['who_are_you' => ['not_sure']],
        'options' => [
            ['value' => 'yes', 'icon' => 'ti-passport', 'label' => ['th' => 'มี', 'en' => 'Yes']],
            ['value' => 'no', 'icon' => 'ti-ban', 'label' => ['th' => 'ไม่มี', 'en' => 'No']],
            ['value' => 'not_sure', 'icon' => 'ti-help-circle', 'label' => ['th' => 'ไม่แน่ใจ', 'en' => 'Not sure']],
        ],
    ],
    [
        'id' => 'unsure_activity',
        'stage' => 1,
        'type' => 'single',
        'title' => ['th' => 'คุณมาประเทศไทยเพื่อสิ่งใดเป็นหลัก?', 'en' => 'What mainly brings you to Thailand?'],
        'help' => ['th' => 'จุดประสงค์หลักของคุณเป็นตัวบ่งชี้ที่ชัดเจนที่สุดอย่างหนึ่งว่าคุณอยู่ในกลุ่มใด', 'en' => 'Your main purpose is one of the clearest signals of which group you likely belong to.'],
        'show_if' => ['who_are_you' => ['not_sure']],
        'options' => [
            ['value' => 'visit', 'icon' => 'ti-beach', 'label' => ['th' => 'ท่องเที่ยวหรือเยี่ยมเยียน', 'en' => 'Visiting or tourism']],
            ['value' => 'study', 'icon' => 'ti-school', 'label' => ['th' => 'ศึกษา', 'en' => 'Studying']],
            ['value' => 'work', 'icon' => 'ti-briefcase', 'label' => ['th' => 'ทำงาน', 'en' => 'Working']],
            ['value' => 'live', 'icon' => 'ti-home', 'label' => ['th' => 'พำนักระยะยาว', 'en' => 'Living long-term']],
            ['value' => 'unsure', 'icon' => 'ti-help-circle', 'label' => ['th' => 'ไม่แน่ใจ', 'en' => 'Not sure']],
        ],
    ],
    [
        'id' => 'unsure_duration',
        'stage' => 1,
        'type' => 'single',
        'title' => ['th' => 'คุณวางแผนจะอยู่นานเท่าใด?', 'en' => 'How long do you plan to stay?'],
        'help' => ['th' => 'ระยะเวลาพำนักมีผลต่อวีซ่าหรือการอนุญาตประเภทที่คุณอาจต้องใช้', 'en' => 'Length of stay affects which type of visa or permission you may need.'],
        'show_if' => ['who_are_you' => ['not_sure']],
        'options' => [
            ['value' => 'short', 'icon' => 'ti-clock', 'label' => ['th' => 'ไม่เกิน 60 วัน', 'en' => 'Under 60 days']],
            ['value' => 'long', 'icon' => 'ti-calendar', 'label' => ['th' => 'นานกว่านั้น', 'en' => 'Longer than that']],
            ['value' => 'unsure', 'icon' => 'ti-help-circle', 'label' => ['th' => 'ไม่แน่ใจ', 'en' => 'Not sure']],
        ],
    ],

    // ── Stage 2 — Understand Your Legal Basis ─────────────────────────────
    // (root question; per-branch refinement is added in Phase 2)
    [
        'id' => 'legal_basis',
        'stage' => 2,
        'type' => 'single',
        'title' => ['th' => 'คุณเข้าหรืออยู่ในประเทศไทยด้วยวิธีใด?', 'en' => 'How did you enter or stay in Thailand?'],
        'help' => ['th' => 'เลือกข้อที่ตรงกับสถานการณ์ของคุณมากที่สุด', 'en' => 'Choose the option that best describes your situation.'],
        // Legal-basis-to-stay only applies to foreign nationals.
        'show_if' => ['who_are_you' => ['foreign_national']],
        'options' => [
            ['value' => 'has_visa', 'icon' => 'ti-id-badge-2', 'label' => ['th' => 'มีวีซ่า', 'en' => 'I have a visa']],
            ['value' => 'visa_exemption', 'icon' => 'ti-world-check', 'label' => ['th' => 'ได้รับยกเว้นการตรวจลงตรา', 'en' => 'Visa exemption']],
            ['value' => 'visa_on_arrival', 'icon' => 'ti-plane-arrival', 'label' => ['th' => 'ใช้ Visa on Arrival', 'en' => 'I used Visa on Arrival']],
            ['value' => 'temporary_permission', 'icon' => 'ti-calendar', 'label' => ['th' => 'ได้รับอนุญาตให้อยู่ชั่วคราว', 'en' => 'Temporary stay permission']],
            ['value' => 'permanent_residence', 'icon' => 'ti-home-check', 'label' => ['th' => 'มีใบสำคัญถิ่นที่อยู่ถาวร', 'en' => 'I have permanent residence']],
            ['value' => 'special_permission', 'icon' => 'ti-star', 'label' => ['th' => 'ได้รับอนุญาตเป็นกรณีพิเศษ', 'en' => 'Special permission']],
            ['value' => 'applying_pr', 'icon' => 'ti-file-import', 'label' => ['th' => 'อยู่ระหว่างขอมีถิ่นที่อยู่', 'en' => 'Applying for permanent residence']],
            ['value' => 'overstay', 'icon' => 'ti-clock-exclamation', 'label' => ['th' => 'ระยะเวลาพำนักหมดอายุแล้ว', 'en' => 'My stay has expired (overstay)']],
            ['value' => 'revoked', 'icon' => 'ti-alert-triangle', 'label' => ['th' => 'การอนุญาตถูกเพิกถอน', 'en' => 'My permission has been revoked']],
            ['value' => 'awaiting_removal', 'icon' => 'ti-plane', 'label' => ['th' => 'อยู่ระหว่างรอส่งกลับ', 'en' => 'I am awaiting removal / deportation']],
            ['value' => 'entered_without_records', 'icon' => 'ti-user-x', 'label' => ['th' => 'เข้ามาโดยไม่มีหลักฐานการเข้าเมือง', 'en' => 'I entered without valid entry records']],
            ['value' => 'not_sure', 'icon' => 'ti-help-circle', 'label' => ['th' => 'ไม่ทราบหรือไม่แน่ใจ', 'en' => 'I am not sure']],
        ],
    ],

    // ── Stage 3 — Why Are You in Thailand ─────────────────────────────────
    [
        'id' => 'purpose',
        'stage' => 3,
        'type' => 'single',
        'title' => ['th' => 'คุณเข้ามาในประเทศไทยเพื่ออะไร?', 'en' => 'Why are you in Thailand?'],
        'help' => ['th' => 'เลือกวัตถุประสงค์หลัก 1 ข้อ', 'en' => 'Select the main purpose of your visit or stay.'],
        // Purpose-of-stay applies to foreign nationals and stateless persons.
        'show_if' => ['who_are_you' => ['foreign_national', 'stateless']],
        'options' => [
            ['value' => 'tourism', 'icon' => 'ti-beach', 'label' => ['th' => 'ท่องเที่ยว', 'en' => 'Tourism']],
            ['value' => 'transit', 'icon' => 'ti-plane', 'label' => ['th' => 'เดินทางผ่าน', 'en' => 'Transit']],
            ['value' => 'study', 'icon' => 'ti-school', 'label' => ['th' => 'ศึกษา', 'en' => 'Study']],
            ['value' => 'work', 'icon' => 'ti-briefcase', 'label' => ['th' => 'ทำงาน', 'en' => 'Work']],
            ['value' => 'professional', 'icon' => 'ti-user-star', 'label' => ['th' => 'ทำงานเป็นผู้เชี่ยวชาญหรือวิชาชีพ', 'en' => 'Professional / Expert']],
            ['value' => 'investment', 'icon' => 'ti-chart-line', 'label' => ['th' => 'ลงทุน', 'en' => 'Investment']],
            ['value' => 'business', 'icon' => 'ti-building-store', 'label' => ['th' => 'ประกอบธุรกิจ', 'en' => 'Doing business']],
            ['value' => 'family', 'icon' => 'ti-users-group', 'label' => ['th' => 'ติดตามครอบครัว', 'en' => 'Family visit / dependent']],
            ['value' => 'marriage', 'icon' => 'ti-heart', 'label' => ['th' => 'สมรสหรืออยู่กับคู่สมรสชาวไทย', 'en' => 'Marriage to a Thai citizen']],
            ['value' => 'retirement', 'icon' => 'ti-armchair', 'label' => ['th' => 'เกษียณอายุ', 'en' => 'Retirement']],
            ['value' => 'medical', 'icon' => 'ti-stethoscope', 'label' => ['th' => 'รักษาพยาบาล', 'en' => 'Medical treatment']],
            ['value' => 'research', 'icon' => 'ti-microscope', 'label' => ['th' => 'ทำงานวิจัยหรือสอน', 'en' => 'Research / Teaching']],
            ['value' => 'diplomatic', 'icon' => 'ti-building-bank', 'label' => ['th' => 'ปฏิบัติหน้าที่ทางการทูตหรือกงสุล', 'en' => 'Diplomatic / Consular']],
            ['value' => 'official', 'icon' => 'ti-briefcase-2', 'label' => ['th' => 'ปฏิบัติหน้าที่ราชการ', 'en' => 'Official assignment']],
            ['value' => 'media', 'icon' => 'ti-microphone', 'label' => ['th' => 'สื่อมวลชน', 'en' => 'Media / Journalism']],
            ['value' => 'religion', 'icon' => 'ti-flame', 'label' => ['th' => 'ศาสนา', 'en' => 'Religion']],
            ['value' => 'migrant_worker', 'icon' => 'ti-helmet', 'label' => ['th' => 'แรงงานข้ามชาติ', 'en' => 'Migrant worker']],
            ['value' => 'applying_pr', 'icon' => 'ti-home-plus', 'label' => ['th' => 'ขอมีถิ่นที่อยู่', 'en' => 'Applying for permanent residence']],
            ['value' => 'applying_nationality', 'icon' => 'ti-flag', 'label' => ['th' => 'ขอแปลงสัญชาติ', 'en' => 'Applying for Thai nationality']],
            ['value' => 'protection', 'icon' => 'ti-shield-heart', 'label' => ['th' => 'ขอรับความคุ้มครอง', 'en' => 'Seeking protection']],
            ['value' => 'other', 'icon' => 'ti-dots', 'label' => ['th' => 'อื่น ๆ', 'en' => 'Other']],
            ['value' => 'not_sure', 'icon' => 'ti-help-circle', 'label' => ['th' => 'ไม่แน่ใจ', 'en' => "I'm not sure"]],
        ],
    ],

    // ── Stage 4 — What Do You Need To Do ──────────────────────────────────
    [
        'id' => 'actions',
        'stage' => 4,
        'type' => 'multi',
        'title' => ['th' => 'คุณต้องการทำสิ่งใด?', 'en' => 'What do you need to do?'],
        'help' => ['th' => 'เลือกได้มากกว่า 1 ข้อ', 'en' => 'Select all that apply.'],
        'show_if' => null,
        'options' => [
            ['value' => 'apply_visa', 'icon' => 'ti-id-badge-2', 'label' => ['th' => 'ขอวีซ่าหรือเตรียมเดินทางเข้าไทย', 'en' => 'Apply for visa / prepare to travel']],
            ['value' => 'check_eligibility', 'icon' => 'ti-search', 'label' => ['th' => 'ตรวจสอบสิทธิการเข้าประเทศ', 'en' => 'Check entry eligibility']],
            ['value' => 'extend_stay', 'icon' => 'ti-calendar-plus', 'label' => ['th' => 'ต่อระยะเวลาพำนัก', 'en' => 'Extend your stay']],
            ['value' => 'change_visa', 'icon' => 'ti-switch-horizontal', 'label' => ['th' => 'เปลี่ยนประเภทวีซ่าหรือวัตถุประสงค์', 'en' => 'Change visa type / purpose']],
            ['value' => 'report_90day', 'icon' => 'ti-calendar-event', 'label' => ['th' => 'รายงานตัว 90 วัน', 'en' => '90-day report']],
            ['value' => 'report_tm30', 'icon' => 'ti-home', 'label' => ['th' => 'แจ้งที่พัก (TM30)', 'en' => 'Report residence (TM30)']],
            ['value' => 'reentry_permit', 'icon' => 'ti-plane-departure', 'label' => ['th' => 'ขอ Re-entry Permit', 'en' => 'Apply for re-entry permit']],
            ['value' => 'work_permit', 'icon' => 'ti-briefcase', 'label' => ['th' => 'ขอใบอนุญาตทำงาน', 'en' => 'Apply for work permit']],
            ['value' => 'change_employer', 'icon' => 'ti-user-cog', 'label' => ['th' => 'เปลี่ยนนายจ้างหรือสถานที่ทำงาน', 'en' => 'Change employer / workplace']],
            ['value' => 'register_marriage', 'icon' => 'ti-heart', 'label' => ['th' => 'จดทะเบียนสมรส', 'en' => 'Register marriage']],
            ['value' => 'family_birth', 'icon' => 'ti-baby-carriage', 'label' => ['th' => 'แจ้งเกิดหรือดำเนินการด้านครอบครัว', 'en' => 'Family / birth registration']],
            ['value' => 'request_certificate', 'icon' => 'ti-file-description', 'label' => ['th' => 'ขอหนังสือรับรองหรือประวัติการเดินทาง', 'en' => 'Request certificate / travel history']],
            ['value' => 'permanent_residence', 'icon' => 'ti-home-check', 'label' => ['th' => 'ขอมีถิ่นที่อยู่ถาวร', 'en' => 'Apply for permanent residence']],
            ['value' => 'apply_nationality', 'icon' => 'ti-flag', 'label' => ['th' => 'ขอแปลงสัญชาติ', 'en' => 'Apply for Thai nationality']],
            ['value' => 'overstay_help', 'icon' => 'ti-alert-triangle', 'label' => ['th' => 'แก้ไขกรณี Overstay', 'en' => 'Overstay assistance']],
            ['value' => 'contact_embassy', 'icon' => 'ti-building-bank', 'label' => ['th' => 'ติดต่อสถานทูตหรือสถานกงสุล', 'en' => 'Contact embassy / consulate']],
            ['value' => 'lost_passport', 'icon' => 'ti-file-alert', 'label' => ['th' => 'หนังสือเดินทางสูญหาย', 'en' => 'Lost or stolen passport']],
            ['value' => 'legal_assistance', 'icon' => 'ti-scale', 'label' => ['th' => 'ขอความช่วยเหลือด้านกฎหมาย', 'en' => 'Legal assistance']],
            ['value' => 'emergency', 'icon' => 'ti-urgent', 'label' => ['th' => 'ขอความช่วยเหลือฉุกเฉิน', 'en' => 'Emergency assistance']],
            ['value' => 'find_office', 'icon' => 'ti-map-pin', 'label' => ['th' => 'ค้นหาสำนักงานที่ใกล้ที่สุด', 'en' => 'Find nearest office']],
            ['value' => 'not_sure', 'icon' => 'ti-help-circle', 'label' => ['th' => 'ฉันไม่แน่ใจว่าต้องทำอะไร', 'en' => "I'm not sure what to do"]],
        ],
    ],

];
