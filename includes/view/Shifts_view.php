<?php

use Engelsystem\Models\Room;
use Engelsystem\Models\User\User;
use Engelsystem\ShiftSignupState;
use Illuminate\Support\Collection;

/**
 * Renders the basic shift view header.
 *
 * @param array $shift
 * @param Room  $room
 * @return string HTML
 */
function Shift_view_header($shift, Room $room)
{
    $title = $shift['URL'] != ''
        ? '<a href="' . $shift['URL'] . '">' . htmlspecialchars((string)$shift['title']) . '</a>'
        : htmlspecialchars((string)$shift['title']);

    if ($shift['address']) {
        $addressCard = '<div class="bd-stat"><div class="bd-stat__label">' . __('Address') . '</div>'
            . '<div class="bd-stat__value"><a href="https://www.google.com/maps/search/?api=1&query='
            . urlencode($shift['address']) . '" target="_blank" rel="noopener">📍 '
            . htmlspecialchars((string)$shift['address']) . '</a></div></div>';
    } else {
        $addressCard = '<div class="bd-stat"><div class="bd-stat__label">' . __('Location') . '</div>'
            . '<div class="bd-stat__value">📍 ' . Room_name_render($room) . '</div></div>';
    }

    return '<div class="bd-stats">'
        . '<div class="bd-stat"><div class="bd-stat__label">' . __('Title') . '</div>'
        . '<div class="bd-stat__value">' . $title . '</div></div>'
        . '<div class="bd-stat"><div class="bd-stat__label">' . __('Start') . '</div>'
        . '<div class="bd-stat__value">📅 ' . date(__('d.m.Y'), $shift['start']) . '</div>'
        . '<div class="bd-stat__value bd-stat__value--accent">🕘 ' . date('H:i', $shift['start']) . '</div></div>'
        . '<div class="bd-stat"><div class="bd-stat__label">' . __('End') . '</div>'
        . '<div class="bd-stat__value">📅 ' . date(__('d.m.Y'), $shift['end']) . '</div>'
        . '<div class="bd-stat__value bd-stat__value--accent">🕘 ' . date('H:i', $shift['end']) . '</div></div>'
        . $addressCard
        . '</div>';
}

/**
 * @param array $shift
 * @return string
 */
function Shift_editor_info_render($shift)
{
    $info = [];
    if (!empty($shift['created_by_user_id'])) {
        $info[] = sprintf(
            icon('plus-lg') . __('created at %s by %s'),
            date('Y-m-d H:i', $shift['created_at_timestamp']),
            User_Nick_render(User::find($shift['created_by_user_id']))
        );
    }
    if (!empty($shift['edited_by_user_id'])) {
        $info[] = sprintf(
            icon('pencil') . __('edited at %s by %s'),
            date('Y-m-d H:i', $shift['edited_at_timestamp']),
            User_Nick_render(User::find($shift['edited_by_user_id']))
        );
    }
    return join('<br />', $info);
}

/**
 * @param array $shift
 * @param array $angeltype
 * @param array $user_angeltype
 * @return string
 */
function Shift_signup_button_render($shift, $angeltype, $user_angeltype = null)
{
    if (empty($user_angeltype)) {
        $user_angeltype = UserAngelType_by_User_and_AngelType(auth()->user()->id, $angeltype);
    }

    if (
        isset($angeltype['shift_signup_state'])
        && (
            $angeltype['shift_signup_state']->isSignupAllowed()
            || User_is_AngelType_supporter(auth()->user(), $angeltype)
        )
    ) {
        return button(shift_entry_create_link($shift, $angeltype), __('Sign up'), 'btn-signup');
    } elseif (empty($user_angeltype)) {
        return button(
            page_link_to('angeltypes', ['action' => 'view', 'angeltype_id' => $angeltype['id']]),
            sprintf(__('Become %s'),
                $angeltype['name']),
            'btn-pill-outline'
        );
    }
    return '';
}

/**
 * @param array            $shift
 * @param array            $shifttype
 * @param Room             $room
 * @param array[]          $angeltypes_source
 * @param ShiftSignupState $shift_signup_state
 * @return string
 */
