<?php
session_start();

// Language handling
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'] === 'ar' ? 'ar' : 'en';
}
$currentLang = $_SESSION['lang'] ?? 'en';

// Authorization check


// Translations
$translations = [
    'en' => [
        'search_by' => 'Search by',
        'user_id' => 'Number',
        'category' => 'Category',
        'date' => 'Date',
        'search_term' => 'Search term   ',
        'select_date' => 'Select date',
        'search' => 'Search',
        'category_distribution' => 'Category Distribution',
        'daily_orders' => 'Daily Orders',
        'export_excel' => 'Export to Excel',
        'close_dashboard' => 'Close Dashboard',
        'no_orders' => 'No orders found',
        'try_filters' => 'Try adjusting your search filters',
        'delete_confirm' => 'Are you sure you want to delete this order?',
        'actions' => 'Actions',
        'amount' => 'Amount',
        'size' => 'Size',
        'specifications' => 'Specifications',
        'order_date' => 'Order Date',
        'original_text' => 'Original Text',
        'delete' => 'Delete',
        'page' => 'Page',
        'approve' => 'Approve'
    ],
    'ar' => [
        'search_by' => 'البحث حسب',
        'user_id' => 'رقم الهاتف',
        'category' => 'الفئة',
        'date' => 'التاريخ',
        'search_term' => 'كلمة البحث',
        'select_date' => 'اختر تاريخًا',
        'search' => 'بحث',
        'category_distribution' => 'توزيع الفئات',
        'daily_orders' => 'الطلبات اليومية',
        'export_excel' => 'تصدير إلى Excel',
        'close_dashboard' => 'إغلاق لوحة التحكم',
        'no_orders' => 'لم يتم العثور على طلبات',
        'try_filters' => 'حاول تعديل فلاتر البحث',
        'delete_confirm' => 'هل أنت متأكد من رغبتك في حذف هذا الطلب؟',
        'actions' => 'الإجراءات',
        'amount' => 'الكمية',
        'size' => 'الحجم',
        'specifications' => 'التخصيصات',
        'order_date' => 'تاريخ الطلب',
        'original_text' => 'النص الأصلي',
        'delete' => 'حذف',
        'page' => 'صفحة',
        'approve' => 'موافقة'
    ]
];




// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ridy');
define('DB_USER', 'root');
define('DB_PASS', 'AliBinMySQL7858@');

// Initialize variables
$result = [];
$error = null;
$searchPerformed = false;
$totalPages = $currentPage = 1;
$perPage = 10;
$categoryData = $dailyOrdersData = [];

