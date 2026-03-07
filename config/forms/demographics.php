<?php

return [
    'key' => 'demographics',
    'title' => ['en' => 'Family Demographics', 'es' => 'Demografía Familiar'],
    'icon' => 'users',
    'order' => 1,
    'estimated_minutes' => 5,
    'sections' => [
        [
            'key' => 'parent_info',
            'title' => ['en' => 'Parent/Guardian Information', 'es' => 'Información del Padre/Tutor'],
            'fields' => [
                [
                    'key' => 'first_name',
                    'type' => 'text',
                    'label' => ['en' => 'First Name', 'es' => 'Nombre'],
                    'validation' => ['required', 'string', 'max:255'],
                    'monday_field' => 'parent_first_name',
                ],
                [
                    'key' => 'last_name',
                    'type' => 'text',
                    'label' => ['en' => 'Last Name', 'es' => 'Apellido'],
                    'validation' => ['required', 'string', 'max:255'],
                    'monday_field' => 'parent_last_name',
                ],
                [
                    'key' => 'phone',
                    'type' => 'phone',
                    'label' => ['en' => 'Phone Number', 'es' => 'Número de Teléfono'],
                    'validation' => ['required', 'string', 'max:20'],
                    'monday_field' => 'parent_phone',
                ],
                [
                    'key' => 'email',
                    'type' => 'email',
                    'label' => ['en' => 'Email Address', 'es' => 'Correo Electrónico'],
                    'validation' => ['required', 'email', 'max:255'],
                    'monday_field' => 'parent_email',
                ],
                [
                    'key' => 'address',
                    'type' => 'address',
                    'label' => ['en' => 'Home Address', 'es' => 'Dirección'],
                    'validation' => ['required', 'string', 'max:500'],
                    'monday_field' => 'parent_address',
                ],
                [
                    'key' => 'preferred_language',
                    'type' => 'select',
                    'label' => ['en' => 'Preferred Language', 'es' => 'Idioma Preferido'],
                    'options' => [
                        ['value' => 'en', 'label' => ['en' => 'English', 'es' => 'Inglés']],
                        ['value' => 'es', 'label' => ['en' => 'Spanish', 'es' => 'Español']],
                        ['value' => 'other', 'label' => ['en' => 'Other', 'es' => 'Otro']],
                    ],
                    'validation' => ['required', 'string'],
                    'monday_field' => 'preferred_language',
                ],
                [
                    'key' => 'has_secondary_guardian',
                    'type' => 'checkbox',
                    'label' => ['en' => 'Is there a second parent/guardian?', 'es' => '¿Hay un segundo padre/tutor?'],
                ],
                [
                    'key' => 'secondary_guardian_name',
                    'type' => 'text',
                    'label' => ['en' => 'Second Guardian Full Name', 'es' => 'Nombre Completo del Segundo Tutor'],
                    'validation' => ['required_if:has_secondary_guardian,true', 'nullable', 'string', 'max:255'],
                    'monday_field' => 'secondary_guardian_name',
                    'conditions' => [
                        ['field' => 'has_secondary_guardian', 'equals' => true],
                    ],
                ],
                [
                    'key' => 'secondary_guardian_phone',
                    'type' => 'phone',
                    'label' => ['en' => 'Second Guardian Phone', 'es' => 'Teléfono del Segundo Tutor'],
                    'validation' => ['nullable', 'string', 'max:20'],
                    'monday_field' => 'secondary_guardian_phone',
                    'conditions' => [
                        ['field' => 'has_secondary_guardian', 'equals' => true],
                    ],
                ],
                [
                    'key' => 'secondary_guardian_email',
                    'type' => 'email',
                    'label' => ['en' => 'Second Guardian Email', 'es' => 'Correo del Segundo Tutor'],
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
            'title' => ['en' => 'Referral Information', 'es' => 'Información de Referencia'],
            'fields' => [
                [
                    'key' => 'referral_source',
                    'type' => 'select',
                    'label' => ['en' => 'How did you hear about us?', 'es' => '¿Cómo se enteró de nosotros?'],
                    'options' => [
                        ['value' => 'pediatrician', 'label' => ['en' => 'Pediatrician', 'es' => 'Pediatra']],
                        ['value' => 'school', 'label' => ['en' => 'School', 'es' => 'Escuela']],
                        ['value' => 'friend_family', 'label' => ['en' => 'Friend or Family', 'es' => 'Amigo o Familiar']],
                        ['value' => 'online', 'label' => ['en' => 'Online Search', 'es' => 'Búsqueda en Línea']],
                        ['value' => 'other', 'label' => ['en' => 'Other', 'es' => 'Otro']],
                    ],
                    'validation' => ['required', 'string'],
                    'monday_field' => 'referral_source',
                ],
                [
                    'key' => 'referring_provider',
                    'type' => 'text',
                    'label' => ['en' => 'Referring Provider Name', 'es' => 'Nombre del Proveedor que Refiere'],
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
