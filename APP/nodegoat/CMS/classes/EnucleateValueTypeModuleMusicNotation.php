<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class EnucleateValueTypeModuleMusicNotation extends EnucleateValueTypeModuleBase {
	
	protected static $str_type = 'music_notation';
		
	protected function createModuleTemplate() {
				
		$return = '<fieldset>
			<ul>
				<li>
					<label>'.getLabel('lbl_music_notation_clef').'</label>
					<div><input type="text" name="'.$this->str_template_name.'[notation][clef]" value="'.($this->arr_value['notation']['clef'] ?? '').'" placeholder="C-1, F-4, ..." /></div>
				</li>
				<li>
					<label>'.getLabel('lbl_music_notation_key_signature').'</label>
					<div><input type="text" name="'.$this->str_template_name.'[notation][key_signature]" value="'.($this->arr_value['notation']['key_signature'] ?? '').'" placeholder="xFCG, bBEADG, ..." title="xFCG = 3♯, bBEADG = 5♭, ..." /></div>
				</li>
				<li>
					<label>'.getLabel('lbl_music_notation_time_signature').'</label>
					<div><input type="text" name="'.$this->str_template_name.'[notation][time_signature]" value="'.($this->arr_value['notation']['time_signature'] ?? '').'" placeholder="c, c/, 3/4, ..." /></div>
				</li>
				<li>
					<label>'.getLabel('lbl_music_notation_key').'</label>
					<div><input type="text" name="'.$this->str_template_name.'[notation][key]" value="'.($this->arr_value['notation']['key'] ?? '').'" placeholder="A|b, f, ..." title="A|b = A♭ major, f = F minor, ..." /></div>
				</li>
				<li>
					<label>'.getLabel('lbl_music_notation_code').'</label>
					<div><textarea name="'.$this->str_template_name.'[notation][code]" placeholder="Plaine & Easie">'.($this->arr_value['notation']['code'] ?? '').'</textarea></div>
				</li>
			</ul>
		</fieldset>
		
		<textarea name="'.$this->str_template_name.'[render][image]" class="hide">'.strEscapeHTML($this->arr_value['render']['image'] ?? '').'</textarea>
		
		<div class="image">'.($this->arr_value['render']['image'] ?? '').'</div>';
		
		return $return;
	}
	
	protected function enucleateModule($mode) {
		
		$return = '';
		
		if ($mode == static::VIEW_TEXT) {
			
			if ($this->arr_value['notation']) {
				 
				$return = 
					'@clef: '.$this->arr_value['notation']['clef'].EOL_1100CC
					.'@keysig: '.$this->arr_value['notation']['key_signature'].EOL_1100CC
					.'@timesig: '.$this->arr_value['notation']['time_signature'].EOL_1100CC
					.'@key: '.$this->arr_value['notation']['key'].EOL_1100CC
					.'@data: '.$this->arr_value['notation']['code'].EOL_1100CC
				;
			}
		} else {
			
			$return = '';
			
			if (!empty($this->arr_value['render']['image'])) {
				
				$str_image = 'data:image/svg+xml;charset=utf-8,'.rawurlencode($this->arr_value['render']['image']);
				//$str_image = 'data:image/svg+xml;base64,'.base64_encode($this->arr_value['render']['image']);
				
				$num_width = (isset($this->arr_settings['size']) ? $this->arr_settings['size']['width'] : $this->arr_value['render']['size']['width']);
				$num_height = (isset($this->arr_settings['size']) ? $this->arr_settings['size']['height'] : $this->arr_value['render']['size']['height']);
				
				$return = '<img class="enlarge" width="'.$num_width.'" height="'.$num_height.'" data-native_width="'.$this->arr_value['render']['size']['width'].'" data-native_height="'.$this->arr_value['render']['size']['height'].'" src="'.$str_image.'" />';
			}
		}
		
		return $return;
	}
	
	protected function parseModuleTemplate() {
		
		$arr = [];
		
		if (!empty($this->arr_value['notation']['code'])) {
			
			$arr['notation'] = [
				'clef' => $this->arr_value['notation']['clef'],
				'key_signature' => $this->arr_value['notation']['key_signature'],
				'time_signature' => $this->arr_value['notation']['time_signature'],
				'key' => $this->arr_value['notation']['key'],
				'code' => $this->arr_value['notation']['code']
			];
			
			preg_match('/viewBox="(\d+) (\d+) (\d+) (\d+)"/', $this->arr_value['render']['image'], $arr_size);
			
			$arr['render'] = [
				'image' => $this->arr_value['render']['image'],
				'size' => [
					'width' => $arr_size[3],
					'height' => $arr_size[4]
				]
			];
		}
		
		return $arr;
	}
	
	protected static function getModuleValueFields() {
		
		return [
			'clef' => ['name' => getLabel('lbl_music_notation_clef'), 'type' => '', 'path' => '$.notation.clef'],
			'key_signature' => ['name' => getLabel('lbl_music_notation_key_signature'), 'type' => '', 'path' => '$.notation.key_signature'],
			'time_signature' => ['name' => getLabel('lbl_music_notation_time_signature'), 'type' => '', 'path' => '$.notation.time_signature'],
			'key' => ['name' => getLabel('lbl_music_notation_key'), 'type' => '', 'path' => '$.notation.key'],
			'code' => ['name' => getLabel('lbl_music_notation_code'), 'type' => '', 'path' => '$.notation.code']
		];
	}
	
	protected static function getModuleStyle() {
		
		return '
			'.static::STYLE_CLASS_ELEMENT.'.music_notation input[type=text] { max-width: 200px; }
			'.static::STYLE_CLASS_ELEMENT.'.music_notation textarea[name$="\[code\]"] { width: 350px; }
			'.static::STYLE_CLASS_ELEMENT.'.music_notation .image { max-width: 500px; }
			'.static::STYLE_CLASS_ELEMENT.'.music_notation .image.rendering { opacity: 0.2; }
		';
	}
	
	protected static function getModuleScriptTemplate() {
		
		return "
			const elm_module = ".static::SCRIPT_ELEMENT.";
			
			const elm_render = elm_module.find('textarea[name$=\"[render][image]\"]')[0];
			const elm_image = elm_module.find('.image')[0];
			
			const elm_clef = elm_module.find('[name$=\"[clef]\"]')[0];
			const elm_key_signature = elm_module.find('[name$=\"[key_signature]\"]')[0];
			const elm_time_signature = elm_module.find('[name$=\"[time_signature]\"]')[0];
			const elm_key = elm_module.find('[name$=\"[key]\"]')[0];
			const elm_code = elm_module.find('[name$=\"[code]\"]')[0];
			
			var func = `function() {
			
				var renderer = null;
				let is_ready = false;
				
				verovio.module.onRuntimeInitialized = function() {
				
					renderer = new verovio.toolkit();
					
					is_ready = true;
					self.postMessage({ready: true});
				};

				function func_generate(str_value) {
				
					if (!is_ready) {
						return '';
					}
								
					const str_svg = renderer.renderData(str_value, {
						inputFrom: 'pae',
						adjustPageHeight: true,
						header: 'none',
						pageWidth: 1048,
						svgViewBox: true,
						svgFormatRaw: true,
						xmlIdSeed: 1
					});
					
					return str_svg;
				}
				
				self.onmessage = function(event) {
				
					const str_value = event.data.value;
					const str_svg = func_generate(str_value);
					
					self.postMessage({svg: str_svg});
				};
				
				self.onerror = function(event) {
					
					self.postMessage({report: event.message});
				};
			}`;

			var worker = ASSETS.createWorker(func, ['/js/support/verovio-toolkit-wasm.js']);
			
			function func_render() {

				const str_test = elm_clef.value + elm_key_signature.value + elm_time_signature.value + elm_key.value + elm_code.value;
				
				if (str_test == '') {
					
					elm_render.value = '';
					elm_image.innerHTML = '';

					return;
				}
				
				elm_render.value = '';
				elm_image.classList.add('rendering');
				
				const str_value = 
					'@clef:'+elm_clef.value+EOL_1100CC
					+'@keysig:'+elm_key_signature.value+EOL_1100CC
					+'@timesig:'+elm_time_signature.value+EOL_1100CC
					+'@key:'+elm_key.value+EOL_1100CC
					+'@data:'+elm_code.value+EOL_1100CC
				;
				
				worker.postMessage({value: str_value});
			}
			
			const func_message = function(event) {
			
				if (event.data.report || event.data.ready === true) {
					return;
				}
				
				var str_svg = event.data.svg;
				
				elm_render.value = str_svg;
				elm_image.innerHTML = str_svg;
				elm_image.classList.remove('rendering');
			};
								
			worker.addEventListener('message', func_message);
			
			const func_ready = function(event) {
			
				if (event.data.ready !== true) {
					return;
				}
				
				worker.removeEventListener('message', func_ready);
				
				elm_module.find('fieldset').on('input', function(e) {
					func_render();
				});
				func_render();			
			};
								
			worker.addEventListener('message', func_ready);
			
			const func_close = function() {
				
				if (onStage(elm_module)) {
					return;
				}
				
				worker.terminate();
				
				document.removeEventListener('close', func_close);
				document.removeEventListener('closed', func_close);
			};
			
			document.addEventListener('close', func_close);
			document.addEventListener('closed', func_close);
		";
	}
	
	protected static function getModuleScriptEnucleate() {
		
		return '';
	}
	
	protected static function getMusicNotationOptions() {
		
		return [
			'clef' => [
				'G-1' => ['id' => 'G-1', 'name' => 'G-1'],
				'G-2' => ['id' => 'G-2', 'name' => 'G-2 (treble)'],
				'C-1' => ['id' => 'C-1', 'name' => 'C-1'],
				'C-2' => ['id' => 'C-2', 'name' => 'C-2'],
				'C-3' => ['id' => 'C-3', 'name' => 'C-3'],
				'C-4' => ['id' => 'C-4', 'name' => 'C-4'],
				'C-5' => ['id' => 'C-5', 'name' => 'C-5'],
				'F-4' => ['id' => 'F-4', 'name' => 'F-4 (bass)'],
				'F-3' => ['id' => 'F-3', 'name' => 'F-3']
			],
			'key_signature' => [
				'xF' => ['id' => 'xF', 'name' => '1 ♯'],
				'xFC' => ['id' => 'xFC', 'name' => '2 ♯'],
				'xFCG' => ['id' => 'xFCG', 'name' => '3 ♯'],
				'xFCGD' => ['id' => 'xFCGD', 'name' => '4 ♯'],
				'xFCGDA' => ['id' => 'xFCGDA', 'name' => '5 ♯'],
				'xFCGDAE' => ['id' => 'xFCGDAE', 'name' => '6 ♯'],
				'xFCGDAEB' => ['id' => 'xFCGDAEB', 'name' => '7 ♯'],
				'bB' => ['id' => 'bB', 'name' => '1 ♭'],
				'bBE' => ['id' => 'bBE', 'name' => '2 ♭'],
				'bBEA' => ['id' => 'bBEA', 'name' => '3 ♭'],
				'bBEAD' => ['id' => 'bBEAD', 'name' => '4 ♭'],
				'bBEADG' => ['id' => 'bBEADG', 'name' => '5 ♭'],
				'bBEADGC' => ['id' => 'bBEADGC', 'name' => '6 ♭'],
				'bBEADGCF' => ['id' => 'bBEADGCF', 'name' => '7 ♭']
			]
		];
	}	
}
