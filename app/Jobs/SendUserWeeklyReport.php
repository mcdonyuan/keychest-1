<?php

namespace App\Jobs;

use App\Keychest\DataClasses\ReportDataModel;
use App\Keychest\DataClasses\ValidityDataModel;
use App\Keychest\Services\AnalysisManager;
use App\Keychest\Services\EmailManager;
use App\Keychest\Services\ScanManager;
use App\Keychest\Services\ServerManager;
use App\Mail\WeeklyNoServers;
use App\Mail\WeeklyReport;
use App\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Class SendUserWeeklyReport
 * Per-user weekly report job, submitted from the weekly reporting job
 *
 * Benefits:
 *  - avoid segfaults with large payloads for email model (only user ID is serialized as job data)
 *  - more scalable as there might be many workers processing independent per-user jobs
 *
 * @package App\Jobs
 */
class SendUserWeeklyReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * User to analyze
     * @var User
     */
    protected $user;

    /**
     * Scan manager
     * @var ScanManager
     */
    protected $scanManager;

    /**
     * @var ServerManager
     */
    protected $serverManager;

    /**
     * @var EmailManager
     */
    protected $emailManager;

    /**
     * @var AnalysisManager
     */
    protected $analysisManager;

    /**
     * Create a new job instance.
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     * @param ServerManager $serverManager
     * @param ScanManager $scanManager
     * @param EmailManager $emailManager
     * @param AnalysisManager $analysisManager
     * @return void
     */
    public function handle(ServerManager $serverManager, ScanManager $scanManager,
                           EmailManager $emailManager, AnalysisManager $analysisManager)
    {
        $this->serverManager = $serverManager;
        $this->scanManager = $scanManager;
        $this->emailManager = $emailManager;
        $this->analysisManager = $analysisManager;

        // Double check last sent email, if job is enqueued multiple times by any chance, do not spam the user.
        if ($this->user->last_email_report_sent_at &&
            Carbon::now()->subDays(4)->lessThanOrEqualTo($this->user->last_email_report_sent_at)) {
            Log::info('Report for the user '
                . $this->user->id . ' already sent recently: '
                . $this->user->last_email_report_sent_at);
            return;
        }

        $this->loadUserDataAndProcess($this->user);
    }

    /**
     * Loads user related data and proceeds to the reporting.
     * @param User $user
     */
    protected function loadUserDataAndProcess($user){
        $md = new ValidityDataModel($user);

        // Host load, dns scans
        $this->analysisManager->loadHosts($user, $md);

        Log::info('--------------------');
        $this->analysisManager->loadCerts($md);

        Log::info(var_export($md->getCerts()->count(), true));
        $this->analysisManager->loadWhois($md);

        // Processing section
        $this->analysisManager->processExpiring($md);

        // 2. incidents
        // TODO: ...

        $this->sendReport($md);
    }

    /**
     * Translates model from ValidityDataModel to ReportDataModel
     * @param ValidityDataModel $md
     * @return ReportDataModel
     */
    protected function translateModel(ValidityDataModel $md){
        $mm = new ReportDataModel($md->getUser());
        $mm->setNumActiveWatches($md->getActiveWatches()->count());
        $mm->setNumAllCerts($md->getNumAllCerts());
        $mm->setNumCertsActive($md->getNumCertsActive());

        $mm->setCertExpired($this->thinCertsModel($md->getCertExpired()));
        $mm->setCertExpire7days($this->thinCertsModel($md->getCertExpire7days()));
        $mm->setCertExpire28days($this->thinCertsModel($md->getCertExpire28days()));
        return $mm;
    }

    /**
     * Removes unnecessary data from the certs model - removes the serialization overhead.
     * @param Collection $certs
     * @return Collection
     */
    protected function thinCertsModel(Collection $certs){
        if (!$certs){
            return collect();
        }

        return $certs->map(function($item, $key){
            if ($item->tls_watches){
                $item->tls_watches->map(function($item2, $key2){
                    $item2->dns_scan = collect();
                    $item2->tls_scans = collect();
                    return $item2;
                });
            }
            return $item;
        });
    }

    /**
     * Stub function for sending a report
     * @param ValidityDataModel $md
     */
    protected function sendReport(ValidityDataModel $md){
        Log::debug('Sending email...');

        $news = $this->emailManager->loadEmailNewsToSend($md->getUser());

        // No watched servers?
        if ($md->getActiveWatches()->isEmpty()){
            $this->sendNoServers($md, $news);
            return;
        }

        $mm = $this->translateModel($md);
        $this->sendMail($md->getUser(), new WeeklyReport($mm, $news), false);
        $this->onReportSent($md, $news);
    }

    /**
     * Sends no servers yet message
     * @param ValidityDataModel $md
     * @param Collection $news
     */
    protected function sendNoServers(ValidityDataModel $md, Collection $news){
        // Check if the last report is not too recent
        if ($md->getUser()->last_email_no_servers_sent_at &&
            Carbon::now()->subDays(28)->lessThanOrEqualTo($md->getUser()->last_email_no_servers_sent_at)) {
            return;
        }

        $mm = $this->translateModel($md);
        $this->sendMail($md->getUser(), new WeeklyNoServers($mm, $news), false);

        $md->getUser()->last_email_no_servers_sent_at = Carbon::now();
        $this->onReportSent($md, $news);
    }

    /**
     * Actually sends the email, either synchronously or enqueue for sending.
     * @param User $user
     * @param Mailable $mailable
     * @param bool $enqueue
     */
    protected function sendMail(User $user, Mailable $mailable, $enqueue=false){
        $s = Mail::to($user);
        if ($enqueue){
            $s->queue($mailable
                ->onConnection(config('keychest.wrk_weekly_emails_conn'))
                ->onQueue(config('keychest.wrk_weekly_emails_queue')));
        } else {
            $s->send($mailable);
        }
    }

    /**
     * Update user last report sent date.
     * @param ValidityDataModel $md
     * @param Collection $news
     */
    protected function onReportSent(ValidityDataModel $md, Collection $news){
        $user = $md->getUser();
        $user->last_email_report_sent_at = Carbon::now();
        $user->save();

        // Save sent news.
        $this->emailManager->associateNewsToUser($user, $news);
    }

}
