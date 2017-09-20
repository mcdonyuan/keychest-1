<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Carbon converted date fields
     * @return array
     */
    public function getDates()
    {
        return array('created_at', 'updated_at', 'deleted_at', 'closed_at', 'last_email_report_sent_at',
            'last_email_no_servers_sent_at', 'last_email_report_enqueued_at',
            'last_login_at', 'cur_login_at', 'last_action_at');
    }

    /**
     * Associated watch targets
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function watchTargets()
    {
        return $this->belongsToMany('App\Models\WatchTarget',
            null,
            'user_id',
            'watch_id')->using('App\Models\UserWatchTarget');
    }

    /**
     * Active watches only
     * @return mixed
     */
    public function activeWatchTargets(){
        return $this->watchTargets()
            ->whereNull('deleted_at')
            ->whereNull('disabled_at');
    }

    /**
     * Latest DNS scan
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function latestDnsScan(){
        return $this->belongsTo('\App\Models\DnsResult', 'last_dns_scan_id');
    }

    /**
     * Latest login record
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function latestLogin(){
        return $this->belongsTo('\App\Models\UserLoginHistory', 'user_id');
    }

    /**
     * Email news already sent to the user
     */
    public function emailNews()
    {
        return $this->belongsToMany(
            'App\Models\EmailNews',
            'email_news_user',
            'user_id',
            'email_news_id')
            ->withTimestamps();
    }

    /**
     * Associated IP scan records
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function ipScanRecords()
    {
        return $this->belongsToMany('App\Models\IpScanRecord')->using('App\Models\UserIpScanRecord');
    }
}
