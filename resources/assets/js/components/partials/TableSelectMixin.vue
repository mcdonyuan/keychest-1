<script>
    export default {
        props: {
        },
        data: function() {
            return {
            }
        },
        computed: {
        },
        methods: {
            invertCheckBoxes(){
                const self = this;
                const idColumn = this.trackBy;

                this.tableData.forEach(function(dataItem) {
                    const id = dataItem[idColumn];
                    self.toggleCheckbox(dataItem, 'fld', {target:{checked: !self.isSelectedRow(id)}});
                });

                this.$emit('vuetable:checkbox-inverted');
            },
            checkAll(){
                const self = this;
                const idColumn = this.trackBy;

                this.tableData.forEach(function(dataItem) {
                    const id = dataItem[idColumn];
                    self.toggleCheckbox(dataItem, 'fld', {target:{checked: true}});
                });
            },
            uncheckAll(){
                const self = this;
                const idColumn = this.trackBy;

                this.tableData.forEach(function(dataItem) {
                    const id = dataItem[idColumn];
                    self.toggleCheckbox(dataItem, 'fld', {target:{checked: false}});
                });
            },
            uncheckAllPages(){
                this.selectedTo = [];
                this.$emit('vuetable:checkbox-toggled-all', false);
            },
            getSelectedIds(){
                return this.selectedTo;
            },
            getSelectedObjects(){
                const self = this;
                const idColumn = this.trackBy;
                const ret = [];

                this.tableData.forEach(function(dataItem) {
                    const id = dataItem[idColumn];
                    if (self.isSelectedRow(id)){
                        ret.push(dataItem);
                    }
                });

                return ret;
            }
        }
    }
</script>
