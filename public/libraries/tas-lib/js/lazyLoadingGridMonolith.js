/**
 * lazyLoadingGridMonolith.js
 * Library Lazy Loading khusus untuk Monolith (PHP/CI)
 * Dioptimalkan dari logic lazyLoadingGrid.js terbaru.
 */

var lazyStates = {};

function getGridState(grid) {
    var id = grid;
    if (typeof grid === 'object') id = grid.attr('id') || grid.jqGrid('getGridParam', 'id');
    if (typeof id === 'string') id = id.replace('#', '');
    if (!lazyStates[id]) {
        lazyStates[id] = {
            totalPages: 1,
            totalRecord: 0,
            loading: false,
            loadingQueue: [],
            cachedData: {},
            minPageLoaded: 1,
            maxPageLoaded: 1,
            lastScrollTop: 0,
            currentFilters: null,
            currentSortIdx: null,
            currentSortOrd: null,
            currentViewPage: 1
        };
    }
    return lazyStates[id];
}

var WINDOW_PAGES = 3;
var rowsPerPage = 50;
var gapPage = 15;

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
    var state = getGridState(grid);
    var rawNewFilters = grid.jqGrid('getGridParam', 'postData').filters;
    var newFilters = (rawNewFilters === undefined || rawNewFilters === null) ? '' : rawNewFilters;
    var oldFilters = (state.currentFilters === undefined || state.currentFilters === null) ? '' : state.currentFilters;
    let newSort = grid.jqGrid('getGridParam', 'sortname');
    let newOrder = grid.jqGrid('getGridParam', 'sortorder');

    if (state.currentFilters === null || state.currentFilters === undefined || state.currentSortIdx === null || state.currentSortIdx === undefined) {
        state.currentFilters = newFilters;
        state.currentSortIdx = newSort;
        state.currentSortOrd = newOrder;
        return false;
    }

    var isChanged = (oldFilters !== newFilters) || (state.currentSortIdx !== newSort) || (state.currentSortOrd !== newOrder);
    if (isChanged) {
        state.currentFilters = newFilters;
        state.currentSortIdx = newSort;
        state.currentSortOrd = newOrder;
        return true;
    }
    return false;
}

function resetGridState(grid, resetFilters = false) {
    var state = getGridState(grid);
    state.cachedData = {};
    state.minPageLoaded = 1;
    state.maxPageLoaded = 1;
    state.lastScrollTop = 0;
    state.loading = false;
    state.loadingQueue = [];
    if (resetFilters) state.currentFilters = null;
    grid.jqGrid('clearGridData');
}

function processLoadingQueue(gridId, api, postData, rowsCount) {
    var grid = $(gridId);
    var state = getGridState(grid);
    if (state.loadingQueue.length && !state.loading) {
        var next = state.loadingQueue.shift();
        loadGridData(gridId, api, postData, next.page, rowsCount, next.direction, 'page');
    }
}

function loadGridData(gridId, api, postData, pageNumber, rowsCount, direction = 'down', proses = 'page', callback) {
    var grid = $(gridId);
    var state = getGridState(grid);
    rowsPerPage = rowsCount;

    if (proses !== 'reload' && proses !== 'jump' && state.minPageLoaded > 0 && hasFilterChanged(grid)) {
        resetGridState(grid);
        pageNumber = 1;
        direction = 'down';
        proses = 'reload';
    }

    if (proses === 'reload') {
        resetGridState(grid);
        state.minPageLoaded = pageNumber;
        state.maxPageLoaded = pageNumber;
        state.currentFilters = grid.jqGrid('getGridParam', 'postData').filters;
        state.currentSortIdx = grid.jqGrid('getGridParam', 'sortname');
        state.currentSortOrd = grid.jqGrid('getGridParam', 'sortorder');
        state.loading = false;
    }

    if (proses === 'jump') {
        state.loading = true;
        grid.jqGrid('clearGridData');
        state.minPageLoaded = pageNumber;
        state.maxPageLoaded = pageNumber;
        state.lastScrollTop = 0;
        state.loading = false;
    }

    if (state.cachedData[pageNumber] && proses === 'page') {
        renderFromCache(grid, state.cachedData[pageNumber], direction, rowsCount, pageNumber);
        if (callback) callback();
        return;
    }

    if (state.loading) {
        var alreadyQueued = state.loadingQueue.some(function (q) { return q.page === pageNumber; });
        if (!alreadyQueued) state.loadingQueue.push({ page: pageNumber, direction: direction });
        return;
    }

    state.loading = true;
    $('.loaderGrid').removeClass('d-none');

    var currentFilters = grid.jqGrid('getGridParam', 'postData').filters;
    var _isSearch = grid.jqGrid('getGridParam', 'postData')._search;
    if (currentFilters) {
        try {
            var filtersObj = JSON.parse(currentFilters);
            _isSearch = (filtersObj.rules && filtersObj.rules.length > 0);
        } catch(e) {}
    }

    var fullPostData = $.extend({}, postData, {
        page: pageNumber,
        rows: rowsCount,
        sidx: grid.jqGrid('getGridParam', 'sortname'),
        sord: grid.jqGrid('getGridParam', 'sortorder'),
        filters: currentFilters,
        _search: _isSearch
    });

    $.ajax({
        url: api,
        type: 'POST',
        data: fullPostData,
        success: function (res) {
            $('.loaderGrid').addClass('d-none');
            state.totalRecord = res.records;
            state.totalPages = res.total;

            if (!res.rows || !res.rows.length) {
                state.loading = false;
                return;
            }

            var mappedRows = res.rows;
            if (res.rows[0].cell && Array.isArray(res.rows[0].cell)) {
                var cm = grid.jqGrid('getGridParam', 'colModel');
                mappedRows = res.rows.map(function (row) {
                    var newRow = { id: row.id };
                    var cellIdx = 0;
                    for (var j = 0; j < cm.length; j++) {
                        if (cm[j].name !== 'rn' && cm[j].name !== 'cb' && cm[j].name !== 'subgrid') {
                            newRow[cm[j].name] = row.cell[cellIdx++];
                        }
                    }
                    return newRow;
                });
            }

            state.cachedData[pageNumber] = mappedRows;

            if (res.userdata) {
                grid.jqGrid('setGridParam', { userData: res.userdata });
            }

            renderFromCache(grid, mappedRows, direction, rowsCount, pageNumber, res);
            grid.jqGrid('setGridParam', { records: state.totalRecord });
            if (callback) callback(res);
        },
        error: function (xhr, textStatus, errorThrown) {
            $('.loaderGrid').addClass('d-none');
            console.error('Grid Load Error:', textStatus, errorThrown);
        },
        complete: function () {
            state.loading = false;
            var freshPostData = grid.jqGrid('getGridParam', 'postData');
            processLoadingQueue(gridId, api, freshPostData, rowsCount);
        }
    });
}

