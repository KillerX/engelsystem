<?php

namespace Engelsystem\Controllers;

use Engelsystem\Config\Config;
use Engelsystem\Helpers\Authenticator;
use Engelsystem\Http\Request;
use Engelsystem\Http\Response;
use Engelsystem\Models\Shifts\Schedule;
use Engelsystem\Models\Shifts\Shift;
use Psr\Log\LoggerInterface;

class ShiftExportController extends BaseController
{
    /** @var Authenticator */
    protected $auth;

    /** @var Config */
    protected $config;

    /** @var LoggerInterface */
    protected $log;

    /** @var Response */
    protected $response;

    /** @var Request */
    protected $request;

    /** @var Schedule */
    protected $schedule;

    /** @var Shift */
    protected $shift;

    /** @var array */
    protected $permissions = [
        'export' => 'shifts_admin',
    ];

    /**
     * @param Authenticator   $auth
     * @param Config          $config
     * @param LoggerInterface $log
     * @param Response        $response
     * @param Request         $request
     * @param Shift           $shift
     */
    public function __construct(
        Authenticator $auth,
        Config $config,
        LoggerInterface $log,
        Response $response,
        Request $request,
        Schedule $schedule,
        Shift $shift,
    ) {
        $this->auth = $auth;
        $this->config = $config;
        $this->log = $log;
        $this->response = $response;
        $this->request = $request;
        $this->schedule = $schedule;
        $this->shift = $shift;
    }

    /**
     * @return Response
     */
    public function index(Request $request): Response
    {
        $id = $request->getAttribute('id');
        $y = $this->shift->findOrFail($id)->entries()->get();
        $y->load('user.personalData');

        $this->response->headers->set('Content-Type', 'text/csv');
        $this->response->headers->set('Content-Description', 'Shift Export');
        $this->response->headers->set('Content-Disposition', 'attachment; filename=event-' . $id . '.csv');
        $this->response->headers->set('Content-Transfer-Encoding', 'binary');
        $this->response->headers->set('Expires', '0');
        $this->response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        $this->response->headers->set('Pragma', 'public');
        //$this->response->headers->set('Content-Length: ' . filesize($file));
        return $this->response->withView(
            'pages/shifts/export.twig',
            ['sch' => $y]
        );
    }
}
