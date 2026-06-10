/**
 * lazyLoadingGridMonolith.js
 * Library Lazy Loading khusus untuk Monolith (PHP/CI)
 * Dioptimalkan dari logic lazyLoadingGrid.js terbaru.
 */

var page = 1;
var totalPages = 1;
var totalRecord = 0;
var loading = false;
var loadingQueue = [];
var cachedData = {};
var minPageLoaded = 1;
var maxPageLoaded = 1;
var lastScrollTop = 0;
var currentFilters = null;
var currentSortIdx = null;
var currentSortOrd = null;

var WINDOW_PAGES = 3;
var rowsPerPage = 50;
var gapPage = 15;
var currentViewPage = 1;

function throttle(func, limit) {
    let inThrottle;
    let lastArgs;
    return function () {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => {
                inThrottle = false;
                if (lastArgs) {
                    func.apply(context, lastArgs);
                    lastArgs = null;
                }
            }, limit);
        } else {
            lastArgs = args;
        }
    };
}

function hasFilterChanged(grid) {
    var rawNewFilters = grid.jqGrid('getGridParam', 'postData').filters;
    var newFilters = (rawNewFilters === undefined || rawNewFilters === null) ? '' : rawNewFilters;
    var oldFilters = (currentFilters === undefined || currentFilters === null) ? '' : currentFilters;
    let newSort = grid.jqGrid('getGridParam', 'sortname');
    let newOrder = grid.jqGrid('getGridParam', 'sortorder');

    if (currentFilters === null || currentFilters === undefined || currentSortIdx === null || currentSortIdx === undefined) {
        currentFilters = newFilters;
        currentSortIdx = newSort;
        currentSortOrd = newOrder;
        return false;
    }

    var isChanged = (oldFilters !== newFilters) || (currentSortIdx !== newSort) || (currentSortOrd !== newOrder);
    if (isChanged) {
        currentFilters = newFilters;
        currentSortIdx = newSort;
        currentSortOrd = newOrder;
        return true;
    }
    return false;
}

function resetGridState(grid, resetFilters = false) {
    cachedData = {};
    minPageLoaded = 1;
    maxPageLoaded = 1;
    lastScrollTop = 0;
    loading = false;
    loadingQueue = [];
    if (resetFilters) currentFilters = null;
    grid.jqGrid('clearGridData');
}

function processLoadingQueue(gridId, api, postData, rowsCount) {
    if (loadingQueue.length && !loading) {
        var next = loadingQueue.shift();
        loadGridData(gridId, api, postData, next.page, rowsCount, next.direction, 'page');
    }
}

function loadGridData(gridId, api, postData, pageNumber, rowsCount, direction = 'down', proses = 'page', callback) {
    var grid = $(gridId);
    rowsPerPage = rowsCount;

    if (proses !== 'reload' && proses !== 'jump' && minPageLoaded > 0 && hasFilterChanged(grid)) {
        resetGridState(grid);
        pageNumber = 1;
        direction = 'down';
        proses = 'reload';
    }

    if (proses === 'reload') {
        resetGridState(grid);
        minPageLoaded = pageNumber;
        maxPageLoaded = pageNumber;
        currentFilters = grid.jqGrid('getGridParam', 'postData').filters;
        currentSortIdx = grid.jqGrid('getGridParam', 'sortname');
        currentSortOrd = grid.jqGrid('getGridParam', 'sortorder');
        loading = false;
    }

    if (proses === 'jump') {
        loading = true;
        grid.jqGrid('clearGridData');
        minPageLoaded = pageNumber;
        maxPageLoaded = pageNumber;
        if (typeof lastScrollTop !== 'undefined') lastScrollTop = 0;
        loading = false;
    }

    if (cachedData[pageNumber] && proses === 'page') {
        renderFromCache(grid, cachedData[pageNumber], direction, rowsCount, pageNumber);
        if (callback) callback();
        return;
    }

    if (loading) {
        var alreadyQueued = loadingQueue.some(function (q) { return q.page === pageNumber; });
        if (!alreadyQueued) loadingQueue.push({ page: pageNumber, direction: direction });
        return;
    }

    loading = true;
    $('.loaderGrid').removeClass('d-none');

    var fullPostData = $.extend({}, postData, {
        page: pageNumber,
        rows: rowsCount,
        sidx: grid.jqGrid('getGridParam', 'sortname'),
        sord: grid.jqGrid('getGridParam', 'sortorder'),
        filters: grid.jqGrid('getGridParam', 'postData').filters,
        _search: grid.jqGrid('getGridParam', 'postData')._search
    });

    $.ajax({
        url: api,
        type: 'POST',
        data: fullPostData,
        success: function (res) {
            $('.loaderGrid').addClass('d-none');
            totalRecord = res.records;
            totalPages = res.total;

            if (!res.rows || !res.rows.length) {
                loading = false;
                return;
            }

            // Map cell array to object if necessary (standard JQGrid Monolith format)
            var mappedRows = res.rows;
            if (res.rows[0].cell && Array.isArray(res.rows[0].cell)) {
                var cm = grid.jqGrid('getGridParam', 'colModel');
                mappedRows = res.rows.map(function (row) {
                    var newRow = { id: row.id };
                    var cellIdx = 0;
                    for (var j = 0; j < cm.length; j++) {
                        // Skip system columns
                        if (cm[j].name !== 'rn' && cm[j].name !== 'cb' && cm[j].name !== 'subgrid') {
                            newRow[cm[j].name] = row.cell[cellIdx++];
                        }
                    }
                    return newRow;
                });
            }

            cachedData[pageNumber] = mappedRows;

            if (res.userdata) {
                grid.jqGrid('setGridParam', { userData: res.userdata });
            }

            renderFromCache(grid, mappedRows, direction, rowsCount, pageNumber, res);
            grid.jqGrid('setGridParam', { records: totalRecord });
            if (callback) callback(res);
        },
        error: function (xhr, textStatus, errorThrown) {
            $('.loaderGrid').addClass('d-none');
            console.error('Grid Load Error:', textStatus, errorThrown);
        },
        complete: function () {
            loading = false;
            var freshPostData = grid.jqGrid('getGridParam', 'postData');
            processLoadingQueue(gridId, api, freshPostData, rowsCount);
        }
    });
}

