<?php
header('Content-Type: application/json; charset=utf-8');

$data_file = 'form_submissions.json';
$submissions_by_id = []; 

if (file_exists($data_file)) {
    $json_data = file_get_contents($data_file);
    $submissions_by_id = json_decode($json_data, true);
    if ($submissions_by_id === null) {
        error_log("Error decoding form_submissions.json in get_submissions.php. File might be corrupted.");
        echo json_encode(['error' => 'Failed to decode data']);
        exit();
    }
}

$formatted_submissions = [];
foreach ($submissions_by_id as $id_number => $entries) {
    $sorted_entries = $entries; 
    usort($sorted_entries, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']); 
    });

    $latest_entry = reset($sorted_entries); 
    
    $formatted_submissions[] = [
        'id_number' => $id_number,
        'owner_name' => $latest_entry['owner_name'] ?? 'اسم غير معروف',
        'phone' => $latest_entry['phone'] ?? 'غير متوفر',
        'status' => $latest_entry['status'] ?? 'pending', 
        'entries' => $sorted_entries 
    ];
}

usort($formatted_submissions, function($a, $b) {
    $time_a = strtotime($a['entries'][0]['timestamp'] ?? '1970-01-01');
    $time_b = strtotime($b['entries'][0]['timestamp'] ?? '1970-01-01');
    return $time_b - $time_a; 
});


echo json_encode($formatted_submissions);
?>