<template>
    <form id="feedback_form" novalidate="" @submit.prevent="submitForm()">

        <div class="form-group">
            <label for="feedback_email">
                Email (if you want)
            </label>
            <input id="feedback_email" class="form-control" type="email" name="email">
            <div class="help-block"></div></div>

        <div class="form-group">
            <label for="feedback_message">
                I would find quite useful:
            </label><textarea id="feedback_message" class="form-control" rows="4" cols="50" required="" name="message"></textarea>
            <div class="help-block"></div></div>

        <div class="alert alert-info alert-waiting scan-alert" v-if="sentState == 2">
            <span id="info-text">Sending...</span>
        </div>

        <transition name="fade">
            <div class="alert alert-danger alert-dismissable" v-if="sentState == -1">
                <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                <strong>Bugger,</strong> something broke down. Please email us directly at <em>keychest@enigmabridge.com</em>.
            </div>
        </transition>

        <transition name="fade">
            <div class="alert alert-success alert-dismissable" v-if="sentState == 1">
                <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                <strong>Thanks!</strong> Your message has been sent.
            </div>
        </transition>

        <button class="bloc-button btn btn-lg btn-block btn-rich-electric-blue"
                v-bind:disabled="sentState == 2"
                v-bind:class="{'disabled' : sentState == 2}" type="submit">
            Add my vote for these features
        </button>
    </form>
</template>

<script>
    import axios from 'axios';
    import _ from 'lodash';
    import toastr from 'toastr';

    export default {
        data: function() {
            return {
                sentState: 0,

                Laravel: window.Laravel
            };
        },

        mounted() {
            this.$nextTick(function () {
                this.hookup();
            })
        },

        methods: {
            hookup(){

            },

            submitForm(){
                this.sentState = 0;
                const $form = $('#feedback_form');
                const $email = $('#feedback_email');
                const $message = $('#feedback_message');
                const email = $email.val();
                const message = $message.val();

                // Minor domain validation.
                if (_.isEmpty(message)){
                    toastr.error('Empty message.', 'Invalid input', {timeOut: 2000, preventDuplicates: true});
                    $form.effect( "shake" );
                    return;
                }

                let onSuccess = () => {
                    this.sentState = 1;
                    $email.val('');
                    $message.val('');
                };

                let onFail = () => {
                    this.sentState = -1;
                };

                this.sentState = 2;
                axios.post(this.Laravel.urlFeedback, {'email': email, 'message': message})
                    .then(response => {
                        if (!response || !response.data || response.data['status'] !== 'success'){
                            onFail();
                        } else {
                            onSuccess();
                        }
                    })
                    .catch(e => {
                        console.log( "Submit feedback Failed: " + e );
                        onFail();
                    });
            }
        }
    }

</script>