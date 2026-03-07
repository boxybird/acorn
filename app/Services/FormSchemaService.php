<?php

namespace App\Services;

class FormSchemaService
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $schemas = null;

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        $schemas = array_values($this->loadSchemas());
        usort($schemas, fn (array $a, array $b): int => $a['order'] <=> $b['order']);

        return $schemas;
    }

    /** @return array<string, mixed>|null */
    public function get(string $key): ?array
    {
        return $this->loadSchemas()[$key] ?? null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function validationRules(string $key): array
    {
        $schema = $this->get($key);

        if ($schema === null) {
            return [];
        }

        $rules = [];

        /** @var list<array{fields: list<array{key: string, validation?: list<string>}>}> $sections */
        $sections = $schema['sections'];

        foreach ($sections as $section) {
            foreach ($section['fields'] as $field) {
                if (isset($field['validation'])) {
                    $rules[$field['key']] = $field['validation'];
                }
            }
        }

        return $rules;
    }

    /** @return array<string, array<string, mixed>> */
    private function loadSchemas(): array
    {
        if ($this->schemas !== null) {
            return $this->schemas;
        }

        $this->schemas = [];
        $path = config_path('forms');

        if (! is_dir($path)) {
            return $this->schemas;
        }

        /** @var string $file */
        foreach (glob($path.'/*.php') ?: [] as $file) {
            /** @var array<string, mixed> $schema */
            $schema = require $file;
            /** @var string $schemaKey */
            $schemaKey = $schema['key'];
            $this->schemas[$schemaKey] = $schema;
        }

        return $this->schemas;
    }
}