try {
    // Database connection
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle delete action
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
        $stmt = $conn->prepare("DELETE FROM orders WHERE user_id = ? AND chat_date = ?");
        $stmt->execute([$_POST['user_id'], $_POST['chat_date']]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Handle search parameters
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
        $_SESSION['search_params'] = [
            'search-type' => $_POST['search-type'],
            'search-input' => $_POST['search-input'] ?? '',
            'date-input' => $_POST['date-input'] ?? ''
        ];
        header("Location: ?page=1");
        exit;
    }

    // Process search parameters
    $searchParams = $_SESSION['search_params'] ?? [];
    $searchType = $searchParams['search-type'] ?? 'user_id';
    $searchValue = $searchParams['search-input'] ?? '';
    $dateValue = $searchParams['date-input'] ?? '';

    // Build base query with filters
    $baseQuery = "SELECT * FROM orders";
    $where = $params = [];

    if (!empty($searchParams)) {
        switch ($searchType) {
            case 'user_id':
                if (!empty($searchValue)) {
                    $where[] = "user_id = ?";
                    $params[] = $searchValue;
                }
                break;
            case 'category':
                if (!empty($searchValue)) {
                    $where[] = "category LIKE ?";
                    $params[] = "%$searchValue%";
                }
                break;
            case 'date':
                if (!empty($dateValue)) {
                    $where[] = "DATE(chat_date) = ?";
                    $params[] = $dateValue;
                }
                break;
        }
    }

    $whereClause = $where ? " WHERE " . implode(" AND ", $where) : "";

    // Handle CSV export
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        $file_name = "Ridy_Orders_Export_" . date("Y-m-d") . ".xls";
        header("Content-Disposition: attachment; filename=" . $file_name);
        echo "\xEF\xBB\xBF"; // BOM to ensure UTF-8 encoding (fixes Arabic display issues)

        echo "<html>";
        echo "<head>";
        echo "<meta charset='UTF-8'>"; // Ensure proper character encoding
        echo "<style> td, th { text-align: left; direction: ltr; font-family: Arial, sans-serif; } </style>";
        echo "</head>";
        echo "<body>";
        echo "<table border='1'>";

        // Table headers (Arabic)
        echo "<tr style='font-weight: bold; background-color: #f2f2f2;'>";
        echo "<th>Category</th><th>Amount</th><th>Size</th><th>User ID</th><th>Order Date</th><th>Original Text</th><th>Specifications</th></tr>";

        // Fetch and output rows
        $stmt = $conn->prepare("$baseQuery $whereClause ORDER BY chat_date DESC");
        $stmt->execute($params);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            foreach ($row as $cell) {
                echo "<td>" . htmlspecialchars($cell) . "</td>";
            }
            echo "</tr>";
        }

        echo "</table>";
        echo "</body>";
        echo "</html>";
        exit;
    }




    // Get chart data
    $stmtCategory = $conn->prepare("SELECT category, COUNT(*) AS count 
                                  FROM orders $whereClause GROUP BY category");
    $stmtCategory->execute($params);
    $categoryData = $stmtCategory->fetchAll(PDO::FETCH_ASSOC);

    $stmtDaily = $conn->prepare("SELECT DATE(chat_date) AS date, COUNT(*) AS count 
                               FROM orders $whereClause 
                               GROUP BY DATE(chat_date) ORDER BY date");
    $stmtDaily->execute($params);
    $dailyOrdersData = $stmtDaily->fetchAll(PDO::FETCH_ASSOC);

    // Pagination calculation
    $stmtCount = $conn->prepare("SELECT COUNT(*) FROM orders $whereClause");
    $stmtCount->execute($params);
    $totalRecords = $stmtCount->fetchColumn();

    $totalPages = max(ceil($totalRecords / $perPage), 1);
    $currentPage = min(max($_GET['page'] ?? 1, 1), $totalPages);
    $offset = ($currentPage - 1) * $perPage;

    // Get paginated results
    $query = "$baseQuery $whereClause ORDER BY chat_date DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);

    // Bind all parameters properly
    $paramIndex = 1;
    foreach ($params as $param) {
        $stmt->bindValue($paramIndex++, $param);
    }

    // Explicitly bind LIMIT and OFFSET as integers
    $stmt->bindValue($paramIndex++, (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex++, (int)$offset, PDO::PARAM_INT);

    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Safe chart calculations
$maxCategory = $categoryData ? max(array_column($categoryData, 'count')) : 1;
$dailyDates = array_column($dailyOrdersData, 'date');
$dailyCounts = array_column($dailyOrdersData, 'count');
$maxDaily = $dailyCounts ? max($dailyCounts) : 1;
$totalDays = count($dailyDates);
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="emb.png" type="image/icon type">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@100..900&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    <title>Ridy Dashboard</title>
    <style>
        :root {
            --font-family: <?= $currentLang === 'ar' ? '"Tajawal", sans-serif' : '"Noto Sans Arabic", sans-serif' ?>;
            --primary: #406692;
            --background: rgb(255, 255, 255);
            --surface: #FFFFFF;
            --border: #E2E8F0;
            --text: #1E293B;
            --secondary-text: #64748B;
        }

        [data-theme="dark"] {
            --primary: rgb(98, 138, 184);
            --background: #0F172A;
            --surface: #1E293B;
            --border: #334155;
            --text: #F8FAFC;
            --secondary-text: #94A3B8;
        }

        /* RTL specific styles */
        [dir="rtl"] .form-group {
            text-align: right;
        }

        [dir="rtl"] .orders-table th,
        [dir="rtl"] .orders-table td {
            text-align: right;
        }

        [dir="rtl"] .search-form {
            direction: rtl;
        }

        .language-toggle {
            width: auto;
            padding: 0.75em;
            gap: 0.5em;
        }


        .flag-icon {
            font-size: 1.2em;
            font-family: var(--font-family);
            transition: all 0.2s ease;
            opacity: 1;
            display: inline-block;
        }

        .flag-icon.inactive {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }

        .flag-icon.active {
            opacity: 1;
            visibility: visible;
        }

        .language-toggle:hover .flag-icon.active {
            transform: translate(-50%, -50%) scale(1.15);
        }

        .flag-icon.inactive {
            opacity: 0.5;
        }

        body {
            font-family: var(--font-family);
            background: linear-gradient(45deg, #0a0e24, #1a1f4d);
            color: var(--text);
            margin: 0;
            min-height: 100vh;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo-container {
            background: var(--surface);
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .logo {
            width: 280px;
            transition: transform 0.2s ease;
            display: block;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .theme-toggle {
            /* Applies to both buttons now */
            background: var(--primary);
            color: white;
            padding: 0.75em 1em;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 42px;
            height: 42px;
        }

        .theme-toggle:hover {
            transform: translateY(-1px);
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
        }

        .theme-toggle:hover .flag-icon.active {
            transform: translateY(-1px);
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
            opacity: 1;
            width: auto;
        }

        .search-panel {
            background: var(--surface);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        select,
        input {
            padding: 0.875rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: var(--surface);
            color: var(--text);
        }

        select:focus,
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .btn {
            background: var(--primary);
            color: white;
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: var(--font-family);

        }

        .btn:hover {
            background: rgb(55, 89, 127);
            transform: translateY(-1px);
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--surface);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .orders-table th,
        .orders-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .orders-table th {
            background: var(--background);
            font-weight: 600;
            color: var(--secondary-text);
        }

        .orders-table tr:hover {
            background: var(--background);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--surface);
            border-radius: 12px;
            color: var(--secondary-text);
        }

        .error-state {
            background: #FEF2F2;
            color: #B91C1C;
            padding: 2rem;
            border-radius: 8px;
            margin: 2rem 0;
        }

        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        .pagination {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .pagination a {
            padding: 0.5rem 1rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .pagination a:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination .active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .delete-btn {
            background: #dc3545;
            padding: 0.5rem 1rem;
            font-family: var(--font-family);

        }

        .delete-btn:hover {
            background: #bb2d3b;
        }

        .approve-btn {
            background: rgb(85 137 107);

            padding: 0.5rem 1rem;
            font-family: var(--font-family);
            display: inline-flex;
            margin-left: 1rem;

        }

        .approve-btn:hover {
            background: rgb(85 137 107);

        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .chart-card {
            background: var(--surface);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .bar-chart {
            height: 300px;
            display: flex;
            align-items: flex-end;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 2px solid var(--border);
        }

        .bar-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
            position: relative;
            /* Ensures absolute positioning works inside */
        }

        .bar {
            width: 95%;
            background: var(--primary);
            transition: height 0.3s ease;
            position: relative;
            cursor: pointer;
        }

        /* Tooltip styles */
        .bar::after {
            content: attr(title);
            position: absolute;
            top: -1.5rem;
            /* Adjusted positioning */
            left: 50%;
            transform: translateX(-50%);
            background: var(--surface);
            color: var(--text);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            white-space: nowrap;
            font-size: 0.9rem;
            z-index: 4;
            opacity: 0;
            /* Initially hidden */
            transition: opacity 0.2s ease-in-out;
            pointer-events: none;
            /* Prevents flickering */
        }

        /* Show tooltip on hover or when active */
        .bar:hover::after,
        .bar.active::after {
            opacity: 1;
        }


        .bar-label {
            text-align: center;
            font-size: 0.8rem;
            color: var(--text);
            margin-top: 0.5rem;
            word-break: keep-all;
            max-width: 100%;
            padding: 0 2px;
        }

        .line-chart {
            height: 300px;
            position: relative;
            border-bottom: 2px solid var(--border);
            border-left: 2px solid var(--border);
            margin: 20px;
        }

        .data-point {
            position: absolute;
            width: 20px;
            height: 20px;
            background: var(--primary);
            border-radius: 50%;
            transform: translate(-50%, 50%);
            cursor: pointer;
        }

        .data-point:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--surface);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            white-space: nowrap;
        }



        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header {
                flex-direction: column;
                text-align: center;
            }

            .logo-container {
                padding: 0.5rem;
            }

            .logo {
                width: 240px;
            }

            .search-form {
                grid-template-columns: 1fr;
            }

            .orders-table {
                display: block;
                overflow-x: auto;
            }

            .action-buttons {
                flex-direction: column;
                gap: 1rem;
            }

            .charts-container {
                grid-template-columns: 1fr;
            }

            .bar-chart {
                gap: 0.5rem;
                padding: 1rem 0;
            }

            .bar-label {
                font-size: 0.7rem;
            }
        }

        .x-label {
            position: absolute;
            transform: translateX(-50%);
            font-size: 0.75rem;
            color: var(--secondary-text);
            white-space: nowrap;
            transform-origin: top center;
        }

        .chart-card {
            background: var(--surface);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .chart-container {
            position: relative;
            padding: 1rem 2rem 2rem 3rem;
            margin: 20px 0;
        }

        .y-axis {
            position: absolute;
            left: 0;
            top: 1rem;
            bottom: 2rem;
            width: 3rem;
        }

        .y-label {
            position: absolute;
            right: 1rem;
            transform: translateY(-50%);
            font-size: 0.75rem;
            color: var(--secondary-text);
        }

        .x-axis {
            position: absolute;
            left: 3rem;
            right: 2rem;
            bottom: 0;
            height: 2rem;
        }

        .x-label {
            position: absolute;
            transform: translateX(-50%) rotate(-45deg);
            font-size: 0.75rem;
            color: var(--secondary-text);
            white-space: nowrap;
            transform-origin: top center;
        }

        .line-chart {
            position: relative;
            height: 300px;
            margin-left: 1rem;
            background: var(--background);
            border-radius: 8px;
            border-bottom: 2px solid var(--border);
            border-left: 2px solid var(--border);
        }

        .grid-lines {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .grid-line {
            stroke: var(--border);
            stroke-width: 1;
            opacity: 0.5;
        }

        .chart-lines {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: 2;
        }

        .chart-lines polyline {
            fill: none;
            stroke: var(--primary);
            stroke-width: 2;
            opacity: 0.8;
        }

        .data-point {
            position: absolute;
            width: 12px;
            height: 12px;
            background: var(--primary);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.2s ease;
            z-index: 3;
            cursor: pointer;
        }

        /* Apply effect on hover or when clicked */
        .data-point:hover,
        .data-point.active {
            transform: translate(-50%, -50%) scale(1.5);
        }

        /* Tooltip styles */
        .data-point:hover::after,
        .data-point.active::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--surface);
            color: var(--text);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            white-space: nowrap;
            z-index: 4;
            font-size: 0.6rem;
        }


        .no-data {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            color: var(--secondary-text);
        }

        @media (max-width: 768px) {
            .chart-card {
                padding: 1rem;
            }

            .chart-container {
                padding: 1rem 1rem 2rem 2rem;
            }

            .line-chart {
                height: 200px;
            }

            .data-point {
                width: 10px;
                height: 10px;
            }

            .y-label,
            .x-label {
                font-size: 0.7rem;
            }

            .data-point:hover::after {
                font-size: 0.65rem;
                /* Even smaller on mobile */
            }
        }

        /* From Uiverse.io by adamgiebl */
        .container2 {
            background-color: #676767;
            opacity: 1;
            background-image: radial-gradient(circle at center center, #000000, #676767), repeating-radial-gradient(circle at center center, #000000, #000000, 11px, transparent 22px, transparent 11px);
            background-blend-mode: multiply;
        }
    </style>
</head>

<body dir="<?= $currentLang === 'ar' ? 'rtl' : 'ltr' ?>">
    <!-- From Uiverse.io by kennyotsu-monochromia -->
    <div class="container2">
        <div class="container">
            <div class="header">
                <div class="logo-container">
                    <a href="https://t.me/RIDYridyBOT" target="_blank">
                        <img src="logo.png" alt="Ridy Logo" class="logo">
                    </a>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button class="theme-toggle" onclick="toggleLanguage()" aria-label="Toggle language">
                        <span class="flag-icon <?= $currentLang === 'ar' ? 'active' : 'inactive' ?>">🇺🇸</span>
                        <span class="flag-icon <?= $currentLang === 'en' ? 'active' : 'inactive' ?>">🇸🇦</span>
                    </button>
                    <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle theme">
                        <span id="theme-icon">🌙</span>
                    </button>
                </div>
            </div>

            <div class="search-panel">
                <form method="post" class="search-form">
                    <div class="form-group">
                        <label><?= $translations[$currentLang]['search_by'] ?></label>
                        <select id="search-type" name="search-type" class="search-select" onchange="updateSearchField()">
                            <option value="user_id" <?= ($searchType ?? 'user_id') === 'user_id' ? 'selected' : '' ?>><?= $translations[$currentLang]['user_id'] ?></option>
                            <option value="category" <?= ($searchType ?? '') === 'category' ? 'selected' : '' ?>><?= $translations[$currentLang]['category'] ?></option>
                            <option value="date" <?= ($searchType ?? '') === 'date' ? 'selected' : '' ?>><?= $translations[$currentLang]['date'] ?></option>
                        </select>
                    </div>

                    <div class="form-group" id="text-search-field">
                        <label><?= $translations[$currentLang]['search_term'] ?></label>
                        <input type="text" id="search-input" name="search-input"
                            value="<?= htmlspecialchars($searchValue ?? '') ?>"
                            placeholder="<?= $translations[$currentLang]['search_term'] ?>">
                    </div>

                    <div class="form-group" id="date-search-field" style="display: none;">
                        <label><?= $translations[$currentLang]['select_date'] ?></label>
                        <input type="date" id="date-input" name="date-input"
                            value="<?= htmlspecialchars($dateValue ?? '') ?>">
                    </div>

                    <button type="submit" name="search" class="btn"><?= $translations[$currentLang]['search'] ?></button>
                </form>
            </div>

            <?php if ($error): ?>
                <div class="error-state">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php elseif (!empty($result)): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th><?= $translations[$currentLang]['category'] ?></th>
                            <th><?= $translations[$currentLang]['amount'] ?></th>
                            <th><?= $translations[$currentLang]['size'] ?></th>
                            <th><?= $translations[$currentLang]['specifications'] ?></th>
                            <th><?= $translations[$currentLang]['user_id'] ?></th>
                            <th><?= $translations[$currentLang]['order_date'] ?></th>
                            <th><?= $translations[$currentLang]['original_text'] ?></th>
                            <th><?= $translations[$currentLang]['actions'] ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row["category"]) ?></td>
                                <td><?= htmlspecialchars($row["amount"]) ?></td>
                                <td><?= htmlspecialchars($row["order_size"]) ?></td>
                                <td><?= htmlspecialchars($row["specifications"]) ?></td>
                                <td><?= htmlspecialchars($row["user_id"]) ?></td>
                                <td><?= htmlspecialchars($row["chat_date"]) ?></td>
                                <td><?= htmlspecialchars($row["order_info"]) ?></td>
                                <td style="display: flex; gap: 10px; align-items: center; border: none;">
    <form method="post" onsubmit="return confirm('<?= $translations[$currentLang]['delete_confirm'] ?>');">
        <input type="hidden" name="user_id" value="<?= htmlspecialchars($row['user_id']) ?>">
        <input type="hidden" name="chat_date" value="<?= htmlspecialchars($row['chat_date']) ?>">
        <button type="submit" name="delete" class="btn delete-btn"><?= $translations[$currentLang]['delete'] ?></button>
    </form>

    <button type="button" onclick="approveOrder('<?= $row['user_id'] ?>')" 
            class="btn approve-btn">
        <?= $translations[$currentLang]['approve'] ?>
    </button>
</td>
                                    <?php endforeach; ?>
                    </tbody>
                </table>
                
                <script>
    function approveOrder(userId) {
        // Validate user ID before sending
        if (!userId || !/^\d+$/.test(userId)) {
            alert("Invalid User ID");
            return;
        }

        fetch(`http://localhost:8000/approve-order?user_id=${encodeURIComponent(userId)}`)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert(data.message);
                } else {
                    alert(data.error || "Unknown error occurred");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(`فشل الإرسال: ${error.message}`);
            });
    }
</script>
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?= $i ?>&lang=<?= $currentLang ?>" class="<?= $i == $currentPage ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

                <?php
                // Sort category data by count in descending order
                usort($categoryData, function ($a, $b) {
                    return $b['count'] - $a['count'];
                });

                // Get only the top 8 categories
                $topCategories = array_slice($categoryData, 0, 8);
                ?>

                <div class="charts-container">
                    <div class="chart-card">
                        <h3><?= $translations[$currentLang]['category_distribution'] ?></h3>
                        <div class="bar-chart">
                            <?php foreach ($topCategories as $category): ?>
                                <div class="bar-container">
                                    <div class="bar"
                                        style="height: <?= ($category['count'] / $maxCategory) * 80 ?>%;"
                                        title="<?= htmlspecialchars($category['category']) ?>: <?= $category['count'] ?>">
                                    </div>
                                    <div class="bar-label">
                                        <?= htmlspecialchars(substr($category['category'], 0, 25)) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>



                    <div class="chart-card">
                        <h3><?= $translations[$currentLang]['daily_orders'] ?></h3>
                        <div class="chart-container">
                            <!-- Y-axis labels -->
                            <div class="y-axis">
                                <?php
                                $steps = 5;
                                for ($i = 0; $i <= $steps; $i++) {
                                    $value = round(($maxDaily / $steps) * ($steps - $i));
                                    echo "<div class='y-label' style='top: " . ($i * (100 / $steps)) . "%'>{$value}</div>";
                                }
                                ?>
                            </div>

                            <div class="line-chart">
                                <!-- Grid lines -->
                                <svg class="grid-lines" preserveAspectRatio="none" viewBox="0 0 100 100">
                                    <?php for ($i = 0; $i <= $steps; $i++): ?>
                                        <line
                                            x1="0"
                                            y1="<?= $i * (100 / $steps) ?>"
                                            x2="100"
                                            y2="<?= $i * (100 / $steps) ?>"
                                            class="grid-line" />
                                    <?php endfor; ?>
                                </svg>

                                <?php
                                $last7Days = array_slice($dailyDates, -7);
                                $last7Counts = array_slice($dailyCounts, -7);
                                $totalDays = count($last7Days);
                                ?>

                                <?php if ($totalDays > 0): ?>
                                    <!-- Data line -->
                                    <svg class="chart-lines" preserveAspectRatio="none" viewBox="0 0 100 100">
                                        <polyline points="
                        <?php
                                    for ($i = 0; $i < $totalDays; $i++) {
                                        $x = ($i / max($totalDays - 1, 1)) * 100;
                                        $y = ($last7Counts[$i] / $maxDaily) * 100;
                                        echo "$x," . (100 - $y) . " ";
                                    }
                        ?>" />
                                    </svg>

                                    <?php for ($i = 0; $i < $totalDays; $i++): ?>
                                        <div class="data-point"
                                            style="left: <?= ($i / max($totalDays - 1, 1)) * 100 ?>%; 
                                top: <?= (100 - ($last7Counts[$i] / $maxDaily) * 100) ?>%;"
                                            title="<?= htmlspecialchars($last7Days[$i]) ?>: <?= $last7Counts[$i] ?>">
                                        </div>
                                    <?php endfor; ?>

                                <?php else: ?>
                                    <div class="no-data"><?= $translations[$currentLang]['no_orders'] ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- X-axis labels -->
                            <div class="x-axis">
                                <?php
                                for ($i = 0; $i < $totalDays; $i++):
                                ?>
                                    <div class="x-label" style="left: <?= ($i / max($totalDays - 1, 1)) * 100 ?>%">
                                        <?= date('d/m', strtotime($last7Days[$i])) ?>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>



                </div>


                <div class="action-buttons">
                    <form method="post">
                        <button type="submit" name="export" class="btn"><?= $translations[$currentLang]['export_excel'] ?></button>
                    </form>
                    <button class="btn" onclick="window.location.href='index.php'">
                        <?= $translations[$currentLang]['close_dashboard'] ?>
                    </button>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3><?= $translations[$currentLang]['no_orders'] ?></h3>
                    <?php if ($searchPerformed): ?>
                        <p><?= $translations[$currentLang]['try_filters'] ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>

    <script>
        document.querySelectorAll('.data-point, .bar').forEach(element => {
            element.addEventListener('click', function() {
                // Remove active class from all elements (optional, if only one should be active at a time)
                document.querySelectorAll('.data-point, .bar').forEach(el => el.classList.remove('active'));

                // Add 'active' class to the clicked element
                this.classList.add('active');
            });

            // Optional: Remove active class on outside click
            document.addEventListener('click', (event) => {
                if (!event.target.classList.contains('data-point') && !event.target.classList.contains('bar')) {
                    document.querySelectorAll('.data-point, .bar').forEach(el => el.classList.remove('active'));
                }
            });
        });


        function toggleLanguage() {
            const currentLang = '<?= $currentLang ?>';
            const newLang = currentLang === 'en' ? 'ar' : 'en';
            const currentPage = <?= $currentPage ?? 1 ?>;
            window.location.href = `?lang=${newLang}&page=${currentPage}`;
        }

        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('theme-icon');

            if (body.getAttribute('data-theme') === 'dark') {
                body.removeAttribute('data-theme');
                themeIcon.textContent = '🌙';
                localStorage.setItem('theme', 'light');
            } else {
                body.setAttribute('data-theme', 'dark');
                themeIcon.textContent = '☀️';
                localStorage.setItem('theme', 'dark');
            }
        }

        function updateSearchField() {
            const searchType = document.getElementById('search-type').value;
            document.getElementById('text-search-field').style.display = 'flex';
            document.getElementById('date-search-field').style.display = 'none';

            if (searchType === 'date') {
                document.getElementById('text-search-field').style.display = 'none';
                document.getElementById('date-search-field').style.display = 'flex';
            }
        }

        // Initialize theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') {
            document.body.setAttribute('data-theme', 'dark');
            document.getElementById('theme-icon').textContent = '☀️';
        }

        // Initialize search fields
        updateSearchField();
    </script>
</body>

</html>