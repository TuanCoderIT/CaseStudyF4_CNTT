<?php
// Include database connection
require_once './config/db.php';

// Output function for results
function printResults($query_name, $result)
{
    echo "=== TEST: $query_name ===\n";

    if (!$result) {
        echo "ERROR: " . $conn->error . "\n";
        return;
    }

    if ($result->num_rows === 0) {
        echo "No results found\n";
        return;
    }

    echo "Found " . $result->num_rows . " rows\n";

    // Print first row as sample
    $row = $result->fetch_assoc();
    echo "Sample row: " . json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
}

// Test 1: get_top_rooms
echo "Testing get_top_rooms query...\n";
$limit = 3;
$query = "SELECT m.*, d.name as district_name 
         FROM motel m 
         JOIN districts d ON m.district_id = d.id 
         WHERE m.approve = 1 AND m.isExist = 1 
         ORDER BY m.count_view DESC 
         LIMIT ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $limit);
$stmt->execute();
$result = $stmt->get_result();
printResults("get_top_rooms", $result);

// Test 2: get_cheap_rooms
echo "Testing get_cheap_rooms query...\n";
$maxPrice = 2000000; // 2 million VND
$query = "SELECT m.*, d.name as district_name 
         FROM motel m 
         LEFT JOIN districts d ON m.district_id = d.id 
         WHERE m.approve = 1 AND m.isExist = 1 AND m.price <= ?
         ORDER BY m.price ASC 
         LIMIT 5";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $maxPrice);
$stmt->execute();
$result = $stmt->get_result();
printResults("get_cheap_rooms", $result);

// Test 3: get_rooms_by_utilities - potential issue
echo "Testing get_rooms_by_utilities query (original)...\n";
$utility = "wifi";
$query = "SELECT m.*, d.name as district_name 
         FROM motel m 
         LEFT JOIN districts d ON m.district_id = d.id 
         WHERE m.approve = 1 AND m.isExist = 1 AND m.utilities LIKE ?
         ORDER BY m.created_at DESC 
         LIMIT 5";

$searchPattern = '%' . $utility . '%';
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $searchPattern);
$stmt->execute();
$result = $stmt->get_result();
printResults("get_rooms_by_utilities (original)", $result);

// Test 4: get_most_favorited_rooms
echo "Testing get_most_favorited_rooms query...\n";
$query = "SELECT m.*, d.name as district_name, COUNT(w.id) as favorite_count
         FROM motel m 
         LEFT JOIN districts d ON m.district_id = d.id 
         LEFT JOIN user_wishlist w ON m.id = w.motel_id
         WHERE m.approve = 1 AND m.isExist = 1
         GROUP BY m.id
         ORDER BY favorite_count DESC, m.wishlist DESC
         LIMIT 5";

$result = $conn->query($query);
printResults("get_most_favorited_rooms", $result);

// Test 5: NULL handling for isExist
echo "Testing isExist NULL handling...\n";
// Current query
$query1 = "SELECT COUNT(*) as count FROM motel WHERE approve = 1 AND isExist = 1";
$result1 = $conn->query($query1);
$row1 = $result1->fetch_assoc();

// Alternative query including NULLs
$query2 = "SELECT COUNT(*) as count FROM motel WHERE approve = 1 AND (isExist = 1 OR isExist IS NULL)";
$result2 = $conn->query($query2);
$row2 = $result2->fetch_assoc();

echo "Rooms with isExist = 1: " . $row1['count'] . "\n";
echo "Rooms with isExist = 1 OR NULL: " . $row2['count'] . "\n";

// Test 6: Check if utilities field is comma-separated as expected
echo "Testing utilities field format...\n";
$query = "SELECT id, title, utilities FROM motel WHERE utilities IS NOT NULL LIMIT 3";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Room {$row['id']} ({$row['title']}): Utilities = {$row['utilities']}\n";
    }
} else {
    echo "No rooms with utilities found\n";
}

// Test 7: Improved utilities search
echo "Testing improved utilities search...\n";
$utility = "Wifi";
$query = "SELECT m.*, d.name as district_name 
         FROM motel m 
         LEFT JOIN districts d ON m.district_id = d.id 
         WHERE m.approve = 1 AND m.isExist = 1 AND 
         (m.utilities LIKE ? OR m.utilities LIKE ? OR m.utilities LIKE ? OR m.utilities = ?)
         ORDER BY m.created_at DESC 
         LIMIT 5";

$pattern1 = $utility . ',%';  // At start
$pattern2 = '%,' . $utility . ',%';  // In middle
$pattern3 = '%,' . $utility;  // At end
$pattern4 = $utility;  // Exactly matches

$stmt = $conn->prepare($query);
$stmt->bind_param("ssss", $pattern1, $pattern2, $pattern3, $pattern4);
$stmt->execute();
$result = $stmt->get_result();
printResults("get_rooms_by_utilities (improved)", $result);

// Close connection
$conn->close();
echo "Tests completed.\n";
