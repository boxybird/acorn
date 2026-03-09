<?php

return [
    'key' => 'child_information',
    'title' => 'forms/child_information.title',
    'icon' => 'baby',
    'order' => 3,
    'estimated_minutes' => 5,
    'sections' => [
        [
            'key' => 'basic_info',
            'title' => 'forms/child_information.section_basic_info',
            'fields' => [
                [
                    'key' => 'child_first_name',
                    'type' => 'text',
                    'label' => 'forms/child_information.child_first_name',
                    'validation' => ['required', 'string', 'max:255'],
                    'monday_field' => 'child_first_name',
                ],
                [
                    'key' => 'child_last_name',
                    'type' => 'text',
                    'label' => 'forms/child_information.child_last_name',
                    'validation' => ['required', 'string', 'max:255'],
                    'monday_field' => 'child_last_name',
                ],
                [
                    'key' => 'child_dob',
                    'type' => 'date',
                    'label' => 'forms/child_information.child_dob',
                    'validation' => ['required', 'date'],
                    'monday_field' => 'child_dob',
                ],
                [
                    'key' => 'child_gender',
                    'type' => 'select',
                    'label' => 'forms/child_information.child_gender',
                    'options' => [
                        ['value' => 'male', 'label' => 'forms/child_information.option_gender_male'],
                        ['value' => 'female', 'label' => 'forms/child_information.option_gender_female'],
                        ['value' => 'non_binary', 'label' => 'forms/child_information.option_gender_non_binary'],
                        ['value' => 'prefer_not_to_say', 'label' => 'forms/child_information.option_gender_prefer_not'],
                    ],
                    'validation' => ['required', 'string'],
                    'monday_field' => 'child_gender',
                ],
            ],
        ],
        [
            'key' => 'providers',
            'title' => 'forms/child_information.section_providers',
            'fields' => [
                [
                    'key' => 'pediatrician_name',
                    'type' => 'text',
                    'label' => 'forms/child_information.pediatrician_name',
                    'validation' => ['nullable', 'string', 'max:255'],
                    'monday_field' => 'pediatrician_name',
                ],
                [
                    'key' => 'pediatrician_phone',
                    'type' => 'phone',
                    'label' => 'forms/child_information.pediatrician_phone',
                    'validation' => ['nullable', 'string', 'max:20'],
                    'monday_field' => 'pediatrician_phone',
                ],
                [
                    'key' => 'school_name',
                    'type' => 'text',
                    'label' => 'forms/child_information.school_name',
                    'validation' => ['nullable', 'string', 'max:255'],
                    'monday_field' => 'school_name',
                ],
            ],
        ],
    ],
];
