<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_bfdownloadmanager
 *
 * @copyright   Copyright (C) 2018 Jonathan Brain. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

ob_clean();

$header = !empty($_REQUEST['inline']);
$ext = pathinfo($this->item->downloadfile_name, PATHINFO_EXTENSION);
switch($ext) {
  case 'doc';
    header("Content-type: application/msword");
    break;
  case 'html';
    $header = false;
    header("Content-type: text/html");
    break;
  case 'mp3';
    header("Content-type: audio/mpeg3");
    break;
  case 'pdf';
    header("Content-type: application/" . $ext);
    $header = false;
    break;
  case 'txt';
    $header = false;
    header("Content-type: text/plain");
    break;
  case 'zip';
    header("Content-type: application/" . $ext);
    break;
  default:
    header("Content-type: application/octet-stream");
    break; 
}

if ($header) {
  header("Content-Disposition: attachment; filename=" . $this->item->downloadfile_name); 
  header("Content-length: " . $this->item->downloadfile_size);
  header("Expires: 0");
  header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
  header("Cache-Control: no-store, no-cache, must-revalidate");
  header("Cache-Control: post-check=0, pre-check=0", false);
  header("Pragma: no-cache"); 
}

echo base64_decode($this->item->downloadfile);
exit(0);
