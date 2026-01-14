<?php
include '../db.php'; // DB connection

$eng_id = $_POST['eng_id'] ?? 0;
$dol = $_POST['dol'] ?? [];

foreach(['senior','staff'] as $role){
    if(!empty($dol[$role])){
        foreach($dol[$role] as $idx => $assign){
            $soc1 = $assign['soc1'] ?? '';
            $soc2 = $assign['soc2'] ?? '';
            $audit_dols = json_encode(['SOC 1'=>$soc1,'SOC 2'=>$soc2]);

            $sql = "UPDATE engagement_team SET audit_dols=? WHERE eng_id=? AND role=? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sis",$audit_dols,$eng_id,ucfirst($role));
            $stmt->execute();
        }
    }
}

echo json_encode(['success'=>true]);
