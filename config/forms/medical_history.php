<?php

return [
    'key' => 'medical_history',
    'title' => 'forms/medical_history.title',
    'icon' => 'heart-pulse',
    'order' => 4,
    'estimated_minutes' => 10,
    'sections' => [
        [
            'key' => 'diagnoses',
            'title' => 'forms/medical_history.section_diagnoses',
            'fields' => [
                [
                    'key' => 'has_autism_diagnosis',
                    'type' => 'checkbox',
                    'label' => 'forms/medical_history.has_autism_diagnosis',
                ],
                [
                    'key' => 'diagnosis_provider',
                    'type' => 'text',
                    'label' => 'forms/medical_history.diagnosis_provider',
                    'validation' => ['nullable', 'string', 'max:255'],
                    'monday_field' => 'diagnosis_provider',
                    'conditions' => [
                        ['field' => 'has_autism_diagnosis', 'equals' => true],
                    ],
                ],
                [
                    'key' => 'diagnosis_date',
                    'type' => 'date',
                    'label' => 'forms/medical_history.diagnosis_date',
                    'validation' => ['nullable', 'date'],
                    'monday_field' => 'diagnosis_date',
                    'conditions' => [
                        ['field' => 'has_autism_diagnosis', 'equals' => true],
                    ],
                ],
                [
                    'key' => 'other_diagnoses',
                    'type' => 'textarea',
                    'label' => 'forms/medical_history.other_diagnoses',
                    'validation' => ['nullable', 'string', 'max:2000'],
                    'monday_field' => 'other_diagnoses',
                ],
            ],
        ],
        [
            'key' => 'medications',
            'title' => 'forms/medical_history.section_medications',
            'fields' => [
                [
                    'key' => 'current_medications',
                    'type' => 'textarea',
                    'label' => 'forms/medical_history.current_medications',
                    'validation' => ['nullable', 'string', 'max:2000'],
                    'monday_field' => 'current_medications',
                ],
                [
                    'key' => 'allergies',
                    'type' => 'textarea',
                    'label' => 'forms/medical_history.allergies',
                    'validation' => ['nullable', 'string', 'max:1000'],
                    'monday_field' => 'allergies',
                ],
            ],
        ],
        [
            'key' => 'prior_evaluations',
            'title' => 'forms/medical_history.section_prior_evaluations',
            'fields' => [
                [
                    'key' => 'prior_evaluations',
                    'type' => 'textarea',
                    'label' => 'forms/medical_history.prior_evaluations',
                    'validation' => ['nullable', 'string', 'max:2000'],
                    'monday_field' => 'prior_evaluations',
                ],
                [
                    'key' => 'prior_aba_therapy',
                    'type' => 'checkbox',
                    'label' => 'forms/medical_history.prior_aba_therapy',
                ],
                [
                    'key' => 'prior_aba_details',
                    'type' => 'textarea',
                    'label' => 'forms/medical_history.prior_aba_details',
                    'validation' => ['nullable', 'string', 'max:1000'],
                    'monday_field' => 'prior_aba_details',
                    'conditions' => [
                        ['field' => 'prior_aba_therapy', 'equals' => true],
                    ],
                ],
            ],
        ],
    ],
];
