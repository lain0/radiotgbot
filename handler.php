<?php

require_once __DIR__ . "/../../../../../vendor/autoload.php";

use \App\UserInfo;
use \App\Song;
use \App\Questionary;
use \App\UserBase;


use Illuminate\Database\Capsule\Manager as Capsule;


use Carbon\Carbon;

$capsule = new Capsule;

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => DB_HOST,
    'database'  => DB_NAME,
    'username'  => DB_USER,
    'password'  => DB_PASSWORD,
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);

// Make this Capsule instance available globally via static methods
$capsule->setAsGlobal();

// Setup the Eloquent ORM
$capsule->bootEloquent();

class Handler extends HandlerBase{

	public $userInfo = false;
    function __construct($vars, $user, $chat_id){
		parent::__construct($vars, $user, $chat_id);
    }

    public function parse_input ($text, $message_original){
        $hash = "";
		$ret = array();
		$hash = "";
		$c = $this->detect_command( $text );
		$m = $message_original->object->message;
		
		if ( $this->user->isNew ){
			$this->user->set_language("ru");
			$this->vars = new Vars($l);						
			$this->user->changeState( "questionary_1" );
			$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["questionary_1"], $this->vars->menus["questionary_1"] );
			
		}
		else if( "start" == $c["command"] )
		{
		}
		else if(  "questionary_1" == $this->user->base["state"] )
		{
				if( in_array( $c["command"], array("male", "female") ) )
				{
					$q = new Questionary;
					$q->uid = $this->user->tid;
					$q->sex = $c["command"];
					$q->save();
					$this->user->changeState( "" );
					$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["welcome"], $this->vars->menus["default"] );
					$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["help"], $this->vars->menus["default"] );
				}
				else if( $c["command"] == "finish_questionary" )
				{
					$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["welcome"], $this->vars->menus["default"] );
					$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["help"], $this->vars->menus["default"] );
				}
				else
				{
					$t = $this->vars->texts["questionary_error"] . "\n" . $this->vars->texts["delimeter"]  ."\n" . $this->vars->texts["questionary_1"];
					$ret[] = $this->tgbot->createTextMessage( $t, $this->vars->menus["questionary_1"] );
				}
		}
		else if( "stream" == $c["command"] )
		{
			$songs = \App\Song::take(5)->get(); // TODO recommends
			foreach( $songs as $song )
			{
				$ret[] = $this->tgbot->createAudio( $song->telegram_id, "" );
			}
			
			
		}
		if( empty( $ret ) )
		{
			$da = $this->process_default_actions( $c["command"], $c["arg"] );
			if( is_array( $da ) )
			{
				$ret = $da;
			}
		}

	
		if ( count( $ret ) == 0 ){
			if( $text != "" )
				$this->user->addFeedback( $text );
		
			$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["help"], $this->vars->menus["default"] );
		}
        return $ret;
    }

}
