<?php

return [
    'key' => 'child_information',
    'title' => ['en' => 'Child Information', 'es' => 'Información del Niño'],
    'icon' => 'baby',
    'order' => 3,
    'estimated_minutes' => 5,
    'sections' => [
        [
            'key' => 'basic_info',
            'title' => ['en' => 'Basic Information', 'es' => 'Información Básica'],
            'fields' => [
                [
                    'key' => 'child_first_name',
                    'type' => 'text',
                    'label' => ['en' => 'First Name', 'es' => 'Nombre'],
                    'validation' => ['required', 'string', 'max:255'],
                    'monday_field' => 'child_first_name',
                ],
                [
                    'key' => 'child_last_name',
                    'type' => 'text',
                    'label' => ['en' => 'Last Name', 'es' => 'Apellido'],
                    'validation' => ['required', 'string', 'max:255'],
                    'monday_field' => 'child_last_name',
                ],
                [
                    'key' => 'child_dob',
                    'type' => 'date',
                    'label' => ['en' => 'Date of Birth', 'es' => 'Fecha de Nacimiento'],
                    'validation' => ['required', 'date'],
                    'monday_field' => 'child_dob',
                ],
                [
                    'key' => 'child_gender',
                    'type' => 'select',
                    'label' => ['en' => 'Gender', 'es' => 'Género'],
                    'options' => [
                        ['value' => 'male', 'label' => ['en' => 'Male', 'es' => 'Masculino']],
                        ['value' => 'female', 'label' => ['en' => 'Female', 'es' => 'Femenino']],
                        ['value' => 'non_binary', 'label' => ['en' => 'Non-Binary', 'es' => 'No Binario']],
                        ['value' => 'prefer_not_to_say', 'label' => ['en' => 'Prefer Not to Say', 'es' => 'Prefiero No Decir']],
                    ],
                    'validation' => ['required', 'string'],
                    'monday_field' => 'child_gender',
                ],
            ],
        ],
        [
            'key' => 'providers',
            'title' => ['en' => 'Healthcare Providers', 'es' => 'Proveedores de Salud'],
            'fields' => [
                [
                    'key' => 'pediatrician_name',
                    'type' => 'text',
                    'label' => ['en' => 'Pediatrician Name', 'es' => 'Nombre del Pediatra'],
                    'validation' => ['nullable', 'string', 'max:255'],
                    'monday_field' => 'pediatrician_name',
                ],
                [
                    'key' => 'pediatrician_phone',
                    'type' => 'phone',
                    'label' => ['en' => 'Pediatrician Phone', 'es' => 'Teléfono del Pediatra'],
                    'validation' => ['nullable', 'string', 'max:20'],
                    'monday_field' => 'pediatrician_phone',
                ],
                [
                    'key' => 'school_name',
                    'type' => 'text',
                    'label' => ['en' => 'School/Daycare Name', 'es' => 'Nombre de la Escuela/Guardería'],
                    'validation' => ['nullable', 'string', 'max:255'],
                    'monday_field' => 'school_name',
                ],
            ],
        ],
    ],
];