function setupLazyLoadScrollHandler(gridId, apiUrl, postData) {
    var grid = $(gridId);
    var state = getGridState(grid);

    var throttledScroll = throttle(function () {
        var bDiv = $(this);
        var scrollTop = bDiv.scrollTop();
        var viewHeight = bDiv.height();
        var tableHeight = bDiv.find('table').height();
        var rowNum = grid.jqGrid('getGridParam', 'rowNum');
        var rowHeight = grid.find('tr[id]').height() || 30;
        var currentPostData = grid.jqGrid('getGridParam', 'postData');
        var detectedPage = detectCurrentViewPage(grid);

        if (detectedPage !== state.currentViewPage) {
            state.currentViewPage = detectedPage;
            updateGridInfoFast(grid);
        }

        if (scrollTop > state.lastScrollTop && scrollTop + viewHeight >= tableHeight - (gapPage * rowHeight)) {
            state.lastScrollTop = scrollTop;
            var nextPage = state.maxPageLoaded + 1;
            if (nextPage > state.totalPages) return;

            if (state.cachedData[nextPage]) {
                renderFromCache(grid, state.cachedData[nextPage], 'down', rowNum, nextPage);
            } else {
                loadGridData(gridId, apiUrl, currentPostData, nextPage, rowNum, 'down', 'page');
            }
            return;
        }

        var triggerThreshold = gapPage * rowHeight;
        if (state.maxPageLoaded === state.minPageLoaded) triggerThreshold = tableHeight * 0.8;

        if (scrollTop < state.lastScrollTop && scrollTop <= triggerThreshold && state.minPageLoaded > 1) {
            var prevPage = state.minPageLoaded - 1;
            if (state.cachedData[prevPage]) {
                renderFromCache(grid, state.cachedData[prevPage], 'up', rowNum, prevPage);
            } else {
                loadGridData(gridId, apiUrl, currentPostData, prevPage, rowNum, 'up', 'page');
                state.lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
            }
            return;
        }

        state.lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    }, 150);

    grid.parents('.ui-jqgrid-bdiv').off('scroll.virtual').on('scroll.virtual', throttledScroll);
}

