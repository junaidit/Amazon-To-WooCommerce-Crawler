<?php
/**
 *
 * @author      Junaid
 * @category    Admin
 * @package     amazon-crawler/includes
 * @version     1.0
 */
Class Crawler
{
	var $curl;
	/**
	* create curl handle.
	*/
	function __construct()
	{		
		$this->curl= curl_init();
	}
	/**
	* If login is required use this function
	* to have session/cookie.
	*/
	function logIn($loginActionUrl,$parameters)
	{
			curl_setopt ($this->curl, CURLOPT_URL,$loginActionUrl);	
			curl_setopt ($this->curl, CURLOPT_POST, 1);	
			curl_setopt ($this->curl, CURLOPT_POSTFIELDS, $parameters);	
			curl_setopt ($this->curl, CURLOPT_COOKIEJAR, realpath('cookie.txt')); // cookie.txt should be in same directoy, where calling script is	
			curl_setopt ($this->curl, CURLOPT_COOKIEFILE, realpath('cookie.txt'));
			curl_setopt ($this->curl, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt ($this->curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($this->curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux i586; de; rv:5.0) Gecko/20100101 Firefox/5.0');			
			curl_setopt ($this->curl, CURLOPT_REFERER, 'http://www.google.com');	// set referer
			curl_setopt ($this->curl, CURLOPT_SSL_VERIFYPEER, FALSE);// ssl certificate
    	    curl_setopt ($this->curl, CURLOPT_SSL_VERIFYHOST, 2);
			
			$result['EXE'] = curl_exec($this->curl);
			$result['INF'] = curl_getinfo($this->curl);
			$result['ERR'] = curl_error($this->curl);
			return $result;					
	}
	/**
	* simply return page content when loged in
	* or where login is not required.
	*/
	function getContent($url, $proxy="") 
	{		
		$headers = array('Accept-Charset: ISO-8859-1','Accept-Language: en-us,en;');			
		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_HEADER, 0);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER,$headers);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
		
		if($proxy != "")
		{
			curl_setopt($this->curl, CURLOPT_PROXY, $proxy);
			curl_setopt($this->curl, CURLOPT_HTTPPROXYTUNNEL, 0); // // to make the request go through as though proxy didn't exist
		}	
			   
		curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($this->curl, CURLOPT_TIMEOUT, 500);
		//curl_setopt ($this->curl, CURLOPT_COOKIEJAR, realpath('cookie.txt'));	
		//curl_setopt ($this->curl, CURLOPT_COOKIEFILE, realpath('cookie.txt'));
		curl_setopt($this->curl, CURLOPT_REFERER, 'http://www.google.com/');
		curl_setopt($this->curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux i586; de; rv:5.0) Gecko/20100101 Firefox/5.0');
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, FALSE);// ssl certificate
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 2);
		
		$result['EXE'] = curl_exec($this->curl);
		$result['INF'] = curl_getinfo($this->curl);
		$result['ERR'] = curl_error($this->curl);
		
		//curl_close($this->curl);
		unset($url);
		return $result;
	}

	public function extractor($str,$from,$to)
	{
		$from_pos = strpos($str,$from);
		$from_pos = $from_pos + strlen($from);
		$to_pos   = strpos($str,$to,$from_pos);// to must be after from
		$return	  = substr($str,$from_pos,$to_pos-$from_pos);
		unset($str,$from,$to,$from_pos,$to_pos );			
		return $return;
	}
	/**
	* array getSelectiveContent($content,$from,$to,$exclude="")
	* return array of content between provided 
	* from and to positions.
	*/
	public function getSelectiveContent($content,$from,$to,$exclude="")
	{
		$return = array(); // array for return elements
		$size_FROM = strlen($from); 
		$size_TO = strlen($to);
		while(true)
		{
			$pos = strpos($content,$from); // find first occurance of $from
			if( $pos === false )
			{
				break;  // if not exist break loop
			}
			else
			{
				$element  = $this->extractor($content,$from,$to); // fetch first element
				if($exclude == "")
				{
					if( trim($element) != "" )
					{
						$return[] = trim($element);
					}
				}
				else
				{
					if(trim($element) != "" && !strstr($element,$exclude)) // if nothing in range, and exclude is not in it
					{
						$return[] = trim($element); // put fetched content in array.
					}
				}
				$content = substr($content,$pos+strlen($element)+$size_FROM+$size_TO); // remove $from to $to from content 
			}
		}
		unset($content,$from,$to,$element,$exclude,$pos,$size_FROM,$size_TO);
		return $return;
	}
	
	/** 
	* string refine_str($str,$from,$to="")
	* remove $from to $to string from subject string
	* if $to is not provided $from will be considered
	* a string to remove.
	*/
	
	public	function refine_str($str,$from,$to="")
		{
		while (($from_pos = strpos($str,$from)) !== false )
		{
			if($to != "")
			{
				$to_pos   = strpos($str,$to,$from_pos);// to must be after from
				$str1 	  = substr($str,0,$from_pos);
				$str2 	  = substr($str,$to_pos+strlen($to));
				$str	  = $str1.$str2;
			}
			else
			{
				$str1 	  = substr($str,0,$from_pos);
				$str2 	  = substr($str,$from_pos+strlen($from));
				$str	  = $str1.$str2;
			}
		}
		unset ($str1,$str2);	
	return $str;
	}
	
	/** 
	* string CustomStrStr($str,$needle,$position = false,$sub = false)
	* $str = "This is sample text. it is really simple example";
	* $position = false and $sub = false show result of before first occurance of $needle
	* $position = true and $sub false show result of before last occurance of $needle
	* $position = false and $sub = true show result of after first occurance of $needle
	* $position = true and $sub true show result of after last occurance of $needle
	*/	
	
	
	public function CustomStrStr($str,$needle,$position = false,$sub = false)
	{
		$Isneedle = strpos($str,$needle);
		if ($Isneedle === false)
		return false;
		
			$needlePos =0;			
			if ( $position === false )
				$needlePos = strpos($str,$needle);
			else
				$needlePos = strrpos($str,$needle);
				
			if ($sub === false)
				$return = substr($str,0,$needlePos);
			else
				$return = substr($str,$needlePos+strlen($needle));
				
		return $return;			
	}
}// class	