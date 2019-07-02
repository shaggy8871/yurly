<?php declare(strict_types=1);

namespace Yurly\Inject\Response;

use Yurly\Core\Context;

class Response extends ResponseFoundation implements ResponseInterface
{

    private $responseClass;

    /**
     * Use our own constructor, do not call the parent constructor so the
     * view directory is not set initially
     */
    public function __construct(Context $context)
    {

        $this->context = $context;

    }

    /**
     * Set the response class
     */
    public function setResponseClass(ResponseInterface $responseClass): ResponseInterface
    {

        $this->responseClass = $responseClass;

        return $this; // allow for chaining

    }

    /**
     * Overridden response class method
     */
    public function setView(array $view): ResponseInterface
    {

        if (!$this->responseClass) {
            throw new \Exception('No Response type defined. Use $response->setResponseClass() to indicate.');
        }

        $this->responseClass->setView($view);

        return $this; // allow for chaining

    }

    /**
     * Overridden response class method
     */
    public function setViewDir(string $dir): ResponseInterface
    {

        if (!$this->responseClass) {
            throw new \Exception('No Response type defined. Use $response->setResponseClass() to indicate.');
        }

        $this->responseClass->setViewDir($dir);

        return $this; // allow for chaining

    }

    /**
     * Overridden response class method
     */
    public function setViewParams($params = null): ResponseInterface
    {

        if (!$this->responseClass) {
            throw new \Exception('No Response type defined. Use $response->setResponseClass() to indicate.');
        }

        $this->responseClass->setViewParams($params);

        return $this; // allow for chaining

    }

    /**
     * Overridden response class method
     */
    public function setStatusCode(int $statusCode): ResponseInterface
    {

        if (!$this->responseClass) {
            throw new \Exception('No Response type defined. Use $response->setResponseClass() to indicate.');
        }

        $this->responseClass->setStatusCode($statusCode);

        return $this; // allow for chaining

    }

    /**
     * Overridden response class method
     */
    public function setContentType(string $contentType): ResponseInterface
    {

        if (!$this->responseClass) {
            throw new \Exception('No Response type defined. Use $response->setResponseClass() to indicate.');
        }

        $this->responseClass->setContentType($contentType);

        return $this; // allow for chaining

    }

    /**
     * Overridden response class method
     */
    public function render($params = null): void
    {

        if (!$this->responseClass) {
            throw new \Exception('No Response type defined. Use $response->setResponseClass() to indicate.');
        }

        $this->responseClass->render($params);

    }

}
