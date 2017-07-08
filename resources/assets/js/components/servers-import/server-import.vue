<template>
    <div class="server-import">
        <h3>Monitored Server Import</h3>

        <sbox cssBox="box-success" :remove="true" v-if="importState === 1">
            <template slot="title">Import Successful</template>
            <div class="table-responsive table-xfull">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Servers added</th>
                        <th>Servers already present</th>
                        <th>Import errors</th>
                        <th>Skipped</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>{{ numImported }}</td>
                        <td>{{ numPresent }}</td>
                        <td>{{ numFailed }}</td>
                        <td>{{ numSkipped }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </sbox>

        <div class="form-group">
            <p>Bulk import servers to monitoring. Enter line separated server names / URLs below.</p>
            <textarea class="form-control" rows="10" placeholder="Line separated server names"
                      v-model="inputData"></textarea>
            <small>Max 100 records per one import</small>
        </div>

        <div class="form-group">
            <button type="button" class="btn btn-block btn-success"
                    v-on:click="process">Import</button>
        </div>

    </div>
</template>
<script>
    import toastr from 'toastr';
    import Vue from 'vue';
    import VueEvents from 'vue-events';

    Vue.use(VueEvents);

    export default {
        data () {
            return {
                loadingState: 0,
                inputData: "",

                importState:0,
                numImported: 0,
                numPresent: 0,
                numFailed: 0,
                numSkipped: 0
            }
        },

        methods: {
            needRefresh(){

            },

            process(){
                this.inputData = _.trim(this.inputData);
                this.inputData = _.deburr(this.inputData);
                this.inputData = _.toLower(this.inputData);
                if (this.inputData.length === 0){
                    toastr.error('Nothing to import.', 'Invalid input', {timeOut: 2000, preventDuplicates: true});
                    return;
                }

                this.importServers(this.inputData);
            },

            importServers(data){
                const onFail = (function(){
                    Vue.nextTick(() => {
                        Req.bodyProgress(false);
                        toastr.error('Could not import servers.', 'Invalid input', {timeOut: 2000, preventDuplicates: true});
                        this.$events.fire('on-manual-refresh');
                    });
                }).bind(this);

                const onSuccess = (function(data){
                    Vue.nextTick(() => {
                        this.inputData = data['transformed'];
                        this.importState = 1;
                        this.numImported = data['num_added'];
                        this.numPresent = data['num_present'];
                        this.numFailed = data['num_failed'];
                        this.numSkipped = data['num_skipped'];
                        setTimeout(()=> {
                            $('.server-import .box').show('slow');
                        }, 500);

                        Req.bodyProgress(false);
                        toastr.success('Server import successfull.', 'Success');
                        if (this.numImported) {
                            this.$events.fire('on-manual-refresh');
                        }
                    });
                }).bind(this);

                Req.bodyProgress(true);
                axios.post('/home/servers/import', {'data': data})
                    .then(response => {
                        if (!response || !response.data || response.data['status'] !== 'success'){
                            onFail();
                        } else {
                            onSuccess(response.data);
                        }
                    })
                    .catch(e => {
                        console.log( "Server import failed " + e );
                        onFail();
                    });
            },
        },
        events: {
            'on-manual-refresh'(){
                Vue.nextTick(() => this.needRefresh());
            }
        }
    }
</script>
<style>

</style>