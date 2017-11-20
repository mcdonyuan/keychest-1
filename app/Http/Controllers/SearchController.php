<?php
/**
 * Created by PhpStorm.
 * User: dusanklinec
 * Date: 16.05.17
 * Time: 17:39
 */
namespace App\Http\Controllers;


use App\Jobs\ScanHostJob;
use App\Keychest\Coverage\Interval;
use App\Keychest\Services\ServerManager;
use App\Keychest\Utils\DomainTools;
use App\Models\BaseDomain;
use App\Models\Certificate;
use App\Models\CertificateAltName;
use App\Models\CrtShQuery;
use App\Models\DnsResult;
use App\Models\HandshakeScan;
use App\Models\ScanJob;
use App\Models\WhoisResult;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;


use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

use Webpatser\Uuid\Uuid;

class SearchController extends Controller
{
    // use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @var ServerManager
     */
    protected $serverManager;

    /**
     * Create a new controller instance.
     * @param ServerManager $serverManager
     */
    public function __construct(ServerManager $serverManager)
    {
        $this->serverManager = $serverManager;
    }

    /**
     * Show the main index page
     *
     * @return Response
     */
    public function show()
    {
        $data = [];
        return view('index', $data);
    }

    /**
     * Show the main index page
     *
     * @return Response
     */
    public function showHome()
    {
        $data = [];
        return view('scan', $data);
    }

    /**
     * Performs the search / check
     *
     * @return Response
     */
    public function search()
    {
        list($newJobDb, $elDb) = $this->submitJob();

        $data = ['job_id' => $newJobDb['uuid']];
        return view('index', $data);
    }

    /**
     * Sends email with a feedback
     */
    public function voteFeedback(){
        $res = $this->submitFeedback();
        if ($res){
            redirect()->back()->with('info', 'Mail sent');
        } else {
            redirect()->back()->with('info', 'Mail sent');
        }
    }

    /**
     * Rest endpoint for feedback submit
     */
    public function restSubmitFeedback(){
        $res = $this->submitFeedback();
        if ($res){
            return response()->json(['status' => 'success'], 200);
        } else {
            return response()->json(['status' => 'error'], 200);
        }
    }

    /**
     * Rest endpoint for job submit
     * @return \Illuminate\Http\JsonResponse
     */
    public function restSubmitJob()
    {
        list($newJobDb, $elDb) = $this->submitJob();
        if (empty($newJobDb)){
            return response()->json(['status' => 'error'], 500);
        }

        $data = [
            'status' => 'success',
            'uuid' => $newJobDb['uuid'],
            'scan_scheme' => $newJobDb['scan_scheme'],
            'scan_port' => $newJobDb['scan_port'],
            'scan_host' => $newJobDb['scan_host'],
        ];

        $req = request();
        $req->session()->put('last_scan_url', $newJobDb['scan_url']);

        return response()->json($data, 200);
    }

    /**
     * Simple check for session keep alive & CSRF validity check.
     * Improves user UX when using  loaded page after some time.
     * @return \Illuminate\Http\JsonResponse
     */
    public function restPing()
    {
        return response()->json([
            'status' => 'success'
        ], 200);
    }

    /**
     * Updates missing timezone automatically - browser TZ.
     */
    public function restTimezoneSet(){
        if (Auth::guest()){
            return response()->json([
                'error' => 'not-logged-in'
            ], 403);
        }

        $tz = trim(Input::get('timezone'));
        $utcOffset = intval(Input::get('utcOffset'));

        $curUser = Auth::user();
        if (!empty($curUser->timezone)){
            return response()->json([
                'error' => 'already-set'
            ], 411);
        }
        
        $curUser->timezone = $tz;
        $curUser->utc_offset = $utcOffset;
        $curUser->save();

        return response()->json([
            'status' => 'success'
        ], 200);
    }

    /**
     * Returns current job state
     */
    public function restGetJobState()
    {
        $uuid = trim(Input::get('job_uuid'));
        $job = ScanJob::query()->where('uuid', $uuid)->first();
        if (empty($job)){
            return response()->json(['status' => 'not-found'], 404);
        }

        // TODO: improvement, sleep 1-2 seconds for an event change.

        $data = [
            'status' => 'success',
            'job' => $this->augmentScanJob($job),
        ];

        return response()->json($data, 200);
    }

