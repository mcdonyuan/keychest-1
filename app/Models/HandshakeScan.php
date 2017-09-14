<?php
/**
 * Created by PhpStorm.
 * User: dusanklinec
 * Date: 26.05.17
 * Time: 14:31
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class HandshakeScan extends Model
{
    const TABLE = 'scan_handshakes';

    public $incrementing = true;

    protected $guarded = array();

    protected $table = self::TABLE;

    /**
     * Get the watch_id record for this result
     */
    public function watch_target()
    {
        return $this->belongsTo('App\Models\WatchTarget', 'watch_id');
    }
}
