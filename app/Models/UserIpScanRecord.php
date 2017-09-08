<?php
/**
 * Created by PhpStorm.
 * User: dusanklinec
 * Date: 07.09.17
 * Time: 20:25
 */
namespace App\Models;

use App\Keychest\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserIpScanRecord extends Pivot
{
    protected $guarded = array();

    protected $table = 'user_ip_scan_record';

    protected $foreignKey = 'ip_scan_record_id';

    protected $relatedKey = 'user_id';

    public function getDates()
    {
        return array('created_at', 'updated_at', 'deleted_at', 'disabled_at');
    }
}
