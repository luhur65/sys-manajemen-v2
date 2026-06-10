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
                var targetGridId = this.id ? '#' + this.id : '#jqGrid'; if (typeof gridState !== 'undefined' && gridState[targetGridId]) gridState[targetGridId].cachedData = {};
                if(typeof loadGridData === 'function') {
                    loadGridData("#jqGridAcl", gridAclUrl, $gridAcl.jqGrid('getGridParam', 'postData'), 1, $(this).jqGrid('getGridParam', 'rowNum'), 'jump', 'page');
                }
                return 'stop';
            },
            loadComplete: function(data) {
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
                
                // Add View Text
                $('#jqGridAclPager_center').css('width', '405px');
                var jumlah = data.rows == undefined ? 0 : data.rows.length;
                if ($("#showListAcl").length == 0) {
                    $("#jqGridAclPager_center table tbody tr").append(`<td><span id="showListAcl"></span></td>`);
                }
                $("#showListAcl").html(`View 1 - ${jumlah} of ${data.records}`);
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
                var targetGridId = this.id ? '#' + this.id : '#jqGrid'; if (typeof gridState !== 'undefined' && gridState[targetGridId]) gridState[targetGridId].cachedData = {};
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