function setupLazyLoadScrollHandler(gridId, apiUrl, postData) {
    var grid = $(gridId);
    // var suppressScrollEvent = false;

    var throttledScroll = throttle(function () {
        // if (suppressScrollEvent) return;

        var bDiv = $(this);
        var scrollTop = bDiv.scrollTop();
        var viewHeight = bDiv.height();
        var tableHeight = bDiv.find('table').height();
        var rowNum = grid.jqGrid('getGridParam', 'rowNum');
        var rowHeight = grid.find('tr[id]').height() || 30;
        var currentPostData = grid.jqGrid('getGridParam', 'postData');
        var detectedPage = detectCurrentViewPage(grid);

        if (detectedPage !== currentViewPage) {
            currentViewPage = detectedPage;
            updateGridInfoFast(grid);
        }

        if (scrollTop > lastScrollTop && scrollTop + viewHeight >= tableHeight - (gapPage * rowHeight)) {
            lastScrollTop = scrollTop;
            var nextPage = maxPageLoaded + 1;
            if (nextPage > totalPages) return;

            if (cachedData[nextPage]) {
                renderFromCache(grid, cachedData[nextPage], 'down', rowNum, nextPage);
            } else {
                loadGridData(gridId, apiUrl, currentPostData, nextPage, rowNum, 'down', 'page');
            }
            return;
        }

        var triggerThreshold = gapPage * rowHeight;
        if (maxPageLoaded === minPageLoaded) triggerThreshold = tableHeight * 0.8;

        if (scrollTop < lastScrollTop && scrollTop <= triggerThreshold && minPageLoaded > 1) {
            var prevPage = minPageLoaded - 1;
            // suppressScrollEvent = true;
            if (cachedData[prevPage]) {
                renderFromCache(grid, cachedData[prevPage], 'up', rowNum, prevPage);
            } else {
                loadGridData(gridId, apiUrl, currentPostData, prevPage, rowNum, 'up', 'page');
                lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
            }
            // requestAnimationFrame(function() { suppressScrollEvent = false; });
            return;
        }

        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    }, 150);

    grid.parents('.ui-jqgrid-bdiv').off('scroll.virtual').on('scroll.virtual', throttledScroll);
}

