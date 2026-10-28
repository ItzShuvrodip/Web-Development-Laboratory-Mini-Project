// ==============================================================================
// js/main.js - Client-Side Interactivity using jQuery (Filtering & Live Polling)
// ==============================================================================
$(document).ready(function () {

    // === 1. MENU CATEGORY TAB FILTERING (index.php) ===
    $(".filter-btn").on("click", function () {
        $(".filter-btn").removeClass("active"); // Remove active highlight from all tab buttons
        $(this).addClass("active");             // Add active highlight to clicked tab
        var cat = $(this).data("category");     // Read selected category (e.g. 'all', 'Snacks', etc.)
        
        // Show or hide menu cards matching selected category
        $(".menu-card").each(function () {
            $(this).toggle(cat === "all" || $(this).data("category") === cat);
        });
    });

    // === 2. QUANTITY INPUT VALIDATION & CLAMPING ===
    $("input[name='quantity']").on("change keyup", function () {
        var val = parseInt($(this).val(), 10);
        // [SPOT-CHANGE]: Enforce minimum 1 and maximum 20
        $(this).val(isNaN(val) || val < 1 ? 1 : Math.min(val, 20));
    });

    // === 3. LIVE QUEUE AUTO-POLLING VIA JQUERY AJAX (queue.php) ===
    if ($("#btn-refresh-queue").length > 0) {

        // Function fetching updated queue JSON from queue_data.php
        function fetchQueue() {
            var search = $("#btn-refresh-queue").data("search") || $("#search").val() || "";
            var url = "queue_data.php" + (search.trim() ? "?search=" + encodeURIComponent(search.trim()) : "");

            $.getJSON(url, function (res) {
                if (res.status !== "success") return;
                var lists = { ready: "", preparing: "", received: "" };
                var counts = { ready: 0, preparing: 0, received: 0 };
                var rows = "";

                // Iterate through orders and sort them into visual column cards & table rows
                $.each(res.orders, function (_, o) {
                    var st = (o.order_status || "").toLowerCase();
                    var label = st === "ready" ? "READY" : (st === "preparing" ? "Cooking" : "Queued");
                    var bg = st === "ready" ? "#d3f9d8" : (st === "preparing" ? "#fff9db" : "#e7f5ff");

                    // Build column card HTML
                    var card = '<div style="border-radius:10px;padding:12px 14px;display:flex;justify-content:space-between;align-items:center;background:' + bg + ';">' +
                        '<div><strong style="font-size:1.2rem;display:block;letter-spacing:1px;">' + $("<div>").text(o.order_number).html() + '</strong>' +
                        '<span style="font-size:0.85rem;">' + $("<div>").text(o.customer_name).html() + '</span></div>' +
                        '<span class="status-pill status-' + st + '">' + label + '</span></div>';

                    if (lists[st] !== undefined) {
                        lists[st] += card;
                        counts[st]++;
                    }

                    // Build table row HTML
                    rows += '<tr><td><strong style="color:#e8590c;">' + $("<div>").text(o.order_number).html() + '</strong></td>' +
                        '<td>' + $("<div>").text(o.customer_name).html() + '</td>' +
                        '<td>' + $("<div>").text(o.created_at).html() + '</td>' +
                        '<td><strong>₹' + parseFloat(o.total_amount).toFixed(2) + '</strong></td>' +
                        '<td><span class="status-pill status-' + st + '">' + $("<div>").text(o.order_status).html() + '</span></td></tr>';
                });

                // Update counter numbers
                $("#ready-count").text(counts.ready);
                $("#prep-count").text(counts.preparing);
                $("#received-count").text(counts.received);

                // Update column cards or show empty stage notice
                $("#ready-orders-list").html(lists.ready || '<div class="empty-col-msg">No orders in this stage</div>');
                $("#prep-orders-list").html(lists.preparing || '<div class="empty-col-msg">No orders in this stage</div>');
                $("#received-orders-list").html(lists.received || '<div class="empty-col-msg">No orders in this stage</div>');
                $("#all-orders-tbody").html(rows || '<tr><td colspan="5" style="text-align:center;padding:24px;">No orders found.</td></tr>');
                $("#queue-last-updated").text("Last updated: " + new Date().toLocaleTimeString());
            });
        }

        $("#btn-refresh-queue").on("click", fetchQueue); // Manual refresh button click
        setInterval(fetchQueue, 5000);                   // [SPOT-CHANGE]: Auto-poll timer (5000ms = 5 seconds)
    }
});
