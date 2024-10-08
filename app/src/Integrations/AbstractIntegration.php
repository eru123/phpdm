<?php

namespace App\Integrations;

abstract class AbstractIntegration
{
    /**
     * Get and transform raw data into ingestable data
     * @param mixed $data The raw data
     * @return mixed The transformed data
     */
    abstract public function transform($data);

    /**
     * Store the transformed data
     * @param mixed $data The transformed data
     * @return void
     */
    abstract public function ingest($data);
}
