<?php declare(strict_types=1);

namespace Yurly\Inject\Request;

class Request extends RequestFoundation implements RequestInterface
{

    const TYPE_GET = 'Get';
    const TYPE_POST = 'Post';
    const TYPE_PUT = 'Put';
    const TYPE_DELETE = 'Delete';
    const TYPE_OPTIONS = 'Options';
    const TYPE_PATCH = 'Patch';

    protected $get;
    protected $post;
    protected $put;
    protected $args;
    protected $routeParams;

    /*
     * Returns a GET request object
     */
    public function get(): Get
    {

        if (!$this->get) {
            $this->get = new Get($this->context);
        }

        return $this->get;

    }

    /*
     * Returns a POST request object
     */
    public function post(): Post
    {

        if (!$this->post) {
            $this->post = new Post($this->context);
        }

        return $this->post;

    }

    /*
     * Returns a PUT request object
     */
    public function put(): Put
    {

        if (!$this->put) {
            $this->put = new Put($this->context);
        }

        return $this->put;

    }

    /*
     * Returns route parameters
     */
    public function routeParams(): RouteParams
    {

        if (!$this->routeParams) {
            $this->routeParams = new RouteParams($this->context);
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
     * @return boolean if this request is a OPTIONS
     */
    public function isOptions(): bool
    {

        return $this->type == self::TYPE_OPTIONS;

    }

    /**
     * @return boolean if this request is a PATCH
     */
    public function isPatch(): bool
    {

        return $this->type == self::TYPE_PATCH;

    }

    /*
     * To meet contract requirements
     */
    public function toArray(): array
    {

        return [];

    }

}
