<?php declare(strict_types=1);

namespace Yurly\Inject\Request;

class Request extends RequestFoundation implements RequestInterface
{

    const TYPE_GET = 'Get';
    const TYPE_POST = 'Post';
    const TYPE_PUT = 'Put';
    const TYPE_DELETE = 'Delete';
    const TYPE_PATCH = 'Patch';
    const TYPE_ROUTE_PARAMS = 'RouteParams';

    protected $get;
    protected $post;
    protected $put;
    protected $delete;
    protected $patch;
    protected $routeParams;

    /**
     * Returns a GET request object
     */
    public function get(): Get
    {

        if (!$this->get) {
            $this->get = new Get($this->context);
            $this->get->hydrate();
        }

        return $this->get;

    }

    /**
     * Returns a POST request object
     */
    public function post(): Post
    {

        if (!$this->post) {
            $this->post = new Post($this->context);
            $this->post->hydrate();
        }

        return $this->post;

    }

    /**
     * Returns a PUT request object
     */
    public function put(): Put
    {

        if (!$this->put) {
            $this->put = new Put($this->context);
            $this->put->hydrate();
        }

        return $this->put;

    }

    /**
     * Returns a DELETE request object
     */
    public function delete(): Delete
    {

        if (!$this->delete) {
            $this->delete = new Delete($this->context);
            $this->delete->hydrate();
        }

        return $this->delete;

    }

    /**
     * Returns a PATCH request object
     */
    public function patch(): Patch
    {

        if (!$this->patch) {
            $this->patch = new Patch($this->context);
            $this->patch->hydrate();
        }

        return $this->patch;

    }

    /**
     * Returns route parameters
     */
    public function routeParams(): RouteParams
    {

        if (!$this->routeParams) {
            $this->routeParams = new RouteParams($this->context);
            $this->routeParams->hydrate();
        }

        return $this->routeParams;

    }

    /**
     * @return Boolean if this request is a GET
     */
    public function isGet(): bool
    {

        return $this->type == self::TYPE_GET;

    }

    /**
     * @return boolean if this request is a POST
     */
    public function isPost(): bool
    {

        return $this->type == self::TYPE_POST;

    }

    /**
     * @return boolean if this request is a PUT
     */
    public function isPut(): bool
    {

        return $this->type == self::TYPE_PUT;

    }

    /**
     * @return boolean if this request is a DELETE
     */
    public function isDelete(): bool
    {

        return $this->type == self::TYPE_DELETE;

    }

    /**
     * @return boolean if this request is a PATCH
     */
    public function isPatch(): bool
    {

        return $this->type == self::TYPE_PATCH;

    }

    /**
     * @return boolean if this request is a RouteParams
     */
    public function isRouteParams(): bool
    {

        return $this->type == self::TYPE_ROUTE_PARAMS;

    }

    /**
     * Set the request type with props.
     */
    public function setTypeProps(string $type, array $props): void
    {

        switch($type) {
            case self::TYPE_GET:
                $this->get = new Get($this->context);
                $this->get->setProps($props);
                break;
            case self::TYPE_POST:
                $this->post = new Post($this->context);
                $this->post->setProps($props);
                break;
            case self::TYPE_PUT:
                $this->put = new Put($this->context);
                $this->put->setProps($props);
                break;
            case self::TYPE_DELETE:
                $this->delete = new Delete($this->context);
                $this->delete->setProps($props);
                break;
            case self::TYPE_PATCH:
                $this->patch = new Patch($this->context);
                $this->patch->setProps($props);
                break;
            case self::TYPE_ROUTE_PARAMS:
                $this->routeParams = new RouteParams($this->context);
                $this->routeParams->setProps($props);
                break;
        }

    }

    /**
     * To meet contract requirements
     */
    public function hydrate(): void
    {

    }

    /**
     * To meet contract requirements
     */
    public function toArray(): array
    {

        return [];

    }

}
