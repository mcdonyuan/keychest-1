<template>
    <div class="row">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class=""><a href="#tab_1" data-toggle="tab" aria-expanded="false">Risk groups</a></li>
                <li class="active"><a href="#tab_2" data-toggle="tab" aria-expanded="true">Solutions</a></li>
                <li class=""><a href="#tab_3" data-toggle="tab" aria-expanded="false">Services</a></li>
                <li class=""><a href="#tab_4" data-toggle="tab" aria-expanded="false">Groups</a></li>
                <li class=""><a href="#tab_5" data-toggle="tab" aria-expanded="false">Managed hosts</a></li>
                <li class="pull-right"><a href="#" class="text-muted" title="Refresh" v-on:click="refresh"><i class="fa fa-refresh"></i></a></li>
            </ul>
            <div class="tab-content">

                <div class="tab-pane" id="tab_1">
                    <mgmt-sec-groups/>
                </div>

                <div class="tab-pane active" id="tab_2">
                    <mgmt-solutions/>
                </div>

                <div class="tab-pane" id="tab_3">
                    <mgmt-services/>
                </div>

                <div class="tab-pane" id="tab_4">
                    <h3>Host group list</h3>
                </div>

                <div class="tab-pane" id="tab_5">
                    <mgmt-hosts/>
                </div>

            </div>
            <!-- /.tab-content -->
        </div>
    </div>
</template>
<script>
    import _ from 'lodash';
    import Req from 'req';

    import Vue from 'vue';
    import VueEvents from 'vue-events';
    import VueRouter from 'vue-router';

    import ManagementHosts from './Hosts.vue';
    import ManagementSecGroups from './SecGroups.vue';
    import ManagementServices from './Services.vue';
    import ManagementSolutions from './Solutions.vue';

    import ChangeHost from './ChangeHost.vue';
    import ChangeSecGroup from './ChangeSecGroup.vue';
    import ChangeService from './ChangeService.vue';
    import ChangeSolution from './ChangeSolution.vue';

    Vue.use(VueEvents);
    Vue.use(VueRouter);

    const router = window.VueRouter; // type: VueRouter
    const routes = [
        {
            path: '/addHost',
            name: 'addHost',
            component: ChangeHost,
            meta: {
                editMode: false,
                tabCode: 'mgmt',
                tab:  5,
                parent: {name: 'management'},
            },
        },

        {
            path: '/editHost/:id',
            name: 'editHost',
            component: ChangeHost,
            props: true,
            meta: {
                editMode: true,
                tabCode: 'mgmt',
                tab:  5,
                parent: {name: 'management'},
            },
        },

        {
            path: '/addService',
            name: 'addService',
            component: ChangeService,
            meta: {
                editMode: false,
                tabCode: 'mgmt',
                tab:  3,
                parent: {name: 'management'}
            },
        },

        {
            path: '/editService/:id',
            name: 'editService',
            component: ChangeService,
            props: true,
            meta: {
                editMode: true,
                tabCode: 'mgmt',
                tab:  3,
                parent: {name: 'management'}
            },
        },

        {
            path: '/addSolution',
            name: 'addSolution',
            component: ChangeSolution,
            meta: {
                editMode: false,
                tabCode: 'mgmt',
                tab:  2,
                parent: {name: 'management'}
            },
        },

        {
            path: '/editSolution/:id',
            name: 'editSolution',
            component: ChangeSolution,
            props: true,
            meta: {
                editMode: true,
                tabCode: 'mgmt',
                tab:  2,
                parent: {name: 'management'}
            },
        },

        {
            path: '/addRiskGroup',
            name: 'addRiskGroup',
            component: ChangeSecGroup,
            meta: {
                editMode: false,
                tabCode: 'mgmt',
                tab:  1,
                parent: {name: 'management'},
            },
        },

        {
            path: '/editRiskGroup/:id',
            name: 'editRiskGroup',
            component: ChangeSecGroup,
            props: true,
            meta: {
                editMode: true,
                tabCode: 'mgmt',
                tab:  1,
                parent: {name: 'management'},
            },
        },
    ];
    router.addRoutes(routes);

    // BUG: this closure slows down the navigation a bit from some reason
    router.afterEach((to, fromr) => {
        if (fromr){
            fromr.meta.predecessor = null;
            to.meta.predecessor = fromr.meta;
        }
        console.log('mgmt-aftereach-done');
    });



    export default {
        components: {
            'mgmt-hosts': ManagementHosts,
            'mgmt-sec-groups': ManagementSecGroups,
            'mgmt-services': ManagementServices,
            'mgmt-solutions': ManagementSolutions,
        },
        data () {
            return {
                loadingState: 0,
            }
        },

        mounted() {
            this.$nextTick(() => {
                this.hookup();
            });
        },

        methods: {
            refresh(){
                this.$events.fire('on-manual-refresh');
            },

            hookup(){
                // If the previous link defines the tab which should be displayed, switch the tab.
                const pred = _.get(this.$route, 'meta.predecessor');
                if (pred && pred.tabCode && pred.tabCode === 'mgmt' && pred.tab){
                    Req.switchTab('tab_' + pred.tab);
                }
            },
        },
    }
</script>
<style>
</style>
