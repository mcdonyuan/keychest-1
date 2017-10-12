<div class="row">

    {{--<div class="row">--}}
        {{--<div class="col-md-8 col-md-offset-2 text-center">--}}
            {{--<h1>Iont Detector</h1>--}}
        {{--</div>--}}
    {{--</div>--}}

    {{--<div class="row">--}}
        {{--<div class="col-md-8 col-md-offset-2 text-center">--}}
            {{--<h3>Public RSA key vulnerability checker</h3>--}}
        {{--</div>--}}
    {{--</div>--}}

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="row">

                        <div class="col-md-8 col-md-offset-2 text-center">
                            <h1>ROCA Vulnerability Test Suite</h1>
                        </div>
                        <div class="col-md-8 col-md-offset-2 text-center">
                            <h3>Information and tools to test RSA keys for ROCA vulnerability</h3>
                        </div>

                        <div class="col-md-12">
                            <p>
                                This test suite provides information about the ROCA vulnerability, which is caused by
                                an error in RSA key generation in Infineon security chips. These computer chips are
                                used in a number of products and applications as detailed in the ROCA vulnerability
                                summary below.

                                You can use this test suit to check your RSA keys in a text form, by uploading a keystore
                                in one of the supported types, or by sending an email with a digital signature (S/MIME)
                                or your PGP key to an email responder. Use the form below to select the most suitable
                                method.
                            </p>

                            <p>
                                If you experience difficulties or errors on this page, please let us know via our
                                <a href="https://enigmabridge.freshdesk.com/support/tickets/new">support system</a>.
                            </p>

                            <p>
                                <strong>Privacy notice:</strong> Any data you provide on this page is deleted as soon
                                as we complete a requested test. We do not keep your keys or any other data generated
                                during testing.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- press release -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">ROCA vulnerability summary</h3>
                </div>
                <div class="box-body">
                    <p>
                        Brno, Czech Republic and Cambridge, UK – October 13th, 2017
                    </p>
                    <p>
                        Strictly - For release on October 16th at 12:00 UK, 4:00 PDT, 7:00 EDT, 22:00 AEDT
                    </p>
                    <h3>ROCA - Critical vulnerability in Infineon security chips including TPM and smart cards</h3>
                    <h4>
                        Technical details: <a target="_blank" href="https://acmccs.github.io/session-H1/">The Return
                            of Coppersmith's Attack: Practical Factorization of Widely Used
                            RSA Moduli</a> (ROCA) - ACM CCS conference on 2nd November 2017
                    </h4>

                    <p>
                        This press release and referenced resources provide information needed to assess a potential
                        impact of the Return of Coppersmith Attack (ROCA) vulnerability on their systems and
                        applications. It follows a responsible disclosure process, which we started at the beginning of
                        February 2017 by notifying the manufacturer Infineon (IFX.DE). We have reached the end of the
                        agreed non-disclosure period, which has been extended from usual 45 days to eight months due to
                        the nature of the vulnerability.
                    </p>

                    <p>
                        The responsible disclosure framework is a multi-stage process to prevent third parties gaining
                        security, financial, or other advantage over users of products vulnerable to cyber attacks.
                        These best practices have been established to protect users by ensuring that security
                        vulnerabilities are corrected in a timely and orderly fashion and they are widely accepted by
                        the IT industry, and government bodies (CERT, CSIRT, OWASP, NIST, etc.). Publishing
                        vulnerabilities helps identify their causes and improves the overall security of technology
                        users.
                    </p>

                    <p>
                        The ROCA vulnerability allows computation of RSA private key from its public component with low
                        to medium budget and computation times from minutes to days. The vulnerability has been verified
                        for RSA keys of up to 2,048 bits. A successful computation of a private key allows, depending on
                        its use, decrypt sensitive data (from file encryption to HTTPS), forging digital signatures
                        (email security, qualified signatures), impersonation (access control to IT systems or
                        buildings), or personal identity theft (e-ID cards).
                    </p>

                    <p><b>Overview</b></p>

                    <p>
                        A team of scientists at Masaryk University (Czech Republic) and Enigma Bridge Ltd (UK) have
                        discovered a serious vulnerability in the algorithm used to generate encryption keys in an
                        Infineon (IFX.DE) on-chip cryptographic library. This library was provided with security chips
                        manufactured from 2012, possibly earlier. Infineon, as the manufacturer of impacted products,
                        have been informed of the vulnerability in February 2017. Infineon subsequently informed their
                        customers. The research team provided full and timely support to a number of these customers.
                        The support included further clarifications of the vulnerability, expert consultations, and
                        tools for detection of vulnerable products. All this support was provided as quickly as possible
                        and free of charge.
                    </p>

                    <p>
                        The disclosure process concludes with the publication of technical details of the security
                        vulnerability at the ACM CCS 2017, a computer security conference, on 2nd November 2017 in
                        Dallas, USA under the title: <i>The Return of Coppersmith's Attack: Practical Factorization of
                            Widely Used RSA Moduli</i>.
                    </p>

                    <p>
                    An error in a key generation function allows computation of a private RSA key from its public
                        component with a low to medium budget. The current processor times are as follows.
                    </p>
                    <ul>
                        <li>1,024 bit RSA keys – 97 vCPU days (maximum cost of $40-$80); and</li>
                        <li>2,048 bit RSA keys – 51,400 vCPU days, (maximum cost of $20,000 - $40,000).</li>
                    </ul>
                    <p>
                        These cost estimates are valid at the time of publication and may decrease. Computations can
                        also be split among an arbitrary number of processors and completed in days, hours, or even
                        minutes.
                    </p>

                    <p><b>Impact</b></p>

                    <p>
                        The vulnerability impacts applications, which use the faulty on-chip key generation library. A
                        large number of government (including EU-wide qualified electronic signature schemes),
                        enterprise and organizational applications may be affected and we encourage organizations to
                        activate their security incident response plans to assess potential impact and mitigate relevant
                        risks. See below for links to tools for an initial assessment of your exposure.
                    </p>

                    <p>
                        Some large deployments of these chips are NOT impacted. Use cases, where RSA keys are generated
                        outside the security chips, e.g., EMV (chip & PIN and chip & signature) banking cards, or
                        passports should not be affected. The vulnerability also has a very limited impact on the
                        internet security (HTTPS, SSL/TLS, etc.).
                    </p>

                    <p>
                        RSA is a widely used encryption and signing algorithm for data encryption, digital signing, user
                        and device authentication. It also provides citizen identification for some e-Government
                        services. The library in question has been used in a range of products including smart-cards,
                        java-cards, or trusted platform modules (TPM). These products are used to provide additional
                        security for users as well as laptops and computers.
                    </p>

                    <p>
                        The fault is present in the Infineon cryptographic library RSA v1.02.013 and subsequent
                        versions. We have identified the following products using this library:
                    </p>
                    <ul>
                        <li>smartcards and Java cards with Infineon processors, including SLE77, SLE78 – not all
                            products make use of the library;</li>
                        <li>Infineon TPM modules – around 30% of all TPM modules and present in a number of PC and
                            laptop brands including Lenovo, HP, Dell, Microsoft, or Asus; and</li>
                        <li>third-party hardware authentication tokens utilizing affected processors.</li>
                    </ul>

                    <p>
                        As the affected products are also used to provide a high-level physical security, the
                        vulnerability may impact authentication cards or tokens for physical access to high-security
                        buildings, authentication to critical information systems, EU qualified electronic signatures,
                        or even national health and ID schemes. Using our limited resources and access, 760,000 keys
                        generated on different devices are confirmed to be vulnerable to date. We expect the overall
                        number of vulnerable keys to be in the order of tens of millions or more.
                    </p>

                    <p><b>Assessment, mitigation</b></p>

                    <p>
                        If you suspect you or your organization's security may be at risk, we have implemented an
                        online tool for RSA public keys, which will help you assess your exposure to the vulnerability,
                        It is available on these web addresses:
                    </p>
                    <ul>
                        <li><a target="_blank" href="https://rocahelp.com">https://rocahelp.com</a> (or <a target="_blank"
                            href="https://keychest.net/roca">https://keychest.net/roca</a>a>)</li>
                        <li><a target="_blank" href="https://keytester.cryptosense.com">https://keytester.cryptosense.com</a></li>
                    </ul>

                    <p>We also encourage you to contact manufacturers of products you suspect may be affected, or check
                        their latest bug and press releases, and product updates for more information.</p>

                    <p>
                        A list of possible mitigation, risk management actions includes:
                    </p>
                    <ul>
                        <li>replacement of security chips / products with these chips with secure ones;</li>
                        <li>change the source of RSA keys to a secure key generator;</li>
                        <li>replace RSA algorithm with an elliptic curve encryption (ECC);</li>
                        <li>shorten the lifetime of RSA keys;</li>
                        <li>limit access to repositories with public keys; or</li>
                        <li>separate data-at-rest and data-in-transit encryption.</li>
                    </ul>

                    <p>-------- end of the text -------- </p>

                    <p>
                        <b>The Faculty of Informatics at Masaryk University</b> was established in 1994 and is the very
                        first specialized faculty of its kind in the Czech Republic. Its establishment followed decades
                        of experience built in the area of Mathematical Informatics at the Faculty of Science, and with
                        significant involvement from prominent specialists in the field of informatics at both national
                        and international levels. A high standard of teaching is combined with in depth scientific
                        research in many areas of informatics, such as Quantum Informatics, Concurrent and Distributed
                        Computing or Computer and Communication Security.
                    </p>

                    <p>
                        <b>Enigma Bridge Ltd</b> is a technology start-up providing cloud encryption and key management
                        services. Its KeyChest key management platform provides a simple certificate expiry monitoring
                        with automatic key enrolment and discovery. Its certificate renewal capabilities significantly
                        reduce labour and management cost. It uses a unique secure hardware encryption platform for
                        physical security of customer secrets and encryption keys. This hardware platform uses large
                        numbers of secure processors to provide scalability, as well as secure remote operations.
                    </p>
                </div>
            </div>
        </div>
    </div>


    <tester></tester>

</div>
