<?php

namespace Drupal\zinco_etl\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Database\Connection;

/**
 * Service for generating PDF files from HTML content using Dompdf.
 */
class PdfGeneratorService {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new PdfGeneratorService object.
   *
   * @param Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory, Connection $database) {
    $this->logger = $logger_factory;
    $this->database = $database;
  }

  /**
   * Generates a PDF from HTML content.
   *
   * @param string $html
   *   The HTML content to convert to PDF.
   * @param string $filename
   *   The desired filename for the PDF.
   *
   * @return string|false
   *   The binary content of the PDF file, or FALSE on failure.
   */
  public function generatePdfFromHtml(string $html, string $filename = 'document.pdf') {
    try {
      $options = new Options();
      $options->set('isHtml5ParserEnabled', TRUE);
      $options->set('isRemoteEnabled', TRUE);
      $dompdf = new Dompdf($options);

      $dompdf->loadHtml($html);
      $dompdf->setPaper('A4', 'portrait');
      $dompdf->render();

      return $dompdf->output();
    }
    catch (\Exception $e) {
      $this->logger->error('Error generating PDF: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Generates a PDF from table data.
   *
   * @param array $headers
   *   An array of table headers.
   * @param array $rows
   *   An array of table rows, where each row is an array of cell data.
   * @param string $title
   *   The title for the PDF document.
   * @param string $filename
   *   The desired filename for the PDF.
   *
   * @return string|false
   *   The binary content of the PDF file, or FALSE on failure.
   */
  public function generatePdfFromTableData(array $headers, array $rows, string $title = 'Table Data', string $filename = 'table_data.pdf') {
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>' . $title . '</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif;  }
        table { width: 300px; border-collapse: collapse; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>' . $title . '</h1>
    <table>
        <thead>
            <tr>';
    foreach ($headers as $header) {
      $html .= '<th>' . $header . '</th>';
     
    
    }
    $html .= '</tr>
        </thead>
        <tbody>';
    foreach ($rows as $row) {
      $html .= '<tr>';
      foreach ($row as $cell) {
        $html .= '<td>' . $cell . '</td>';
      }
      $html .= '</tr>';
    }
    $html .= '</tbody>
    </table>
</body>
</html>';

    return $this->generatePdfFromHtml($html, $filename);
  }

   /**
     * Generates a PDF from a database table.
     *
     * @param string $tableName
     *   The name of the database table.
     * @param string $title
     *   The title for the PDF document.
     * @param string $filename
     *   The desired filename for the PDF.
     *
     * @return string|false
     *   The binary content of the PDF file, or FALSE on failure.
     */
    public function generatePdfFromDatabaseTable(string $tableName, string $title = 'Table Data', string $filename = 'table_data.pdf') {
        try {
            $query = $this->database->select($tableName, 't');
            $query->fields('t');
            $result = $query->execute();

            $headers = [];
            $rows = [];

            // Fetch headers from the first row if available.
            $firstRow = $result->fetchAssoc();
            if ($firstRow) {
            $headers = array_keys($firstRow);
            // Reset the result set to include the first row in the data.
            $result = $query->execute();
            }

            foreach ($result as $row) {
            $rows[] = array_values((array) $row);
            }

            if (empty($headers) && !empty($rows)) {
            // If no headers were fetched (e.g., empty table initially),
            // try to get them from the first row of data.
            $headers = array_keys($rows[0]);
            }

            return $this->generatePdfFromTableData($headers, $rows, $title, $filename);
        }
        catch (\Exception $e) {
            $this->logger->error('Error generating PDF from database table @table: @message', [
            '@table' => $tableName,
            '@message' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

}