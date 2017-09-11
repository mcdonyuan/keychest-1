<?php

namespace App\Models;

use App\Keychest\Uuids;
use Illuminate\Database\Eloquent\Model;

class WatchAssoc extends Model
{
    const TABLE = 'user_watch_target';

    public $incrementing = true;

    protected $guarded = array();

    protected $table = self::TABLE;

    public function getDates()
    {
        return array('created_at', 'updated_at', 'deleted_at', 'disabled_at', 'auto_scan_added_at');
    }
}
