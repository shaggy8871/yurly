<?php declare(strict_types=1);

namespace Yurly\Inject\Response;

use Yurly\Core\{Context, Router};
use Yurly\Core\Utils\{Annotations, Canonical};
use Yurly\Inject\Response\Exception\ResponseConfigException;

abstract class ResponseFoundation
{

    protected $context;
    protected $viewDir = '';
    protected $viewFilename = '';
    protected $viewParams;
    protected $statusCode = 200;
    protected $contentType = 'text/html';

    public function __construct(Context $context)
    {

        $this->context = $context;

        // Attempt to auto-detect the view directory path
        if (isset($this->context->getProject()->path) && !$this->viewDir) {
            $this->setViewDir($this->context->getProject()->path . '/Views');
        }

    }

    /**
     * Set defaults for the response class post instantiation
     */
    public function setDefaults(array $defaults): void
    {

        if (isset($defaults['viewDir'])) {
            $this->setViewDir($defaults['viewDir']);
        }
        if (isset($defaults['viewFilename'])) {
            $this->setViewFilename($defaults['viewFilename']);
        }

        if (is_array($defaults['view'])) {
            $this->setView($defaults['view']);
        }

    }

    /**
     * Change the view filename and base directory
     */
    public function setView(array $view): ResponseInterface
    {

        if ((isset($view['dir'])) && (isset($view['filename']))) {
            $this->viewDir = $view['dir'];
            $this->viewFilename = $view['filename'];
        } else {
            throw new ResponseConfigException("Parameter 1 of setView must contain keys 'dir' and 'filename'.");
        }

        return $this; // allow for chaining

    }

    /**
     * Change the view base directory
     */
    public function setViewDir(string $dir): ResponseInterface
    {

        $this->viewDir = $dir;

        return $this; // allow for chaining

    }

    /**
     * Change the view filename and path
     */
    public function setViewFilename(string $filename): ResponseInterface
    {

        $this->viewFilename = $filename;

        return $this; // allow for chaining

    }

    /**
     * Set the view parameters prior to rendering
     */
    public function setViewParams($params = null): ResponseInterface
    {

        $this->viewParams = $params;

        return $this; // allow for chaining

    }

    /**
     * Set the response status code
     */
    public function setStatusCode(int $statusCode): ResponseInterface
    {

        $this->statusCode = $statusCode;

        return $this; // allow for chaining

    }

    /**
     * Set the content type to something other than the default
     */
    public function setContentType(string $contentType): ResponseInterface
    {

        $this->contentType = $contentType;

        return $this; // allow for chaining

    }

    /**
     * Sends a Flash message that disappears on the next page view
     */
    public function flash(string $key, $message): void
    {

        if (session_status() == PHP_SESSION_NONE) {
            throw new ResponseConfigException("Flash message requires sessions to be enabled.");
        }

        if (isset($_SESSION['YURLY.flash'])) {
            $flash = json_decode($_SESSION['YURLY.flash']);
        } else {
            $flash = [];
        }

        if (is_array($flash)) {
            $flash[$key] = $message;
        } else {
            $flash = [
                $key => $message
            ];
        }

        $_SESSION['YURLY.flash'] = json_encode($flash);

    }

    /**
     * Redirect to the specified URL
     */
    public function redirect(string $url, int $statusCode = 302): void
    {

        header(sprintf("Location: %s", $url), true, $statusCode);
        die(); // make sure we stop

    }

    /**
     * Looks up the canonical URL for a method if it's available via DocBlock
     * The $method parameter should be of type callable, which is a string of format
     * class::methodName or an array of [class, methodName]
     */
    public function urlFor($callback, ?array $params = null): string
    {

        return (new Router($this->context->getProject()))->urlFor($callback, $params, $this->context->getCaller());

    }

    /**
     * Handy method that combines redirect and urlFor
     */
    public function redirectToUrl($callback, ?array $params = null): void
    {

        $this->redirect($this->urlFor($callback, $params));

    }

    /**
     * Returns the saved context
     */
    public function getContext(): Context
    {

        return $this->context;

    }

    /**
     * Return all public and protected values
     */
    public function __get(string $property)
    {

        if (!property_exists($this, $property)) {
            throw new UnknownPropertyException($property, __CLASS__);
        }

        $reflect = new \ReflectionProperty($this, $property);
        if (!$reflect->isPrivate()) {
            return $this->$property;
        }

    }

    /**
     * If the response class itself is output, call the render method automatically
     */
    public function __toString()
    {

        if (method_exists($this, 'render')) {
            $this->render();
        }

    }

}
