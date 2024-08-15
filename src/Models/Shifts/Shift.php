<?php

namespace Engelsystem\Models\Shifts;

use Carbon\Carbon;
use Engelsystem\Models\BaseModel;
use Engelsystem\Models\Room;
use Engelsystem\Models\Shifts\ShiftEntry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @property int                                          $id
 * @property string                                       $name
 * @property string                                       $url
 * @property int                                          $shift_type
 * @property int                                          $minutes_before
 * @property int                                          $minutes_after
 * @property Carbon                                       $created_at
 * @property Carbon                                       $updated_at
 * @property string                                       $responsible_name
 * @property string                                       $responsible_phone
 * @property string                                       $address
 * @property string                                       $requirements
 * @property int                                          $min_age
 * @property int                                          $max_age
 *
 * @property-read QueryBuilder|Collection|ScheduleShift[] $scheduleShifts
 *
 * @method static QueryBuilder|Schedule[] whereId($value)
 * @method static QueryBuilder|Schedule[] whereName($value)
 * @method static QueryBuilder|Schedule[] whereUrl($value)
 * @method static QueryBuilder|Schedule[] whereShiftType($value)
 * @method static QueryBuilder|Schedule[] whereMinutesBefore($value)
 * @method static QueryBuilder|Schedule[] whereMinutesAfter($value)
 * @method static QueryBuilder|Schedule[] whereCreatedAt($value)
 * @method static QueryBuilder|Schedule[] whereUpdatedAt($value)
 */
class Shift extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'Shifts';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'SID';

    public function entries()
    {
        return $this->hasMany(ShiftEntry::class, 'SID', 'SID');
    }

    public function neededAngels()
    {
        return $this->hasMany(NeededAngelTypes::class, 'shift_id', 'SID');
    }

    public function room()
    {
        return $this->hasOne(Room::class, 'id', 'RID');
    }

    public function canRegister($birthday)
    {
        $userAge = $birthday->diffInYears(Carbon::createFromTimestamp($this->start));
        return $userAge >= $this->min_age && $userAge <= $this->max_age;
    }
}
