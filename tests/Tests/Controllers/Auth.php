<?php declare(strict_types=1);

namespace Tests\Controllers;

use Yurly\Core\{
    Controller, 
    Url, 
    Context
};

class Auth extends Controller
{

    /**
     * @before authUser
     */
    public function routeAllowed(): string
    {

        return "routeAuthOkay";

    }

    /**
     * @before authUser
     */
    public function routeNotAllowed(): string
    {

        return "routeAuthShouldNotSeeThis";

    }

    /**
     * @before authUser
     */
    public function routeNotAllowedOutside(): string
    {

        return "routeAuthShouldNotSeeThis";

    }

    public function routeNotAuthorized(): string
    {

        return "routeNotAuthorized";

    }

    /**
     * Authorizes the user
     */
    public function authUser(Url $url, Context $context): ?string
    {

        // One method:
        switch($url->getRequestUri()) {
            case '/auth/notallowed':
                return 'routeNotAuthorized';
        }

        // Another method:
        switch($context->getCaller()->getMethod()) {
            case 'routeNotallowedoutside': // Note case change!
                return 'Index::routeNotAuthorized';
        }

        // No response allows the rest through
        return null;

    }

}
