import _ from 'lodash';
import accounting from 'accounting';
import moment from 'moment';
import pluralize from 'pluralize';

import Req from 'req';

export default {

    /**
     * Host group sorting function.
     * Sort groups so the host group is first.
     *
     * @param group
     * @returns {*}
     */
    hostGroupSortFunction(group){
        return group && group.group_name ?
            (!_.startsWith(group.group_name, 'host-')) + '_' + group.group_name :
            '';
    },

    /**
     * Sort groups so the host group is first.
     * Returns a new array.
     *
     * @param groups
     */
    sortHostGroups(groups){
        if (!groups || _.isEmpty(groups)){
            return groups;
        }

        return _.sortBy(groups, this.hostGroupSortFunction);
    },

    /**
     * Sorts host groups in place.
     * @param groups
     * @returns {*}
     */
    sortHostGroupsInPlace(groups){
        if (!groups || _.isEmpty(groups)){
            return groups;
        }

        return Req.sortByInPlace(groups, this.hostGroupSortFunction);
    },

    /**
     * vue-router: Returns either to the parent from meta or to the previous page (if no parent).
     * @param router
     * @param route
     * @returns {void|*}
     */
    windowBack(router, route){
        const parent = _.get(route, 'meta.parent');
        return parent ? router.push(parent) : router.back();
    },

    allcap (value) {
        return value.toUpperCase()
    },

    formatNumber (value) {
        return accounting.formatNumber(value, 2)
    },

    formatDate (value, fmt = 'DD-MM-YYYY') {
        return (value === null) ? '' : moment.utc(value, 'YYYY-MM-DD HH:mm').local().format(fmt);
    },
}

