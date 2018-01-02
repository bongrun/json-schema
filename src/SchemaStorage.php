<?php

namespace bongrun\JsonSchema;

use bongrun\JsonSchema\exception\JsonSchemaException;

/**
 * Class SchemaStorage
 * @package bongrun\JsonSchema
 */
class SchemaStorage
{
    /**
     * @var Schema[]
     */
    private $schemes = [];
    /**
     * @var Schema[]
     */
    private $baseSchemes = [];
    protected $stopAtAnError;

    public function __construct($stopAtAnError = true)
    {
        $this->stopAtAnError = $stopAtAnError;
    }

    /**
     * @param string $uri
     * @param $data
     * @return Schema
     */
    public function addSchema(string $uri, $data = null): Schema
    {
        if (array_key_exists($uri, $this->schemes)) {
            return $this->schemes[$uri];
        }
        if ($this->stopAtAnError) {
            $schema = new Schema($this, $uri, $data);
        } else {
            try {
                $schema = new Schema($this, $uri, $data);
            } catch (JsonSchemaException $e) {
                if (!(explode('#', $uri . '#')[1])) {
                    $this->baseSchemes[explode('#', $uri . '#')[0]] = null;
                }
                $this->schemes[$uri] = null;
                return null;
            }
        }
        if (!array_key_exists($schema->getUri(), $this->schemes)) {
            $this->schemes[$schema->getUri()] = $schema;
            if (!$schema->getFragment()) {
                $this->baseSchemes[$schema->getBaseUrl()] = $schema;
            }
        }
        return $this->schemes[$schema->getUri()];
    }

    /**
     * @param string $uri
     * @return Schema|null
     */
    public function getSchema(string $uri): Schema
    {
        if (array_key_exists($uri, $this->schemes)) {
            return $this->schemes[$uri];
        }
        [$baseUrl, $fragment] = explode('#', $uri . '#');
        if (array_key_exists($baseUrl, $this->baseSchemes)) {
            $schema = $this->baseSchemes[$baseUrl];
        } else {
            $schema = $this->addSchema($uri);
        }
        if (!$schema) {
            return null;
        }
        $fragmentData = $schema->getFragmentData($fragment);
        return $this->addSchema($uri, $fragmentData);
    }
}