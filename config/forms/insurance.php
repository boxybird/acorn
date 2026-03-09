<?php

return [
    'key' => 'insurance',
    'title' => 'forms/insurance.title',
    'icon' => 'shield',
    'order' => 2,
    'estimated_minutes' => 5,
    'sections' => [
        [
            'key' => 'primary_insurance',
            'title' => 'forms/insurance.section_primary_insurance',
            'fields' => [
                [
                    'key' => 'insurance_provider',
                    'type' => 'text',
                    'label' => 'forms/insurance.insurance_provider',
                    'validation' => ['required', 'string', 'max:255'],
                    'monday_field' => 'insurance_provider',
                ],
                [
                    'key' => 'policy_number',
                    'type' => 'text',
                    'label' => 'forms/insurance.policy_number',
                    'validation' => ['required', 'string', 'max:100'],
                    'monday_field' => 'policy_number',
                ],
                [
                    'key' => 'group_number',
                    'type' => 'text',
                    'label' => 'forms/insurance.group_number',
                    'validation' => ['nullable', 'string', 'max:100'],
                    'monday_field' => 'group_number',
                ],
                [
                    'key' => 'policyholder_name',
                    'type' => 'text',
                    'label' => 'forms/insurance.policyholder_name',
                    'validation' => ['required', 'string', 'max:255'],
                    'monday_field' => 'policyholder_name',
                ],
                [
                    'key' => 'policyholder_dob',
                    'type' => 'date',
                    'label' => 'forms/insurance.policyholder_dob',
                    'validation' => ['required', 'date'],
                    'monday_field' => 'policyholder_dob',
                ],
                [
                    'key' => 'policyholder_relationship',
                    'type' => 'select',
                    'label' => 'forms/insurance.policyholder_relationship',
                    'options' => [
                        ['value' => 'parent', 'label' => 'forms/insurance.option_rel_parent'],
                        ['value' => 'guardian', 'label' => 'forms/insurance.option_rel_guardian'],
                        ['value' => 'self', 'label' => 'forms/insurance.option_rel_self'],
                        ['value' => 'other', 'label' => 'forms/insurance.option_rel_other'],
                    ],
                    'validation' => ['required', 'string'],
                    'monday_field' => 'policyholder_relationship',
                ],
                [
                    'key' => 'insurance_card_front',
                    'type' => 'file',
                    'label' => 'forms/insurance.insurance_card_front',
                    'accept' => 'image/*,.pdf',
                    'validation' => ['required'],
                ],
                [
                    'key' => 'insurance_card_back',
                    'type' => 'file',
                    'label' => 'forms/insurance.insurance_card_back',
                    'accept' => 'image/*,.pdf',
                    'validation' => ['required'],
                ],
            ],
        ],
    ],
];
