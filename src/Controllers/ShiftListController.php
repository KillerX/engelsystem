<?php

namespace Engelsystem\Controllers;

use Carbon\Carbon;
use Engelsystem\Config\Config;
use Engelsystem\Helpers\Authenticator;
use Engelsystem\Http\Redirector;
use Engelsystem\Http\Request;
use Engelsystem\Http\Response;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Collection;
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

    protected $redirect;

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
SELECT shift_id,
angel_type_id,
GREATEST(0, count-COALESCE(CNT, 0)) remaining,
COALESCE(UserRegistered, 0) user_registered,
c.UserEntryId user_entry_id,
c.u users_list
FROM NeededAngelTypes nat
                LEFT JOIN
                    (
                        SELECT GROUP_CONCAT(CONCAT(upd.first_name, ' ', upd.last_name)) as u, upd.user_id, upd.first_name, upd.last_name, SID, TID, COUNT(*) as CNT, SUM(IF(UID = ?, 1, 0)) as UserRegistered, MAX(IF(UID = ?, ShiftEntry.id, NULL)) as UserEntryId FROM ShiftEntry
                        LEFT JOIN users_personal_data upd ON ShiftEntry.UID = upd.user_id
                        WHERE SID IN (" . implode(',', $shift_ids) . ")
                        GROUP BY SID, TID
                    ) c ON nat.shift_id = c.SID AND nat.angel_type_id = c.TID
                WHERE shift_id IN (" . implode(',', $shift_ids) . ")
        GROUP BY shift_id, angel_type_id
ORDER BY `nat`.`shift_id` ASC;
            "), [$user->id, $user->id]);

            foreach ($shifts as $shift) {
                $shift->remaining = 0;
                $shift->border = "primary";
                $shift->registered = false;

                foreach ($shift->neededAngels as $na) {
                    $na->remaining = 0;
                    $na->registered_users = "";
                    $na->registered = false;
                    $na->user_entry_id = null;
                    foreach ($shifts_needs as $sn) {
                        if ($sn->shift_id == $na->shift_id && $sn->angel_type_id == $na->angel_type_id) {
                            $shift->registered |= $sn->user_registered > 0;
                            $na->registered = $sn->user_registered > 0;
                            $na->user_entry_id = $sn->user_entry_id;
                            $na->remaining = $sn->remaining;
                            $shift->remaining += $na->remaining;
                            if ($sn->users_list != null) {
                                $na->registered_users = $sn->users_list;
                            }
                            break;
                        }
                    }
                }

                if ($shift->remaining == 0 || $shift->registered) {
                    $shift->border = "success";
                }
            }
        }

        $this->decorate($shifts, $when);

        return $shifts;
    }

    /**
     * Add the presentation fields the jobs list template renders: a localized date
     * label, the place used for the card and the place filter, and the time bucket
     * the shift is grouped into.
     *
     * @param iterable $shifts
     * @param string   $when
     */
    protected function decorate($shifts, string $when): void
    {
        $locale = session('locale', $this->config->get('default_locale'));
        $endOfThisWeek = Carbon::now()->endOfWeek();
        $endOfNextWeek = $endOfThisWeek->copy()->addWeek();

        foreach ($shifts as $shift) {
            $start = Carbon::createFromTimestamp($shift->start);

            $shift->date_label = $start->locale($locale)->isoFormat('ddd D. MMM');
            $shift->place_label = $shift->address ?: ($shift->room ? $shift->room->name : '');

            if ($when == 'history') {
                $shift->bucket = 'earlier';
            } elseif ($start <= $endOfThisWeek) {
                $shift->bucket = 'this_week';
            } elseif ($start <= $endOfNextWeek) {
                $shift->bucket = 'next_week';
            } else {
                $shift->bucket = 'later';
            }
        }
    }

    /**
     * Group the shifts into the ordered time buckets the list renders as sections.
     * Empty buckets are dropped.
     *
     * @param iterable $shifts
     * @return array[]
     */
    protected function groupShifts($shifts): array
    {
        $buckets = [
            'this_week' => __('This week'),
            'next_week' => __('Next week'),
            'later'     => __('Later'),
            'earlier'   => __('Earlier'),
        ];

        $groups = [];
        foreach ($buckets as $key => $label) {
            $groups[$key] = ['key' => $key, 'label' => $label, 'shifts' => []];
        }

        foreach ($shifts as $shift) {
            $groups[$shift->bucket]['shifts'][] = $shift;
        }

        return array_values(array_filter($groups, function ($group) {
            return count($group['shifts']) > 0;
        }));
    }

    /**
     * The distinct places of the given shifts, for the place filter.
     *
     * @param iterable $shifts
     * @return string[]
     */
    protected function places($shifts): array
    {
        $places = [];
        foreach ($shifts as $shift) {
            if ($shift->place_label && !in_array($shift->place_label, $places)) {
                $places[] = $shift->place_label;
            }
        }
        sort($places);

        return $places;
    }

    /**
     * @param Collection $shifts
     * @param string     $view   One of upcoming, mine, history
     * @param string     $title
     * @return Response
     */
    protected function renderList(Collection $shifts, string $view, string $title): Response
    {
        $mineOnly = $view == 'mine';

        if ($mineOnly) {
            $shifts = $shifts->filter(function ($shift) {
                return (bool) $shift->registered;
            })->values();
        }

        return $this->response->withView(
            'pages/shifts/list.twig',
            [
                'sch'       => $shifts,
                'groups'    => $this->groupShifts($shifts),
                'places'    => $this->places($shifts),
                'view'      => $view,
                'mine_only' => $mineOnly,
                'admin'     => false,
                'user'      => $this->auth->user(),
                'title'     => $title,
            ]
        );
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

        return $this->renderList($this->getData('upcoming'), 'upcoming', __('Upcoming jobs'));
    }

    /**
     * @return Response
     */
    public function mine(Request $request): Response
    {
        $user = $this->auth->user();
        if (!$user) {
            return $this->redirect->to('/');
        }

        return $this->renderList($this->getData('upcoming'), 'mine', __('My upcoming jobs'));
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

        return $this->renderList($this->getData('history'), 'history', __('Job history'));
    }
}
