<template>
    <div>
        <div class="subdomains-wrapper">
            <h4>File upload</h4>
            <form @submit.prevent="keyFileCheck()" data-vv-scope="keyFile">
                <div class="form-group">
                    <p>
                        If you have your public RSA key in a file, you can upload the file with the form below.
                    </p>
                    <input type="file" placeholder="public key in text form" class="form-control"
                           @change="onFileChange"
                           name="keyFile"
                           data-vv-as="Key File"
                           v-validate="{size: 1000, required: true}"/>

                    <i v-show="errors.has('keyFile.keyFile')" class="fa fa-warning"></i>
                    <span v-show="errors.has('keyFile.keyFile')" class="help is-danger"
                    >{{ errors.first('keyFile.keyFile') }}</span>

                </div>
                <div class="form-group">
                    <p>
                        Supported formats:
                    </p>
                    <ul>
                        <li>X509 Certificate, PEM/DER encoded;</li>
                        <li>SSH public RSA key;</li>
                        <li>RSA PEM encoded private key, public key;</li>
                        <li>ASC encoded PGP key, *.pgp, *.asc;</li>
                        <li>Java Key Store file (JKS);</li>
                        <li>PKCS7 signature with user certificate;</li>
                        <li>LDIFF file - LDAP database dump. We try to decode any field ending with ";binary::" as
                            X509 certificate;</li>
                        <li>Text file (*.txt) with an RSA key modulus per line; the modulus can be a
                            a) base64 encoded number, b) hex coded number, c) decimal coded number;</li>
                        <li>JSON file with RSA keys, a record per line; supported records are RSA moduli
                            (the "mod" key), certificates (the "cert" key) in the base64 encoded DER format, or an
                            array of certificates
                            (the "certs" key) in the base64 encoded DER format. </li>
                    </ul>

                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-block btn-primary"
                            :disabled="errors.has('keyFile.keyFile') || isRequestInProgress"
                    >Test key</button>
                </div>
            </form>
        </div>

        <div class="row result-row-file">
            <results-general
                    ref="gresults"
            ></results-general>
        </div>

    </div>
</template>
<script>
    import _ from 'lodash';
    import axios from 'axios';
    import moment from 'moment';
    import sprintf from 'sprintf-js';
    import Req from 'req';
    import ph4 from 'ph4';
    import mixin from './TesterMixin';

    import ToggleButton from 'vue-js-toggle-button';
    import VueScrollTo from 'vue-scrollto';
    import toastr from 'toastr';

    import Vue from 'vue';
    import VueEvents from 'vue-events';
    import VeeValidate from 'vee-validate';
    import { mapFields } from 'vee-validate';

    import ResultsGeneral from './ResultsGeneral.vue';

    Vue.use(VueEvents);
    Vue.use(ToggleButton);
    Vue.use(VueScrollTo);
    Vue.use(VeeValidate, {fieldsBagName: 'formFields'});

    Vue.component('results-general', ResultsGeneral);

    export default {
        mixins: [mixin],
        data: function() {
            return {
                keyFile: null,

                sendingState: 0,
                resultsAvailable: 0,
                resultsError: false,

            }
        },

        mounted() {
            this.$nextTick(() => {
                this.hookup();
            })
        },

        computed: {
            isRequestInProgress(){
                return this.sendingState === 1;
            },
            hasResults(){
                return this.resultsAvailable === 1;
            },
        },

        methods: {
            hookup(){

            },

            onStartSending(){
                this.sendingState = 1;
                this.resultsError = false;
                this.$refs.gresults.onReset();
            },

            onSendingFail(){
                this.sendingState = -1;
                this.$refs.gresults.onError();
            },

            onSendFinished(){
                this.sendingState = 2;
            },

            transitionHook(){

            },

            onFileChange(e){
                const files = e.target.files || e.dataTransfer.files;
                if (!files.length){
                    return;
                }

                this.keyFile = files[0];
            },

            keyFileCheck(){
                const data = new FormData();

                this.generateUuid();
                this.listenWebsocket();

                data.append('uuid', this.uuid);
                data.append('file', this.keyFile);

                const config = {
                    onUploadProgress: (progressEvent) => {
                        const percentCompleted = Math.round( (progressEvent.loaded * 100) / progressEvent.total );
                        console.log(percentCompleted);
                    }
                };

                this.$scrollTo('.result-row-file');
                const onValid = () => {
                    return new Promise((resolve, reject) => {
                        this.onStartSending();

                        axios.post('/tester/file', data, config)
                            .then(res => {
                                this.onSendFinished();
                                resolve(res);
                            })
                            .catch(err => {
                                this.onSendFinished();
                                reject(new Error(err));
                            });
                    });
                };

                // Validate and submit
                this.$validator.validateAll('keyFile')
                    .then((result) => this.validCheck(result, 'Invalid Key File'))
                    .then((result) => onValid())
                    .then((result) => this.onSubmited(result))
                    .catch((err) => {
                        this.$refs.gresults.onError();
                        this.abortResults();
                        console.log(err);
                    })
            },

            onSubmited(result){
                return new Promise((resolve, reject)=> {
                    try {
                        console.log(result);
                        const data = result.data;

                        this.scheduleResultsTimeout();
                        this.$refs.gresults.onWaitingForResults();

                        resolve(data);

                    } catch (e){
                        console.warn(e);
                        toastr.error('Unexpected result in result processing', 'Check failed', {
                            timeOut: 2000, preventDuplicates: true
                        });
                        reject(e);
                    }
                });
            },

            onResult(data){
                console.log(data);
                this.resultsAvailable = 1;
                this.$refs.gresults.onResultsLoaded(data);
            },

            onResultWaitTimeout(){
                this.abortResults();
                this.resultsError = true;
                this.$refs.gresults.onError();
            },

        },

        events: {

        }
    }
</script>
<style scoped>
    .h2-nomarg {
        margin: 0;
    }
</style>