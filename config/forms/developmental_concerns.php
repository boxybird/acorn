<?php

return [
    'key' => 'developmental_concerns',
    'title' => 'forms/developmental_concerns.title',
    'icon' => 'activity',
    'order' => 5,
    'estimated_minutes' => 10,
    'sections' => [
        [
            'key' => 'milestones',
            'title' => 'forms/developmental_concerns.section_milestones',
            'fields' => [
                [
                    'key' => 'first_words_age',
                    'type' => 'text',
                    'label' => 'forms/developmental_concerns.first_words_age',
                    'validation' => ['nullable', 'string', 'max:50'],
                    'monday_field' => 'first_words_age',
                ],
                [
                    'key' => 'first_walking_age',
                    'type' => 'text',
                    'label' => 'forms/developmental_concerns.first_walking_age',
                    'validation' => ['nullable', 'string', 'max:50'],
                    'monday_field' => 'first_walking_age',
                ],
                [
                    'key' => 'toilet_trained',
                    'type' => 'select',
                    'label' => 'forms/developmental_concerns.toilet_trained',
                    'options' => [
                        ['value' => 'fully', 'label' => 'forms/developmental_concerns.option_toilet_fully'],
                        ['value' => 'in_progress', 'label' => 'forms/developmental_concerns.option_toilet_in_progress'],
                        ['value' => 'not_started', 'label' => 'forms/developmental_concerns.option_toilet_not_started'],
                    ],
                    'validation' => ['nullable', 'string'],
                    'monday_field' => 'toilet_trained',
                ],
            ],
        ],
        [
            'key' => 'current_concerns',
            'title' => 'forms/developmental_concerns.section_current_concerns',
            'fields' => [
                [
                    'key' => 'primary_concerns',
                    'type' => 'textarea',
                    'label' => 'forms/developmental_concerns.primary_concerns',
                    'validation' => ['required', 'string', 'max:3000'],
                    'monday_field' => 'primary_concerns',
                ],
                [
                    'key' => 'communication_level',
                    'type' => 'select',
                    'label' => 'forms/developmental_concerns.communication_level',
                    'options' => [
                        ['value' => 'nonverbal', 'label' => 'forms/developmental_concerns.option_comm_nonverbal'],
                        ['value' => 'single_words', 'label' => 'forms/developmental_concerns.option_comm_single_words'],
                        ['value' => 'phrases', 'label' => 'forms/developmental_concerns.option_comm_phrases'],
                        ['value' => 'sentences', 'label' => 'forms/developmental_concerns.option_comm_sentences'],
                    ],
                    'validation' => ['required', 'string'],
                    'monday_field' => 'communication_level',
                ],
                [
                    'key' => 'behavioral_concerns',
                    'type' => 'textarea',
                    'label' => 'forms/developmental_concerns.behavioral_concerns',
                    'validation' => ['nullable', 'string', 'max:3000'],
                    'monday_field' => 'behavioral_concerns',
                ],
                [
                    'key' => 'strengths',
                    'type' => 'textarea',
                    'label' => 'forms/developmental_concerns.strengths',
                    'validation' => ['nullable', 'string', 'max:2000'],
                    'monday_field' => 'child_strengths',
                ],
            ],
        ],
    ],
];
