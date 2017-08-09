<?php

/*
 * Dashboard controller, loading data.
 */

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Keychest\Services\ScanManager;
use App\Keychest\Utils\DataTools;
use App\Keychest\Utils\DomainTools;
use App\Models\BaseDomain;
use App\Models\Certificate;
use App\Models\CrtShQuery;
use App\Models\DnsResult;
use App\Models\HandshakeScan;
use App\Models\WatchAssoc;
use App\Models\WatchTarget;
use App\Models\WhoisResult;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Traversable;

/**
 * Class DashboardController
 * TODO: move logic to the manager, this will be needed also for reporting (scheduled emails)
 *
 * @package App\Http\Controllers
 */
class DashboardController extends Controller
{
    /**
     * Scan manager
     * @var ScanManager
     */
    protected $scanManager;

    /**
     * Create a new controller instance.
     *
     * @param ScanManager $scanManager
     */
    public function __construct(ScanManager $scanManager)
    {
        $this->middleware('auth');
        $this->scanManager = $scanManager;
    }

    public function loadActiveCerts()
    {
        $curUser = Auth::user();
        $userId = $curUser->getAuthIdentifier();

        $wq = $this->scanManager->getActiveWatcher($userId);
        $activeWatches = $wq->get()->mapWithKeys(function($item){
            return [$item->watch_id => $item];
        });

        $activeWatchesIds = $activeWatches->pluck('wid')->sort()->values();
        Log::info('Active watches ids: ' . var_export($activeWatchesIds->toJson(), true));

        // Load all newest DNS scans for active watches
        $q = $this->scanManager->getNewestDnsScansOptim($activeWatchesIds);
        $dnsScans = $this->processDnsScans($q->get());
        Log::info(var_export($dnsScans->count(), true));

        // Determine primary IP addresses
        $primaryIPs = $this->getPrimaryIPs($dnsScans);

        // Load latest TLS scans for active watchers for primary IP addresses.
        $q = $this->scanManager->getNewestTlsScansOptim($activeWatchesIds, true);  // TODO: all IPs
        $tlsScans = $this->processTlsScans($q->get());

        // Latest CRTsh scan
        $crtshScans = $this->scanManager->getNewestCrtshScansOptim($activeWatchesIds)->get();
        $crtshScans = $this->processCrtshScans($crtshScans);
        Log::info(var_export($crtshScans->count(), true));

        // Certificate IDs from TLS scans - more important certs.
        // Load also crtsh certificates.
        $tlsCertMap = collect($tlsScans->mapWithKeys(function($item, $key){
            return empty($item->cert_id_leaf) ? [] : [$item->watch_id => $item->cert_id_leaf];
        }));
        // watch_id -> leaf cert from the last tls scanning
        $tlsCertsIds = $tlsCertMap->values()->reject(function($item){
            return empty($item);
        })->unique();

        // watch_id -> array of certificate ids
        $crtshCertMap = collect($crtshScans->mapWithKeys(function($item, $key){
            return empty($item->certs_ids) ? [] : [$item->watch_id => $item->certs_ids];
        }));
        $crtshCertIds = $crtshScans->reduce(function($carry, $item){
            return $carry->union(collect($item->certs_ids));
        }, collect())->unique()->sort()->reverse()->take(300);

        // cert id -> watches contained in, tls watch, crtsh watch detection
        $cert2watchTls = DataTools::invertMap($tlsCertMap);
        $cert2watchCrtsh = DataTools::invertMap($crtshCertMap);

        $certsToLoad = $tlsCertsIds->union($crtshCertIds)->values()->unique();
        $certs = $this->scanManager->loadCertificates($certsToLoad)->get();
        $certs = $certs->transform(
            function ($item, $key) use ($tlsCertsIds, $certsToLoad, $cert2watchTls, $cert2watchCrtsh) {
                $this->attributeCertificate($item, $tlsCertsIds->values(), 'found_tls_scan');
                $this->attributeCertificate($item, $certsToLoad->values(), 'found_crt_sh');
                $this->augmentCertificate($item);
                $this->addWatchIdToCert($item, $cert2watchTls, $cert2watchCrtsh);
                return $item;
        })->mapWithKeys(function ($item){
            return [$item->id => $item];
        });

        Log::info(var_export($certs->count(), true));

        // Whois scan load
        $topDomainsMap = $activeWatches->mapWithKeys(function($item){
            return empty($item->top_domain_id) ? [] : [$item->watch_id => $item->top_domain_id];
        });
        $topDomainToWatch = DataTools::invertMap($topDomainsMap);
        $topDomainIds = $activeWatches->reject(function($item){
            return empty($item->top_domain_id);
        })->pluck('top_domain_id')->unique();
        $whoisScans = $this->scanManager->getNewestWhoisScansOptim($topDomainIds)->get();
        $whoisScans = $this->processWhoisScans($whoisScans);

        // TODO: downtime computation?
        // TODO: CAs?
        // TODO: self signed?
        // TODO: custom PEM certs?
        // TODO: %. crtsh / CT wildcard search?
        // TODO: wildcard scan search - from neighbourhood

        // Search based on crt.sh search.
        $data = [
            'status' => 'success',
            'watches' => $activeWatches->all(),
            'wids' => $activeWatchesIds->all(),
            'dns' => $dnsScans,
            'whois' => $whoisScans,
            'tls' => $tlsScans,
            'primary_ip' => $primaryIPs,
            'tls_cert_map' => $tlsCertMap,
            'crtsh_cert_map' => $crtshCertMap,
            'crt_to_watch_tls' => $cert2watchTls,
            'crt_to_watch_crtsh' => $cert2watchCrtsh,
            'domain_to_watch' => $topDomainToWatch,
            'certificates' => $certs

        ];

        return response()->json($data, 200);
    }

