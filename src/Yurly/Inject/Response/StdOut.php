<?php declare(strict_types=1);

namespace Yurly\Inject\Response;

class StdOut extends ResponseFoundation implements ResponseInterface
{

    /*
     * Render must send through a string
     */
    public function render($params = null): void
    {

        $params = ($params != null ? $params : $this->viewParams);

        if (!is_string($params)) {
            throw new InvalidResponseException('StdOut response value must be a string');
        }

        fwrite(STDOUT, $params);

    }

}
