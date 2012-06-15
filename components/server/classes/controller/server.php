<?php

namespace Server;

class Controller_Server extends \Controller
{
	public static $serve_list = array(
		'css' => array(),
		'js' => array()
	);

	private static $is_public_asset = false;

	private function _get_type($identifier)
	{
		foreach (static::$serve_list as $key => $value) 
		{
			if(in_array($identifier, array_keys(static::$serve_list[$key])))
				return $key;
		}

		return false;
	}

	private function _get_file_data()
	{
		$parts = explode('/',$_SERVER['REQUEST_URI']);
		$type = explode('.',$parts[count($parts)-1]);
		$type = $type[count($type)-1];

		$parts2 = $parts;
		array_pop($parts2);
		$cache_dir = implode('_',$parts2);

		return array(
			'cache_dir' => $cache_dir,
			'filename' => $parts[count($parts)-1],
			'filename_no_ext' => str_replace('.' . $type, '', $parts[count($parts)-1]),
			'extension' => $type,
		);
	}

	public static function set_global_serve_list()
	{
		\Session::set('server_serve_list',static::$serve_list);
	}

	public static function update_global_serve_list()
	{
		$serve_list = \Session::get('server_serve_list');
		
		if(is_array($serve_list))
			static::$serve_list = $serve_list;
	}

	private function _get_mimetype($value='') 
	{
		 
		$ct['htm'] = 'text/html';
		$ct['html'] = 'text/html';
		$ct['txt'] = 'text/plain';
		$ct['asc'] = 'text/plain';
		$ct['bmp'] = 'image/bmp';
		$ct['gif'] = 'image/gif';
		$ct['jpeg'] = 'image/jpeg';
		$ct['jpg'] = 'image/jpeg';
		$ct['jpe'] = 'image/jpeg';
		$ct['png'] = 'image/png';
		$ct['ico'] = 'image/vnd.microsoft.icon';
		$ct['mpeg'] = 'video/mpeg';
		$ct['mpg'] = 'video/mpeg';
		$ct['mpe'] = 'video/mpeg';
		$ct['qt'] = 'video/quicktime';
		$ct['mov'] = 'video/quicktime';
		$ct['avi'] = 'video/x-msvideo';
		$ct['wmv'] = 'video/x-ms-wmv';
		$ct['mp2'] = 'audio/mpeg';
		$ct['mp3'] = 'audio/mpeg';
		$ct['rm'] = 'audio/x-pn-realaudio';
		$ct['ram'] = 'audio/x-pn-realaudio';
		$ct['rpm'] = 'audio/x-pn-realaudio-plugin';
		$ct['ra'] = 'audio/x-realaudio';
		$ct['wav'] = 'audio/x-wav';
		$ct['css'] = 'text/css';
		$ct['sass'] = 'text/css';
		$ct['scss'] = 'text/css';
		$ct['zip'] = 'application/zip';
		$ct['pdf'] = 'application/pdf';
		$ct['doc'] = 'application/msword';
		$ct['bin'] = 'application/octet-stream';
		$ct['exe'] = 'application/octet-stream';
		$ct['class']= 'application/octet-stream';
		$ct['dll'] = 'application/octet-stream';
		$ct['xls'] = 'application/vnd.ms-excel';
		$ct['ppt'] = 'application/vnd.ms-powerpoint';
		$ct['wbxml']= 'application/vnd.wap.wbxml';
		$ct['wmlc'] = 'application/vnd.wap.wmlc';
		$ct['wmlsc']= 'application/vnd.wap.wmlscriptc';
		$ct['dvi'] = 'application/x-dvi';
		$ct['spl'] = 'application/x-futuresplash';
		$ct['gtar'] = 'application/x-gtar';
		$ct['gzip'] = 'application/x-gzip';
		$ct['js'] = 'application/x-javascript';
		$ct['coffee'] = 'application/x-javascript';
		$ct['swf'] = 'application/x-shockwave-flash';
		$ct['tar'] = 'application/x-tar';
		$ct['xhtml']= 'application/xhtml+xml';
		$ct['au'] = 'audio/basic';
		$ct['snd'] = 'audio/basic';
		$ct['midi'] = 'audio/midi';
		$ct['mid'] = 'audio/midi';
		$ct['m3u'] = 'audio/x-mpegurl';
		$ct['tiff'] = 'image/tiff';
		$ct['tif'] = 'image/tiff';
		$ct['rtf'] = 'text/rtf';
		$ct['wml'] = 'text/vnd.wap.wml';
		$ct['wmls'] = 'text/vnd.wap.wmlscript';
		$ct['xsl'] = 'text/xml';
		$ct['xml'] = 'text/xml';
		 
		$extension =  @array_pop(explode('.',$value));
		 
		if (!$type = $ct[strtolower($extension)]) 
		{
			$type = 'text/html';
		}
	 
		return $type;
	}

	public function action_index()
	{
		$id = $this->param('identifier');

		static::$serve_list = \Config::get('server');

		switch($id)
		{
			case 'css':
			case 'js':

			$body = '';

			foreach (static::$serve_list[$id] as $key => $value) 
			{
				$body .= Model_Cache::check($key,$value);
			}

			$this->response->set_header('Content-Type', $this->_get_mimetype('.' . $id));
			$this->response->body = $body;

			break;
			default:
				$multiple = explode(',',$id);
				foreach ($multiple as $identifier) 
				{
					$cmd = explode(':',$identifier);
					$method = isset($cmd[1]) ? $cmd[1] : 'c';
					$type =  $this->_get_type($cmd[0]);
					$name = $cmd[0];
					if($type != false)
					{
						$finfo = new \finfo();
						$filepath = static::$serve_list[$type][$name];
						$type = static::_get_mimetype($filepath);
						$this->response->set_header('Content-Type', $type);
						$get_cache_content = Model_Cache::check($identifier,$filepath);
						$this->response->body .= '/* ----- ' . $identifier . ' -----' . PHP_EOL;
						$this->response->body .= $get_cache_content . PHP_EOL;
					}
				}
			break;
		}

		return $this->response;
	}

	public function action_component()
	{
		$component = $this->param('component');

		$file_data = $this->_get_file_data();
		$type = $file_data['extension'];
		$file = $file_data['filename'];

		if($type == 'coffee')
			$type = 'js';

		if($type == 'sass' || $type == 'scss')
			$type = 'css';

		if($type != 'js' && $type != 'css')
			$type = 'img';

		if(static::$is_public_asset)
			$path = DOCROOT . 'assets/' . $type . '/' . $component . '/' . $file;
		else
			$path = APPPATH . '../../components/' . $component . '/assets/' . $type . '/' . $file;

		if($type == 'img')
			$type = $file_data['extension'];

		$this->response->set_header('Content-Type', $this->_get_mimetype('test.' . $type));

		if($type == 'img' || $type == 'js')
		{
			$this->response->body = file_get_contents($path);
		}
		else
		{
			$this->response->body = Model_Cache::check(
				$file,
				$path,
				$file_data['cache_dir'],
				$file_data['extension']
			);
		}

			 
		
		return $this->response;
	}

	public function action_public()
	{
		static::$is_public_asset = true;
		return $this->action_component();
	}
}