    /**
     * Adds watch_id to the certificate record
     * @param $certificate
     * @param Collection $tls_map
     * @param Collection $crt_sh_map
     */
    protected function addWatchIdToCert($certificate, $tls_map, $crt_sh_map){
        $certificate->tls_watches = $tls_map->get($certificate->id, []);
        $certificate->crtsh_watches = $crt_sh_map->get($certificate->id, []);
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
     * @return mixed
     */
    protected function augmentCertificate($certificate)
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

        $certificate->is_legacy = false;  // will be defined by a separate relation
        $certificate->is_expired = $certificate->valid_to->lt(Carbon::now());
        $certificate->is_le = strpos($certificate->issuer, 'Let\'s Encrypt') !== false;
        $certificate->is_cloudflare = $alts->filter(function($val, $key){
            return strpos($val, '.cloudflaressl.com') !== false;
        })->isNotEmpty();

        return $certificate;
    }

    /**
     * Processes loaded Whois scan results
     * @param Collection $whoisScans
     * @return Collection
     */
    protected function processWhoisScans($whoisScans){
        return $this->scanManager->processWhoisScans($whoisScans);
    }

    /**
     * Builds collection mapping watch_id -> primary IP address.
     *
     * @param Collection $dnsScans
     * @return Collection
     */
    protected function getPrimaryIPs($dnsScans){
        return $dnsScans->values()->mapWithKeys(function ($item) {
            try{
                $primary = empty($item->dns) ? null : $item->dns[0][1];
                return [intval($item->watch_id) => $primary];
            } catch (Exception $e){
                return [];
            }
        });
    }

    /**
     * Processes loaded crtsh scan results
     * @param Collection $crtshScans
     * @return Collection
     */
    protected function processCrtshScans($crtshScans){
        return $this->scanManager->processCrtshScans($crtshScans);
    }

    /**
     * Processes loaded tls scan results
     * @param Collection $tlsScans
     * @return Collection
     */
    protected function processTlsScans($tlsScans){
        return $this->scanManager->processTlsScans($tlsScans);
    }

    /**
     * Processes loaded scan results (deserialization)
     * @param Collection $dnsScans
     * @return Collection
     */
    protected function processDnsScans($dnsScans){
        return $this->scanManager->processDnsScans($dnsScans);
    }



}