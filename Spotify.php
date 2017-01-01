<?php

//error_reporting(-1);
//ini_set('display_errors', 1);

set_time_limit(0);

require 'vendor/autoload.php';

class Spotify{
	private static $configs = array(
		'CLIENT_ID'     => '',
		'CLIENT_SECRET' => '',
		'REDIRECT'      => 'http://localhost/dctb-spotify/Spotify.php',
		'PLAYLIST_ID'   => '',
		'DATA'          => './data/musics.txt');

	private static $scopes = [
			        'scope' => [
				        'user-read-email',
				        'user-library-modify',
				        'playlist-modify-private',
				        'playlist-modify-public',
				        'playlist-read-collaborative',
				        'playlist-read-private'
			        ],
			    ];

	private static $instance = null;

	public $session;
	public $api;

	public static function getInstance(){
		if (!isset(self::$instance) && is_null(self::$instance)) {
			$c = __CLASS__;
			self::$instance = new $c;
		}
		return self::$instance;
	}

	private function __construct(){}

	private function search($track){
		$track = $this->api->search($track, 'track');
		$items = $track->tracks;
		foreach ($items as $key => $value) {
			if(is_array($value) && !empty($value[0]->id))
				return $value[0]->id;
		}
		return null;
	}

	private function getMusics(){
		return file(Spotify::$configs['DATA']);
	}

	private function console($msg){
		echo "[CONSOLE] : " . $msg . "\n";
	}

	private function go(){
		echo "<pre>";
		$this->console("STARTING MUSICS SEARCH AT DATA FILE");
		$list = $this->getMusics();
		$ids  = [];
		foreach ($list as $key => $value){
			$id = $this->search($value);
			if(empty($id)){
				$this->console("MUSIC NOT FOUND: " . $value);
			}
			else{
				$ids[] = $id;
				$this->console("FOUND MUSIC: " . $value . " (". $id . ")");
			}
		}
		$this->console("END MUSICS SEARCHED");
		$me = $this->api->me();
		$uid = $me->id;
		$this->console("MUSICS TO PLAYLIST");
		$this->api->addUserPlaylistTracks($uid, Spotify::$configs['PLAYLIST_ID'], $ids);
		echo "</pre>";
	}

	public function start(){
		$this->session = new SpotifyWebAPI\Session(
			Spotify::$configs['CLIENT_ID'],
			Spotify::$configs['CLIENT_SECRET'],
			Spotify::$configs['REDIRECT']
		);
		$this->api = new SpotifyWebAPI\SpotifyWebAPI();
		if(isset($_GET['code'])){
			$this->session->requestAccessToken($_GET['code']);
			$this->api->setAccessToken($this->session->getAccessToken());
			$this->go();
		}
		else{
			header('Location: ' . $this->session->getAuthorizeUrl($this->scopes));
		}
	}
}

$s = Spotify::getInstance();
$s->start();