function renderFromCache(grid, data, direction, rowsPerPage, currentPage, res) {
    var state = getGridState(grid);
    var selectedId = grid.jqGrid('getGridParam', 'selrow');
    var existingIds = grid.jqGrid('getDataIDs');
    var existingIdSet = new Set(existingIds.map(String));

    var originalOnSelectRow = grid.jqGrid('getGridParam', 'onSelectRow');
    var originalOnSelectAll = grid.jqGrid('getGridParam', 'onSelectAll');
    grid.jqGrid('setGridParam', { onSelectRow: null, onSelectAll: null });

    if (direction === 'down') {
        data.forEach(function (row) {
            if (!existingIdSet.has(row.id.toString())) {
                grid.jqGrid('addRowData', row.id, row, 'last');
            } else {
                grid.jqGrid('setRowData', row.id, row);
            }
        });
        let validPage = currentPage || state.maxPageLoaded;
        state.maxPageLoaded = Math.max(state.maxPageLoaded, validPage);
        state.currentViewPage = currentPage;
        trimGridRows(grid, 'down', rowsPerPage);
    } else if (direction === 'up') {
        var scrollDiv = grid.parents('.ui-jqgrid-bdiv');
        var prevScroll = scrollDiv.scrollTop();
        var addedCount = 0;

        for (var i = data.length - 1; i >= 0; i--) {
            if (!existingIdSet.has(data[i].id.toString())) {
                grid.jqGrid('addRowData', data[i].id, data[i], 'first');
                addedCount++;
            }
        }

        let validPage = currentPage || state.minPageLoaded;
        state.minPageLoaded = Math.min(state.minPageLoaded, validPage);
        state.currentViewPage = currentPage;
        trimGridRows(grid, 'up', rowsPerPage);

        if (addedCount > 0) {
            var rowHeight = grid.find('tr[id]').height() || 30;
            var newScrollTop = prevScroll + (addedCount * rowHeight);
            scrollDiv.scrollTop(newScrollTop);
            state.lastScrollTop = newScrollTop;
        }
        state.currentViewPage = currentPage;
        trimGridRows(grid, 'up', rowsPerPage);
    } else if (direction === 'jump' || direction === 'reload') {
        grid.jqGrid('clearGridData');
        data.forEach(function (row) { grid.jqGrid('addRowData', row.id, row, 'last'); });
        state.currentViewPage = parseInt(currentPage) || 1;
        state.minPageLoaded = state.currentViewPage;
        state.maxPageLoaded = state.currentViewPage;
        grid.jqGrid('setGridParam', { page: state.currentViewPage, records: state.totalRecord });
        selectedId = null;
    }

    grid.jqGrid('setGridParam', { onSelectRow: originalOnSelectRow, onSelectAll: originalOnSelectAll });

    if (typeof setHighlight === 'function') setHighlight(grid);
    refreshRowNumbers(grid, state.minPageLoaded, rowsPerPage);
    updateGridInfoFast(grid);

    var loadCompleteFunc = grid.jqGrid('getGridParam', 'loadComplete');
    if (typeof loadCompleteFunc === 'function') {
        var userData = grid.jqGrid('getGridParam', 'userData');
        loadCompleteFunc.call(grid[0], res || { userdata: userData });
    }
}

function trimGridRows(grid, direction, rowsPerPage) {
    var state = getGridState(grid);
    var ids = grid.jqGrid('getDataIDs');
    var maxRows = WINDOW_PAGES * rowsPerPage;
    if (ids.length <= maxRows) return;

    var excess = ids.length - maxRows;
    var pagesRemoved = Math.max(1, Math.floor(excess / rowsPerPage));

    if (direction === 'down') {
        for (let i = 0; i < excess; i++) grid.jqGrid('delRowData', ids[i]);
        state.minPageLoaded += pagesRemoved;
    } else if (direction === 'up') {
        for (let i = ids.length - 1; i >= ids.length - excess; i--) grid.jqGrid('delRowData', ids[i]);
        state.maxPageLoaded -= pagesRemoved;
    }
    refreshRowNumbers(grid, state.minPageLoaded, rowsPerPage);
}

function refreshRowNumbers(grid, pageNumber, rowLimit) {
    var state = getGridState(grid);
    var ids = grid.jqGrid('getDataIDs');
    if (ids.length === 0) return;
    var limit = parseInt(rowLimit) || 50;
    var idToNumber = {};
    for (var pg in state.cachedData) {
        var realPage = parseInt(pg);
        state.cachedData[pg].forEach(function (row, idx) {
            idToNumber[row.id.toString()] = (realPage - 1) * limit + idx + 1;
        });
    }
    ids.forEach(function (currentId, i) {
        var num = idToNumber[currentId] || ((state.minPageLoaded - 1) * limit + i + 1);
        if (state.totalRecord > 0 && num > state.totalRecord) num = state.totalRecord;
        grid.jqGrid('setCell', currentId, 'rn', num);
    });
    updateGridInfoFast(grid);
}

function updateGridInfoFast(grid) {
    var state = getGridState(grid);
    var start = (state.currentViewPage - 1) * rowsPerPage + 1;
    var actual = state.cachedData[state.currentViewPage] ? state.cachedData[state.currentViewPage].length : rowsPerPage;
    var end = start + actual - 1;
    if (state.totalRecord > 0 && end > state.totalRecord) end = state.totalRecord;
    var gridIdStr = grid.attr('id') || grid.getGridParam('id');
    $('#' + gridIdStr + 'InfoHandler').text('View ' + start + ' - ' + end + ' of ' + state.totalRecord);
}

function detectCurrentViewPage(grid) {
    var state = getGridState(grid);
    var scrollDiv = grid.parents('.ui-jqgrid-bdiv');
    var scrollTop = scrollDiv.scrollTop();
    var rowHeight = grid.find('tr[id]').height() || 30;
    var visibleIndex = Math.floor(scrollTop / rowHeight);
    var recordNumber = (state.minPageLoaded - 1) * rowsPerPage + visibleIndex + 1;
    var page = Math.ceil(recordNumber / rowsPerPage);
    return Math.max(1, Math.min(page, state.totalPages));
}
