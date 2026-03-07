<?php

return [
    'key' => 'medical_history',
    'title' => ['en' => 'Medical History', 'es' => 'Historial Médico'],
    'icon' => 'heart-pulse',
    'order' => 4,
    'estimated_minutes' => 10,
    'sections' => [
        [
            'key' => 'diagnoses',
            'title' => ['en' => 'Current Diagnoses', 'es' => 'Diagnósticos Actuales'],
            'fields' => [
                [
                    'key' => 'has_autism_diagnosis',
                    'type' => 'checkbox',
                    'label' => ['en' => 'Has the child received an Autism Spectrum Disorder (ASD) diagnosis?', 'es' => '¿El niño ha recibido un diagnóstico de Trastorno del Espectro Autista (TEA)?'],
                ],
                [
                    'key' => 'diagnosis_provider',
                    'type' => 'text',
                    'label' => ['en' => 'Diagnosing Provider', 'es' => 'Proveedor del Diagnóstico'],
                    'validation' => ['nullable', 'string', 'max:255'],
                    'monday_field' => 'diagnosis_provider',
                    'conditions' => [
                        ['field' => 'has_autism_diagnosis', 'equals' => true],
                    ],
                ],
                [
                    'key' => 'diagnosis_date',
                    'type' => 'date',
                    'label' => ['en' => 'Date of Diagnosis', 'es' => 'Fecha del Diagnóstico'],
                    'validation' => ['nullable', 'date'],
                    'monday_field' => 'diagnosis_date',
                    'conditions' => [
                        ['field' => 'has_autism_diagnosis', 'equals' => true],
                    ],
                ],
                [
                    'key' => 'other_diagnoses',
                    'type' => 'textarea',
                    'label' => ['en' => 'Other diagnoses or medical conditions', 'es' => 'Otros diagnósticos o condiciones médicas'],
                    'validation' => ['nullable', 'string', 'max:2000'],
                    'monday_field' => 'other_diagnoses',
                ],
            ],
        ],
        [
            'key' => 'medications',
            'title' => ['en' => 'Medications & Allergies', 'es' => 'Medicamentos y Alergias'],
            'fields' => [
                [
                    'key' => 'current_medications',
                    'type' => 'textarea',
                    'label' => ['en' => 'List all current medications and dosages', 'es' => 'Liste todos los medicamentos actuales y dosis'],
                    'validation' => ['nullable', 'string', 'max:2000'],
                    'monday_field' => 'current_medications',
                ],
                [
                    'key' => 'allergies',
                    'type' => 'textarea',
                    'label' => ['en' => 'Known allergies', 'es' => 'Alergias conocidas'],
                    'validation' => ['nullable', 'string', 'max:1000'],
                    'monday_field' => 'allergies',
                ],
            ],
        ],
        [
            'key' => 'prior_evaluations',
            'title' => ['en' => 'Prior Evaluations & Services', 'es' => 'Evaluaciones y Servicios Previos'],
            'fields' => [
                [
                    'key' => 'prior_evaluations',
                    'type' => 'textarea',
                    'label' => ['en' => 'List any prior evaluations (speech, OT, psychological, etc.)', 'es' => 'Liste cualquier evaluación previa (habla, TO, psicológica, etc.)'],
                    'validation' => ['nullable', 'string', 'max:2000'],
                    'monday_field' => 'prior_evaluations',
                ],
                [
                    'key' => 'prior_aba_therapy',
                    'type' => 'checkbox',
                    'label' => ['en' => 'Has the child previously received ABA therapy?', 'es' => '¿El niño ha recibido terapia ABA anteriormente?'],
                ],
                [
                    'key' => 'prior_aba_details',
                    'type' => 'textarea',
                    'label' => ['en' => 'If yes, please provide details (provider, duration, etc.)', 'es' => 'Si es así, proporcione detalles (proveedor, duración, etc.)'],
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
