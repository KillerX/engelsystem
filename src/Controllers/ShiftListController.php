<?php

namespace Engelsystem\Controllers;

use Engelsystem\Config\Config;
use Engelsystem\Helpers\Authenticator;
use Engelsystem\Http\Redirector;
use Engelsystem\Http\Request;
use Engelsystem\Http\Response;
use Illuminate\Database\Capsule\Manager as DB;
use Engelsystem\Models\Shifts\Schedule;
use Engelsystem\Models\Shifts\Shift;
use Psr\Log\LoggerInterface;

class ShiftListController extends BaseController
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
     * @param Redirector      $redirector
     */
    public function __construct(
        Authenticator $auth,
        Config $config,
        Redirector $redirector,
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
        $this->redirect = $redirector;
        $this->schedule = $schedule;
        $this->shift = $shift;
    }


    public function getData($when)
    {
        $user = $this->auth->user();

        $shifts = $this->shift->with(['neededAngels']);

        if ($when == 'history') {
            $shifts = $shifts->where('start', '<', time())
                             ->orderBy('start', 'DESC')
                             ->limit(100);
        } else {
            $shifts = $shifts->where('start', '>', time())
                           ->orderBy('start', 'ASC');
        }

        $shifts = $shifts->get();

        $shift_ids = [];
        foreach ($shifts as $shift) {
            $shift_ids[] = $shift->SID;
        }


        if (count($shift_ids) > 0) {
            $shifts_needs = DB::select(DB::raw("
                SELECT shift_id, angel_type_id, GREATEST(0, count-COALESCE(CNT, 0)) remaining, COALESCE(UserRegistered, 0) user_registered FROM NeededAngelTypes nat
                LEFT JOIN
                    (SELECT SID, TID, COUNT(*) as CNT, SUM(IF(UID = ?, 1, 0)) as UserRegistered FROM ShiftEntry GROUP BY SID, TID) c ON nat.shift_id = c.SID AND nat.angel_type_id = c.TID
                WHERE shift_id IN (" . implode(',', $shift_ids) . ");
            "), [$user->id]);

            foreach ($shifts as $shift) {
                $shift->remaining = 0;
                $shift->border = "primary";
                $shift->registered = false;

                foreach ($shift->neededAngels as $na) {
                    $na->remaining = 0;
                    foreach ($shifts_needs as $sn) {
                        if ($sn->shift_id == $na->shift_id && $sn->angel_type_id == $na->angel_type_id) {
                            $shift->registered |= $sn->user_registered > 0;
                            $na->registered = $sn->user_registered > 0;
                            $na->remaining = $sn->remaining;
                            $shift->remaining += $na->remaining;
                            break;
                        }
                    }
                }

                if ($shift->remaining == 0 || $shift->registered) {
                    $shift->border = "success";
                }
            }
        }

        return $shifts;
    }

    /**
     * @return Response
     */
    public function index(Request $request): Response
    {
        $user = $this->auth->user();
        if (!$user) {
            return $this->redirect->to('/');
        }

        $shifts = $this->getData('upcoming');

        return $this->response->withView(
            'pages/shifts/list.twig',
            [
                'sch' => $shifts,
                'admin' => false,
                'user' => $user,
                'title' => 'Upcoming jobs',
            ]
        );
    }

    /**
     * @return Response
     */
    public function history(Request $request): Response
    {
        $user = $this->auth->user();
        if (!$user) {
            return $this->redirect->to('/');
        }

        $shifts = $this->getData('history');

        return $this->response->withView(
            'pages/shifts/list.twig',
            [
                'sch' => $shifts,
                'admin' => false,
                'user' => $user,
                'title' => 'Job history',
            ]
        );
    }
}