    /**
     * Basic job results
     */
    public function restJobResults()
    {
        $uuid = trim(Input::get('job_uuid'));
        $job = ScanJob::query()->where('uuid', $uuid)->first();
        if (empty($job)){
            return response()->json(['status' => 'not-found'], 404);
        }

        $job = $this->augmentScanJob($job);
        if ($job->state != 'finished'){
            return response()->json(['status' => 'not-finished'], 201);
        }

        // TLS scan results
        $tlsHandshakeScans = HandshakeScan::query()->where('job_id', $job->id)->get();
        $tlsHandshakeScans->transform(function($val, $key){
            $val->certs_ids = json_decode($val->certs_ids);
            return $val;
        });

        // Get corresponding certificates IDs
        $handshakeCertsId = $this->certificateList($tlsHandshakeScans);

        // Search based on alt domain names from the scan query
        $altNames = array_merge(DomainTools::altNames($job->scan_host), [$job->scan_host]);
        $altNamesCertIds = CertificateAltName::query()->select('cert_id')->distinct()
                ->whereIn('alt_name', $altNames)->get()->pluck('cert_id');
        $cnameCertIds = Certificate::query()->select('id')->distinct()
            ->whereIn('cname', $altNames)->get()->pluck('id');

        // crt.sh lookup
        $crtShScans = CrtShQuery::query()->where('job_id', $job->id)->get();
        $crtShScans->transform(function($val, $key){
            $val->certs_ids = json_decode($val->certs_ids);
            return $val;
        });
        $crtShCertsIdAll = $this->certificateList($crtShScans);
        $crtShCertsId = $crtShCertsIdAll->sort()->reverse()->take(100)->values();

        // Certificate fetch
        $certIds = collect();
        $certIds = $certIds
            ->merge($handshakeCertsId)
            ->merge($crtShCertsId)
            ->merge($altNamesCertIds)
            ->merge($cnameCertIds);

        $certificates = Certificate::query()->whereIn('id', $certIds->unique())->get();

        // certificate attribution, which cert from which scan
        $certificates->transform(function($x, $key) use ($handshakeCertsId, $crtShCertsId, $crtShCertsIdAll,
            $altNamesCertIds, $cnameCertIds, $altNames, $job)
        {
            $this->attributeCertificate($x, $handshakeCertsId, 'found_tls_scan');
            $this->attributeCertificate($x, $crtShCertsIdAll->values(), 'found_crt_sh');
            $this->attributeCertificate($x, $altNamesCertIds, 'found_altname');
            $this->attributeCertificate($x, $cnameCertIds, 'found_cname');
            $this->augmentCertificate($x, $altNames, $job);
            return $x;
        });

        // fetch dns scan data
        $dnsScan = empty($job->dns_check_id) ? null
            : DnsResult::query()->where('id', $job->dns_check_id)->first();
        $dnsScan = $this->processDnsScan($dnsScan);

        // fetch whois scan data
        $whoisScan = $this->loadWhoisScan($job);
        $whoisScan = $this->processWhoisScan($whoisScan);

        // downtime computation
        $downtimeMatch = $this->computeDowntime($certificates, collect($altNames));
        $downtimeTls = null;

        $tlsCert = $this->findTlsLeafCertificate($certificates);
        if ($tlsCert != null){
            $downtimeTls = $this->computeDowntime($certificates, null, $tlsCert);
        }

        // Can add to my server watch list?
        $curUser = Auth::user();
        $canAddToList = false;
        $isMonitored = false;
        if (!empty($curUser)){
            $canAdd = $this->serverManager->canAddHost(
                DomainTools::assembleUrl($job->scan_scheme, $job->scan_host, $job->scan_port), $curUser);
            $canAddToList = $canAdd == 1;
            $isMonitored = $canAdd == 0;
        }

        // Search based on crt.sh search.
        $data = [
            'status' => 'success',
            'job' => $job,
            'tlsScans' => $tlsHandshakeScans,
            'crtshScans' => $crtShScans,
            'altNames' => $altNames,
            'crtshSaturated' => $crtShCertsIdAll->count() > count($crtShCertsId),
            'certificates' => $certificates->map(function($item, $key) {
                return $this->restizeCertificate($item);
            })->keyBy('id'),
            'downtime' => $downtimeMatch,
            'downtimeTls' => $downtimeTls,
            'canAddToList' => $canAddToList,
            'isMonitored' => $isMonitored,
            'dns' => $dnsScan,
            'whois' => $whoisScan
        ];

        return response()->json($data, 200);
    }

    /**
     * Loads whois scan from DB
     * @param $job
     * @return mixed|null
     */
    protected function loadWhoisScan($job){
        if (empty($job->whois_check_id)){
            return null;
        }

        $domainsTable = BaseDomain::TABLE;
        $q = WhoisResult::query()
            ->from(WhoisResult::TABLE . ' AS s')
            ->select(['s.*', $domainsTable.'.domain_name AS domain'])
            ->join($domainsTable, $domainsTable.'.id', '=', 's.domain_id')
            ->where('s.id', $job->whois_check_id);
        return $q->first();
    }