function Shift_view($shift, $shifttype, Room $room, $angeltypes_source, ShiftSignupState $shift_signup_state)
{
    $shift_admin = auth()->can('admin_shifts');
    $user_shift_admin = auth()->can('user_shifts_admin');
    $admin_rooms = auth()->can('admin_rooms');
    $admin_shifttypes = auth()->can('shifttypes');

    $parsedown = new Parsedown();

    $angeltypes = [];
    foreach ($angeltypes_source as $angeltype) {
        $angeltypes[$angeltype['id']] = $angeltype;
    }

    $needed_angels = '';
    $neededAngels = new Collection($shift['NeedAngels']);
    foreach ($neededAngels as $needed_angeltype) {
        $needed_angels .= Shift_view_render_needed_angeltype($needed_angeltype, $angeltypes, $shift, $user_shift_admin);
    }

    $shiftEntry = new Collection($shift['ShiftEntry']);
    foreach ($shiftEntry->groupBy('TID') as $angelTypes) {
        /** @var Collection $angelTypes */
        $type = $angelTypes->first()['TID'];
        if (!$neededAngels->where('TID', $type)->first()) {
            $needed_angels .= Shift_view_render_needed_angeltype([
                'TID'        => $type,
                'count'      => 0,
                'restricted' => true,
                'taken'      => $angelTypes->count(),
            ], $angeltypes, $shift, $user_shift_admin);
        }
    }

    $content = [msg()];

    if ($shift_signup_state->getState() == ShiftSignupState::COLLIDES) {
        $content[] = info(__('This shift collides with one of your shifts.'), true);
    }

    if ($shift_signup_state->getState() == ShiftSignupState::SIGNED_UP) {
        $content[] = info(__('You are signed up for this shift.'), true);
    }

    if (config('signup_advance_hours') && $shift['start'] > time() + config('signup_advance_hours') * 3600) {
        $content[] = info(sprintf(
            __('This shift is in the far future and becomes available for signup at %s.'),
            date(__('Y-m-d') . ' H:i', $shift['start'] - config('signup_advance_hours') * 3600)
        ), true);
    }

    // ---- title header + action buttons ----
    $actions = '';
    if ($shift_admin) {
        $actions .= '<a class="btn btn-pill-outline" href="' . shift_edit_link($shift) . '">✏️ ' . __('edit') . '</a>';
        $actions .= '<a class="btn btn-pill-danger" href="' . shift_delete_link($shift) . '">🗑 ' . __('delete') . '</a>';
        $actions .= '<a class="btn btn-pill-outline" href="/export_shift/' . $shift['SID'] . '">📊 Export</a>';
    }
    $actions .= '<a class="btn btn-signup" href="' . user_link(auth()->user()->id) . '">👤 ' . __('My shifts') . '</a>';

    $content[] = '<a class="bd-back" href="/shifts/list">← ' . __('Back to jobs') . '</a>'
        . '<div class="bd-detail__head">'
        . '<div class="bd-detail__titlewrap">'
        . '<h1 class="bd-title">' . htmlspecialchars((string)$shift['title']) . '</h1>'
        . '<span class="bd-countdown">⏳ <span class="moment-countdown" data-timestamp="'
        . $shift['start'] . '">%c</span></span>'
        . '</div>'
        . '<div class="bd-actions">' . $actions . '</div>'
        . '</div>';

    // ---- stat cards ----
    $content[] = Shift_view_header($shift, $room);

    // ---- right column: responsible + requirements + description ----
    $rightPanels = '<div class="bd-panel"><h2>' . __('Ansvarlig') . '</h2>';
    if ($shift['responsible_name']) {
        $name = trim((string)$shift['responsible_name']);
        $initials = '';
        foreach (preg_split('/\s+/', $name) as $word) {
            if ($word !== '') {
                $initials .= mb_strtoupper(mb_substr($word, 0, 1));
            }
        }
        $initials = mb_substr($initials, 0, 2);
        $rightPanels .= '<div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;">'
            . '<span class="bd-avatar">' . htmlspecialchars($initials) . '</span>'
            . '<div class="bd-stat__value">' . htmlspecialchars($name) . '</div></div>';
    }
    if ($shift['responsible_phone']) {
        $rightPanels .= '<div class="bd-contact">📞 ' . __('Telefon') . ': <a href="tel:'
            . htmlspecialchars((string)$shift['responsible_phone']) . '">'
            . htmlspecialchars((string)$shift['responsible_phone']) . '</a></div>';
    }
    if (!$shift['responsible_name'] && !$shift['responsible_phone']) {
        $rightPanels .= '<p class="bd-desc">—</p>';
    }
    $rightPanels .= '</div>';

    if ($shift['requirements']) {
        $rightPanels .= '<div class="bd-panel"><h2>' . __('Bekledning/Nødvendig utstyr') . '</h2>'
            . '<div class="bd-desc">' . $parsedown->parse((string)$shift['requirements']) . '</div></div>';
    }

    $rightPanels .= '<div class="bd-panel"><h2>' . __('Description') . '</h2><div class="bd-desc">'
        . $parsedown->parse((string)$shifttype['description'])
        . $parsedown->parse((string)$shift['description'])
        . '</div></div>';

    // ---- left column: needed workers ----
    $editor = $shift_admin ? '<div class="bd-meta-note"><span style="font-size:16px;">➕</span> '
        . Shift_editor_info_render($shift) . '</div>' : '';

    $content[] = '<div class="bd-cols">'
        . '<div class="bd-panel"><h2>' . __('Needed workers') . '</h2>' . $needed_angels . $editor . '</div>'
        . '<div class="bd-side">' . $rightPanels . '</div>'
        . '</div>';

    return div('bd-page bd-detail', $content);
}

