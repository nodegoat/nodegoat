<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class EnucleateValueTypeMedia {
	
	protected $path_file = false;
	protected $path_enucleate = '';
	
	protected $width = false;
	protected $height = false;
	
	protected $is_external = false;
	protected $use_cache = true;
	protected $num_cache_width = 800;
	
	public function __construct($path_file) {
	
		$this->path_file = $path_file;
		$this->is_external = FileGet::getProtocolExternal($this->path_file);
		
		$this->setPath(false);
	}

	public function setPath($path_enucleate, $do_prefix = true) {
		
		if ($this->is_external) {
			return false;
		}
		
		if ($path_enucleate === false) { // Set path to default
			$this->path_enucleate = '/'.DIR_TYPE_OBJECT_MEDIA;
		} else {
			$this->path_enucleate = $path_enucleate.($do_prefix ? $this->path_enucleate : '');
		}
	}
	
	public function isExternal() {
		
		return $this->is_external;
	}
	
	public function setSize($width, $height, $use_cache = null) {
		
		$this->width = $width;
		$this->height = $height;
		
		if ($use_cache !== null) {
			
			$this->use_cache = (bool)$use_cache;
			
			if (is_numeric($use_cache)) {
				$this->num_cache_width = $use_cache;
			}
		}
	}
	
	public function getSize($use_source = true) {
				
		if (!$use_source || $this->is_external) {
			return [
				'width' => $this->width,
				'height' => $this->height
			];
		}
		
		$arr_info = getimagesize(DIR_HOME_TYPE_OBJECT_MEDIA.$this->path_file);
		if (!$arr_info) {
			return false;
		}
		
		return [
			'width' => $arr_info[0],
			'height' => $arr_info[1]
		];
	}
	
	public function enucleate($get_url = false, $type = false) {
		
		$arr_info = pathinfo($this->path_file);
		$arr_info['extension'] = strtolower($arr_info['extension']);
		
		if ($get_url) {
			
			$return = $this->path_enucleate.$this->path_file;
		} else {

			switch ($arr_info['extension']) {
				case 'jpeg':
				case 'jpg':
				case 'png':
				case 'gif':
				case 'webp':
				case 'bmp':
					if ($type) {
						$return = 'image';
					} else {
						if ($this->use_cache) {
							if ($this->height || $this->width) {
								$return = '<img class="enlarge"'.($this->height ? ' height="'.$this->height.'"' : '').($this->width ? ' width="'.$this->width.'"' : '').' src="'.SiteStartVars::getCacheUrl('img', [$this->width, $this->height], $this->path_enucleate.$this->path_file).'" />';
							} else {
								$return = '<img class="enlarge" src="'.SiteStartVars::getCacheUrl('img', [$this->num_cache_width, false], $this->path_enucleate.$this->path_file).'" />';
							}
						} else {
							$return = '<img class="enlarge"'.($this->height ? ' height="'.$this->height.'"' : '').($this->width ? ' width="'.$this->width.'"' : '').' src="'.$this->path_enucleate.$this->path_file.'" />';
						}
					}
					break;
				case 'mp3':
					if ($type) {
						$return = 'audio';
					} else {
						$return = '<audio controls="true" height="100" width="200"><source src="'.$this->path_enucleate.$this->path_file.'" type="audio/mpeg" /></audio>';
					}
					break;
				case 'mp4':
					if ($type) {
						$return = 'video';
					} else {
						$return = '<video controls="true"'.($this->height ? ' height="'.$this->height.'"' : '').($this->width ? ' width="'.$this->width.'"' : '').'><source src="'.$this->path_enucleate.$this->path_file.'" type="video/mp4" /></video>';
					}
					break;
				case 'pdf':
					if ($type) {
						$return = 'text';
					} else {
						$return = '<object type="application/pdf" width="'.($this->width ?: ($this->height ? ($this->height * 0.66) : '100%')).'" height="'.($this->height ?: ($this->width ? ($this->width * 1.33) : '100%')).'" data="'.$this->path_enucleate.$this->path_file.'"></object>';
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
						$return = '<iframe width="'.($this->width ?: ($this->height ? ($this->height * 0.66) : '100%')).'" height="'.($this->height ?: ($this->width ? ($this->width * 1.33) : '100%')).'" src="//www.youtube.com/embed/'.$arr_info['filename'].'"></iframe>';
						break;
					case 'http://vimeo.com':
					case 'https://vimeo.com':
						$return ='<iframe src="//player.vimeo.com/video/'.$arr_info['filename'].'" width="'.($this->width ?: ($this->height ? ($this->height * 0.66) : '100%')).'" height="'.($this->height ?: ($this->width ? ($this->width * 1.33) : '100%')).'" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
						break;
				}
			}
			
			if (!$return) {
				$return = $this->path_file;
			}
		}
		
		return $return;
	}
}
