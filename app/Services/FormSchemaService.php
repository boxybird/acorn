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

    /**
     * Get a schema with all translation keys resolved to the current locale.
     *
     * @return array<string, mixed>|null
     */
    public function getResolved(string $key): ?array
    {
        $schema = $this->get($key);

        if ($schema === null) {
            return null;
        }

        /** @var array<string, mixed> */
        return self::resolveRecursive($schema);
    }

    /**
     * Recursively resolve translation keys in a schema structure.
     */
    private static function resolveRecursive(mixed $value): mixed
    {
        if (is_string($value) && str_starts_with($value, 'forms/')) {
            return __($value);
        }

        if (! is_array($value)) {
            return $value;
        }

        return array_map(fn (mixed $item): mixed => self::resolveRecursive($item), $value);
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
