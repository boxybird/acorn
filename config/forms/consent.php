<?php

return [
    'key' => 'consent',
    'title' => ['en' => 'Consent & Authorization', 'es' => 'Consentimiento y Autorización'],
    'icon' => 'file-check',
    'order' => 6,
    'estimated_minutes' => 5,
    'sections' => [
        [
            'key' => 'consent_for_evaluation',
            'title' => ['en' => 'Consent for Evaluation', 'es' => 'Consentimiento para Evaluación'],
            'fields' => [
                [
                    'key' => 'consent_evaluation',
                    'type' => 'checkbox',
                    'label' => ['en' => 'I consent to an initial evaluation of my child by JumpStart Autism Collective.', 'es' => 'Doy mi consentimiento para una evaluación inicial de mi hijo por JumpStart Autism Collective.'],
                    'validation' => ['accepted'],
                ],
                [
                    'key' => 'consent_information_sharing',
                    'type' => 'checkbox',
                    'label' => ['en' => "I authorize JumpStart Autism Collective to share relevant information with my child's healthcare providers and school as needed.", 'es' => 'Autorizo a JumpStart Autism Collective a compartir información relevante con los proveedores de salud y la escuela de mi hijo según sea necesario.'],
                    'validation' => ['accepted'],
                ],
                [
                    'key' => 'consent_photo_video',
                    'type' => 'select',
                    'label' => ['en' => 'Photo/Video consent for therapy sessions', 'es' => 'Consentimiento de fotos/videos para sesiones de terapia'],
                    'options' => [
                        ['value' => 'yes', 'label' => ['en' => 'I consent', 'es' => 'Doy mi consentimiento']],
                        ['value' => 'no', 'label' => ['en' => 'I do not consent', 'es' => 'No doy mi consentimiento']],
                    ],
                    'validation' => ['required', 'string'],
                    'monday_field' => 'photo_video_consent',
                ],
            ],
        ],
        [
            'key' => 'signatures',
            'title' => ['en' => 'Signatures', 'es' => 'Firmas'],
            'fields' => [
                [
                    'key' => 'guardian_signature',
                    'type' => 'signature',
                    'label' => ['en' => 'Parent/Guardian Signature', 'es' => 'Firma del Padre/Tutor'],
                    'validation' => ['required', 'string'],
                ],
                [
                    'key' => 'signature_date',
                    'type' => 'date',
                    'label' => ['en' => 'Date', 'es' => 'Fecha'],
                    'validation' => ['required', 'date'],
                ],
            ],
        ],
    ],
];