    /**
     * Post processing whois results
     * @param $whois
     * @return mixed
     */
    protected function processWhoisScan($whois){
        if (empty($whois)){
            return $whois;
        }

        try{
            $whois->dns = json_decode($whois->dns);
        } catch (Exception $e){
        }

        try{
            $whois->emails = json_decode($whois->emails);
        } catch (Exception $e){
        }

        return $whois;
    }

    /**
     * Post processing DNS results
     * @param $dns
     * @return mixed
     */
    protected function processDnsScan($dns){
        if (empty($dns)){
            return $dns;
        }
        try{
            $dns->dns = json_decode($dns->dns);
        } catch (Exception $e){
        }
        return $dns;
    }

    /**
     * Augments json record for scan job.
     * @param $job
     */
    protected function augmentScanJob($job){
        if (empty($job)){
            return $job;
        }

        $job->created_at_utc = $job->created_at->getTimestamp();
        $job->updated_at_utc = $job->updated_at->getTimestamp();
        return $job;
    }

    /**
     * Computes overall downtime without valid certificate fo the given certificates in
     * last 2 years.
     * @param array|Collection $certificates
     * @param array|Collection|null $domains
     * @param \stdClass|null $tlsCert
     * @return \stdClass
     */
    protected function computeDowntime($certificates, $domains=null, $tlsCert=null){
        $newCol = collect($certificates->all());
        $sorted = $newCol->sortBy('valid_from_utc');

        // Collection conversion
        if ($domains != null && !($domains instanceof Collection)){
            $domains = collect($domains);
        }

        // Tls leaf cert filter - domain filter build
        if ($tlsCert != null){
            if ($domains == null){
                $domains = collect();
            }

            $domains = $domains->merge($tlsCert->alt_names);
            $domains = $domains->push($tlsCert->cname);
            $domains = $domains->unique();
        }

        $now = time();
        $since = $now - 3600*24*365*2;

        $downtime = 0;
        $intervals = 0;
        $gaps = [];
        $wrapInterval = null;
        foreach($sorted->all() as $cert){
            // Cert type filter - no CA certs
            if ($cert->is_ca || $cert->is_precert || $cert->is_precert_ca){
                continue;
            }

            $curInterval = new Interval($cert->valid_from_utc, $cert->valid_to_utc);

            // Validity filter, only recent certificates
            if (!$curInterval->contains($since) && $cert->valid_from_utc < $since){
                continue;
            }

            // Domain name filter
            if ($domains != null){
                if ($this->matchingDomains($cert, $domains)->isEmpty()){
                    continue;
                }
            }

            $intervals += 1;
            if ($wrapInterval == null){
                $wrapInterval = $curInterval;
                continue;
            }

            $gapInterval = $wrapInterval->absorb($curInterval);
            if ($gapInterval != null) {
                $downtime += $gapInterval->size();
                $gaps[] = ['from'=>$gapInterval->getStart(), 'to'=>$gapInterval->getEnd()];
            }
        }

        $ret = new \stdClass();
        $ret->downtime = $downtime;
        $ret->size = $wrapInterval == null ? 0 : $wrapInterval->size();
        $ret->count = $intervals;
        $ret->gaps = $gaps;
        return $ret;
    }

    /**
     * @param \Illuminate\Support\Collection $objects
     * @return \Illuminate\Support\Collection|static
     */
    protected function certificateList($objects)
    {
        $handshakeCertsId = collect();
        $objects->map(function($x, $val) use ($handshakeCertsId) {
            if (empty($x) || !isset($x->certs_ids)){
                return;
            }
            foreach($x->certs_ids as $y) {
                $handshakeCertsId->push($y);
            }
        });
        $handshakeCertsId = $handshakeCertsId->unique();
        return $handshakeCertsId;
    }

    /**
     * @param $certificate
     * @param \Illuminate\Support\Collection $idset
     * @param string $val
     */
    protected function attributeCertificate($certificate, $idset, $val)
    {
        $certificate->$val = $idset->contains($certificate->id);
        return $certificate;
    }

