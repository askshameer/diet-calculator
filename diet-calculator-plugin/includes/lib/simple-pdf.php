<?php
/**
 * Simple PDF Generator
 * A lightweight PDF generation class as fallback when TCPDF is not available
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SimplePDF {
    private $content;
    private $title;
    private $author;
    
    public function __construct() {
        $this->content = '';
        $this->title = 'Document';
        $this->author = 'WordPress Site';
    }
    
    public function SetTitle($title) {
        $this->title = $title;
    }
    
    public function SetAuthor($author) {
        $this->author = $author;
    }
    
    public function writeHTML($html) {
        // Convert HTML to plain text for simple PDF
        $text = $this->html_to_text($html);
        $this->content .= $text;
    }
    
    public function Output($filename = 'document.pdf', $destination = 'D') {
        // For WordPress, we'll output as a formatted text file with PDF headers
        $pdf_content = $this->generate_pdf_content();
        
        if ($destination === 'D') {
            // Download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdf_content));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            echo $pdf_content;
        } else {
            return $pdf_content;
        }
    }
    
    private function html_to_text($html) {
        // Remove HTML tags and convert to readable text
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
    
    private function generate_pdf_content() {
        // Generate a simple PDF-like format
        // This is a very basic implementation - in production you'd want a proper PDF library
        
        $content = "%PDF-1.4\n";
        $content .= "1 0 obj\n";
        $content .= "<<\n";
        $content .= "/Type /Catalog\n";
        $content .= "/Pages 2 0 R\n";
        $content .= ">>\n";
        $content .= "endobj\n\n";
        
        $content .= "2 0 obj\n";
        $content .= "<<\n";
        $content .= "/Type /Pages\n";
        $content .= "/Kids [3 0 R]\n";
        $content .= "/Count 1\n";
        $content .= ">>\n";
        $content .= "endobj\n\n";
        
        $text_content = $this->prepare_text_for_pdf($this->content);
        $content_length = strlen($text_content);
        
        $content .= "3 0 obj\n";
        $content .= "<<\n";
        $content .= "/Type /Page\n";
        $content .= "/Parent 2 0 R\n";
        $content .= "/MediaBox [0 0 612 792]\n";
        $content .= "/Contents 4 0 R\n";
        $content .= "/Resources <<\n";
        $content .= "/Font <<\n";
        $content .= "/F1 5 0 R\n";
        $content .= ">>\n";
        $content .= ">>\n";
        $content .= ">>\n";
        $content .= "endobj\n\n";
        
        $content .= "4 0 obj\n";
        $content .= "<<\n";
        $content .= "/Length $content_length\n";
        $content .= ">>\n";
        $content .= "stream\n";
        $content .= "BT\n";
        $content .= "/F1 12 Tf\n";
        $content .= "50 750 Td\n";
        $content .= "($text_content) Tj\n";
        $content .= "ET\n";
        $content .= "endstream\n";
        $content .= "endobj\n\n";
        
        $content .= "5 0 obj\n";
        $content .= "<<\n";
        $content .= "/Type /Font\n";
        $content .= "/Subtype /Type1\n";
        $content .= "/BaseFont /Helvetica\n";
        $content .= ">>\n";
        $content .= "endobj\n\n";
        
        $content .= "xref\n";
        $content .= "0 6\n";
        $content .= "0000000000 65535 f \n";
        $content .= "0000000009 65535 n \n";
        $content .= "0000000074 65535 n \n";
        $content .= "0000000131 65535 n \n";
        $content .= "0000000273 65535 n \n";
        $content .= "0000000391 65535 n \n";
        $content .= "trailer\n";
        $content .= "<<\n";
        $content .= "/Size 6\n";
        $content .= "/Root 1 0 R\n";
        $content .= ">>\n";
        $content .= "startxref\n";
        $content .= "478\n";
        $content .= "%%EOF\n";
        
        return $content;
    }
    
    private function prepare_text_for_pdf($text) {
        // Escape special PDF characters
        $text = str_replace('(', '\\(', $text);
        $text = str_replace(')', '\\)', $text);
        $text = str_replace('\\', '\\\\', $text);
        
        // Limit length to prevent PDF corruption
        if (strlen($text) > 1000) {
            $text = substr($text, 0, 1000) . '...';
        }
        
        return $text;
    }
}