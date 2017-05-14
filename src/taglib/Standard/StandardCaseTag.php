<?php
/**
 * Implements how a CASE clause is translated into a tag. Parent can only be a <standard:switch> tag.
 *
 * Tag syntax:
 * <standard:case value="EXPRESSION|STRING">BODY</standard:case>
 *
 * Tag example:
 * <standard:case value="2">BODY</standard:case>
 *
 * PHP output:
 * <?php case "2": ?> ... <?php break; ?>
 */
class StandardCaseTag extends AbstractTag implements StartEndTag {
	/**
	 * (non-PHPdoc)
	 * @see StartEndTag::parseStartTag()
	 */
	public function parseStartTag($tblParameters=array()) {
		if(!empty($tblParameters["value"])) {
			return '<?php case '.($this->isExpression($tblParameters['value'])?$this->parseExpression($tblParameters['value']):"'".addslashes($tblParameters['value'])."'").': ?>';
		} else {
			return '<?php default: ?>';
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see StartEndTag::parseEndTag()
	 */
	public function parseEndTag() {
		return '<?php break; ?>';
	}
}