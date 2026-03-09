<?php

return [
    'key' => 'demographics',
    'title' => 'forms/demographics.title',
    'icon' => 'users',
    'order' => 1,
    'estimated_minutes' => 5,
    'sections' => [
        [
            'key' => 'parent_info',
            'title' => 'forms/demographics.section_parent_info',
            'fields' => [
                [
                    'key' => 'first_name',
                    'type' => 'text',
                    'label' => 'forms/demographics.first_name',
                    'validation' => ['required', 'string', 'max:255'],
                    'monday_field' => 'parent_first_name',
                ],
                [
                    'key' => 'last_name',
                    'type' => 'text',
                    'label' => 'forms/demographics.last_name',
                    'validation' => ['required', 'string', 'max:255'],
                    'monday_field' => 'parent_last_name',
                ],
                [
                    'key' => 'phone',
                    'type' => 'phone',
                    'label' => 'forms/demographics.phone',
                    'validation' => ['required', 'string', 'max:20'],
                    'monday_field' => 'parent_phone',
                ],
                [
                    'key' => 'email',
                    'type' => 'email',
                    'label' => 'forms/demographics.email',
                    'validation' => ['required', 'email', 'max:255'],
                    'monday_field' => 'parent_email',
                ],
                [
                    'key' => 'address',
                    'type' => 'address',
                    'label' => 'forms/demographics.address',
                    'validation' => ['required', 'string', 'max:500'],
                    'monday_field' => 'parent_address',
                ],
                [
                    'key' => 'preferred_language',
                    'type' => 'select',
                    'label' => 'forms/demographics.preferred_language',
                    'options' => [
                        ['value' => 'en', 'label' => 'forms/demographics.option_lang_english'],
                        ['value' => 'es', 'label' => 'forms/demographics.option_lang_spanish'],
                        ['value' => 'other', 'label' => 'forms/demographics.option_lang_other'],
                    ],
                    'validation' => ['required', 'string'],
                    'monday_field' => 'preferred_language',
                ],
                [
                    'key' => 'has_secondary_guardian',
                    'type' => 'checkbox',
                    'label' => 'forms/demographics.has_secondary_guardian',
                ],
                [
                    'key' => 'secondary_guardian_name',
                    'type' => 'text',
                    'label' => 'forms/demographics.secondary_guardian_name',
                    'validation' => ['required_if:has_secondary_guardian,true', 'nullable', 'string', 'max:255'],
                    'monday_field' => 'secondary_guardian_name',
                    'conditions' => [
                        ['field' => 'has_secondary_guardian', 'equals' => true],
                    ],
                ],
                [
                    'key' => 'secondary_guardian_phone',
                    'type' => 'phone',
                    'label' => 'forms/demographics.secondary_guardian_phone',
                    'validation' => ['nullable', 'string', 'max:20'],
                    'monday_field' => 'secondary_guardian_phone',
                    'conditions' => [
                        ['field' => 'has_secondary_guardian', 'equals' => true],
                    ],
                ],
                [
                    'key' => 'secondary_guardian_email',
                    'type' => 'email',
                    'label' => 'forms/demographics.secondary_guardian_email',
                    'validation' => ['nullable', 'email', 'max:255'],
                    'monday_field' => 'secondary_guardian_email',
                    'conditions' => [
                        ['field' => 'has_secondary_guardian', 'equals' => true],
                    ],
                ],
            ],
        ],
        [
            'key' => 'referral_info',
            'title' => 'forms/demographics.section_referral_info',
            'fields' => [
                [
                    'key' => 'referral_source',
                    'type' => 'select',
                    'label' => 'forms/demographics.referral_source',
                    'options' => [
                        ['value' => 'pediatrician', 'label' => 'forms/demographics.option_ref_pediatrician'],
                        ['value' => 'school', 'label' => 'forms/demographics.option_ref_school'],
                        ['value' => 'friend_family', 'label' => 'forms/demographics.option_ref_friend_family'],
                        ['value' => 'online', 'label' => 'forms/demographics.option_ref_online'],
                        ['value' => 'other', 'label' => 'forms/demographics.option_ref_other'],
                    ],
                    'validation' => ['required', 'string'],
                    'monday_field' => 'referral_source',
                ],
                [
                    'key' => 'referring_provider',
                    'type' => 'text',
                    'label' => 'forms/demographics.referring_provider',
                    'validation' => ['nullable', 'string', 'max:255'],
                    'monday_field' => 'referring_provider',
                    'conditions' => [
                        ['field' => 'referral_source', 'equals' => 'pediatrician'],
                    ],
                ],
            ],
        ],
    ],
];