    /**
     * Extends certificate record
     * @param $certificate
     * @param $altNames
     * @param $job
     * @return mixed
     */
    protected function augmentCertificate($certificate, $altNames, $job)
    {
        $certificate->alt_names = json_decode($certificate->alt_names);
        $alts = collect($certificate->alt_names);
        if (!$alts->contains($certificate->cname)){
            $alts->push($certificate->cname);
        }

        $certificate->created_at_utc = $certificate->created_at->getTimestamp();
        $certificate->updated_at_utc = $certificate->updated_at->getTimestamp();
        $certificate->valid_from_utc = $certificate->valid_from->getTimestamp();
        $certificate->valid_to_utc = $certificate->valid_to->getTimestamp();

        $certificate->is_wildcard = $alts->contains(DomainTools::wildcardDomain($job->scan_host));
        $certificate->is_expired = $certificate->valid_to->lt(Carbon::now());
        $certificate->is_le = strpos($certificate->issuer, 'Let\'s Encrypt') !== false;
        $certificate->is_cloudflare = $alts->filter(function($val, $key){
            return strpos($val, '.cloudflaressl.com') !== false;
        })->isNotEmpty();

        $fqdn = DomainTools::fqdn($job->scan_host);
        $certificate->matched_alt_names = $alts->intersect($altNames)->values()->all();
        $certificate->related_names = $alts->filter(function ($val, $key) use ($fqdn) {
            return empty($fqdn) ? false : strrpos($val, $fqdn) !== false;
        })->values()->all();

        return $certificate;
    }

    /**
     * Modifies certificate record before sending out
     * @param $certificate
     */
    protected function restizeCertificate($certificate)
    {
        $certificate->pem = null;
        return $certificate;
    }

    /**
     * Returns TLS leaf certificate - found during scan.
     * @param Collection $certificates
     * @return \stdClass|null
     */
    protected function findTlsLeafCertificate($certificates){
        foreach($certificates as $cert){
            if ($cert->is_ca || $cert->is_precert || $cert->is_precert_ca){
                continue;
            }

            if ($cert->found_tls_scan){
                return $cert;
            }
        }

        return null;
    }

    /**
     * @param $certificate
     * @param array|Collection $domains list of domains to intersect with.
     * @return Collection of matching domains
     */
    protected function matchingDomains($certificate, $domains){
        if (!($domains instanceof Collection)){
            $domains = collect($domains)->unique();
        }

        $alts = collect($certificate->alt_names);
        if (!$alts->contains($certificate->cname)){
            $alts->push($certificate->cname);
        }

        return $alts->intersect($domains)->values();
    }

    /**
     * Submits the scan job to the queue
     * @return array
     */
    protected function submitJob()
    {
        $uuid = Uuid::generate()->string;
        $server = strtolower(trim(Input::get('scan-target')));
        $ip = strtolower(trim(Input::get('ip')));
        Log::info(sprintf('UUID: %s, target: %s, ip: %s', $uuid, $server, $ip));

        $parsed = parse_url($server);
        if (empty($parsed) || strpos($server, '.') === false){
            return [null, null];
        }

        // DB Job data
        $newJobDb = [
            'uuid' => $uuid,
            'scan_scheme' => isset($parsed['scheme']) ? $parsed['scheme'] : null,
            'scan_host' => isset($parsed['host']) ? $parsed['host'] : $server,
            'scan_port' => isset($parsed['port']) ? $parsed['port'] : null,
            'scan_ip' => !empty($ip) ? $ip : null,
            'state' => 'init',
            'user_ip' => request()->ip(),
            'user_sess' => substr(md5(request()->session()->getId()), 0, 16),
        ];

        $curUser = Auth::user();
        if (!empty($curUser)){
            // Manual user association, could be done via ->user()->associate($usr) on the ORM inserted object.
            $newJobDb['user_id'] = $curUser->getAuthIdentifier();
        }

        $elJson = $newJobDb;
        $elDb = ScanJob::create($newJobDb);

        // Queue entry to the scanner queue
        dispatch((new ScanHostJob($elDb, $elJson))->onQueue('scanner'));

        $newJobDb['scan_url'] = DomainTools::normalizeUserDomainInput($server);
        return [$newJobDb, $elDb];
    }

    /**
     * Submits feedback - sends an email
     * @return bool
     */
    protected function submitFeedback(){
        $email = trim(Input::get('email'));
        $message = trim(Input::get('message'));
        if(empty($message)) {
            return false;
        }

        $to = 'keychest@enigmabridge.com'; // Email submissions are sent to this email

        // Create email
        $email_subject = "Message from keychest.net.";
        $email_body = "You have received a new message. \n\n".
            "Email: $email \nMessage4: $message \n";
        $headers = "MIME-Version: 1.0\r\nContent-type: text/plain; charset=UTF-8\r\n";
        $headers .= "From: feedback@keychest.net\n";
        if (!empty($email)) {
            $headers .= "Reply-To: $email";
        }

        mail($to,$email_subject,$email_body,$headers); // Post message
        return true;
    }

}

