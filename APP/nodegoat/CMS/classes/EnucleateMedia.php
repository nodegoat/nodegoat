<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class EnucleateMedia {
	
	private $file_name = false;
	private $is_external = false;
	private $is_preview = false;
	
    public function __construct($file_name, $is_preview = false) {
	
		$this->file_name = $file_name;
		$this->is_external = FileGet::getExternalProtocol($this->file_name);
		$this->is_preview = $is_preview;
    }
	
	public function enucleate($url = false, $type = false) {
		
		$arr_info = pathinfo($this->file_name);
		$arr_info['extension'] = strtolower($arr_info['extension']);
		$dir = (!$this->is_external ? '/'.DIR_TYPE_OBJECT_MEDIA : '');
		
		if ($url) {
			
			$return = $dir.$this->file_name;
		} else {

			switch ($arr_info['extension']) {
				case 'jpeg':
				case 'jpg':
				case 'png':
				case 'gif':
				case 'bmp':
					if ($type) {
						$return = 'image';
					} else {
						$return = ($this->is_preview ? '<img class="enlarge" height="20" src="'.SiteStartVars::getCacheUrl('img', [false, 20], $dir.$this->file_name).'" />' : '<img class="enlarge" src="'.SiteStartVars::getCacheUrl('img', [800, false], $dir.$this->file_name).'" />');
					}
					break;
				case 'mp3':
					if ($type) {
						$return = 'audio';
					} else {
						$return = '<audio controls="true"><source src="'.$dir.$this->file_name.'" type="audio/mpeg" /><embed height="50" width="100" src="'.$dir.$this->file_name.'" type="audio/mpeg" /></audio>';
					}
					break;
				case 'pdf':
					if ($type) {
						$return = 'text';
					} else {
						$return = '<iframe width="600" height="500" src="'.$dir.$this->file_name.'"></iframe>';
					}
					break;
			}
			
			if (!$return) {
				
				switch ($arr_info['dirname']) {
					case 'http://youtu.be':
					case 'https://youtu.be':
					case 'youtube.com':
					case 'www.youtube.com':
					case 'http://www.youtube.com':
					case 'https://www.youtube.com':
						$return = '<iframe width="500" height="375" src="//www.youtube.com/embed/'.$arr_info['filename'].'"></iframe>';
						break;
					case 'http://vimeo.com':
					case 'https://vimeo.com':
						$return ='<iframe src="//player.vimeo.com/video/'.$arr_info['filename'].'" width="500" height="375" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
						break;
				}
			}
			
			if (!$return) {
				$return = $this->file_name;
			}
		}
	
	
		return $return;
		
	}
    
}
