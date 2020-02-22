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

$suffix_list = BfdownloadmanagerHelper::getCategoryAttr($this->item->catid, 'download_suffix_list');
if (!BfdownloadmanagerHelper::validateFilenameSuffix($this->item->downloadfile_name, $suffix_list))
{
	header("HTTP/1.0 404 Not Found");
	exit(0);
}

$browserNav = BfdownloadmanagerHelper::getCategoryAttr($download->catid, 'download_browserNav');
switch ($browserNav)
{
	case 2:
	case 3:
		$header = false;
		break;
	case 1:
	default:
		$header = true;
		$ext = strtolower(pathinfo($download->downloadfile_name, PATHINFO_EXTENSION));
		switch ($ext)
		{
			case 'html';
			case 'pdf';
			case 'txt';
				$browserNav = 3;
				break;
			default:
				break;
		}
		break;
}

$ext = strtolower(pathinfo($this->item->downloadfile_name, PATHINFO_EXTENSION));
switch ($ext)
{
	case 'doc';
		header("Content-type: application/msword");
		break;
	case 'html';
		header("Content-type: text/html");
		if (!isset($_REQUEST['inline']))
		{
			$header = ($browserNav == 1);
		}
		break;
	case 'mp3';
		header("Content-type: audio/mpeg3");
		break;
	case 'pdf';
		header("Content-type: application/" . $ext);
		if (!isset($_REQUEST['inline']))
		{
			$header = ($browserNav == 1);
		}
		break;
	case 'txt';
		header("Content-type: text/plain");
		if (!isset($_REQUEST['inline']))
		{
			$header = ($browserNav == 1);
		}
		break;
	case 'zip';
		header("Content-type: application/" . $ext);
		break;
	default:
		header("Content-type: application/octet-stream");
		break;
}

if ($header)
{
	header("Content-Disposition: attachment; filename=" . preg_replace('/[^a-zA-Z0-9]+/', '_', ($this->item->downloadfile_name)));
	header("Content-length: " . $this->item->downloadfile_size);
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
}

echo base64_decode($this->item->downloadfile);
exit(0);
