<?php

return [
    'key' => 'consent',
    'title' => 'forms/consent.title',
    'icon' => 'file-check',
    'order' => 6,
    'estimated_minutes' => 5,
    'sections' => [
        [
            'key' => 'consent_for_evaluation',
            'title' => 'forms/consent.section_consent_for_evaluation',
            'fields' => [
                [
                    'key' => 'consent_evaluation',
                    'type' => 'checkbox',
                    'label' => 'forms/consent.consent_evaluation',
                    'validation' => ['accepted'],
                ],
                [
                    'key' => 'consent_information_sharing',
                    'type' => 'checkbox',
                    'label' => 'forms/consent.consent_information_sharing',
                    'validation' => ['accepted'],
                ],
                [
                    'key' => 'consent_photo_video',
                    'type' => 'select',
                    'label' => 'forms/consent.consent_photo_video',
                    'options' => [
                        ['value' => 'yes', 'label' => 'forms/consent.option_consent_yes'],
                        ['value' => 'no', 'label' => 'forms/consent.option_consent_no'],
                    ],
                    'validation' => ['required', 'string'],
                    'monday_field' => 'photo_video_consent',
                ],
            ],
        ],
        [
            'key' => 'signatures',
            'title' => 'forms/consent.section_signatures',
            'fields' => [
                [
                    'key' => 'guardian_signature',
                    'type' => 'signature',
                    'label' => 'forms/consent.guardian_signature',
                    'validation' => ['required', 'string'],
                ],
                [
                    'key' => 'signature_date',
                    'type' => 'date',
                    'label' => 'forms/consent.signature_date',
                    'validation' => ['required', 'date'],
                ],
            ],
        ],
    ],
];
