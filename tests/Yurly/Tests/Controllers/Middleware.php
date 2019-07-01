<?php declare(strict_types=1);

namespace Yurly\Tests\Controllers;

use Yurly\Core\{
    Controller, 
    Url, 
    Context
};
use Yurly\Inject\Response\Html;
use Yurly\Middleware\MiddlewareState;

class Middleware extends Controller
{

    public function beforeAllRoutes(Context $context, MiddlewareState $state)
    {

        echo "beforeAllRoutes";

    }

    public function afterAllRoutes(Context $context, MiddlewareState $state)
    {

        echo "afterAllRoutes";

    }

    /**
     * @before beforeHandler1, beforeHandler2
     */
    public function routeBeforeHandlerTest(): string
    {

        return "routeBeforeHandlerTest";

    }

    /**
     * @before beforeStop, beforeHandler3
     */
    public function routeBeforeHandlerTestWithStop(): string
    {

        return "routeBeforeHandlerTestWithStop";

    }

    /**
     * @after afterHandler1, afterHandler2
     */
    public function routeAfterHandlerTest(): string
    {

        return "routeAfterHandlerTestFailed";

    }

    /**
     * @after afterStop, afterHandler3
     */
    public function routeAfterHandlerTestWithStop(): string
    {

        return "routeAfterHandlerTestWithStop";

    }

    /**
     * Final route
     */
    public function routeFinal(Html $response): string
    {

        return "Final";

    }

    /**
     * ----------------------------------------------------------------------
     * Middleware functions:
     * ----------------------------------------------------------------------
     */

    /**
     * Called before routeBeforeHandlerTest()
     */
    public function beforeHandler1(): ?string
    {

        echo 'beforeHandler1';

        /**
         * If this route is called, we have failed
         */
        return 'routeFinalFailed';

    }

    /**
     * Called before routeBeforeHandlerTest()
     */
    public function beforeHandler2(): ?string
    {

        echo 'beforeHandler2';

        /**
         * If this route is called instead of routeBeforeHandlerTest(), we have succeeded
         */
        return 'routeFinal';

    }

    /**
     * Called before routeBeforeHandlerTest()
     */
    public function beforeHandler3(): ?string
    {

        echo 'beforeHandler3';

        /**
         * If this route is called, we have failed because we should have stopped prior
         */
        return 'routeFinalFailed';

    }

    /**
     * Called before routeAfterHandlerTest()
     */
    public function afterHandler1(): ?string
    {

        echo 'afterHandler1';

        /**
         * If this is the response, we have failed
         */
        return 'afterHandler1Failed';

    }

    /**
     * Called before routeAfterHandlerTest()
     */
    public function afterHandler2(): ?string
    {

        echo 'afterHandler2';

        /**
         * If this is the response, we have succeeded
         */
        return 'afterHandler2';

    }

    /**
     * Called before routeAfterHandlerTestWithStop()
     */
    public function afterHandler3(): ?string
    {

        echo 'afterHandler3';

        /**
         * If this is the response, we have failed because we should have stopped prior
         */
        return 'afterHandler3Failed';

    }

    /**
     * Called before routeBeforeHandlerTestWithStop()
     */
    public function beforeStop(MiddlewareState $state): ?string
    {

        $state->stop();

        echo 'BeforeStopped';

        /**
         * If this route is called instead of routeBeforeHandlerTestWithStop(), we have succeeded
         */
        return 'routeFinal';

    }

    /**
     * Called after routeAfterHandlerTestWithStop()
     */
    public function afterStop(MiddlewareState $state): ?string
    {

        $state->stop();

        echo 'AfterStopped';

        /**
         * If this is the response, we have succeeded
         */
        return 'afterStop';

    }

}
