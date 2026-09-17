/* global wpecPublic, jQuery */
(function ($) {
    'use strict';

    var i18n     = wpecPublic.i18n;
    var ajaxUrl  = wpecPublic.ajaxUrl;
    var nonce    = wpecPublic.nonce;

    // ----------------------------------------------------------------
    // State
    // ----------------------------------------------------------------
    var state = {
        view:     'month',
        year:     new Date().getFullYear(),
        month:    new Date().getMonth() + 1,
        day:      new Date().getDate(),
        page:     1,
        category: '',
        perPage:  10,
        template: 'classic',
    };

    // ----------------------------------------------------------------
    // Init all calendars on page
    // ----------------------------------------------------------------
    function initCalendars() {
        $('.wpec-calendar-wrap').each(function () {
            var $wrap = $(this);
            var s = $.extend({}, state, {
                view:     $wrap.data('view')     || state.view,
                template: $wrap.data('template') || state.template,
                perPage:  parseInt($wrap.data('per-page'), 10) || state.perPage,
                category: $wrap.data('category') || '',
            });
            initCalendar($wrap, s);
        });
    }

    function initCalendar($wrap, s) {
        render($wrap, s);
        bindToolbar($wrap, s);
    }

    // ----------------------------------------------------------------
    // Toolbar bindings
    // ----------------------------------------------------------------
    function bindToolbar($wrap, s) {
        // View switcher
        $wrap.on('click', '.wpec-view-btn', function () {
            s.view = $(this).data('view');
            s.page = 1;
            $wrap.find('.wpec-view-btn').removeClass('active');
            $(this).addClass('active');
            render($wrap, s);
        });

        // Prev / Next / Today
        $wrap.on('click', '.wpec-nav-prev', function () { navigate($wrap, s, -1); });
        $wrap.on('click', '.wpec-nav-next', function () { navigate($wrap, s, 1); });
        $wrap.on('click', '.wpec-nav-today', function () {
            var now = new Date();
            s.year  = now.getFullYear();
            s.month = now.getMonth() + 1;
            s.day   = now.getDate();
            s.page  = 1;
            render($wrap, s);
        });

        // Category filter
        $wrap.on('change', '.wpec-cat-filter', function () {
            s.category = $(this).val();
            s.page     = 1;
            render($wrap, s);
        });

        // Pagination
        $wrap.on('click', '.wpec-page-btn', function () {
            s.page = parseInt($(this).data('page'), 10);
            render($wrap, s);
        });

        // Day click in month view
        $wrap.on('click', '.wpec-day-cell[data-date]', function () {
            var d     = $(this).data('date').split('-');
            s.view    = 'day';
            s.year    = parseInt(d[0], 10);
            s.month   = parseInt(d[1], 10);
            s.day     = parseInt(d[2], 10);
            s.page    = 1;
            $wrap.find('.wpec-view-btn').removeClass('active');
            $wrap.find('.wpec-view-btn[data-view="day"]').addClass('active');
            render($wrap, s);
        });

        // Modal open / close
        $wrap.on('click', '.wpec-event-card[data-url]', function (e) {
            if ($(e.target).closest('a').length) return;
            openModal($wrap, $(this).data('event'));
        });
        $wrap.on('click', '.wpec-modal-overlay, .wpec-modal-close', function () {
            closeModal($wrap);
        });
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') closeModal($wrap);
        });
    }

    // ----------------------------------------------------------------
    // Navigation
    // ----------------------------------------------------------------
    function navigate($wrap, s, dir) {
        if (s.view === 'month') {
            s.month += dir;
            if (s.month > 12) { s.month = 1;  s.year++; }
            if (s.month < 1)  { s.month = 12; s.year--; }
        } else if (s.view === 'week') {
            var d = new Date(s.year, s.month - 1, s.day);
            d.setDate(d.getDate() + dir * 7);
            s.year  = d.getFullYear();
            s.month = d.getMonth() + 1;
            s.day   = d.getDate();
        } else if (s.view === 'day') {
            var d2 = new Date(s.year, s.month - 1, s.day);
            d2.setDate(d2.getDate() + dir);
            s.year  = d2.getFullYear();
            s.month = d2.getMonth() + 1;
            s.day   = d2.getDate();
        } else {
            // list / summary / photo — navigate by page
            s.page = Math.max(1, s.page + dir);
        }
        render($wrap, s);
    }

    // ----------------------------------------------------------------
    // Main render
    // ----------------------------------------------------------------
    function render($wrap, s) {
        var $body = $wrap.find('.wpec-calendar-body');
        $body.html('<div class="wpec-loading"><span class="wpec-spinner"></span> ' + i18n.loading + '</div>');
        updateLabel($wrap, s);

        $.post(ajaxUrl, {
            action:   'wpec_get_events',
            nonce:    nonce,
            view:     s.view,
            year:     s.year,
            month:    s.month,
            day:      s.day,
            page:     s.page,
            per_page: s.perPage,
            category: s.category,
        }, function (res) {
            if (!res.success) {
                $body.html('<p class="wpec-error">' + (res.data || 'Error loading events.') + '</p>');
                return;
            }
            var data = res.data;
            switch (s.view) {
                case 'month':   renderMonth($wrap, $body, s, data); break;
                case 'week':    renderWeek($wrap, $body, s, data);  break;
                case 'day':     renderDay($wrap, $body, s, data);   break;
                case 'list':    renderList($wrap, $body, s, data);  break;
                case 'summary': renderSummary($wrap, $body, s, data); break;
                case 'photo':   renderPhoto($wrap, $body, s, data); break;
                default:        renderList($wrap, $body, s, data);
            }
            renderPagination($wrap, s, data);
        });
    }

    // ----------------------------------------------------------------
    // Update header label
    // ----------------------------------------------------------------
    function updateLabel($wrap, s) {
        var label = '';
        if (s.view === 'month') {
            label = i18n.months[s.month - 1] + ' ' + s.year;
        } else if (s.view === 'week') {
            label = i18n.months[s.month - 1] + ' ' + s.year + ' — ' + i18n.months[s.month - 1];
        } else if (s.view === 'day') {
            label = i18n.months[s.month - 1] + ' ' + s.day + ', ' + s.year;
        } else {
            label = i18n.months[s.month - 1] + ' ' + s.year;
        }
        $wrap.find('.wpec-current-label').text(label);
    }

    // ----------------------------------------------------------------
    // Month view
    // ----------------------------------------------------------------
    function renderMonth($wrap, $body, s, data) {
        // Index events by date
        var byDate = {};
        (data.events || []).forEach(function (ev) {
            var k = ev.start_date;
            if (!byDate[k]) byDate[k] = [];
            byDate[k].push(ev);
        });

        var daysInMonth  = new Date(s.year, s.month, 0).getDate();
        var firstDayOfWeek = new Date(s.year, s.month - 1, 1).getDay(); // 0=Sun
        var startOffset  = firstDayOfWeek; // weeks start Sunday by default

        var dayNames = [i18n.sun, i18n.mon, i18n.tue, i18n.wed, i18n.thu, i18n.fri, i18n.sat];

        var html = '<div class="wpec-month-grid">';

        // Day headers
        html += '<div class="wpec-month-header-row">';
        dayNames.forEach(function (d) { html += '<div class="wpec-day-header">' + escHtml(d) + '</div>'; });
        html += '</div><div class="wpec-month-days">';

        // Empty cells before month start
        for (var i = 0; i < startOffset; i++) {
            html += '<div class="wpec-day-cell wpec-day-empty"></div>';
        }

        var today = new Date();
        for (var d = 1; d <= daysInMonth; d++) {
            var dateStr = s.year + '-' + pad(s.month) + '-' + pad(d);
            var isToday = (today.getFullYear() === s.year && (today.getMonth() + 1) === s.month && today.getDate() === d);
            var events  = byDate[dateStr] || [];
            var cls     = 'wpec-day-cell' + (isToday ? ' wpec-today' : '') + (events.length ? ' wpec-has-events' : '');

            html += '<div class="' + cls + '" data-date="' + dateStr + '">';
            html += '<span class="wpec-day-num">' + d + '</span>';
            html += '<div class="wpec-day-events">';
            events.slice(0, 3).forEach(function (ev) {
                html += '<div class="wpec-month-event" data-event="' + escAttr(JSON.stringify(ev)) + '" data-url="' + escAttr(ev.url) + '">';
                html += '<span class="wpec-dot"></span>';
                html += '<a href="' + escAttr(ev.url) + '" class="wpec-month-event-title">' + escHtml(ev.title) + '</a>';
                html += '</div>';
            });
            if (events.length > 3) {
                html += '<div class="wpec-more-events">+' + (events.length - 3) + ' ' + 'more' + '</div>';
            }
            html += '</div></div>';
        }

        // Fill remaining cells
        var totalCells = startOffset + daysInMonth;
        var remainder  = totalCells % 7;
        if (remainder > 0) {
            for (var j = 0; j < (7 - remainder); j++) {
                html += '<div class="wpec-day-cell wpec-day-empty"></div>';
            }
        }

        html += '</div></div>';
        $body.html(html);
    }

    // ----------------------------------------------------------------
    // Week view
    // ----------------------------------------------------------------
    function renderWeek($wrap, $body, s, data) {
        var weekStart = new Date(s.year, s.month - 1, s.day);
        // Snap to Monday
        var dow = weekStart.getDay();
        weekStart.setDate(weekStart.getDate() - ((dow + 6) % 7));

        var dayNames = [i18n.mon, i18n.tue, i18n.wed, i18n.thu, i18n.fri, i18n.sat, i18n.sun];
        var html = '<div class="wpec-week-grid">';

        var byDate = {};
        (data.events || []).forEach(function (ev) {
            var k = ev.start_date;
            if (!byDate[k]) byDate[k] = [];
            byDate[k].push(ev);
        });

        for (var i = 0; i < 7; i++) {
            var d       = new Date(weekStart);
            d.setDate(weekStart.getDate() + i);
            var dateStr = fmtDate(d);
            var events  = byDate[dateStr] || [];
            var isToday = fmtDate(new Date()) === dateStr;

            html += '<div class="wpec-week-col' + (isToday ? ' wpec-today' : '') + '">';
            html += '<div class="wpec-week-col-header">';
            html += '<span class="wpec-week-day-name">' + dayNames[i] + '</span>';
            html += '<span class="wpec-week-day-num">' + d.getDate() + '</span>';
            html += '</div>';
            html += '<div class="wpec-week-events">';
            if (events.length) {
                events.forEach(function (ev) {
                    html += buildEventCard(ev, 'compact');
                });
            } else {
                html += '<div class="wpec-no-event-placeholder"></div>';
            }
            html += '</div></div>';
        }
        html += '</div>';
        $body.html(html);
    }

    // ----------------------------------------------------------------
    // Day view
    // ----------------------------------------------------------------
    function renderDay($wrap, $body, s, data) {
        var events = data.events || [];
        var html = '<div class="wpec-day-view">';
        if (!events.length) {
            html += '<p class="wpec-no-events">' + i18n.noEvents + '</p>';
        } else {
            events.forEach(function (ev) { html += buildEventCard(ev, 'full'); });
        }
        html += '</div>';
        $body.html(html);
    }

    // ----------------------------------------------------------------
    // List view
    // ----------------------------------------------------------------
    function renderList($wrap, $body, s, data) {
        var events = data.events || [];
        if (!events.length) {
            $body.html('<p class="wpec-no-events">' + i18n.noEvents + '</p>');
            return;
        }

        // Group by date
        var groups = {};
        var order  = [];
        events.forEach(function (ev) {
            var k = ev.start_date;
            if (!groups[k]) { groups[k] = []; order.push(k); }
            groups[k].push(ev);
        });

        var html = '<div class="wpec-list-view">';
        order.forEach(function (dateKey) {
            html += '<div class="wpec-list-date-group">';
            html += '<h3 class="wpec-list-date-heading">' + escHtml(groups[dateKey][0].start_formatted) + '</h3>';
            groups[dateKey].forEach(function (ev) { html += buildEventCard(ev, 'list'); });
            html += '</div>';
        });
        html += '</div>';
        $body.html(html);
    }

    // ----------------------------------------------------------------
    // Summary view
    // ----------------------------------------------------------------
    function renderSummary($wrap, $body, s, data) {
        var events = data.events || [];
        if (!events.length) {
            $body.html('<p class="wpec-no-events">' + i18n.noEvents + '</p>');
            return;
        }
        var html = '<div class="wpec-summary-view">';
        events.forEach(function (ev) {
            html += '<div class="wpec-summary-row">';
            html += '<div class="wpec-summary-date"><span>' + escHtml(ev.start_date ? new Date(ev.start_date + 'T12:00:00').getDate() : '') + '</span>' +
                    '<small>' + escHtml(ev.start_date ? i18n.months[new Date(ev.start_date + 'T12:00:00').getMonth()].slice(0,3) : '') + '</small></div>';
            html += '<div class="wpec-summary-info">';
            html += '<a href="' + escAttr(ev.url) + '" class="wpec-summary-title">' + escHtml(ev.title) + '</a>';
            if (ev.venue_name) html += '<div class="wpec-summary-venue">📍 ' + escHtml(ev.venue_name) + (ev.city ? ', ' + escHtml(ev.city) : '') + '</div>';
            html += '<div class="wpec-summary-meta">';
            if (ev.start_formatted) html += '<span>🕐 ' + escHtml(ev.start_formatted) + '</span>';
            if (ev.cost_formatted)  html += '<span>💰 ' + escHtml(ev.cost_formatted) + '</span>';
            html += '</div>';
            html += '</div></div>';
        });
        html += '</div>';
        $body.html(html);
    }

    // ----------------------------------------------------------------
    // Photo view
    // ----------------------------------------------------------------
    function renderPhoto($wrap, $body, s, data) {
        var events = data.events || [];
        if (!events.length) {
            $body.html('<p class="wpec-no-events">' + i18n.noEvents + '</p>');
            return;
        }
        var html = '<div class="wpec-photo-grid">';
        events.forEach(function (ev) {
            html += '<div class="wpec-photo-card" data-event="' + escAttr(JSON.stringify(ev)) + '" data-url="' + escAttr(ev.url) + '">';
            if (ev.thumbnail) {
                html += '<div class="wpec-photo-img-wrap"><img src="' + escAttr(ev.thumbnail) + '" alt="' + escAttr(ev.title) + '" loading="lazy" /></div>';
            } else {
                html += '<div class="wpec-photo-img-wrap wpec-photo-placeholder"><span>📅</span></div>';
            }
            html += '<div class="wpec-photo-info">';
            html += '<a href="' + escAttr(ev.url) + '" class="wpec-photo-title">' + escHtml(ev.title) + '</a>';
            if (ev.start_formatted) html += '<div class="wpec-photo-date">' + escHtml(ev.start_formatted) + '</div>';
            if (ev.venue_name)      html += '<div class="wpec-photo-venue">📍 ' + escHtml(ev.venue_name) + '</div>';
            if (ev.cost_formatted)  html += '<div class="wpec-photo-cost">' + escHtml(ev.cost_formatted) + '</div>';
            html += '</div></div>';
        });
        html += '</div>';
        $body.html(html);
    }

    // ----------------------------------------------------------------
    // Shared event card builder
    // ----------------------------------------------------------------
    function buildEventCard(ev, mode) {
        var cls = 'wpec-event-card wpec-event-card-' + mode;
        var html = '<div class="' + cls + '" data-event="' + escAttr(JSON.stringify(ev)) + '" data-url="' + escAttr(ev.url) + '">';

        if (mode === 'full' && ev.thumbnail) {
            html += '<img class="wpec-card-img" src="' + escAttr(ev.thumbnail) + '" alt="' + escAttr(ev.title) + '" loading="lazy" />';
        }

        html += '<div class="wpec-card-body">';
        html += '<a href="' + escAttr(ev.url) + '" class="wpec-card-title">' + escHtml(ev.title) + '</a>';

        if (mode !== 'compact') {
            if (ev.start_formatted) html += '<div class="wpec-card-date">🕐 ' + escHtml(ev.start_formatted) + '</div>';
            if (ev.venue_name)      html += '<div class="wpec-card-venue">📍 ' + escHtml(ev.venue_name) + (ev.city ? ', ' + escHtml(ev.city) : '') + '</div>';
            if (ev.cost_formatted)  html += '<div class="wpec-card-cost">💰 ' + escHtml(ev.cost_formatted) + '</div>';
        } else {
            if (ev.start_formatted) html += '<small>' + escHtml(ev.start_formatted) + '</small>';
        }

        if (mode === 'full' && ev.excerpt) {
            html += '<p class="wpec-card-excerpt">' + escHtml(ev.excerpt) + '</p>';
        }

        if (ev.categories && ev.categories.length) {
            html += '<div class="wpec-card-cats">';
            ev.categories.forEach(function (c) { html += '<span class="wpec-cat-badge">' + escHtml(c) + '</span>'; });
            html += '</div>';
        }

        html += '<a href="' + escAttr(ev.url) + '" class="wpec-card-cta">View Details →</a>';
        html += '</div></div>';
        return html;
    }

    // ----------------------------------------------------------------
    // Pagination
    // ----------------------------------------------------------------
    function renderPagination($wrap, s, data) {
        var $pager = $wrap.find('.wpec-pagination');
        var paged  = ['list', 'summary', 'photo'].includes(s.view);

        if (!paged || data.total_pages <= 1) {
            $pager.hide();
            return;
        }

        var html = '';
        for (var p = 1; p <= data.total_pages; p++) {
            var cls = p === s.page ? 'wpec-page-btn active' : 'wpec-page-btn';
            html += '<button class="' + cls + '" data-page="' + p + '">' + p + '</button>';
        }
        $pager.html('<div class="wpec-page-wrap">' + html + '</div>').show();
    }

    // ----------------------------------------------------------------
    // Modal
    // ----------------------------------------------------------------
    function openModal($wrap, eventData) {
        if (!eventData) return;
        var ev;
        try { ev = typeof eventData === 'string' ? JSON.parse(eventData) : eventData; } catch(e) { return; }

        var html = '';
        if (ev.thumbnail) html += '<img class="wpec-modal-img" src="' + escAttr(ev.thumbnail) + '" alt="' + escAttr(ev.title) + '" />';
        html += '<h2 class="wpec-modal-title"><a href="' + escAttr(ev.url) + '">' + escHtml(ev.title) + '</a></h2>';
        if (ev.start_formatted) html += '<p class="wpec-modal-date">🕐 ' + escHtml(ev.start_formatted) + (ev.end_formatted ? ' — ' + escHtml(ev.end_formatted) : '') + '</p>';
        if (ev.venue_name) {
            html += '<p class="wpec-modal-venue">📍 ' + escHtml(ev.venue_name);
            var loc = [ev.city, ev.country].filter(Boolean).join(', ');
            if (loc) html += ', ' + escHtml(loc);
            html += '</p>';
        }
        if (ev.cost_formatted) html += '<p class="wpec-modal-cost">💰 ' + escHtml(ev.cost_formatted) + '</p>';
        if (ev.excerpt) html += '<p class="wpec-modal-excerpt">' + escHtml(ev.excerpt) + '</p>';
        if (ev.organizers && ev.organizers.length) {
            html += '<p class="wpec-modal-orgs">👤 ';
            html += ev.organizers.map(function (o) { return escHtml(o.name); }).join(', ');
            html += '</p>';
        }
        html += '<a href="' + escAttr(ev.url) + '" class="wpec-btn wpec-btn-primary" style="margin-top:1rem;">View Full Event →</a>';
        if (ev.booking_enabled) {
            html += '<a href="' + escAttr(ev.url) + '#wpec-booking-form" class="wpec-btn wpec-btn-outline" style="margin-top:.5rem;margin-left:.5rem;">' + i18n.bookNow + '</a>';
        }

        $wrap.find('.wpec-modal-body').html(html);
        $wrap.find('.wpec-modal-overlay').fadeIn(200);
        $('body').addClass('wpec-modal-open');
    }

    function closeModal($wrap) {
        $wrap.find('.wpec-modal-overlay').fadeOut(150);
        $('body').removeClass('wpec-modal-open');
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------
    function pad(n) { return String(n).padStart(2, '0'); }

    function fmtDate(d) {
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }

    function escHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escAttr(str) { return escHtml(str); }

    // ----------------------------------------------------------------
    // Boot
    // ----------------------------------------------------------------
    $(document).ready(initCalendars);

})(jQuery);
