<?php

namespace Yurly\Middleware;

class MiddlewareState
{

    protected $lastResponse;
    protected $isStopped = false;

    /**
     * Set the flag to stop processing future middleware handlers
     */
    public function stop(): void
    {

        $this->isStopped = true;

    }

    /**
     * Returns true if the last middleware handler asked to stop
     */
    public function hasStopped(): bool
    {

        return $this->isStopped;

    }

    /**
     * Set the last response message
     */
    public function setLastResponse($response): void
    {

        $this->lastResponse = $response;

    }

    /**
     * Get the last response message
     */
    public function getLastResponse(): ?string
    {

        return $this->lastResponse;

    }

}