<?php
/**
 * Rewriter
 * 
 * A class created to generate Apache RewriteRules based upon source
 * and destination URLs
 * 
 * @author OBush.co.uk
 *
 */
class Rewriter{
	
	private $includeIfModuleCheck = true;
	private $includeTurnOnEngine = true;
	private $http301 = true;
	private $urls = array();
	private $rewrites = '';
	
	public function __construct($includeIfModuleCheck = true, $includeTurnOnEngine = true, $http301 = true){
		$this->includeIfModuleCheck = $includeIfModuleCheck;
		$this->includeTurnOnEngine = $includeTurnOnEngine;
		$this->http301 = $http301;
	}
	
	/**
	 * Generate rewrites based upon an array where 'source' is the key and 'destination' the value
	 * 
	 * @param array $urls - array in format array('source' => 'destination')
	 * @throws Exception
	 * @return string
	 */
	public function generateRewritesFromArray(array $urls = array()){
		if(is_array($urls)){
			foreach($urls as $source => $destination){
				// Make sure the destination is not an array
				if(is_array($destination)){
					throw new Exception('Destinations cannot be arrays. Check the destination for: ' . $source);
				}
				$this->urls[$source] = $destination;
			}

			// Build the rewrites
			return $this->buildRewrites();
		}else{
			throw new Exception('URLs are not in array format.');
		}
	}
	
	/**
	 * Generate rewrites based upon a string with specified separators and linebreaks
	 * 
	 * @param string $urls
	 * @param string $separator - character to separate source from destination
	 * @param string $lineBreak - character to split url pairs
	 * @throws Exception
	 * @return string
	 */
	public function generateRewritesFromString($urls, $separator = ' ', $lineBreak = "\n"){
		if(is_array($urls)){
			return $this->generateRewritesFromArray($urls);
		}else{
			// Remove any whitespace at the start/end of the url list
			$urls = trim($urls);
			
			// Split URL list by line first
			$urlsArray = explode($lineBreak, $urls);
			foreach($urlsArray as $url){
				// If there is a blank line ignore it
				if($url == ''){
					continue;
				}
				// The separator is not used for this URL pair - throw an exception
				if(!strpos($url, $separator)){
					throw new Exception('URL does not contain valid separator: ' . $url);
				}else{
					// Split the source and destination parts by the provided separator
					// Limit the number of parts after splitting to 2
					$splitURLs = explode($separator, $url, 2);
					
					// The separator existed but there were not 2 parts present
					if(count($splitURLs) !== 2){
						throw new Exception('URL does not contain valid number of separators: ' . $url);
					}
					// Set the source and destination URLs to the split parts
					list($source, $destination) = $splitURLs;
					
					// Remove the leading slash on the source URLs
					if(substr($source, 0, 1) == '/'){
						$source = substr($source, 1);
					}
					
					// Add this URL pair to the URL array
					$this->urls[$source] = $destination;
					unset($splitURLs);
				}
			}
			unset($urlsArray);
			
			// Build the rewrites
			return $this->buildRewrites();
		}
	}
	
	/**
	 * Generate the actual rewrites statements
	 * 
	 * @return string
	 */
	private function buildRewrites(){
		
		// If we want to add IfModule checks, add the opening tag
		if($this->includeIfModuleCheck){
			$this->appendIfModuleCheckStart();
		}
			
		// If we want to turn the rewrite engine on, add the statement
		if($this->includeTurnOnEngine){
			$this->appendTurnOnEngine();
		}
		
		// Are there actually URLs to rewrite?
		if(!empty($this->urls)){
			
			// Loop through the URLs
			foreach($this->urls as $source => $destination){
				
				// Check for query strings, as RewriteRule will ignore them
				$queryStringPos = strpos($source, '?');
				
				// URL has a query string
				if($queryStringPos !== FALSE){
					
					// Grab the query string
					$queryString = substr($source, $queryStringPos + 1);
					
					// If there wasn't just a lone ? in the URL
					if($queryString != ''){
						
						// Add a RewriteCond for this query string
						$this->buildRewriteCondition('QUERY_STRING', $queryString);
					}
					
					// RewriteRule matches on the request URI without query strings, so remove the query string
					$source = substr($source, 0, $queryStringPos);
				}
				
				// Add a RewriteRule for this source / destination
				$this->buildRewriteRule($source, $destination);
			}
		}
		
		// If we are adding the check for mod_rewrite.c add the closing tag
		if($this->includeIfModuleCheck){
			$this->appendIfModuleCheckEnd();
		}
		
		// Return our rewrites
		return $this->rewrites;
	}
	
	/**
	 * Add a new line to the rewrites
	 * 
	 * @param unknown_type $line
	 */
	private function appendLineToRewrites($line){
		$this->rewrites .= "\n" . $line;
	}
	
	/**
	 * Add the check for the mod_rewrite.c module
	 */
	private function appendIfModuleCheckStart(){
		$string = '<IfModule mod_rewrite.c>';
		$this->appendLineToRewrites($string);
	}
	
	/**
	 * Add the closing tag for the check for mod_rewrite.c
	 */
	private function appendIfModuleCheckEnd(){
		$string = '</IfModule>';
		$this->appendLineToRewrites($string);
	}
	
	/**
	 * Add a line for turning on RewriteEngine
	 */
	private function appendTurnOnEngine(){
		$string = 'RewriteEngine on';
		$this->appendLineToRewrites($string);
	}
	
	/**
	 * Surround any regexes used in RewriteCond and RewriteRule
	 * with start and end characters so we don't match anything
	 * we aren't intending to
	 * 
	 * @param string $regex
	 * @return string
	 */
	private function wrapRegex($regex){
		return '^' . $regex . '$';
	}
	
	/**
	 * Add a RewriteCond line
	 * 
	 * @param string $type
	 * @param string $regex
	 */
	private function buildRewriteCondition($type, $regex){
		$string = 'RewriteCond %{' . $type . '} ' . $this->wrapRegex(preg_quote($regex));
		$this->appendLineToRewrites($string);
	}
	
	/**
	 * Add a RewriteRule line
	 * 
	 * @param string $source
	 * @param string $destination
	 */
	private function buildRewriteRule($source, $destination){
		// Append ? to the destination to prevent the query string being passed through.
		$string = 'RewriteRule ' . $this->wrapRegex(preg_quote($source)) . ' ' . $destination . '? ' . $this->buildRewriteFlags();
		$this->appendLineToRewrites($string);
	}
	
	/**
	 * Return the required flags
	 * 
	 * @return string
	 */
	private function buildRewriteFlags(){
		switch($this->http301){
			case false:
				$response = 'R';
				break;
			case true:
			default:
				$response = 'R=301';
		}
		return '[' . $response . ',L,NC]';
	}
	
}
?>