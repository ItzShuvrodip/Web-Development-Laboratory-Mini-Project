// ====================================================================
// js/main.js - Client-Side Interactivity using jQuery
// Features: Category filtering, Quantity validation, Live Queue Polling
// ====================================================================

// Ensure DOM is fully loaded before executing jQuery code
$(document).ready(function () {
    console.log("Canteen Pre-Order & Queue System initialized.");

    // ----------------------------------------------------------------
    // 1. Menu Category Filtering (index.php)
    // ----------------------------------------------------------------
    $(".filter-btn").on("click", function () {
        // Remove 'active' class from all buttons and add it to the clicked button
        $(".filter-btn").removeClass("active");
        $(this).addClass("active");

        // Get selected category
        var selectedCategory = $(this).data("category");

        // If 'all', show all food cards
        if (selectedCategory === "all") {
            $(".menu-card").fadeIn(200);
        } else {
            // Hide all cards first, then fade in cards matching the selected category
            $(".menu-card").hide();
            $(".menu-card[data-category='" + selectedCategory + "']").fadeIn(200);
        }
    });

    // ----------------------------------------------------------------
    // 2. Client-Side Quantity Protection
    // ----------------------------------------------------------------
    $("input[name='quantity']").on("change keyup", function () {
        var val = parseInt($(this).val(), 10);
        if (isNaN(val) || val < 1) {
            $(this).val(1);
        } else if (val > 20) {
            $(this).val(20);
        }
    });

    // ----------------------------------------------------------------
    // 3. Live Queue Auto-Refresh (queue.php)
    // ----------------------------------------------------------------
    // Check if we are on the queue page by checking for the refresh button
    if ($("#btn-refresh-queue").length > 0) {
        
        // Function to fetch updated order status from queue_data.php via jQuery AJAX
        function fetchQueueUpdates() {
            // Preserve active search filter if present
            var activeSearch = $("#btn-refresh-queue").data("search") || $("#search").val() || "";
            var endpointUrl = "queue_data.php";
            if (activeSearch.trim() !== "") {
                endpointUrl += "?search=" + encodeURIComponent(activeSearch.trim());
            }

            $.ajax({
                url: endpointUrl,
                type: "GET",
                dataType: "json",
                cache: false,
                success: function (response) {
                    if (response.status === "success") {
                        updateQueueUI(response.orders);
                        var now = new Date();
                        var timeStr = now.toLocaleTimeString();
                        $("#queue-last-updated").text("Last updated: " + timeStr);
                    }
                },
                error: function (xhr, status, error) {
                    console.warn("Queue polling error: " + error);
                }
            });
        }

        // Helper function to update DOM elements with fresh order data
        function updateQueueUI(orders) {
            var receivedHtml = "";
            var preparingHtml = "";
            var readyHtml = "";
            var tableRowsHtml = "";

            var receivedCount = 0;
            var prepCount = 0;
            var readyCount = 0;

            // Iterate through received orders
            $.each(orders, function (index, ord) {
                var st = (ord.order_status || "").toLowerCase();

                // Build Board Card according to status theme
                var cardBg = "#e7f5ff";
                var cardBorder = "#a5d8ff";
                var tokenColor = "#1864ab";
                var statusLabel = escapeHtml(ord.order_status);

                if (st === "ready") {
                    cardBg = "#d3f9d8";
                    cardBorder = "#b2f2bb";
                    tokenColor = "#1b5e20";
                    statusLabel = "READY";
                } else if (st === "preparing") {
                    cardBg = "#fff9db";
                    cardBorder = "#ffe066";
                    tokenColor = "#8a6100";
                    statusLabel = "Cooking";
                } else if (st === "received") {
                    cardBg = "#e7f5ff";
                    cardBorder = "#a5d8ff";
                    tokenColor = "#1864ab";
                    statusLabel = "Queued";
                }

                var cardHtml = '<div style="background-color: ' + cardBg + '; border: 1px solid ' + cardBorder + '; border-radius: 10px; padding: 12px 14px; display: flex; justify-content: space-between; align-items: center;">' +
                    '<div>' +
                        '<strong style="font-size: 1.2rem; color: ' + tokenColor + '; display: block; letter-spacing: 1px;">' + escapeHtml(ord.order_number) + '</strong>' +
                        '<span style="font-size: 0.85rem; color: #2d2218;">' + escapeHtml(ord.customer_name) + '</span>' +
                    '</div>' +
                    '<span class="status-pill status-' + st + '">' + statusLabel + '</span>' +
                '</div>';

                if (st === "ready") {
                    readyHtml += cardHtml;
                    readyCount++;
                } else if (st === "preparing") {
                    preparingHtml += cardHtml;
                    prepCount++;
                } else if (st === "received") {
                    receivedHtml += cardHtml;
                    receivedCount++;
                }

                // Build Table row
                tableRowsHtml += '<tr>' +
                    '<td><strong>' + escapeHtml(ord.order_number) + '</strong></td>' +
                    '<td>' + escapeHtml(ord.customer_name) + '</td>' +
                    '<td>' + escapeHtml(ord.created_at) + '</td>' +
                    '<td>₹' + parseFloat(ord.total_amount).toFixed(2) + '</td>' +
                    '<td><span class="status-pill status-' + st + '">' + escapeHtml(ord.order_status) + '</span></td>' +
                '</tr>';
            });

            // Update Counts
            $("#ready-count").text(readyCount);
            $("#prep-count").text(prepCount);
            $("#received-count").text(receivedCount);

            // Update Lists (show empty placeholder if count is 0)
            if (readyCount > 0) {
                $("#ready-orders-list").html(readyHtml);
            } else {
                $("#ready-orders-list").html('<div class="empty-col-msg">No orders currently waiting at counter</div>');
            }

            if (prepCount > 0) {
                $("#prep-orders-list").html(preparingHtml);
            } else {
                $("#prep-orders-list").html('<div class="empty-col-msg">No orders currently being prepared</div>');
            }

            if (receivedCount > 0) {
                $("#received-orders-list").html(receivedHtml);
            } else {
                $("#received-orders-list").html('<div class="empty-col-msg">No orders in queue</div>');
            }

            // Update Table body
            if (orders.length > 0) {
                $("#all-orders-tbody").html(tableRowsHtml);
            } else {
                $("#all-orders-tbody").html('<tr><td colspan="5" style="text-align: center; color: #9ca3af; padding: 20px;">No orders found matching criteria.</td></tr>');
            }
        }

        // Helper to escape HTML characters safely in JavaScript
        function escapeHtml(text) {
            if (!text) return "";
            return $("<div>").text(text).html();
        }

        // Attach manual click event to "Refresh Now" button
        $("#btn-refresh-queue").on("click", function () {
            $(this).text("Refreshing...");
            fetchQueueUpdates();
            var btn = $(this);
            setTimeout(function () {
                btn.text("🔄 Refresh Now");
            }, 500);
        });

        // Poll every 5000 milliseconds (5 seconds)
        var queueInterval = setInterval(fetchQueueUpdates, 5000);
    }
});
