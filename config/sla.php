<?php

return [
    'default_policies' => [
        'DOCUMENT_COMPLETION' => [
            'name' => 'SLA Dokumen',
            'duration_value' => 3,
            'duration_unit' => 'BUSINESS_DAYS',
            'reminder_before_minutes' => 24 * 60, // 1 day
            'first_escalation_after_minutes' => 24 * 60,
            'second_escalation_after_minutes' => 48 * 60,
            'uses_business_calendar' => true,
        ],
        'ENTRY_PROCESS' => [
            'name' => 'SLA Entry SIHALAL',
            'duration_value' => 2,
            'duration_unit' => 'BUSINESS_DAYS',
            'reminder_before_minutes' => 24 * 60,
            'first_escalation_after_minutes' => 24 * 60,
            'second_escalation_after_minutes' => 48 * 60,
            'uses_business_calendar' => true,
        ],
        'SPV_ENTRY_REVIEW' => [
            'name' => 'SLA Review SPV Entry',
            'duration_value' => 1,
            'duration_unit' => 'BUSINESS_DAYS',
            'reminder_before_minutes' => 4 * 60, // 4 hours before
            'first_escalation_after_minutes' => 24 * 60,
            'second_escalation_after_minutes' => 48 * 60,
            'uses_business_calendar' => true,
        ],
        'AUDIT_PLANNING' => [
            'name' => 'SLA Audit Planning',
            'duration_value' => 2,
            'duration_unit' => 'BUSINESS_DAYS',
            'reminder_before_minutes' => 24 * 60,
            'first_escalation_after_minutes' => 24 * 60,
            'second_escalation_after_minutes' => 48 * 60,
            'uses_business_calendar' => true,
        ],
        'AUDIT_EXECUTION' => [
            'name' => 'SLA Audit Execution',
            'duration_value' => 0, // Using SCHEDULED_DATE
            'duration_unit' => 'SCHEDULED_DATE',
            'reminder_before_minutes' => 24 * 60,
            'first_escalation_after_minutes' => 24 * 60,
            'second_escalation_after_minutes' => 48 * 60,
            'uses_business_calendar' => true,
        ],
        'AUDITOR_REVIEW' => [
            'name' => 'SLA Auditor Review',
            'duration_value' => 2,
            'duration_unit' => 'BUSINESS_DAYS',
            'reminder_before_minutes' => 24 * 60,
            'first_escalation_after_minutes' => 24 * 60,
            'second_escalation_after_minutes' => 48 * 60,
            'uses_business_calendar' => true,
        ],
    ]
];
