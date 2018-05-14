<?php
/*
 *
 * Copyright (C) 2012 Pascal Noisette
 *
 * This file is part of gui.scan an Ajaxplorer plugin
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Library General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor Boston, MA 02110-1301,  USA
 *
 */
defined('AJXP_EXEC') or die( 'Access not allowed');

class Scanner extends AJXP_Plugin{
	public function listDeviceAction($action, $httpVars, $fileVars){
		$command="scanimage --list-devices"; $output = array(); $return_var = 0; $matches=array();
		exec ($command, $output, $return_var);
		echo '<select id="scan_device" name="scan_device">';
		foreach($output as $line) {
			
			if (strpos($line, "device `") === 0) {
				list($valuePart, $name) = explode("' is a ", $line);
			
				list($unused, $value) = explode("`", $valuePart);
	
				echo '<option value="'.$value.'">'.$name.'</option>';
			}
		}
		echo '</select>';
		die ();
	}
	public function scanAction($action, $httpVars, $fileVars){
		$directory  = AJXP_Utils::decodeSecureMagic($httpVars["dir"]);
		$newFile    = AJXP_Utils::decodeSecureMagic($httpVars["scan_name"]);
		$device     = AJXP_Utils::decodeSecureMagic($httpVars["scan_device"]);
		$resolution = AJXP_Utils::decodeSecureMagic($httpVars["scan_resolution"]);
		$mode       = AJXP_Utils::decodeSecureMagic($httpVars["scan_mode"]);
		if ($this->_scanFileIntoDirectory($directory, $newFile, $device, $resolution, $mode)) {
			$message    = $this->_translateThatFileSuccessFullyCreated($newFile);
			$this->_sendAjaxResponse($message);
		}		
	}
	protected function _scanFileIntoDirectory($directory, $filename, $device, $resolution, $mode) {
		
		$realFile = $this->_getRealFileName($directory,  $filename.'.tiff');
		$command = "scanimage -d ".escapeshellarg($device).
				   " --resolution ".intval($resolution).
				   " --mode ".escapeshellarg($mode).
				   " --format=tiff > " . escapeshellarg($realFile);
		$output = array(); $return_var = 0;
		exec ($command, $output, $return_var);
		return $return_var == 0;
	}
	protected function _getRealFileName($directory, $filename) {
		$repo = ConfService::getRepository();
                $repo->detectStreamWrapper();
                $wrapperData = $repo->streamData;
                $urlBase = $wrapperData["protocol"]."://".$repo->getId();
		$realFile = call_user_func(array($wrapperData["classname"], "getRealFSReference"), rtrim($urlBase, '/').   str_replace('//','/', $directory .  '/'). $filename);
		return $realFile;
	}
	protected function _translateThatFileSuccessFullyCreated($filename){
		$translationTable = ConfService::getMessages();
		$message = sprintf($translationTable["gui_scan.5"], $filename);

		return $message;
	}
	protected function _sendAjaxResponse($message){
		AJXP_XMLWriter::header();
		AJXP_XMLWriter::sendMessage($message, null);
		AJXP_XMLWriter::triggerBgAction("reload_node", array(), "Triggering DL ", true, 2);
		AJXP_XMLWriter::close();
		session_write_close();
		exit();
	}

}

?>