/**
 * @param array   $needed_angeltype
 * @param array   $angeltypes
 * @param array[] $shift
 * @param bool    $user_shift_admin
 * @return string
 */
function Shift_view_render_needed_angeltype($needed_angeltype, $angeltypes, $shift, $user_shift_admin)
{
    $angeltype = $angeltypes[$needed_angeltype['TID']];
    $angeltype_supporter = User_is_AngelType_supporter(auth()->user(), $angeltype);

    $taken = (int)$needed_angeltype['taken'];
    $count = (int)$needed_angeltype['count'];
    $full = $count > 0 && $taken >= $count;
    $pct = $count > 0 ? min(100, (int)floor($taken * 100 / $count)) : ($taken > 0 ? 100 : 0);
    $label = $full
        ? ($taken . ' / ' . $count . ' · ' . __('Full') . ' 🎉')
        : ($taken . ' / ' . $count);

    $angels = [];
    foreach ($shift['ShiftEntry'] as $shift_entry) {
        if ($shift_entry['TID'] == $needed_angeltype['TID']) {
            $angels[] = Shift_view_render_shift_entry($shift_entry, $user_shift_admin, $angeltype_supporter, $shift);
        }
    }

    return '<div class="bd-need-box">'
        . '<div class="bd-need-box__head"><h3>' . AngelType_name_render($angeltype) . '</h3>'
        . Shift_signup_button_render($shift, $angeltype) . '</div>'
        . '<div class="bd-progress-lg"><div class="bd-progress-lg__bar" style="width:' . $pct . '%;">'
        . $label . '</div></div>'
        . '<div class="worker-chips">' . join('', $angels) . '</div>'
        . '</div>';
}

/**
 * @param array $shift_entry
 * @param bool  $user_shift_admin
 * @param bool  $angeltype_supporter
 * @param array $shift
 * @return string
 */
function Shift_view_render_shift_entry($shift_entry, $user_shift_admin, $angeltype_supporter, $shift)
{
    $entry = User_Nick_render(User::find($shift_entry['UID']));
    if ($shift_entry['freeloaded']) {
        $entry = '<del>' . $entry . '</del>';
    }
    $isUser = $shift_entry['UID'] == auth()->user()->id;
    $controls = '';
    if ($user_shift_admin || $angeltype_supporter || $isUser) {
        $controls .= '<span class="btn-group">';
        if ($user_shift_admin || $isUser) {
            $controls .= button_icon(
                page_link_to('user_myshifts', ['edit' => $shift_entry['id'], 'id' => $shift_entry['UID']]),
                'pencil',
                'btn-sm'
            );
        }
        $angeltype = AngelType($shift_entry['TID']);
        $disabled = Shift_signout_allowed($shift, $angeltype, $shift_entry['UID']) ? '' : ' btn-disabled';
        $controls .= button_icon(shift_entry_delete_link($shift_entry), 'trash', 'btn-sm' . $disabled);
        $controls .= '</span>';
    }
    return '<span class="bd-worker">' . $entry . $controls . '</span>';
}

/**
 * Calc shift length in format 12:23h.
 *
 * @param array $shift
 * @return string
 */
function shift_length($shift)
{
    $length = floor(($shift['end'] - $shift['start']) / (60 * 60)) . ':';
    $length .= str_pad(
            (($shift['end'] - $shift['start']) % (60 * 60)) / 60,
            2,
            '0',
            STR_PAD_LEFT
        ) . 'h';
    return $length;
}
