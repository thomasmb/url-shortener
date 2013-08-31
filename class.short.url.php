<?php

class short_url{

	protected $db = false,
			  $url = false, //holds the long url
			  $slug = false,//holds the url slug
			  $return_url = "";
	
	function __construct(){
		
		$this->_connect_to_db();
		
		//if a long url is submitted for shortening
		if( $this->is_new_url() )
			$this->gen_short_url();
			
		//if a short url is requested
		else
			$this->handle_redirect();
			
	}
	
	
	//If we are adding a url
	public function is_new_url(){
			
		if( isset( $_GET['url'] ) ){
		
			if( SECRET_REQUIRED && ( !isset( $_GET['api_secret'] ) || $_GET['api_secret'] != API_SECRET ) ){
				echo "api secret is required";
				exit;
			}
				
		
			$this->url = $_GET['url'];
			
			if( empty( $this->url ) || filter_var( $this->url, FILTER_VALIDATE_URL ) === false )
				$this->url = false;
			
			
			//this one of our short-urls
			if( $this->url && ( $url = parse_url( $this->url  ) ) && $url['host'] == BASE_URL ){
			
				$this->return_url = $this->url;
				
				$this->present_url();
			}
				
		}
		
		if( isset( $_GET['slug'] ) ){
		
			$this->slug = $this->clean_slug( $_GET['slug'] );
		
			if( $this->has_short_url() && isset( $_GET['bookmark'] ) ){
				echo "alert('This slug has already been used');";
			}
			
		}
		
		return $this->url;
		
	}
	
	public function clean_slug($str){
		// replace non letter or digits by -
		$str = preg_replace( '~[^\\pL\d]+~u', '-', $str );
		
		// trim
		$str = trim( $str, '-' );
		
		// transliterate
		$str = iconv( 'utf-8', 'us-ascii//TRANSLIT', $str );
		
		// lowercase
		$str = strtolower( $str );
		
		// remove unwanted characters
		$str = preg_replace( '~[^-\w]+~', '', $str );
		
		return empty( $str ) ? false : $str;
	}
	
	
	//Create a new short url
	public function gen_short_url(){
	
		//If this link is not shortend allready, do it now. Otherwise, get the short url for it
		if( $this->slug || !$this->has_long_url() ){
			
			//if a slug was passed with the API, and it has not been used in the database before
			if( !$this->slug && !$this->has_short_url() ){
				//Keep creating short urls untill we have a unique one
				do{
					$this->new_slug();
				} while ( $this->has_short_url() );
			}
			
			//Insert it into the database
			$query = $this->db->prepare( "INSERT INTO redirects (slug, url) VALUES (?,?)" );
			
			$query->execute( array( $this->slug, $this->url ) );	
		}
		
		$this->return_url = ( ADD_SCHEME ? "http://" : "" ) . BASE_URL . ( substr( BASE_URL,-1 ) !== '/' ? '/' : '' ) . $this->slug;
		
		$this->present_url();
	
	}

	
	
	public function has_short_url(){
		$query = $this->db->prepare( "SELECT * FROM redirects WHERE slug= ?" );
		$query->execute( array( $this->slug ) );
		
		//If this slug allready has been used
		return $query->rowCount() > 0 ? true : false;
	}
	
	
	//Check if a short url exists for a long url
	public function has_long_url(){
	
		$query = $this->db->prepare( "SELECT * FROM redirects WHERE url= ?" );
		$query->execute( array( $this->url ) );
		
		//If the long url is in the database
		if( $query->rowCount() > 0 ){
			
			//Get the short url
			$obj = $query->fetch( PDO::FETCH_OBJ );
			$this->slug = $obj->slug;
			return true;
			
		}
		
		return false;
		
	}
	
	//Get the long url from the dabase 
	public function get_long_url(){
		
		$query = $this->db->prepare( "SELECT * FROM redirects WHERE slug= ?" );
		$query->execute( array( $this->slug ) );
		
		//If the long url is in the database
		if($query->rowCount() > 0){
			
			//Get the short url
			$obj = $query->fetch( PDO::FETCH_OBJ );
			$this->url = $obj->url;
			return true;
			
		}
		
		return false;
		
	}
	
		
	//Creates a new random slug
	public function new_slug(){
		$this->slug = substr( str_shuffle( URL_CHARSET ), 0, URL_LENGTH );
	}
	
	//Send redirect header
	public function redirect(){
		header( 'Location: ' . $this->url, null, 301 );
	}
	
	//Redirects the user to the long url or to the fallback
	public function handle_redirect(){
	
		if(!( $this->get_slug() && $this->get_long_url() ) ){
			$this->url = BASE_FORWARD;
		}
		
		//forward any $_GET data that we got from the previous source
		if(isset( $_GET ) && ( $get_data = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY ) ) )
			$this->url .= "?" . $get_data;
		
		$this->redirect();
		
	}
	
	
	//Gets the slug from the url
	public function get_slug(){
		if($this->slug === false && isset( $_GET['c']) && $_GET['c'] != "/" ){
			$this->slug = ltrim( $_GET['c'], '/' );
		}
		
		return $this->slug;
	}
	
	public function present_url(){
		header( 'Content-Type: text/javascript' );
		
		if( isset( $_GET['bookmark'] ) )
			echo "prompt('Your URL was generated','".$this->return_url."')";
		else
			echo json_encode( array( "shorturl" => $this->return_url ), JSON_FORCE_OBJECT );
		
		exit;
	}
	
	
	//Connects to the mysql database
	protected function _connect_to_db(){
		
		try {
		    $this->db = new PDO( "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
		    					 DB_USER,
		    					 DB_PASS,
		    					 array( PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'' ) );
		} catch (PDOException $e) {
		    echo 'Connection failed: ' . $e->getMessage();
		}
		
	}
		
}

?>
