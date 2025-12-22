<?php
// UML: Report class (Model)
class ReportModel {
    private $id;
    private $type;
    private $data;
    private $generatedBy;
    private $createdAt;
    
    public function __construct($id, $type, $data, $generatedBy, $createdAt) {
        $this->id = $id;
        $this->type = $type;
        $this->data = $data;
        $this->generatedBy = $generatedBy;
        $this->createdAt = $createdAt;
    }
    
    // UML: +generate()
    public static function generate($pdo, $type, $data, $generatedBy) {
        $jsonData = json_encode($data);
        $sql = "INSERT INTO reports (type, data, generatedBy) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$type, $jsonData, $generatedBy])) {
            return $pdo->lastInsertId();
        }
        return false;
    }
    
    // UML: +exportPDF()
    public function exportPDF() {
        // In a real implementation, this would use a PDF library like TCPDF or FPDF
        // For now, return a placeholder
        $filename = "report_{$this->id}_{$this->type}.pdf";
        // PDF generation would happen here
        return $filename;
    }
    
    // UML: +exportCSV()
    public function exportCSV() {
        $filename = "report_{$this->id}_{$this->type}.csv";
        $data = is_string($this->data) ? json_decode($this->data, true) : $this->data;
        
        if (is_array($data)) {
            $fp = fopen($filename, 'w');
            if ($fp) {
                // Write headers if data is associative array
                if (!empty($data) && is_array($data[0] ?? null)) {
                    fputcsv($fp, array_keys($data[0]));
                }
                // Write data
                foreach ($data as $row) {
                    if (is_array($row)) {
                        fputcsv($fp, $row);
                    }
                }
                fclose($fp);
                return $filename;
            }
        }
        return false;
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getType() { return $this->type; }
    public function getData() { return $this->data; }
    public function getGeneratedBy() { return $this->generatedBy; }
    public function getCreatedAt() { return $this->createdAt; }
}
?>

