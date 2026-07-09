<hr>
<br>
<div class="row">
    <div class="col-12">
        <!-- <h5 class="mb-3">Detail Hak Akses (ACL) - User ID: <?= esc($userpk) ?></h5> -->
        <h5 class="mb-3">Detail Hak Akses (ACL) - User</h5>
        <table id="jqGridAcl"></table>
    </div>
</div>

<script>
    $(document).ready(function() {
        var userpk = "<?= $userpk ?>";
        var gridAclUrl = "<?= base_url('useracl/grid/') ?>" + userpk;
        
        $gridAcl = $("#jqGridAcl");
        
        $gridAcl.jqGrid({
            url: gridAclUrl,
            mtype: "POST",
            datatype: "local",
            jsonReader: { repeatitems: true },
            styleUI: 'Bootstrap4',
            iconSet: 'fontAwesome',
            height: 250,
            autowidth: true,
            shrinkToFit: true,
            colModel: [
                {
                    label: 'Aco ID',
                    name: 'acoid',
                    index: 'acoid',
                    width: 450,
                    fixed: true,
                    search: false,
                },
                {
                    label: 'Modified By',
                    name: 'modifiedby',
                    index: 'modifiedby',
                    width: 120,
                    fixed: true,
                    searchoptions:{sopt:['cn']},
                },
                {
                    label: 'Modified On',
                    name: 'modifiedon',
                    index: 'modifiedon',
                    width: 220,
                    fixed: true,
                    // formatter: function(cellvalue, options, rowObject) {
                    //     return `<div style="width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${cellvalue}">${cellvalue}</div>`;
                    // },
                    // cellattr: function(rowId, cellvalue, rowdata, options, rawdata) {
                    //     return 'style="max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"';
                    // }
                }
            ],
            rowNum: 50,
            toolbar: [true, "top"],
            // rowList: [10, 20, 50, 100],
            mtype: "POST",
            rownumbers: true,
            rownumWidth: 35,
            gridview: true,
            pager: '#jqGridAclPager',
            viewrecords: false,
            sortname: 'useraclid',
            sortorder: 'asc',
            altRows: true,
            altclass: 'myAltRowClass',
            onSortCol: function(index, iCol, sortorder) {
                // Fix: Update jqGrid internal state so it toggles next time and sends correct sort to server
                this.p.sortorder = sortorder;
                this.p.sortname = index;
                var pd = $(this).jqGrid('getGridParam', 'postData');
                if (pd) {
                    pd.sidx = index;
                    pd.sord = sortorder;
                }

                // Update UI sorting arrows manually since we are using return 'stop'
                var previousSelectedTh = this.grid.headers[this.p.lastsort].el;
                var newSelectedTh = this.grid.headers[iCol].el;
                $("span.s-ico", previousSelectedTh).hide();
                $("span.s-ico", newSelectedTh).show();
                var disabledClass = "ui-state-disabled";
                $("span.ui-icon-asc, span.ui-icon-desc", newSelectedTh).addClass(disabledClass);
                $("span.ui-icon-" + sortorder, newSelectedTh).removeClass(disabledClass);
                this.p.lastsort = iCol;


                var targetGridId = this.id || 'jqGrid'; if (typeof lazyStates !== 'undefined' && lazyStates[targetGridId]) lazyStates[targetGridId].cachedData = {};
                if(typeof loadGridData === 'function') {
                    loadGridData("#jqGridAcl", gridAclUrl, $gridAcl.jqGrid('getGridParam', 'postData'), 1, $(this).jqGrid('getGridParam', 'rowNum'), 'jump', 'reload');
                }
                return 'stop';
            },
            loadComplete: function(data) {
                // Gracefully clear footer by feeding an empty userdata object
                // This preserves custom footer text labels but zeroes out the totals
                if (data && (data.records === 0 || data.records === "0")) {
                    data.userdata = {};
                    try { $(this).jqGrid('setGridParam', { userData: null }); } catch(e) {}
                    $('#lastUpdateHandler, #jqGridInfoHandler').text('');
                }
                // Force clear footer if no records found
                if (data && (data.records === 0 || data.records === "0")) {
                    setTimeout(function() {
                        try {
                            var _sDiv = $(this)[0].grid.sDiv;
                            if (_sDiv) {
                                $(_sDiv).find('tr.footrow td, tr[class*=\"myfootrow\"] td').html('&nbsp;');
                            }
                            $('#lastUpdateHandler, #jqGridInfoHandler').html('');
                            $(this).jqGrid('setGridParam', { userData: null });
                        } catch(e) {}
                    }.bind(this), 50);
                }
                $('#gsh_' + $.jgrid.jqID($gridAcl[0].id) + '_rn').html($("<div id='resetFilterOptionsAcl' class='clearsearchclass text-center' style='cursor: pointer;' title='Clear Filter'><span id='resetFilterOptionsAclSpan'><i class='fas fa-times text-danger'></i></span></div>"));
                $("#resetFilterOptionsAcl").click(function(){
                    $('input[id*="gs_"]').val("");
                    $("#resetFilterOptionsAcl span#resetFilterOptionsAclSpan").removeClass('aktif');
                    $gridAcl.jqGrid('setGridParam', { search: false, postData: { "filters": ""} }).trigger("reloadGrid");
                });
                
                var filter = $(this).getGridParam("postData").filters;
                var clas = "";
                if (filter != undefined && filter != "") {
                    clas = "aktif";
                    if (JSON.parse(filter).rules.length == 0) {
                        clas = "";
                        $("#resetFilterOptionsAcl span#resetFilterOptionsAclSpan").removeClass("aktif");
                    }
                }

                $("#resetFilterOptionsAcl span#resetFilterOptionsAclSpan").addClass(clas);
                $gridAcl.removeClass('table-striped');
                
                if(typeof setHighlight === 'function') {
                    setHighlight($gridAcl);
                }
                
                if(typeof setupLazyLoadScrollHandler === 'function') {
                    setupLazyLoadScrollHandler("#jqGridAcl", gridAclUrl, $gridAcl.jqGrid('getGridParam', 'postData'));
                }
                
            }
        }).customPager({
            lazyLoading: true,
            buttons: [
                {
                    id: 'addAcl_' + userpk,
                    innerHTML: '<i class="fa fa-key"></i> MANAGE USER ROLES',
                    class: 'btn btn-primary btn-sm mr-1',
                    onClick: () => {
                        let currentMasterId = $('#jqGrid').jqGrid('getGridParam', 'selrow');
                        if (currentMasterId) {
                            newAcl(currentMasterId);
                        } else {
                            newAcl(userpk); // fallback
                        }
                    }
                }
            ]
        });
        
        $gridAcl.jqGrid('filterToolbar', {
            stringResult: true,
            searchOnEnter: false,
            defaultSearch: 'cn',
            beforeSearch: function() {
                var targetGridId = this.id || 'jqGrid'; if (typeof lazyStates !== 'undefined' && lazyStates[targetGridId]) lazyStates[targetGridId].cachedData = {};
                $gridAcl.jqGrid('clearGridData');
                loadGridData("#jqGridAcl", gridAclUrl, $gridAcl.jqGrid('getGridParam', 'postData'), 1, $gridAcl.jqGrid('getGridParam', 'rowNum'), 'down', 'reload');
                return false;
            }
        });
        
        if(typeof loadGridData === 'function') {
            loadGridData("#jqGridAcl", gridAclUrl, $gridAcl.jqGrid('getGridParam', 'postData'), 1, $gridAcl.jqGrid('getGridParam', 'rowNum'), 'down', 'init');
        }
    });

    function newAcl(userpk) {
        $('.modal-loader').removeClass('d-none');
        var page = "<?= base_url('useracl/userroles/') ?>" + userpk;
        
        // Memuat konten modal secara dinamis dan menampilkannya dengan cache: false
        $.ajax({
            url: page,
            type: 'GET',
            cache: false,
            success: function(html) {
                $('.modal-loader').addClass('d-none');
                if ($('#aclModal').length) {
                    $('#aclModal').remove(); 
                }
                $('body').append(html);
                $('#aclModal').modal('show');
            },
            error: function() {
                $('.modal-loader').addClass('d-none');
                alert('Gagal memuat form User Roles');
            }
        });
    }
</script>
