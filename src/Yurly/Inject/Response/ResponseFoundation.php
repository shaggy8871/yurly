<?php declare(strict_types=1);

namespace Yurly\Inject\Response;

use Yurly\Core\Context;
use Yurly\Core\Utils\{Annotations, Canonical};
use Yurly\Inject\Response\Exception\{ReverseRouteLookupException, ResponseConfigException};

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
        if (isset($this->context->getProject()->path)) {
            $this->setViewDir($this->context->getProject()->path . '/Views');
        }

    }

    /*
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

    /*
    * Change the view filename and base directory
    */
    public function setView(array $view): ResponseInterface
    {

        if ((isset($view['dir'])) && (isset($view['filename']))) {
            $this->viewDir = $view['dir'];
            $this->viewFilename = $view['filename'];
        } else {
            throw new ResponseConfigException("Parameter 1 of setView must contain keys 'dir' and 'filename'");
        }

        return $this; // allow for chaining

    }

    /*
    * Change the view base directory
    */
    public function setViewDir(string $dir): ResponseInterface
    {

        $this->viewDir = $dir;

        return $this; // allow for chaining

    }

    /*
     * Change the view filename and path
     */
    public function setViewFilename(string $filename): ResponseInterface
    {

        $this->viewFilename = $filename;

        return $this; // allow for chaining

    }

    /*
     * Set the view parameters prior to rendering
     */
    public function setViewParams($params = null): ResponseInterface
    {

        $this->viewParams = $params;

        return $this; // allow for chaining

    }

    /*
     * Set the response status code
     */
    public function setStatusCode(int $statusCode): ResponseInterface
    {

        $this->statusCode = $statusCode;

        return $this; // allow for chaining

    }

    /*
    * Set the content type to something other than the default
    */
    public function setContentType(string $contentType): ResponseInterface
    {

        $this->contentType = $contentType;

        return $this; // allow for chaining

    }

    /*
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

    /*
     * Redirect to the specified URL
     */
    public function redirect(string $url, int $statusCode = 302): void
    {

        header(sprintf("Location: %s", $url), true, $statusCode);
        die(); // make sure we stop

    }

    /*
     * Looks up the canonical URL for a method if it's available via DocBlock
     * The $method parameter should be of type callable, which is a string of format
     * class::methodName or an array of [class, methodName]
     */
    public function urlFor($callback, ?array $params = null): string
    {

        try {
            // Standard array-based callable [$object, $methodName]
            if (is_array($callback)) {
                $reflection = new \ReflectionMethod($callback[0], $callback[1]);
            } else
            // Static callable - class::methodName
            if (is_callable($callback)) {
                $reflection = new \ReflectionMethod($callback);
            } else
            // Fallback 1 - try to make it callable by adding a namespace
            if (strpos($callback, '::') !== false) {
                $reflection = new \ReflectionMethod($this->context->getProject()->ns . '\\Controllers\\' . $callback);
            } else
            // Fallback 2 - if partial string, assume it's a method name in the current controller class
            if ($this->context->getCaller()->controller) {
                $reflection = new \ReflectionMethod($this->context->getCaller()->controller, $callback);
            } else {
                throw new ReverseRouteLookupException("Parameter passed to the urlFor method is not callable");
            }
        } catch (\ReflectionException $e) {
            throw new ReverseRouteLookupException("Parameter passed to the urlFor method is not callable");
        }

        $doc = $reflection->getDocComment();
        if ($doc) {
            $annotations = Annotations::parseDocBlock($doc);
            if (isset($annotations['canonical'])) {
                $canonical = $annotations['canonical'];
            }
        }

        // If it can't be determined from the DocBlock, try to guess it
        if (!isset($canonical)) {
            $className = $reflection->getDeclaringClass()->getShortName();
            if ($className == 'Index') {
                $className = '';
            }
            $methodName = ltrim($reflection->getName(), 'route');
            if ($methodName == 'Default') {
                $methodName = '';
            }
            $canonical = str_replace('_', '-',
                strtolower(($className ? '/' . $className : '') . ($methodName ? '/' . $methodName : '/'))
            );
        }

        // Replace in parameters
        $canonical = $this->context->getUrl()->rootUri . Canonical::replaceIntoTemplate($canonical, $params);

        return $canonical;

    }

    /*
     * Handy method that combines redirect and urlFor
     */
    public function redirectToUrl($callback, ?array $params = null): void
    {

        $this->redirect($this->urlFor($callback, $params));

    }

    /*
     * Returns the saved context
     */
    public function getContext(): Context
    {

        return $this->context;

    }

    /*
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

    /*
     * If the response class itself is output, call the render method automatically
     */
    public function __toString()
    {

        if (method_exists($this, 'render')) {
            $this->render();
        }

    }

}
