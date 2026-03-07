<?php

return [
    'key' => 'developmental_concerns',
    'title' => ['en' => 'Developmental Concerns', 'es' => 'Preocupaciones del Desarrollo'],
    'icon' => 'activity',
    'order' => 5,
    'estimated_minutes' => 10,
    'sections' => [
        [
            'key' => 'milestones',
            'title' => ['en' => 'Developmental Milestones', 'es' => 'Hitos del Desarrollo'],
            'fields' => [
                [
                    'key' => 'first_words_age',
                    'type' => 'text',
                    'label' => ['en' => 'Age of first words (months)', 'es' => 'Edad de las primeras palabras (meses)'],
                    'validation' => ['nullable', 'string', 'max:50'],
                    'monday_field' => 'first_words_age',
                ],
                [
                    'key' => 'first_walking_age',
                    'type' => 'text',
                    'label' => ['en' => 'Age of first independent walking (months)', 'es' => 'Edad de la primera caminata independiente (meses)'],
                    'validation' => ['nullable', 'string', 'max:50'],
                    'monday_field' => 'first_walking_age',
                ],
                [
                    'key' => 'toilet_trained',
                    'type' => 'select',
                    'label' => ['en' => 'Toilet training status', 'es' => 'Estado del entrenamiento para ir al baño'],
                    'options' => [
                        ['value' => 'fully', 'label' => ['en' => 'Fully trained', 'es' => 'Completamente entrenado']],
                        ['value' => 'in_progress', 'label' => ['en' => 'In progress', 'es' => 'En progreso']],
                        ['value' => 'not_started', 'label' => ['en' => 'Not started', 'es' => 'No iniciado']],
                    ],
                    'validation' => ['nullable', 'string'],
                    'monday_field' => 'toilet_trained',
                ],
            ],
        ],
        [
            'key' => 'current_concerns',
            'title' => ['en' => 'Current Concerns', 'es' => 'Preocupaciones Actuales'],
            'fields' => [
                [
                    'key' => 'primary_concerns',
                    'type' => 'textarea',
                    'label' => ['en' => "What are your primary concerns about your child's development?", 'es' => '¿Cuáles son sus principales preocupaciones sobre el desarrollo de su hijo?'],
                    'validation' => ['required', 'string', 'max:3000'],
                    'monday_field' => 'primary_concerns',
                ],
                [
                    'key' => 'communication_level',
                    'type' => 'select',
                    'label' => ['en' => 'Current communication level', 'es' => 'Nivel de comunicación actual'],
                    'options' => [
                        ['value' => 'nonverbal', 'label' => ['en' => 'Non-verbal', 'es' => 'No verbal']],
                        ['value' => 'single_words', 'label' => ['en' => 'Single words', 'es' => 'Palabras sueltas']],
                        ['value' => 'phrases', 'label' => ['en' => 'Short phrases', 'es' => 'Frases cortas']],
                        ['value' => 'sentences', 'label' => ['en' => 'Full sentences', 'es' => 'Oraciones completas']],
                    ],
                    'validation' => ['required', 'string'],
                    'monday_field' => 'communication_level',
                ],
                [
                    'key' => 'behavioral_concerns',
                    'type' => 'textarea',
                    'label' => ['en' => 'Describe any behavioral concerns (tantrums, self-injury, aggression, elopement, etc.)', 'es' => 'Describa cualquier preocupación conductual (berrinches, autolesión, agresión, fuga, etc.)'],
                    'validation' => ['nullable', 'string', 'max:3000'],
                    'monday_field' => 'behavioral_concerns',
                ],
                [
                    'key' => 'strengths',
                    'type' => 'textarea',
                    'label' => ['en' => "What are your child's strengths and interests?", 'es' => '¿Cuáles son las fortalezas e intereses de su hijo?'],
                    'validation' => ['nullable', 'string', 'max:2000'],
                    'monday_field' => 'child_strengths',
                ],
            ],
        ],
    ],
];
