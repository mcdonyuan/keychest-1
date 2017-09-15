<?php

namespace App\Http\Controllers;

use App\Http\Requests;

use App\Keychest\Services\EmailManager;
use App\Keychest\Services\LicenseManager;


/**
 * Class LicenseController
 * @package App\Http\Controllers
 */
class LicenseController extends Controller
{
    /**
     * @var LicenseManager
     */
    protected $licenseManager;

    /**
     * @var EmailManager
     */
    protected $emailManager;

    /**
     * Create a new controller instance.
     * @param LicenseManager $manager
     * @param EmailManager $eManager
     */
    public function __construct(LicenseManager $manager, EmailManager $eManager)
    {
        $this->licenseManager = $manager;
        $this->emailManager = $eManager;
    }

    /**
     * Unsubscribe from email report
     * @param $token
     * @return $this
     */
    public function unsubscribe($token){
        $res = $this->emailManager->unsubscribe($token);

        return view('unsubscribe')->with(
            ['token' => $token, 'res' => $res]
        );
    }
}
