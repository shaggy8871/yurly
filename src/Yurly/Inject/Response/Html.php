<?php declare(strict_types=1);

namespace Yurly\Inject\Response;

class Html extends ResponseFoundation implements ResponseInterface
{

    protected $contentType = 'text/html';

    public function render($params = null): void
    {

        $params = ($params != null ? $params : $this->viewParams);

        if (!headers_sent()) {
            http_response_code($this->statusCode);
            header('Content-Type: ' . $this->contentType);
        }

        echo $params;

    }

}
