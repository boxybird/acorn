<?php

return [
    'key' => 'insurance',
    'title' => ['en' => 'Insurance Information', 'es' => 'Información del Seguro'],
    'icon' => 'shield',
    'order' => 2,
    'estimated_minutes' => 5,
    'sections' => [
        [
            'key' => 'primary_insurance',
            'title' => ['en' => 'Primary Insurance', 'es' => 'Seguro Primario'],
            'fields' => [
                [
                    'key' => 'insurance_provider',
                    'type' => 'text',
                    'label' => ['en' => 'Insurance Provider', 'es' => 'Proveedor de Seguro'],
                    'validation' => ['required', 'string', 'max:255'],
                    'monday_field' => 'insurance_provider',
                ],
                [
                    'key' => 'policy_number',
                    'type' => 'text',
                    'label' => ['en' => 'Policy Number', 'es' => 'Número de Póliza'],
                    'validation' => ['required', 'string', 'max:100'],
                    'monday_field' => 'policy_number',
                ],
                [
                    'key' => 'group_number',
                    'type' => 'text',
                    'label' => ['en' => 'Group Number', 'es' => 'Número de Grupo'],
                    'validation' => ['nullable', 'string', 'max:100'],
                    'monday_field' => 'group_number',
                ],
                [
                    'key' => 'policyholder_name',
                    'type' => 'text',
                    'label' => ['en' => 'Policyholder Name', 'es' => 'Nombre del Titular'],
                    'validation' => ['required', 'string', 'max:255'],
                    'monday_field' => 'policyholder_name',
                ],
                [
                    'key' => 'policyholder_dob',
                    'type' => 'date',
                    'label' => ['en' => 'Policyholder Date of Birth', 'es' => 'Fecha de Nacimiento del Titular'],
                    'validation' => ['required', 'date'],
                    'monday_field' => 'policyholder_dob',
                ],
                [
                    'key' => 'policyholder_relationship',
                    'type' => 'select',
                    'label' => ['en' => 'Relationship to Child', 'es' => 'Relación con el Niño'],
                    'options' => [
                        ['value' => 'parent', 'label' => ['en' => 'Parent', 'es' => 'Padre/Madre']],
                        ['value' => 'guardian', 'label' => ['en' => 'Guardian', 'es' => 'Tutor']],
                        ['value' => 'self', 'label' => ['en' => 'Self', 'es' => 'Mismo']],
                        ['value' => 'other', 'label' => ['en' => 'Other', 'es' => 'Otro']],
                    ],
                    'validation' => ['required', 'string'],
                    'monday_field' => 'policyholder_relationship',
                ],
                [
                    'key' => 'insurance_card_front',
                    'type' => 'file',
                    'label' => ['en' => 'Insurance Card (Front)', 'es' => 'Tarjeta de Seguro (Frente)'],
                    'accept' => 'image/*,.pdf',
                    'validation' => ['required'],
                ],
                [
                    'key' => 'insurance_card_back',
                    'type' => 'file',
                    'label' => ['en' => 'Insurance Card (Back)', 'es' => 'Tarjeta de Seguro (Reverso)'],
                    'accept' => 'image/*,.pdf',
                    'validation' => ['required'],
                ],
            ],
        ],
    ],
];
