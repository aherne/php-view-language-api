<?php
require_once("AbstractParsableTag.php");

/**
 * Implements logical expressions that are going to be interpreted as PHP when response is displayed to client.
 * 
 * Example of tag: 
 * 		<standard:if condition="${request.server.ip}=='127.0.0.1'">
 * 			You are on localhost!
 * 		</standard:if>
 * Is converted to:
 * 		<?php if($request["client"]["ip"]=='127.0.0.1') { ?>
 * 			You are on localhost!
 * 		<?php } ?>
 */
class TagParser {	
	/**
	 * Looks for tags in views and returns an answer where each found match is converted to PHP.
	 * 
	 * @param string $strSubject
	 * @return string
	 */
	public function parse($strSubject) {
		// match start & end tags
		$strSubject = preg_replace_callback("/<([a-zA-Z]+)\:([a-zA-Z]+)(\ (.*)=\"(.*)\")?\/?>/",array($this,"parseStartTagCallback"),$strSubject);
		$strSubject = preg_replace_callback("/<\/([a-zA-Z]+)\:([a-zA-Z]+)>/",array($this,"parseEndTagCallback"),$strSubject);
				
		// if it still contains tags, recurse until all tags are parsed
		if(preg_match("/<([a-zA-Z]+)\:([a-zA-Z]+)(.*?)>/",$strSubject)!=0) {
			$strSubject = $this->parse($strSubject);
		}
		
		return $strSubject;
	}

	/**
	 * Calls for conversion task for each start tag found and returns converted answer. This is done by delegating conversion to detected tag class. 
	 * 
	 * @param array $tblMatches
	 * @return string
	 */
	protected function parseStartTagCallback($tblMatches) {
		return $this->getTagInstance($tblMatches)->parseStartTag(isset($tblMatches[3])?$this->getTagParameters($tblMatches[3]):array());
	}

	/**
	 * Calls for conversion task for each end tag found and returns converted answer. This is done by delegating conversion to detected tag class. 
	 * 
	 * @param array $tblMatches
	 * @return string
	 */
	protected function parseEndTagCallback($tblMatches) {
		return $this->getTagInstance($tblMatches)->parseEndTag();
	}
	
	/**
	 * Detects tag class from tag declaration.
	 * 
	 * Example:
	 * 		<standard:for ...>
	 * 
	 * Where:
	 * 		- "standard" is the name of tag library
	 * 		- "for" is the name of tag function
	 * 
	 * Detected class name will be:
	 * 		StandardForTag
	 * 
	 * @param array $tblMatches
	 * @throws ViewException
	 * @return AbstractParsableTag
	 */
	private function getTagInstance($tblMatches) {
		$strClassName = ucfirst($tblMatches[1]).ucfirst($tblMatches[2]).'Tag';
		if(!class_exists($strClassName)) throw new ViewException("Tag not found: ".$strClassName);
		return new $strClassName();
	}
	
	/**
	 * Detects tag attributes from tag declaration.
	 * 
	 * Example:
	 * 		<standard:for  var="${asd}" value="i" >
	 * 
	 * Parameters detected will be:
	 * 		var
	 * 		value
	 * 
	 * @param string $strParameters
	 * @return array
	 */
	private function getTagParameters($strParameters) {
		$strParameters = trim($strParameters);
		if(!$strParameters || $strParameters=="/") return array();
		preg_match_all('/([a-zA-Z]+)[\ ]{0,}=[\ ]{0,}"(.*?)"/', $strParameters, $tblParameters, PREG_SET_ORDER);
		$tblOutput=array();
		foreach($tblParameters as $tblValues) {
			$tblOutput[trim($tblValues[1])]=trim($tblValues[2]);
		}
		return $tblOutput;
	}
}