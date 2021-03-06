<?php
/**
 * Created by PhpStorm.
 * User: dusanklinec
 * Date: 26.05.17
 * Time: 14:31
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class DnsEntry extends Model
{
    const TABLE = 'scan_dns_entry';

    public $incrementing = true;

    protected $guarded = array();

    protected $table = self::TABLE;

    public function getDates()
    {
        return array();
    }

    /**
     * Get the scan_id record for this result
     */
    public function dnsScan()
    {
        return $this->belongsTo('App\Models\DnsResult', 'scan_id');
    }
}
