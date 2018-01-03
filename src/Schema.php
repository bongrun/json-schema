<?php

namespace bongrun\JsonSchema;

use bongrun\JsonSchema\exception\JsonSchemaException;

/**
 * Class Schema
 * @package bongrun\JsonSchema
 */
class Schema
{
    protected $storage;
    protected $baseUrl;
    protected $fragment;
    protected $data;
    protected $dataSave;

    /**
     * Schema constructor.
     * @param SchemaStorage $storage
     * @param string $uri
     * @param $data
     * @throws JsonSchemaException
     */
    public function __construct(SchemaStorage $storage, string $uri, $data = null)
    {
        $this->storage = $storage;
        [$this->baseUrl, $this->fragment] = explode('#', $uri . '#');
        if ($data) {
            if (\is_array($data)) {
                $this->data = $data;
            } else {
                $this->data = @json_decode($data, true);
            }
        } elseif (strpos($this->baseUrl, 'http') === 0) {
            $this->data = @json_decode(@file_get_contents($uri), true);
        }
        if (!$this->data) {
            throw new JsonSchemaException('Not data. Uri = ' . $uri);
        }
    }

    public function getUri(): string
    {
        return $this->baseUrl . '#' . $this->fragment;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function getData($length = 0): array
    {
        if ($length > 3) {
            return [];
        }
        if (!$this->dataSave) {
            $this->dataSave = $this->getDataRecursive(null, $length);
        } else {
            $this->data = null;
        }
        return $this->dataSave;
    }

    public function getDataRecursive(array $data = null, $length = 0): array
    {
        if (!$data) {
            $data = $this->data;
        }
        $data2 = $data;
        foreach ($data2 as $key => $value) {
            if (\is_array($value)) {
                if ($value) {
                    $data[$key] = $this->getDataRecursive($value, $length);
                }
            } else {
                if ($key === '$ref') {
                    if (strpos($value, 'http') !== 0) {
                        if (strpos($value, '#') === 0) {
                            $value = $this->baseUrl . $value;
                        } else {
                            $paths = array_filter(explode('/', explode('#', $value)[0]));
                            $uri = explode('/', trim($this->baseUrl, '/'));
                            $uri = array_merge(array_slice($uri, 0, -count($paths)), $paths);
                            $value = implode('/', $uri) . '#' . explode('#', $value)[1];
                        }
                    }
                    $schema = $this->storage->getSchema($value);
                    unset($data[$key]);
                    if ($schema) {
                        $data = array_merge($data, $schema->getData($length + 1));
                    }
                }
            }
        }
        $data2 = $data;
        foreach ($data2 as $key => $value) {
            if ($key === 'allOf') {
                $data = array_merge($data, array_merge_recursive(...$value));
                unset($data['allOf']);
            }
        }
        return $data;
    }

    public function getFragmentData($fragment)
    {
        $data = $this->dataSave ? $this->dataSave : $this->data;
        foreach (array_filter(explode('/', $fragment)) as $key) {
            $data = $data[$key] ?? null;
        }
        return $data;
    }
}