function renderFromCache(grid, data, direction, rowsPerPage, currentPage, res) {
    var selectedId = grid.jqGrid('getGridParam', 'selrow');
    var existingIds = grid.jqGrid('getDataIDs');
    var existingIdSet = new Set(existingIds.map(String));

    var originalOnSelectRow = grid.jqGrid('getGridParam', 'onSelectRow');
    grid.jqGrid('setGridParam', { onSelectRow: null });

    if (direction === 'down') {
        data.forEach(function (row) {
            if (!existingIdSet.has(row.id.toString())) {
                grid.jqGrid('addRowData', row.id, row, 'last');
            } else {
                // ← TAMBAH INI: update row yang sudah ada
                grid.jqGrid('setRowData', row.id, row);
            }
        });
        let validPage = currentPage || maxPageLoaded;
        maxPageLoaded = Math.max(maxPageLoaded, validPage);
        currentViewPage = currentPage;
        trimGridRows(grid, 'down', rowsPerPage);
    }

    if (direction === 'up') {
        var scrollDiv = grid.parents('.ui-jqgrid-bdiv');
        var prevScroll = scrollDiv.scrollTop();
        var addedCount = 0;

        // Iterasi mundur, namun sisipkan ke puncak grid ('first')
        for (var i = data.length - 1; i >= 0; i--) {
            if (!existingIdSet.has(data[i].id.toString())) {
                // Hapus parameter 'before' dan 'firstId', cukup gunakan 'first'
                grid.jqGrid('addRowData', data[i].id, data[i], 'first');
                addedCount++;
            }
        }

        minPageLoaded--;
        currentViewPage = currentPage;
        trimGridRows(grid, 'up', rowsPerPage);

        if (addedCount > 0) {
            var rowHeight = grid.find('tr[id]').height() || 30;
            var newScrollTop = prevScroll + (addedCount * rowHeight);
            scrollDiv.scrollTop(newScrollTop);
            lastScrollTop = newScrollTop;
        }
    }

    if (direction === 'jump' || direction === 'reload') {
        grid.jqGrid('clearGridData');
        data.forEach(function (row) { grid.jqGrid('addRowData', row.id, row, 'last'); });
        currentViewPage = parseInt(currentPage) || 1;
        minPageLoaded = currentViewPage;
        maxPageLoaded = currentViewPage;
        grid.jqGrid('setGridParam', { page: currentViewPage, records: totalRecord });
        selectedId = null;
    }

    grid.jqGrid('setGridParam', { onSelectRow: originalOnSelectRow });

    if (typeof setHighlight === 'function') setHighlight(grid);
    refreshRowNumbers(grid, minPageLoaded, rowsPerPage);
    updateGridInfoFast(grid);

    var loadCompleteFunc = grid.jqGrid('getGridParam', 'loadComplete');
    if (typeof loadCompleteFunc === 'function') {
        var userData = grid.jqGrid('getGridParam', 'userData');
        loadCompleteFunc.call(grid[0], res || { userdata: userData });
    }


}

function trimGridRows(grid, direction, rowsPerPage) {
    var ids = grid.jqGrid('getDataIDs');
    var maxRows = WINDOW_PAGES * rowsPerPage;
    if (ids.length <= maxRows) return;

    var excess = ids.length - maxRows;
    var pagesRemoved = Math.max(1, Math.floor(excess / rowsPerPage));

    if (direction === 'down') {
        for (let i = 0; i < excess; i++) grid.jqGrid('delRowData', ids[i]);
        minPageLoaded += pagesRemoved;
    } else if (direction === 'up') {
        for (let i = ids.length - 1; i >= ids.length - excess; i--) grid.jqGrid('delRowData', ids[i]);
        maxPageLoaded -= pagesRemoved;
    }
    refreshRowNumbers(grid, minPageLoaded, rowsPerPage);
}

function refreshRowNumbers(grid, pageNumber, rowLimit) {
    var ids = grid.jqGrid('getDataIDs');
    if (ids.length === 0) return;
    var limit = parseInt(rowLimit) || 50;
    var idToNumber = {};
    for (var pg in cachedData) {
        var realPage = parseInt(pg);
        cachedData[pg].forEach(function (row, idx) {
            idToNumber[row.id.toString()] = (realPage - 1) * limit + idx + 1;
        });
    }
    ids.forEach(function (currentId, i) {
        var num = idToNumber[currentId] || ((minPageLoaded - 1) * limit + i + 1);
        if (totalRecord > 0 && num > totalRecord) num = totalRecord;
        grid.jqGrid('setCell', currentId, 'rn', num);
    });
    updateGridInfoFast(grid);
}

function updateGridInfoFast(grid) {
    var start = (currentViewPage - 1) * rowsPerPage + 1;
    var actual = cachedData[currentViewPage] ? cachedData[currentViewPage].length : rowsPerPage;
    var end = start + actual - 1;
    if (totalRecord > 0 && end > totalRecord) end = totalRecord;
    var gridIdStr = grid.attr('id') || grid.getGridParam('id');
    $('#' + gridIdStr + 'InfoHandler').text('View ' + start + ' - ' + end + ' of ' + totalRecord);
}

function detectCurrentViewPage(grid) {
    var scrollDiv = grid.parents('.ui-jqgrid-bdiv');
    var scrollTop = scrollDiv.scrollTop();
    var rowHeight = grid.find('tr[id]').height() || 30;
    var visibleIndex = Math.floor(scrollTop / rowHeight);
    var recordNumber = (minPageLoaded - 1) * rowsPerPage + visibleIndex + 1;
    var page = Math.ceil(recordNumber / rowsPerPage);
    return Math.max(1, Math.min(page, totalPages));
}
