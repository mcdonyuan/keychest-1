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
                            <h1>Iont Detector</h1>
                        </div>
                        <div class="col-md-8 col-md-offset-2 text-center">
                            <h3>Public RSA key vulnerability checker</h3>
                        </div>

                        <div class="col-md-12">
                            <p>
                                This tools is aimed to help you detect whether you are vulnerable to recently discovered
                                attack on RSA keys
                                generated by particular sources. For more information see our <a
                                        href="https://keychest.net">blog</a>.
                            </p>

                            <p>
                                <strong>Privacy notice:</strong> We do not collect any user data. The keys are not
                                stored. After analysis
                                are done they are forgotten.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <tester></tester>

    <!-- python tester -->
    {{--<div class="row">--}}
        {{--<div class="col-md-8 col-md-offset-2 text-center">--}}
            {{--<h2>Offline tester</h2>--}}
        {{--</div>--}}
    {{--</div>--}}

    <div class="row">
        <div class="col-md-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">Offline tester</h3>
                </div>
                <div class="box-body">
                    <p>
                        There is also a way to test your public keys privately offline with use of our detection script.
                    </p>

                    <p>
                        Supported formats:
                    </p>

                    <ul>
                        <li>X509 Certificate, DER encoded</li>
                        <li>X509 Certificate, PEM encoded</li>
                        <li>RSA PEM encoded private key, public key</li>
                        <li>SSH public key</li>
                        <li>ASC encoded PGP key, *.pgp, *.asc</li>
                        <li>Java Key Store file (JKS). </li>
                        <li>PKCS7 signature with user certificate</li>
                        <li>APK Android application</li>
                        <li>LDIFF file - LDAP database dump. Any field ending with ";binary::" is attempted to decode as X509 certificate</li>
                        <li>One modulus per line text file *.txt, modulus can be
                            a) base64 encoded number, b) hex coded number, c) decimal coded number</li>
                        <li>JSON file with moduli, one record per line, record with modulus has
                            key "mod", certificate(s) with key "cert" / array of certificates with key "certs" are supported, base64 encoded DER. </li>
                    </ul>

                    <h3>Installation</h3>
                    <p>
                        The detection tool is implemented in Python.
                    </p>

                    <div class="alert alert-info">
                        <span class="code-block">pip install iont-detect</span>
                    </div>

                    <h3>Usage</h3>
                    <div class="alert alert-info">
                        <span  class="code-block">iont-detect --help
iont-detect my-cert.pem
iont-detect my-pgp-key.asc</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
