<?php

namespace App\Traits;

trait CsvImportHelper
{
    /**
     * Clean CSV row by removing blank columns and trimming whitespace
     * 
     * @param array $row
     * @param array $requiredHeaders
     * @return array
     */
    protected function cleanCsvRow(array $row, array $requiredHeaders): array
    {
        // Trim all values
        $row = array_map('trim', $row);
        
        // If we have fewer columns than required headers, pad with empty strings
        if (count($row) < count($requiredHeaders)) {
            $row = array_pad($row, count($requiredHeaders), '');
        }
        
        // If we have more columns than required headers, truncate to required length
        if (count($row) > count($requiredHeaders)) {
            $row = array_slice($row, 0, count($requiredHeaders));
        }
        
        return $row;
    }
    
    /**
     * Clean CSV header row by trimming whitespace
     * 
     * @param array $header
     * @return array
     */
    protected function cleanCsvHeader(array $header): array
    {
        // Trim all header values
        return array_map('trim', $header);
    }
    
    /**
     * Process CSV file and clean all rows including headers
     * 
     * @param string $filePath
     * @param array $requiredHeaders
     * @return array
     */
    protected function processCsvFile(string $filePath, array $requiredHeaders): array
    {
        $handle = fopen($filePath, 'r');
        $rows = [];
        $headerFound = false;
        
        while (($row = fgetcsv($handle)) !== false) {
            // Skip rows where first column is empty (blank rows)
            if (empty($row[0]) || $row[0] === null || trim($row[0]) === '') {
                continue;
            }
            
            if (!$headerFound) {
                // First non-blank row is the header
                $cleanedHeader = $this->cleanCsvHeader($row);
                
                // Simple validation: check if first few columns look like expected headers
                $isValidHeader = false;
                foreach (array_slice($requiredHeaders, 0, 3) as $expectedHeader) {
                    if (in_array($expectedHeader, $cleanedHeader)) {
                        $isValidHeader = true;
                        break;
                    }
                }
                
                if ($isValidHeader) {
                    $rows[] = $cleanedHeader;
                    $headerFound = true;
                } else {
                    // If this doesn't look like a header, skip it and continue looking
                    continue;
                }
            } else {
                // Subsequent non-blank rows are data
                $cleanedRow = $this->cleanCsvRow($row, $requiredHeaders);
                $rows[] = $cleanedRow;
            }
        }
        
        fclose($handle);
        
        // If no valid header was found, throw an exception
        if (!$headerFound || empty($rows)) {
            throw new \Exception('No valid header row found in CSV file. Expected columns: ' . implode(', ', $requiredHeaders));
        }
        
        return $rows;
    }
}
